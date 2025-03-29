# SuperSeeder 🚀

[![Latest Version](https://img.shields.io/packagist/v/riftweb/superseeder?style=flat-square)](https://packagist.org/packages/riftweb/superseeder)
[![Total Downloads](https://img.shields.io/packagist/dt/riftweb/superseeder?style=flat-square)](https://packagist.org/packages/riftweb/superseeder)
[![License](https://img.shields.io/github/license/riftwebdev/superseeder?style=flat-square)](https://github.com/riftwebdev/SuperSeeder/blob/main/LICENSE.md)
[![Website](https://img.shields.io/badge/Website-RIFT%20%7C%20Web%20Development-black?style=flat-square)](https://riftweb.com)

A robust database seeder solution for Laravel with execution tracking and rollback capabilities. Brings migration-like behavior to your seeders! 🔄

## Features ✨

- ✅ Track executed seeders in `seeder_executions` table
- ⏮️ Rollback seeders like migrations
- 🛡️ Prevent accidental duplicate executions
- 🔄 Batch management of seeders
- 🚦 Bypass mode for emergency executions
- 🚀 Generator command for trackable seeders

## Installation 💻

Install via Composer:
```bash
composer require riftweb/superseeder
```

Run migrations (creates `seeder_executions` table):
```bash
php artisan migrate
```

## Usage 🛠️

### 1. Create Trackable Seeder
```bash
php artisan make:superseeder UsersSeeder
```

### 2. Implement Seeder Logic
```php
<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Riftweb\SuperSeeder\Traits\TrackableSeed;

class UsersSeeder extends Seeder
{
    use TrackableSeed;

    public function up()
    {
        // Your seeder logic
    }

    public function down(): void
    {
        // Your rollback logic
    }
}
```

### 3. Run Seeders
```bash
php artisan superseed
```

### 4. Rollback Last Batch
```bash
php artisan superseed:rollback
```

## Configuration ⚙️

### Bypass Mode
Add to `.env`:
```ini
SUPERSEED_BYPASS=true
```
**What it does:**
- When enabled, runs seeders regardless if they have been executed before (use with caution)
- DOES create records in `seeder_executions`
- Alternative: Use `--force` flag with `superseed` command

## How It Works 🔍

1. **Tracking Table**  
   The package creates a `seeder_executions` table to track:
    - Seeder class name
    - Batch number
    - Execution timestamp

2. **Rollback Process**
    - Calls `down()` method on each seeder
    - Deletes tracking records for the batch
    - Runs in reverse order of execution

## Workflow Example 🔄

```bash
# 1. Create seeder
php artisan make:superseeder UsersSeeder

# 2. Implement up() and down() methods

# 3. Run seeders
php artisan superseed

# 4. Rollback
php artisan superseed:rollback
```

## Commands 🛠️

##### Create trackable seeder
```bash
php artisan make:superseeder SeederName
```

##### Run seeders
```bash
php artisan superseed
```

##### Rollback seeders
```bash
php artisan superseed:rollback
```

##### Clear all seeder records
```bash
php artisan superseed:fresh
```

##### Clear all seeder records and rollback
```bash
php artisan superseed:clear
```


## Important Notes ⚠️

- **Test rollbacks** thoroughly before production use
- **Backup database** before running seeders

## Contributing 🤝

Contributions are welcome! Please follow:
1. Fork the repository
2. Create your feature branch
3. Commit changes
4. Push to the branch
5. Open a PR

## License 📄

MIT License - See [LICENSE](LICENSE.md) for details.

---

**Crafted with ❤️ by [RIFT | Web Development](https://riftweb.com)**