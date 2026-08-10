# API qaydaları

Route `routes/api.php` · Controller `app/Http/Controllers/Api/`
· Resource `app/Http/Resources/` · Query `app/Queries/Api/`

> Bu layer starter-də **minimaldır** (yalnız `auth:sanctum` nümunəsi). Aşağıdakı
> qaydalar törəmə layihədə API açılanda tətbiq edilir - hər layihədə formatı
> yenidən icad etməmək üçün.

## 1. Cavab formatı - dəyişməz müqavilə

Bütün endpoint-lər eyni zərfi qaytarır:

```json
{ "success": true,  "message": "", "data": {} }
{ "success": false, "message": "Səhv sorğu", "code": "VALIDATION_FAILED", "errors": {} }
```

- `data` **həmişə** obyekt və ya massivdir - skalyar qaytarılmır (sonradan sahə
  əlavə etmək mümkün olsun).
- Xəta halında `code` maşın üçündür (sabit, tərcümə olunmur), `message` insan
  üçündür (Azərbaycan dilində).
- HTTP status kodu düzgün seçilir: 200 / 201 / 401 / 403 / 404 / 422 / 429 / 500.
  «Həmişə 200, içində success:false» **qəbul edilmir**.

## 2. Resource sinifləri

Model birbaşa `response()->json($model)` ilə qaytarılmır - `JsonResource` işlədilir.

Səbəb: model `toArray()`-i bütün sütunları verir. Yeni sütun (məs. `internal_note`,
`password_reset_token`) əlavə edəndə o, sükutla API-yə sızır.

- Resource yalnız lazım olan sahələri açır;
- tarixlər sabit formatda (`Y-m-d H:i:s` və ya ISO-8601) - hər endpoint-də eyni;
- fayl sahələri `CdnUrl::url()` ilə tam URL kimi verilir.

## 3. Sorğu və validasiya

- Validasiya FormRequest-də (`app/Http/Requests/Api/`), controller-də inline yox.
- Böyük SELECT-lər `app/Queries/Api/` altındakı Query sinifləridir;
  Query içində `request()` oxunmur - filtr typed DTO ilə ötürülür.
- Siyahılar **həmişə** səhifələnir; limit yuxarıdan bağlanır
  (məs. `per_page` maksimum 100) - kənardan gələn `per_page=100000` qəbul edilmir.

## 4. Autentifikasiya və təhlükəsizlik

- Token: Laravel Sanctum (`auth:sanctum`).
- Açar/token heç vaxt route-un URL-ində getmir - header-də.
- Rate limit hər açıq endpoint üçün təyin olunur (`throttle:...`), xüsusən
  giriş / OTP / axtarış endpoint-lərində.
- Cavabda daxili xəta detalı (stack trace, SQL) verilmir - jurnala yazılır,
  istifadəçiyə ümumi mesaj gedir.

## 5. Versiyalama

Endpoint-lər versiya prefiksi ilə açılır (`/api/v1/...`). Mövcud versiyada
**sahə silinmir və tipi dəyişmir** - mobil tətbiq köhnə versiyada qala bilər.
Dəyişiklik lazımdırsa yeni sahə əlavə olunur və ya yeni versiya açılır.
