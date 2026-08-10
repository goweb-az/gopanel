# Domenlər, CDN və GoPanel Deployment

> **Status:** deployment qaydası. Domen adları (`example.dev`) **nümunədir** —
> hər layihədə öz domeni ilə əvəzlənir. Kod tərəfi Gopanel-də hazırdır:
> `config/app.php` → `cdn_url` və `App\Support\Url\CdnUrl`.

## A) Lokal iş mühiti

Lokalda subdomen lazım deyil — bütün trafik bir host üzərindən gedir.

### Lokal `.env`

```env
APP_URL=http://gopanel.loc
CDN_URL=
ASSET_URL=
GOPANEL_URL=
```

`CDN_URL` **boş qaldıqda** bütün fayl/storage/assets URL-ləri avtomatik
`APP_URL` ilə yaranır — `App\Support\Url\CdnUrl::base()` `app.cdn_url ?: app.url`
oxuyur. Yəni lokalda heç nə konfiqurasiya etmək lazım deyil.

`GOPANEL_URL` boş qaldıqda host yoxlaması **işləmir** — gopanel hər domenə
cavab verir (lokalda istənilən budur).

### Lokal test ünvanları

```text
http://gopanel.loc/            → sayt
http://gopanel.loc/gopanel     → GoPanel admin
http://gopanel.loc/storage/... → yüklənmiş fayllar
http://gopanel.loc/assets/...  → statik fayllar
```

---

## B) Canlı (production)

### Domen planı

| Domen | Rol |
|---|---|
| `example.dev` | Laravel app — sayt + API |
| `cdn.example.dev` | Statik verilmə — `/storage` və `/assets` |
| `go.example.dev` | Laravel app — GoPanel admin |

Üç domen **eyni** Laravel `public/` qovluğuna yönləndirilir; rolları nginx
konfiqurasiyası ayırır. Ayrı-ayrı deploy lazım deyil.

### Canlı `.env`

```env
APP_URL=https://example.dev
CDN_URL=https://cdn.example.dev
ASSET_URL=https://cdn.example.dev
GOPANEL_URL=https://go.example.dev
```

> ⚠️ **`CDN_URL` tək başına kifayət deyil.** `CdnUrl::storage()` `storage/`
> prefiksi olmayan yolları `Storage::disk('public')->url()`-a ötürür, o isə
> **diskin öz konfiqurasiyasına** baxır. CDN qoşulanda
> `config/filesystems.php` → `disks.public.url` da eyni ünvanla uzlaşdırılır,
> əks halda yüklənmiş fayllar köhnə domendə qalır və şəkillərin bir hissəsi
> CDN-dən, bir hissəsi app serverindən gəlir.
>
> ```php
> 'public' => [
>     'driver' => 'local',
>     'root'   => storage_path('app/public'),
>     'url'    => env('CDN_URL', env('APP_URL')) . '/storage',
>     'visibility' => 'public',
> ],
> ```

### Nginx — app (sayt + API)

```nginx
server {
    listen 443 ssl;
    server_name example.dev;

    root /var/www/gopanel/public;
    index index.php;

    ssl_certificate     /etc/ssl/example.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/example.dev/privkey.pem;

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

### Nginx — GoPanel

```nginx
server {
    listen 443 ssl;
    server_name go.example.dev;

    root /var/www/gopanel/public;
    index index.php;

    ssl_certificate     /etc/ssl/example.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/example.dev/privkey.pem;

    # Kökdən panelə yönləndir (opsional)
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

### Nginx — CDN (yalnız statik)

```nginx
server {
    listen 443 ssl;
    server_name cdn.example.dev;

    ssl_certificate     /etc/ssl/example.dev/fullchain.pem;
    ssl_certificate_key /etc/ssl/example.dev/privkey.pem;

    # /storage → storage/app/public
    location /storage/ {
        alias /var/www/gopanel/storage/app/public/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
        try_files $uri =404;
    }

    # /assets → public/assets
    location /assets/ {
        alias /var/www/gopanel/public/assets/;
        expires 30d;
        add_header Cache-Control "public, immutable";
        add_header Access-Control-Allow-Origin "*";
        try_files $uri =404;
    }

    # Qalan hər şey 404 - CDN domenində PHP İŞLƏMİR
    location / {
        return 404;
    }
}
```

**Niyə CDN blokunda PHP yoxdur:** eyni tətbiq üç domendə açıq olsa, sessiya
cookie-si və CSRF token üç origin arasında qarışır; həmçinin CDN domenində
işləyən PHP bütün keşləmə üstünlüyünü itirir. CDN yalnız fayl verir.

### GoPanel host məhdudiyyəti

Canlıda panel **yalnız** öz subdomenindən açılmalıdır. Bunun üçün
`GOPANEL_URL` yoxlayan middleware yazılır (`app/Http/Middleware/`):

```text
go.example.dev/gopanel  → ✓ açılır
example.dev/gopanel     → 404
cdn.example.dev/gopanel → 404
```

`GOPANEL_URL` boş olduqda middleware yoxlama etmir — lokalda panel hər hostda açılır.

**Niyə vacibdir:** panelin ayrı origin-də olması PWA scope-larını ayırır, admin
sessiya cookie-sini sayt sessiyasından təcrid edir və nginx səviyyəsində
IP məhdudiyyəti (`config/custom/security.php` → `allowed_ips`) tətbiq etməyi
asanlaşdırır.

### Storage symlink

```bash
php artisan storage:link
```

`public/storage → storage/app/public` symlink-i yaradır. CDN blokunda `alias`
işlədildiyi üçün CDN-ə lazım deyil, amma **Laravel-in özünə lazımdır** —
symlink olmasa `public/storage/` mövcud olmayacaq və CDN söndürüləndə bütün
fayl URL-ləri 404 verəcək.

### Deploy sonrası yoxlama siyahısı

```bash
php artisan config:clear && php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link
php artisan migrate --force
php artisan db:seed --class=PermissionSeeder --force
```

Sonra brauzerdə: şəkil CDN domenindən gəlirmi (`view-source` → `cdn.` prefiksi),
panel yalnız `go.` subdomenində açılırmı, `cdn.example.dev/gopanel` 404 verirmi.
