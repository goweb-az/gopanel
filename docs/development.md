# Development Notes

## Common Traits

### Files

Models can define file fields:

```php
protected $files = ['image'];
```

This enables accessors like:

```php
$model->image_url
```

### Slugs and Translations

```php
public $slug_key = 'title';
public $translatedAttributes = ['title', 'description', 'slug'];
```

### Meta Data

Use `MetaData` on models that need SEO meta fields.

### Date Formatting

Use the date formatting traits/helpers where the project already uses them.

## Permissions

Permissions are managed through Spatie Laravel Permission.

Permission config:

```text
config/gopanel/permission_list.php
```

Refresh permissions:

```bash
php artisan config:clear
php artisan db:seed --class=PermissionSeeder
```

## Admin Template

The admin UI is based on Skote Admin & Dashboard Template:

```text
https://themesbrand.com/skote/layouts/index.html
```

