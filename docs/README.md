# Gopanel Sənədləri

Bu qovluqda Gopanel-in əsas modulları və istifadə qaydaları üzrə ayrıca sənədlər saxlanılır. Əsas `README.md` qısa giriş və quraşdırma üçün qalır, detallar isə burada bölmələrə ayrılıb.

## Sənəd Bölmələri

- [Quraşdırma](installation.md)  
  Layihənin yaradılması, `.env` ayarları, migrate və ilk seed prosesi.

- [Mock Seeder-lər və `mock:seed`](mock-seeders.md)  
  Demo/test datalarının ayrıca idarə edilməsi, interaktiv seeder menyusu, `$mockName` qaydası və yeni mock seeder yaratmaq.

- [Analitika Dashboard](analytics.md)  
  Analytics axını, middleware, event/listener, dashboard blokları, UTM və reklam platformaları.

- [Analytics və Bot Qeydləri](analytics-and-bots-readme.md)  
  Analitika və bot izləmə ilə bağlı geniş qeydlər.

- [GeoIP və `geoip:restore`](geoip.md)  
  MaxMind GeoLite2 faylları, backup-dan bərpa, `geoip:restore --force` və path-lər.

- [SEO, Meta, Redirects, LLMs.txt](seo.md)  
  Meta sistemi, SEO kodları, yönləndirmələr və LLMs.txt mexanizmi.

- [Tərcümələr](translations.md)  
  `translations` və `field_translations` strukturu, `Translation` trait və translation seeder-ləri.

- [Menyular və Dinamik Route-lar](menus-and-routes.md)  
  Menyu sistemi, çoxdilli route qeydiyyatı və slug-a görə dinamik səhifə həlli.

- [Sitemap və RSS](sitemap-rss.md)  
  XML sitemap, RSS endpoint-ləri və yeni model əlavə etmə qaydası.

- [Yeniləmə Sistemi](updater.md)  
  `gopanel_updates.json`, GitHub update yoxlaması, action tipləri və backup mexanizmi.

- [Development Qeydləri](development.md)  
  Trait-lər, permission sistemi, fayl strukturu və developer üçün qısa qeydlər.

## Tövsiyə Olunan Oxuma Sırası

1. [Quraşdırma](installation.md)
2. [Mock Seeder-lər və `mock:seed`](mock-seeders.md)
3. [GeoIP və `geoip:restore`](geoip.md)
4. [Analitika Dashboard](analytics.md)
5. [SEO, Meta, Redirects, LLMs.txt](seo.md)
6. [Menyular və Dinamik Route-lar](menus-and-routes.md)

## Fayl Əlavə Etmə Qaydası

Yeni modul üçün sənəd əlavə ediləndə:

1. `docs/` altında ayrıca `.md` faylı yaradın.
2. Bu `docs/README.md` faylındakı “Sənəd Bölmələri” siyahısına link əlavə edin.
3. Lazımdırsa əsas `README.md` içindəki documentation siyahısına da link əlavə edin.

