# SuperSeeder

[![Latest Version](https://img.shields.io/packagist/v/riftweb/superseeder?style=flat-square)](https://packagist.org/packages/riftweb/superseeder)
[![Total Downloads](https://img.shields.io/packagist/dt/riftweb/superseeder?style=flat-square)](https://packagist.org/packages/riftweb/superseeder)
[![Website](https://img.shields.io/badge/Website-RIFT%20%7C%20Web%20Development-black?style=flat-square)](https://riftweb.com)
[![Tests](https://img.shields.io/github/actions/workflow/status/riftwebdev/superseeder/tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/riftwebdev/superseeder/actions/workflows/tests.yml)
[![License](https://img.shields.io/github/license/riftwebdev/superseeder?style=flat-square)](LICENSE.md)

**Seed once. Roll back deliberately.**

SuperSeeder gives Laravel seeders migration-like execution tracking without
introducing a second command workflow. Mark a seeder as trackable and continue
using Laravel's familiar `make:seeder` and `db:seed` commands.

## Why SuperSeeder?

- Skip seeders that have already completed successfully.
- Keep a batch history in `seeder_executions`.
- Roll back the latest batch in reverse order.
- Preview rollbacks before modifying data.
- Block rollbacks when declared seeded records have foreign-key dependants.
- Keep destructive operations behind confirmation and environment safeguards.

## Requirements

- PHP 8.2+
- Laravel 12 or 13

## Installation

```bash
composer require riftweb/superseeder
php artisan migrate
```

The migration creates the `seeder_executions` tracking table.

## Quick start

Generate a trackable seeder:

```bash
php artisan make:seeder PaymentMethodSeeder --trackable
```

Add its normal seed and rollback behavior:

```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Riftweb\SuperSeeder\Traits\Trackable;

class PaymentMethodSeeder extends Seeder
{
    use Trackable;

    protected function up(): void
    {
        // Create your records.
    }

    public function down(): void
    {
        // Remove only the records created by this seeder.
    }
}
```

Call it from `DatabaseSeeder` as you would any Laravel seeder:

```php
public function run(): void
{
    $this->call([
        PaymentMethodSeeder::class,
    ]);
}
```

Now seed normally:

```bash
php artisan db:seed
```

SuperSeeder records the successful run. Subsequent `db:seed` executions skip
that seeder automatically.

## Commands

| Command | Purpose |
| --- | --- |
| `php artisan make:seeder Name --trackable` | Generate a trackable seeder. |
| `php artisan db:seed` | Run seeders that have not been tracked. |
| `php artisan db:seed --rerun` | Run tracked seeders again and record a new execution. |
| `php artisan db:seed --rollback` | Roll back the latest tracked batch. |
| `php artisan db:seed --rollback --dry-run` | Preview the latest rollback without changing data. |
| `php artisan db:seed --fresh` | Clear tracking, then rerun all trackable seeders. |
| `php artisan db:seed --clear` | Clear tracking without running seeders. |

`--fresh` and `--clear` ask for confirmation. Outside the local environment,
they also require `--force`.

## Safe rollbacks

A rollback calls each seeder's `down()` method and deletes its tracking record.
Write `down()` defensively: target only records the seeder owns, never broad
tables or shared data.

SuperSeeder can identify foreign-key dependants before calling `down()`. Return
the primary keys your seeder created from `seededRecords()`:

```php
public function seededRecords(): array
{
    return [
        'users' => [
            'id' => [1, 2],
        ],
    ];
}
```

If another table references those records, rollback stops with an explanation.
Use `--cascade` only when deleting the detected dependant records is safe.
It deletes those records before calling the seeder's `down()` method:

```bash
php artisan db:seed --rollback --dry-run
php artisan db:seed --rollback --cascade
```

Rollbacks require `--force` outside local environments. In production, they
are disabled unless explicitly enabled:

```php
// config/superseeder.php
return [
    'rollback' => [
        'production_enabled' => true,
    ],
];
```

## Configuration

Publish the configuration when you need to customize it:

```bash
php artisan vendor:publish --tag=superseeder-config
```

```php
return [
    'bypass' => false,
    'table' => 'seeder_executions',
    'rollback' => [
        'production_enabled' => false,
    ],
];
```

Set `SUPERSEEDER_BYPASS=true` only for an intentional emergency rerun. Prefer
the one-off `--rerun` option for routine use.

## Testing

```bash
composer test
```

The GitHub Actions workflow tests Laravel 12 and 13 compatibility and checks
code formatting on every push and pull request.

## License
SuperSeeder is open-sourced software licensed under the [MIT license](LICENSE.md).

**Crafted with ❤️ by [RIFT | Web Development](https://riftweb.com)**
