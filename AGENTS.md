---
apply: always
---

# General
- Always write comments in English

# Test execution
- Tests must be run in a docker container `docker compose exec -e APP_ENV=test php vendor/bin/simple-phpunit`

