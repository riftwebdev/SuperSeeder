## SuperSeeder Integration Rules

- Use Laravel's native commands: `make:seeder --trackable` and `db:seed`.
- Add `Riftweb\SuperSeeder\Traits\Trackable` to seeders that must run only once.
- Put creation logic in `protected up(): void` and narrowly scoped cleanup in `public down(): void`.
- Never use broad deletes, table truncation, or unrelated records in `down()`.
- Use `db:seed --rollback --dry-run` before a rollback with production-like data.
- Use `db:seed --rerun` for a one-off rerun; do not enable `SUPERSEEDER_BYPASS` unless a persistent bypass is intentional.
- Declare created primary keys with `seededRecords()` when foreign-key dependency checks are required.
- `--fresh`, `--clear`, and production rollbacks are destructive operations: require explicit confirmation and use `--force` only after verifying the intended scope.
