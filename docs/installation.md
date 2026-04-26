# Installation

## Requirements

- PHP 8.1+
- Composer
- MySQL or MariaDB
- A web server such as OpenServer, Nginx, Apache, or Laravel's local server

## Create Project

```bash
composer create-project goweb/gopanel
```

With a custom directory:

```bash
composer create-project goweb/gopanel your-project-name dev-master
```

## Environment

Copy `.env.example` to `.env` if needed, then configure the database:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gopanel
DB_USERNAME=root
DB_PASSWORD=
```

## First Run

```bash
php artisan key:generate
php artisan migrate --seed
php artisan config:clear
php artisan cache:clear
```

## Optional Demo Data

Core seeders are run through `php artisan migrate --seed`.

Demo/test data lives in `database/seeders/mock` and should be run separately:

```bash
php artisan mock:seed
```

See [Mock Seeders](mock-seeders.md).

