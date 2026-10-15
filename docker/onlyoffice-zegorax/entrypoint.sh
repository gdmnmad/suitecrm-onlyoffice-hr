#!/bin/bash
set -euo pipefail
mkdir -p /var/www/onlyoffice/Data
cp /opt/hr-demo-license.lic /var/www/onlyoffice/Data/license.lic
exec /app/ds/run-document-server.sh
