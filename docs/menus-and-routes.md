# Menus and Dynamic Routes

## Menu System

Menus are stored in:

```text
menus
```

The menu model supports:

- parent and child menu structure
- positions such as header and footer
- translated title, slug, and description
- meta data

Important files:

```text
app/Models/Navigation/Menu.php
database/seeders/MenuSeeder.php
config/site/menu_routes.php
routes/web.php
```

## Route Registration

`routes/web.php` registers routes for active languages and menu routes.

`config/site/menu_routes.php` maps route keys to controller/method pairs.

## Dynamic Content Routes

Unknown site slugs are resolved through translated slugs:

```text
/{locale}/{slug}
  -> DynamicContentController
  -> FieldTranslation::getBySlug()
  -> model
  -> model controller single method
```

For a model to support dynamic detail pages:

```php
public $controller = \App\Http\Controllers\Site\BlogController::class;
public $translatedAttributes = ['title', 'description', 'slug'];
```

The controller should expose a `single($model)` method.

