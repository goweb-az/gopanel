# Mock Seeders

Mock seeders are optional demo/test seeders stored in:

```text
database/seeders/mock
```

They are intentionally separated from `DatabaseSeeder`, so production setup can stay clean while development and demo screens can be filled quickly.

## Command

Run:

```bash
php artisan mock:seed
```

The command scans `database/seeders/mock`, finds seeder classes, and shows an interactive menu:

```text
0 Hamisi
1 Analitika
2 Blog
3 Country and city
4 Tercumeler
5 Hecbiri / cix
```

You can choose one item, all items, or several items:

```text
1,3
```

List only:

```bash
php artisan mock:seed --list
```

## Display Name

The menu name is read from a public property:

```php
public string $mockName = 'Analitika';
```

If the property is missing, the command falls back to the class name.

## Current Mock Seeders

- `Database\Seeders\mock\AnalyticsSeeder` - demo analytics clicks, UTM parameters, ad platforms, browsers, devices, and links.
- `Database\Seeders\mock\BlogSeeder` - demo blog posts, translations, slugs, and page meta data.
- `Database\Seeders\mock\CitiesSeeder` - demo countries and cities.
- `Database\Seeders\mock\TranslationSeeder` - demo/app translation records from `database/seeders/json-data/translations.json`.

## Idempotency

Mock seeders should avoid uncontrolled duplicates.

- Analytics checks existing `ip_address + url + referer` before registering a click.
- Blog checks existing Azerbaijani slug and updates the blog if it exists.
- Cities use `updateOrCreate` for countries and cities.
- Translations use `updateOrCreate` by locale, key, group, and platform.

## Creating a New Mock Seeder

Create a file under `database/seeders/mock`:

```php
<?php

namespace Database\Seeders\mock;

use Illuminate\Database\Seeder;

class DemoFeatureSeeder extends Seeder
{
    public string $mockName = 'Demo feature';

    public function run(): void
    {
        // Use updateOrCreate / firstOrCreate where possible.
    }
}
```

Then run:

```bash
php artisan mock:seed --list
```

