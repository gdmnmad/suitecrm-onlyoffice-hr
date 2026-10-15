#!/bin/sh
# Update application files only; keep server credentials, database and documents.
set -eu
cd "$(dirname "$0")/.."
identity=${1:-runtime/deploy-audit-key}
server=${2:?Specify SSH destination, for example root@your-server}
release=$(mktemp -d /tmp/hr-update.XXXXXX)
trap 'rm -rf "$release"' EXIT HUP INT TERM
COPYFILE_DISABLE=1 tar -czf "$release/update.tar.gz" crm scripts/check-audit-history.php scripts/check-batch.php scripts/check-crm.php scripts/check-delete.php scripts/check-permissions.php scripts/check-ui.php scripts/allow-proxy-sessions.php scripts/configure-static-cache.php
ssh -o ServerAliveInterval=5 -o ServerAliveCountMax=2 -o ConnectTimeout=10 -i "$identity" -o BatchMode=yes -o ConnectTimeout=10 "$server" 'test -d /opt/hr-onlyoffice-demo/crm && mkdir -p /opt/hr-onlyoffice-demo/backups'
scp -o ServerAliveInterval=5 -o ServerAliveCountMax=2 -o ConnectTimeout=10 -i "$identity" "$release/update.tar.gz" "$server":/opt/hr-onlyoffice-demo/update.tar.gz
ssh -o ServerAliveInterval=5 -o ServerAliveCountMax=2 -o ConnectTimeout=10 -i "$identity" "$server" 'sh -s' <<'REMOTE'
set -eu
cd /opt/hr-onlyoffice-demo
stamp=$(date -u +%Y%m%dT%H%M%SZ)
tar -czf "backups/application-$stamp.tar.gz" crm
docker compose exec -T db sh -c 'mariadb-dump -usuitecrm -p"$MARIADB_PASSWORD" --single-transaction --skip-lock-tables suitecrm' > "backups/database-$stamp.sql"
chmod 600 "backups/database-$stamp.sql"
tar -xzf update.tar.gz
for file in HrAuditSupport HrHooks HrAudit HrBatch HrBatchSupport HrDocumentForm HrDocuments HrDocumentSupport HrResource HrUi bootstrap; do docker compose exec -T crm php -l "/opt/hr/$file.php"; done
docker compose -f compose.yaml -f compose.cmwedding.yaml -f compose.server.yaml restart crm
for attempt in $(seq 1 60); do
 if docker compose exec -T crm test -f /var/www/html/public/legacy/custom/HrBatch.php && curl -fsS http://127.0.0.1:8080/ > /dev/null; then break; fi
 sleep 2
done
docker compose exec -T crm php /opt/checks/check-permissions.php
docker compose exec -T crm php /opt/checks/check-ui.php HrAudit hr_viewer
curl -fsS http://127.0.0.1:8082/healthcheck
printf '\nUpdate completed; backups: %s\n' "$stamp"
REMOTE
