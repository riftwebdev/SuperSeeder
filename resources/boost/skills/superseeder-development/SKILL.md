# Developing with SuperSeeder

Use this skill when creating, modifying, running, or rolling back a SuperSeeder
trackable Laravel seeder.

## Creating a trackable seeder

Generate the seeder with Laravel's native command:

```bash
php artisan make:seeder PaymentMethodSeeder --trackable
```

The generated class uses `Riftweb\SuperSeeder\Traits\Trackable`. Keep its
responsibilities explicit:

```php
use Illuminate\Database\Seeder;
use Riftweb\SuperSeeder\Traits\Trackable;

class PaymentMethodSeeder extends Seeder
{
    use Trackable;

    protected function up(): void
    {
        // Create only this seeder's records.
    }

    public function down(): void
    {
        // Delete only records owned by this seeder.
    }
}
```

Register the seeder with the application's `DatabaseSeeder` using Laravel's
normal `$this->call()` workflow. Do not introduce a custom command or hardcoded
execution order outside the application's existing seeder structure.

## Running seeders

| Intent | Command |
| --- | --- |
| Run only untracked seeders | `php artisan db:seed` |
| Rerun tracked seeders once | `php artisan db:seed --rerun` |
| Clear tracking and rerun trackable seeders | `php artisan db:seed --fresh` |
| Clear tracking only | `php artisan db:seed --clear` |

Prefer `--rerun` for one-off work. `SUPERSEEDER_BYPASS=true` changes behavior
for every run and should only be used when that persistence is intentional.

## Designing safe rollbacks

`down()` is responsible for deleting seeded data. It must use a deterministic,
narrow query based on identifiers or attributes uniquely owned by that seeder.
Never truncate a table or delete rows that could have been created by users,
another seeder, or the application.

When the seeder creates records that may be referenced by another table,
implement `seededRecords()`:

```php
public function seededRecords(): array
{
    return [
        'payment_methods' => [
            'id' => [1, 2],
        ],
    ];
}
```

The keys are seeded tables and columns; the values are the primary keys created
by the seeder. SuperSeeder checks foreign keys before rollback and blocks when
dependants are present.

## Rolling back safely

Always preview a rollback first:

```bash
php artisan db:seed --rollback --dry-run
```

Run the rollback only after confirming the preview:

```bash
php artisan db:seed --rollback
```

`--cascade` deletes detected dependent records before calling `down()`. Use it
only when every deleted dependency is expected and safe to remove:

```bash
php artisan db:seed --rollback --cascade
```

Outside the local environment, `--rollback`, `--fresh`, and `--clear` require
`--force`. Rollbacks are disabled in production unless
`superseeder.rollback.production_enabled` is explicitly enabled.

## Testing

Test the seeder's first run, subsequent skipped run, rollback, and any declared
foreign-key dependencies. Use a dry run to verify rollback behavior before
performing destructive operations against shared or production-like data.
