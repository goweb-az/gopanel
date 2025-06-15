
<p align="center">
  <img src="https://proweb.az/uploads/images/statics/06df94f842-Proweb-bu-gunun-reqemsal-dunyasi-ucun-innovativ-veb-heller.png" alt="Gopanel Logo" width="320">
</p>

<p align="center">
  <strong>Versiya:</strong> 1.0.0  
</p>

---

# Gopanel – Laravel əsaslı hazır admin panel

**Gopanel** Laravel 10 ilə hazırlanmış, istifadəyə hazır və genişlənə bilən admin panel layihəsidir.  
Yeni Laravel layihələrinə sürətli və funksional başlanğıc üçün nəzərdə tutulmuşdur.

---

## 🚀 Qurulum

Layihəni qurmaq üçün terminalda aşağıdakı əmri icra edin:

```bash
composer create-project goweb/gopanel
```

və ya öz layihə adınızı qeyd edərək:

```bash
composer create-project goweb/gopanel your-project-name
```

Bu əmr layihəni tam şəkildə qovluğa yükləyəcək.

---

## ⚙️ Verilənlər bazası ayarları

Əgər sisteminizdə:

- PHPMyAdmin quraşdırılıbsa
- MySQL istifadəçi adı `root`, parol `root` və ya boşdursa
- `gopanel` adlı bir database əvvəlcədən yaradılıbsa

heç bir əlavə konfiqurasiya olmadan sistem birbaşa işləyəcək.

Əks halda aşağıdakı düzəlişləri edin:

---

### 🔧 Əl ilə konfiqurasiya

1. Layihə qovluğunda `.env` faylını açın və verilənlər bazası ayarlarını öz sisteminizə uyğun dəyişin:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gopanel
DB_USERNAME=root
DB_PASSWORD=
```

2. Əgər hər hansı bir xəta yaranarsa, əvvəlcə database yaradın və sonra bu əmrləri icra edin:

```bash
php artisan key:generate
php artisan migrate
php artisan db:seed    # (əgər seederlər mövcuddursa)
```

---

## 📦 Daxil edilən paketlər

Aşağıdakı Laravel paketləri Gopanel daxilində avtomatik quraşdırılır:

- [**Spatie Laravel Permission**](https://github.com/spatie/laravel-permission) – Rol və icazələrin idarə olunması
- [**Spatie Laravel Activity Log**](https://github.com/spatie/laravel-activitylog) – İstifadəçi fəaliyyətlərinin qeydi
- [**Opcodes Laravel Log Viewer**](https://github.com/opcodesio/log-viewer) – Geniş log izləmə interfeysi

---

## 📁 Qovluq quruluşu

Layihənin əsas qovluqları Laravel standartlarına uyğundur və əlavə olaraq aşağıdakıları əhatə edir:

```
app/Helpers             → Əlavə köməkçi funksiyalar
resources/views/panel   → Admin panel interfeysi
routes/web.php          → Web yönləndirmələr
```

---

## 📜 Lisenziya

Bu layihə MIT lisenziyası ilə yayımlanır.  
© [Oruc Seyidov](https://github.com/orucseyidov)
