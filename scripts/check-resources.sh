#!/bin/sh
set -eu
base=${CRM_URL:-http://localhost:8080}
code=$(curl -sS -o /dev/null -w '%{http_code}' "$base/legacy/index.php?entryPoint=HrResource&name=template-awards.docx")
[ "$code" = 403 ] || { echo "Expected 403 for unsigned resource, got $code"; exit 1; }
code=$(curl -sS -o /dev/null -w '%{http_code}' "$base/legacy/index.php?entryPoint=HrResource&name=../../config.php&expires=9999999999&sig=invalid")
[ "$code" = 403 ] || { echo "Expected 403 for traversal, got $code"; exit 1; }
echo 'PASS unsigned file and path traversal rejected'
