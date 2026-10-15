# Проверка cmwedding-it/onlyoffice-licensed — 14 октября 2026

Исходники: https://github.com/cmwedding-it/onlyoffice-licensed

Commit: `bc35bba906e8ed03939a1d6f0076b089793adf4e`.
Docker Hub image: `cmwedding/onlyoffice-licensed@sha256:5a05ce235f3f95638e132ad969bc403699f0d05cea850e31b7b17d659118b48a`.
Фактическая версия: ONLYOFFICE 7.2.2-56. Серверный патч проходит `git apply --check` на официальном теге `v7.2.2.56` (`7500770c2e8cc05290715b5a9e23af1c2bde3d82`). Собственный rebuild этого образа не выполнялся.

## Результат

- Встроенный Erlang в amd64-образе падает с Segmentation fault под Rosetta на Apple Silicon. Самостоятельно образ на этом Mac не запустился.
- Подключён внешний официальный RabbitMQ 3.13 Alpine, работающий на архитектуре Mac, через AMQP_URI. Бинарники ONLYOFFICE не менялись.
- После этого `/healthcheck` возвращает true.
- CommandService license: error=0, trial=false, timelimited=false, connections=9999, connections_view=9999, advanced_api=true, customization=true, branding=true, packageType=1, end_date=2099-01-01.
- После завершения первичной подготовки шрифтов все девять комбинаций «3 сотрудника × 3 шаблона» прошли проверку DOCX и PDF: ФИО, отсутствие незаполненных placeholders, пустые награды и текущая служба.
- Сохранение реально изменённого Builder API файла через подписанный callback прошло; содержимое, revision и неизменность активного key_version проверены.
- Послужной список открылся в браузерном редакторе 7.2.2-56 без предупреждений. Скриншот: `cmwedding-editor.png`.
- Первый тест генераций попал в перезапуск DocService при первоначальной подготовке шрифтов и получил HTTP 502 на шестом документе. Повторный полный тест после завершения запуска прошёл.

Рабочая конфигурация: `compose.yaml` + `compose.cmwedding.yaml`; `scripts/start.sh` использует её по умолчанию. Возврат к исходному Community 6.3.1.32: `scripts/start.sh legacy`. Данные CRM общие, тома данных/логов ONLYOFFICE раздельные.

Это проверка функции патча и интеграции в локальном демо, а не нагрузочный тест 9999 одновременных соединений и не подтверждение права использования коммерческих возможностей. Репозиторий архивирован, сам автор отмечает его устаревание. Мобильное редактирование не тестировалось.
