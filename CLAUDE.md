# CommSy

Web-based community and collaboration platform. Symfony 7.4 LTS on PHP 8.4 with
Doctrine ORM, API Platform, Webpack Encore and UIKit.

## Environment

Everything runs in Docker. Do not run PHP, Composer or frontend tooling on the
host — the container owns `vendor/` and `node_modules/`.

```bash
docker compose up -d
```

## Commands

### Tests

Run inside the container, with `APP_ENV=test`, using `simple-phpunit` (not
`phpunit` — the Symfony PHPUnit bridge installs and manages its own version):

```bash
docker compose exec -e APP_ENV=test php vendor/bin/simple-phpunit
docker compose exec -e APP_ENV=test php vendor/bin/simple-phpunit tests/Unit/SomeTest.php
```

Never run two test processes against the same database at once — the test
database is shared and concurrent runs corrupt it.

Test suites live under `tests/`: `Unit` (no container), `Integration`
(kernel/services), `Application` (HTTP through the full stack) and `Api`
(API Platform). `Factory` and `Story` hold Foundry fixtures rather than tests.

There is **no browser-based testing** — no `symfony/panther`, no Codeception,
no JavaScript test runner. `tests/Panther/` holds a single fully commented-out
leftover of an abandoned Codeception attempt and runs nothing. Anything that
only breaks in a browser (editors, Stimulus controllers, UIKit behaviour) is
currently unguarded by tests, so verify it by hand.

### Frontend assets

```bash
docker compose exec php yarn install
docker compose exec php yarn dev      # development build
docker compose exec php yarn build    # production build
```

### Console and Composer

```bash
docker compose exec php bin/console <command>
docker compose exec php composer <command>
```

### Static analysis

```bash
docker compose exec php vendor/bin/phpstan analyse
docker compose exec php vendor/bin/rector process --dry-run
```

## Verifying changes

Beyond the test suite, these catch classes of errors tests miss:

```bash
docker compose exec php bin/console lint:container      # DI/type errors — run after touching services or config
docker compose exec php bin/console lint:yaml config --parse-tags
docker compose exec php bin/console lint:twig templates
```

`lint:container` is especially worth running after changing constructors,
service definitions or anything under `config/packages/` — a broken container
fails at boot, which unit tests do not exercise.

## Code conventions

- Write all comments and docblocks in English, kept short and technical.
- Match the style of the surrounding code rather than introducing new patterns.
- Prefer typed properties, constructor promotion and enums in new code.

## Architecture

- `src/` — modern Symfony code. New functionality belongs here.
- `legacy/` — the historical CommSy codebase (`cs_*` classes and managers).
  It is being replaced incrementally using the strangler pattern: behaviour is
  moved into `src/` (entity + repository + service) and the legacy class is
  deleted once nothing references it. Do not add new features to `legacy/`.
- `templates/` — Twig. `assets/` — Encore entries, split into `uikit2/`
  (the older theme set) and `uikit3/`.
- `config/` — Symfony configuration. Doctrine migrations live in
  `src/Migrations/` (namespace `App\Migrations`).

When replacing legacy behaviour, pin the existing behaviour with a
characterization test first, then migrate, then delete the legacy code.
