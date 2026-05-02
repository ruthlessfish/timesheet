# CLI: Time Entry Artisan Commands

This document describes the `time:start` and `time:stop` Artisan commands added to the application and lists planned improvements.

## Commands

- `php artisan time:start --user=ID|email [--project-id=ID] [--description="..."] [--billable=1]`
  - Starts a new timer for the given user and project.
  - `--user` is required for the initial implementation and accepts a user id or email.
  - If `--project-id` is omitted the command will prompt interactively to select a project for the user.

- `php artisan time:stop --user=ID|email [--project-id=ID] [--confirm]`
  - Stops the currently active timer for the given user.
  - If the active timer's project does not match `--project-id` the command will fail.
  - By default the command asks for confirmation unless `--confirm` is provided.

## Examples

Start a timer non-interactively:

    php artisan time:start --user=1 --project-id=2 --description="Design work" --billable=1

Start a timer interactively (choose project):

    php artisan time:start --user=1

Stop a timer non-interactively:

    php artisan time:stop --user=1 --confirm

## Future features (planned)

- TimeEntryService will use database transactions when starting timers to avoid race conditions when multiple CLI processes attempt to start timers concurrently.
- Projects will have `slug` columns to enable selection by `--project-slug` in the CLI.
- CLI user authentication will be added so commands can resolve the user automatically (for example via a config or session token) instead of requiring `--user`.

## Notes

- These commands use the existing `TimeEntryService` and model logic. They follow the application's service-layer pattern and will be extended as the project evolves.

## CI

This repository includes CI that runs tests across SQLite, MySQL, and PostgreSQL. See `docs/CI.md` for details on running tests locally and the GitHub Actions workflow.
