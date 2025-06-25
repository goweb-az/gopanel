
<p align="center">
  <img src="https://proweb.az/uploads/images/statics/06df94f842-Proweb-bu-gunun-reqemsal-dunyasi-ucun-innovativ-veb-heller.png" alt="Gopanel Logo" width="320">
</p>

<p align="center">
  <strong>Versiya:</strong> 1.0.0  
</p>

---

# Gopanel – Laravel əsaslı hazır admin panel

**Gopanel** Laravel 10 ilə hazırlanmış, istifadəyə tam hazır və genişlənə bilən bir admin panel şablonudur.  
Yeni layihələr üçün sürətli başlanğıc və modul əsaslı inkişaf imkanları təqdim edir.

---

## 🚀 Qurulum

Layihəni yaratmaq üçün terminalda aşağıdakı əmrlərdən birini istifadə edin:

```bash
composer create-project goweb/gopanel
```

və ya qovluq adı ilə:

```bash
composer create-project goweb/gopanel your-project-name dev-master
```

---

## ⚙️ Verilənlər bazası ayarları


`.env` faylını açın və aşağıdakı kimi düzəliş edin:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gopanel
DB_USERNAME=root
DB_PASSWORD=
```

Sonra terminalda aşağıdakı əmrləri icra edin:

```bash
php artisan key:generate
php artisan migrate --seed
```

---

## 📦 Daxil edilən paketlər

- [Spatie Laravel Permission](https://github.com/spatie/laravel-permission)
- [Spatie Laravel Activity Log](https://github.com/spatie/laravel-activitylog)
- [Opcodes Laravel Log Viewer](https://github.com/opcodesio/log-viewer)

---

## 📁 Qovluq quruluşu

```
app/Datatable               → Jquery datatable uyğun classlar
app/Traits                  → Modellər üçün köməkçi traitlər
app/Helpers                 → Əlavə helper funksiyalar
resources/views/gopanel     → Panel interfeysi
routes/gopanel.php          → Admin yönləndirmələri
routes/web.php              → Web yönləndirmələri
```

---

## 🧩 İstifadə olunan traitlər və strukturlar

### 🔹 UID + ID birlikdə istifadə etmək üçün:

**Migration:**
```php
use Illuminate\Support\Facades\DB;
$table->uuid('uid')->unique()->default(DB::raw('UUID()'));
```

**Modeldə:**
```php
use AddUuid;
```

---

### 🔹 Fayl yolu və slug

```php
protected $files = ['image']; // Məsələn: image_url qaytarar
public $slug_key = 'title';   // Slug üçün əsas sütun
public $translatedAttributes = ['title', 'description', 'slug']; // Tərcümə edilən sütunlar
```

**Qeyd:** Translation üçün ayrıca migrationda göstərməyə ehtiyac yoxdur.

---

### 🔹 Translation Trait

Tərcümə dəstəyi verir və `$translatedAttributes` ilə birlikdə işləyir.

---

### 🔹 FormatsDate Trait

Tarixləri avtomatik olaraq Azərbaycan dilində formatlamağa imkan verir.

---

### 🔹 HasArchive Trait

Model arxivlənəcəkdirsə:

**Migration:**
```php
$table->timestamp('archived_at')->nullable();
```

**Model:**
```php
use HasArchive;
```

---

### 🔹 MetaData Trait

Modeldə metadata (title, description, keywords) saxlamaq üçün istifadə olunur.

---

### 🔹 UiElements Trait

Modeldə checkbox və switch kimi inputların UI hissələrini avtomatik idarə etmək üçün istifadə olunur.

---


# 🔐 Rol və İcazə Sistemi

**Gopanel**, `Spatie Laravel Permission` paketi üzərindən rol və icazə sistemini tam şəkildə dəstəkləyir.

---

## 🧩 Konfiqurasiya: `config/gopanel/permission_list.php`

İcazələrin qruplar və guard-lar üzrə strukturlaşdırıldığı yerdir.

**Məqsəd:** Yeni icazələr əlavə edərkən buraya yazılır, seeder faylı vasitəsilə verilənlər bazasına yazılır.

**Struktur:**
```php
return [
    'web' => [
        'blog' => [
            ['name' => 'blog.create', 'title' => 'Bloq yarat'],
            ['name' => 'blog.edit', 'title' => 'Bloq redaktə et'],
        ],
        'services' => [
            ['name' => 'service.view', 'title' => 'Xidmətləri görüntülə'],
        ],
    ],
    'api' => [
        'user' => [
            ['name' => 'user.update', 'title' => 'İstifadəçini yenilə'],
        ],
    ],
];
```
**İcazələri bazada yeniləmə:**

```bash
php artisan config:clear
php artisan db:seed --class=PermissionSeeder
```
### 🔹 Admin panel template 

[Skote - Admin & Dashboard Template](https://themesbrand.com/skote/layouts/index.html)

---

## 📜 Lisenziya

<!-- Bu layihə MIT lisenziyası ilə yayımlanır.   -->
<!-- © [Oruc Seyidov](https://github.com/orucseyidov) -->

Copyright © 2025 [Oruc Seyidov](https://github.com/orucseyidov). All rights reserved.

This software is proprietary and confidential. Unauthorized copying of this file, via any medium is strictly prohibited.

