# Domains, CDN & Gopanel Deployment

## A) Local Development

Localda subdomain lazım deyil. Bütün trafikr `http://aquastores.loc` üzərindən gedir.

### Local .env

```env
APP_URL=http://aquastores.loc
API_URL=http://aquastores.loc
CDN_URL=
ASSET_URL=
GOPANEL_URL=
```

`CDN_URL` boş qaldıqda bütün fayl/storage/assets URL-ləri avtomatik `APP_URL` ilə yaranır.
`GOPANEL_URL` boş qaldıqda `GopanelHostMiddleware` host yoxlaması etmir — gopanel hər domainə cavab verir.

### Local test URL-ləri

```
http://aquastores.loc/api/...          → API endpoints
http://aquastores.loc/gopanel          → Gopanel admin panel
http://aquastores.loc/storage/...      → Storage files
http://aquastores.loc/assets/...       → Public assets
```

---

## B) Production

### Domain planı

| Domain              | Rol                                      |
|---------------------|------------------------------------------|
| `api.aqustores.dev` | Laravel app — API endpoints              |
| `cdn.aqustores.dev` | Static serving — /storage və /assets     |
| `go.aqustores.dev`  | Laravel app — Gopanel admin panel        |

Üç domain eyni Laravel `public/` folder-ə yönləndirilir.
Nginx konfiqurasiyası ilə hər domain öz rolunu yerinə yetirir.

### Production .env

```env
APP_URL=https://api.aqustores.dev
API_URL=https://api.aqustores.dev
CDN_URL=https://cdn.aqustores.dev
ASSET_URL=https://cdn.aqustores.dev
GOPANEL_URL=https://go.aqustores.dev
```

### Nginx konfiqurasiyası

#### api.aqustores.dev — Laravel API

```nginx
server {
    listen 443 ssl;
    server_name api.aqustores.dev;

    root /var/www/aquastores/public;
    index index.php;

    ssl_certificate     /etc/ssl/aqustores.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/aqustores.dev/privkey.pem;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### go.aqustores.dev — Gopanel Admin

```nginx
server {
    listen 443 ssl;
    server_name go.aqustores.dev;

    root /var/www/aquastores/public;
    index index.php;

    ssl_certificate     /etc/ssl/aqustores.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/aqustores.dev/privkey.pem;

    # Root-dan gopanel-ə yönləndir (optional)
    location = / {
        return 301 /gopanel;
    }

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

#### cdn.aqustores.dev — Static file serving

```nginx
server {
    listen 443 ssl;
    server_name cdn.aqustores.dev;

    ssl_certificate     /etc/ssl/aqustores.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/aqustores.dev/privkey.pem;

    # /storage → storage/app/public
    location /storage/ {
        alias /var/www/aquastores/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
        try_files $uri =404;
    }

    # /assets → public/assets
    location /assets/ {
        alias /var/www/aquastores/public/assets/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
        try_files $uri =404;
    }

    # Digər sorğular 404
    location / {
        return 404;
    }
}
```

### Gopanel host restriction

`GopanelHostMiddleware` production-da yalnız `go.aqustores.dev` hostundan gopanel-ə girişə icazə verir.

```
go.aqustores.dev/gopanel   → ✓ açılır
api.aqustores.dev/gopanel  → 404
cdn.aqustores.dev/gopanel  → 404
```

Local mühitdə `GOPANEL_URL` boş olduğundan middleware işləmir.

### Storage symlink

```bash
php artisan storage:link
```

Bu əmr `public/storage → storage/app/public` symlink yaradır.
CDN server blockunda `alias` istifadə etdiyimiz üçün symlink CDN tərəfindən lazım deyil,
amma Laravel-in özü üçün lazımdır (`storage:link` olmasa `public/storage/` mövcud olmayacaq).
