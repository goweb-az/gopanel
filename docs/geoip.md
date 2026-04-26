# GeoIP Databases

Analytics country and city lookup uses MaxMind GeoLite2 `.mmdb` files.

## Config Paths

Configured in `config/seo/analytics.php`:

```php
'geoip' => [
    'paths' => [
        'city'    => database_path('geo-data/GeoLite2-City/GeoLite2-City.mmdb'),
        'country' => database_path('geo-data/GeoLite2-Country/GeoLite2-Country.mmdb'),
        'asn'     => database_path('geo-data/GeoLite2-ASN/GeoLite2-ASN.mmdb'),
    ],
],
```

Expected files:

```text
database/geo-data/GeoLite2-City/GeoLite2-City.mmdb
database/geo-data/GeoLite2-Country/GeoLite2-Country.mmdb
database/geo-data/GeoLite2-ASN/GeoLite2-ASN.mmdb
```

## Restore Command

The project includes:

```bash
php artisan geoip:restore
```

This command restores missing GeoLite2 database files from:

```text
database/backups/geo-data/GeoLite2-City/GeoLite2-City.mmdb
database/backups/geo-data/GeoLite2-Country/GeoLite2-Country.mmdb
database/backups/geo-data/GeoLite2-ASN/GeoLite2-ASN.mmdb
```

It copies them into `database/geo-data/...`.

## Force Overwrite

If the target files already exist, they are skipped by default.

Overwrite existing files:

```bash
php artisan geoip:restore --force
```

## When Files Are Missing

`GeoIpHelper` logs warnings/errors and analytics falls back to default/unknown values where possible. The dashboard can still work, but country/city accuracy and map data may be incomplete.

## Check Command

There is currently no `geoip:check` command in this codebase. To verify manually, check that these files exist:

```text
database/geo-data/GeoLite2-City/GeoLite2-City.mmdb
database/geo-data/GeoLite2-Country/GeoLite2-Country.mmdb
database/geo-data/GeoLite2-ASN/GeoLite2-ASN.mmdb
```

A future `geoip:check` command can validate file existence, readability, and sample IP lookups.

