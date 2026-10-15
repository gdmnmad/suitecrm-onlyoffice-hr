#!/bin/sh
set -eu
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then cp .env.example .env; fi
office_config=${1:-cmwedding}
case "$office_config" in
  cmwedding|legacy) ;;
  *) printf 'Использование: %s [cmwedding|legacy]\n' "$0" >&2; exit 2 ;;
esac
docker compose -f compose.yaml -f "compose.$office_config.yaml" --profile office up -d --build --wait --wait-timeout 360
printf '\nCRM: http://localhost:8080\nONLYOFFICE: http://localhost:8082\n'
printf 'Первый запуск ONLYOFFICE на Apple Silicon может занять несколько минут.\n'
