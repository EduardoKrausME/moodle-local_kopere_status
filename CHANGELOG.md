# Changelog

This changelog documents **only the two most recently published GitHub releases** of `local_kopere_status`. Each release is compared with the immediately preceding **published release** tag. Changes committed after release `1.0.0` are excluded.

## [1.0.0] - 2026-10-19

Published: 2026-10-19 14:56:56 UTC.

Comparison: [0.0.7...1.0.0](https://github.com/EduardoKrausME/moodle-local_kopere_status/compare/0.0.7...1.0.0)

### Changed: continuous integration

- Updated the GitHub Actions CI matrix for PHP 8.3 from `MOODLE_502_STABLE` to `MOODLE_503_STABLE` (Moodle 5.3).
- Updated the PostgreSQL CI service image from `postgres:16` to `postgres:17`.
- Updated the MariaDB CI service image from `mariadb:10` to `mariadb:11.4`.
- Replaced the earlier MariaDB test-service empty-password configuration with `MARIADB_ALLOW_EMPTY_ROOT_PASSWORD=1` and its `mysqladmin ping` health check with `healthcheck.sh --connect --innodb_initialized`, adjusting the retry limit.

### Changed: release automation

- Moved the GitHub head commit message into the `RELEASE_MESSAGE` environment variable before processing it in the release workflow, rather than inserting the commit message directly into a shell script. This reduces shell-injection exposure during release-message parsing.
- Added workflow concurrency configuration with a branch-scoped release group, `cancel-in-progress: false`, and `queue: max` to avoid canceling queued release runs.

### Assets and version metadata

- Updated the existing `pix/icon.png` image asset. The tag comparison identifies an image change but does not establish any specific visual design differences.
- Updated `version.php` from `$plugin->version = 2026100500` / release `0.0.7` to `2026101900` / release `1.0.0`. The minimum Moodle build (`2021041900`) did not change.

### Scope

The published-tag comparison contains eight commits and modifies only `.github/workflows/ci.yml`, `.github/workflows/release.yml`, `pix/icon.png`, and `version.php`. It does **not** contain functional PHP source changes to the status dashboard, monitoring tasks, or reporting features. Updated test-service images do not imply a new minimum production database requirement.

## [0.0.7] - 2026-10-05

Published: 2026-10-05 21:26:16 UTC.

Comparison: [0.0.6...0.0.7](https://github.com/EduardoKrausME/moodle-local_kopere_status/compare/0.0.6...0.0.7)

### Changed: class location and imports

- Relocated `classes/local/logs.php` to `classes/logs.php`, changing its PHP namespace from `local_kopere_status\local` to `local_kopere_status`.
- Updated `index.php` to import `local_kopere_status\logs` instead of `local_kopere_status\local\logs`, keeping the dashboard's class reference consistent with the new location.

### Release metadata

- Updated `version.php` from numeric plugin version `2026010600` and declared release `0.0.5` to `2026100500` and `0.0.7`. **The published preceding tag is `0.0.6`, although its `version.php` still declares release `0.0.5`;** these are distinct facts from the Git comparison.
- The minimum Moodle build (`2021041900`) remained unchanged.

### Scope

The published-tag comparison contains two commits. Its net differences are the PHP file rename/namespace change, the corresponding import update and `version.php`; no other application behavior changes are evidenced in this diff.

---

Only published-tag differences are documented above. This changelog does not incorporate later `master` changes or claim that tests or deployments were run during its creation.
