# Translations

The project uses two translation layers:

## App Text Translations

Stored in:

```text
translations
```

Model:

```text
App\Models\Translations\Translation
```

Seeder data:

```text
database/seeders/json-data/translations.json
```

Mock seeder:

```text
database/seeders/mock/TranslationSeeder.php
```

Run through:

```bash
php artisan mock:seed
```

Choose `Tercumeler`.

## Field Translations

Model content translations are stored in:

```text
field_translations
```

The `Translation` trait lets a model define translated fields:

```php
public $translatedAttributes = ['title', 'description', 'slug'];
```

Helper:

```text
App\Helpers\Gopanel\TranslationHelper
```

Example:

```php
TranslationHelper::basic($blog, $data['title'], 'title');
TranslationHelper::basic($blog, $data['slug'], 'slug');
TranslationHelper::basic($blog, $data['description'], 'description');
```

## Slugs

Translated slugs are resolved through `field_translations`, which allows dynamic routes to find the target model.

