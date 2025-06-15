<p align="center">
  <img src="https://proweb.az/uploads/images/statics/06df94f842-Proweb-bu-gunun-reqemsal-dunyasi-ucun-innovativ-veb-heller.png" alt="Gopanel Logo" width="320">
</p>

<p align="center">
  <strong>Versiya:</strong> 1.0.0  
</p>

---

# Gopanel – Laravel əsaslı hazır admin panel

**Gopanel** Laravel 10 ilə hazırlanmış, istifadəyə hazır və genişlənə bilən admin panel layihəsidir.  
Bu panel yeni layihələr üçün sürətli başlanğıc imkanı yaradır və bir çox vacib funksionallıq artıq içərisində mövcuddur.

---

## 🚀 Qurulum

Layihəni qurmaq üçün terminalda aşağıdakı əmri icra edin:


```composer create-project goweb/gopanel```


və ya öz layihə adınızı qeyd edərək:


```composer create-project goweb/gopanel your-project-name```



Bu əmr layihəni tam şəkildə qovluğa yükləyəcək.

---

## ⚙️ Verilənlər bazası ayarları

Əgər sisteminizdə:

- PHPMyAdmin quraşdırılıbsa
- MySQL istifadəçi adı: `root`, parol: `root` və ya boşdursa
- `gopanel` adlı bir database əvvəlcədən yaradılıbsa

Bu zaman heç bir əlavə ayara ehtiyac olmadan sistem birbaşa işləyəcək.

Əks halda aşağıdakı düzəlişləri etməlisiniz:

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


### Əgər hər hansı bir xəta yaranarsa

öncə database yaradın və .env faylında bu məlumatları qeyd ederək aşağıldakı əmçrləri tək tək icra edin

```
php artisan key:generate
php artisan migrate
php artisan db:seed    # (əgər seederlər mövcuddursa)
```

### 📦 Daxil edilən paketlər
Gopanel aşağıdakı Laravel paketlərini özündə ehtiva edir:

Spatie Laravel Permission – Rol və icazələrin idarə olunması

Spatie Laravel Activity Log – Aktivlik qeydləri

Opcodes Laravel Log Viewer – Geniş log izləmə paneli

### 📁 Qovluq quruluşu
Layihənin əsas qovluqları Laravel standartlarına uyğundur və əlavə olaraq aşağıdakıları da əhatə edir:

app/Helpers – Əlavə köməkçi funksiyalar

resources/views/panel – Panel interfeysi

routes/web.php – Web yönləndirmələri

### 📜 Lisenziya
Bu layihə MIT lisenziyası ilə yayımlanır.
© Oruc Seyidov