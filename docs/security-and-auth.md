# Təhlükəsizlik, autentifikasiya və əməliyyat qatı

Bu sənəd starter-ə əlavə olunmuş ümumi təhlükəsizlik / auth / ops parçalarını
təsvir edir. **Hamısı config ilə idarə olunur və default olaraq mövcud
davranışı dəyişmir** (istisna: giriş limiti - aşağıda).

| Parça | Sinif | Alias / yer | Default |
|---|---|---|---|
| Admin PIN-i (ikinci təsdiq) | `App\Services\Gopanel\Admins\AdminPinService` + `App\Http\Middleware\Gopanel\RequireAdminPin` | `gopanel.pin` (backup endirmə route-una bağlı) | **sönük** (`ADMIN_PIN_ENABLED=false`) |
| Giriş limiti | `App\Services\Auth\LoginThrottle` | `Gopanel\AuthController::attempt` | **AÇIQ**: 5 cəhd / 10 dəq (`LOGIN_MAX_ATTEMPTS=0` söndürür) |
| IP allowlist (panel) | `App\Http\Middleware\Security\RestrictByIp` | `ip.restrict` (gopanel route qrupu) | sönük (`IP_RESTRICTION_ENABLED=false`) |
| Dev domen qoruyucusu | `App\Http\Middleware\Security\RestrictDevDomain` | qlobal + `dev.guard` | sönük (`DEV_GUARD_ENABLED=false`) |
| Sayt statusu → 503 | `App\Http\Middleware\SiteStatusMiddleware` + `App\Services\Site\SiteStatusService` | `site.status` (web route qrupu) | sönük (`SITE_STATUS_ENFORCE=false`) |
| Server-Timing başlığı | `App\Http\Middleware\ServerTiming` | qlobal + `server.timing` | sönük (`SERVER_TIMING_ENABLED=false` **və** `APP_DEBUG`) |
| E-poçt OTP | `App\Services\Auth\OtpService` | - (servis) | - |
| Mail şablonları | `App\Services\Mail\Templates\{OtpMail,ForgotPasswordMail}` | `MailService::sendOtpEmail()` / `sendForgotPasswordEmail()` | - |
| Yüngül icazə yoxlaması | `App\Traits\System\LeanPermissionChecks` | heç bir modelə qoşulmayıb | opt-in |

Config: `config/custom/security.php` (PIN, OTP, sayt statusu, dev guard, IP,
giriş limiti), `config/custom/performance.php` (Server-Timing). Bütün açarlar
`.env.example`-dadır.

---

## 1. Admin PIN-i

**Nə üçün:** oğurlanmış sessiya cookie-si ilə panelə girən adam şifrəni
bilmir, amma sessiya artıq «daxil olub». Backup arxivi bütün bazanı ehtiva
edir - onu endirmək üçün ikinci maneə lazımdır. PIN giriş vermir, yalnız
daxil olmuş adminin konkret addımını təsdiqləyir.

**Necə işləyir**

1. Admin profil → «PIN kodum» (`gopanel.profile.pin.index`) səhifəsində
   cari şifrəsi ilə PIN təyin edir. Hash (`Hash::make`) `admins.pin`-də
   saxlanılır, sütun `$hidden`-dədir və `$fillable`-da deyil.
2. `gopanel.pin` bağlı route-a sorğu gələndə:
   - mexanizm sönükdürsə / admin PIN təyin etməyibsə / son `ADMIN_PIN_TTL`
     dəqiqədə təsdiqləyibsə → heç nə soruşulmur;
   - AJAX/JSON sorğu → **423** + `pin_required: true`. Layout-dakı modal
     (`gopanel.blocks.pin-confirm`) + `js/modules/security/admin-pin.js`
     bunu tutur, PIN soruşur, təsdiqdən sonra sorğunu **eyni parametrlərlə
     təkrarlayır**;
   - adi GET keçidi (backup endirmə linki) → `gopanel.pin.prompt` səhifəsinə
     yönləndirmə. Hədəf URL sessiyada saxlanılır (query-də yox - open
     redirect yaranmasın). Təsdiqdən sonra hədəfə keçilir.
3. Səhv PIN cəhdləri admin üzrə limitlənir (`ADMIN_PIN_ATTEMPTS` /
   `ADMIN_PIN_DECAY`), confirm route-unda əlavə `throttle:30,1` var.

**Yeni route-u qorumaq:**

```php
Route::post('/delete/{item}', [AdminController::class, 'delete'])
    ->name('delete')
    ->middleware('gopanel.pin');
```

**Ən çox unudulanlar**

- PIN unudulubsa: admin profildən **şifrəsi ilə** PIN-i yeniləyir/geri alır.
  Başqa adminin PIN-ini sıfırlama ekranı starter-də yoxdur (lazımdırsa
  törəmə layihədə `AdminPinService::forget()` çağıran icazəli endpoint yazılır).
- Panelin ümumi AJAX xəta handler-ləri (`showError`) 423-ü də göstərə bilər;
  modal yenə açılır. Yeni modulda 423-ü səssiz keçmək üçün xəta callback-ində
  `xhr.status === 423` yoxlanır.
- Mexanizm açılanda da PIN-i olmayan admindən heç nə soruşulmur - «hamı
  üçün məcburi» rejimi bilərəkdən yoxdur (bir gecədə bütün panel bağlanmasın).

## 2. Giriş limiti (`LoginThrottle`)

`config/custom/security.php → login_throttle` əvvəl elan olunmuşdu, amma heç
yerdə tətbiq edilmirdi. İndi `Gopanel\AuthController::attempt()` onu işlədir:
`max_attempts` uğursuz cəhddən sonra `decay_minutes` dəqiqəlik 429.

- Açar **e-poçt + IP** cütüdür: yalnız e-poçta görə bağlansa kənar adam
  istənilən adminin hesabını qəsdən kilidləyərdi; yalnız IP-yə görə bağlansa
  bir IP-dən bütün admin siyahısını yoxlamaq olardı.
- Uğurlu girişdə sayğac sıfırlanır və sessiya ID-si yenilənir (session fixation).
- `LOGIN_MAX_ATTEMPTS=0` → limit sönük (köhnə davranış).
- Sayt/API giriş formasında eyni sinif: `new LoginThrottle('site')`.

## 3. IP allowlist (`ip.restrict`)

`ip_restriction_enabled` / `allowed_ips` / `restricted_domains` açarlarının
tətbiqi. Gopanel route qrupuna (giriş səhifəsi daxil) `RouteServiceProvider`-də bağlıdır.

- `allowed_ips` IP və CIDR qəbul edir: `ALLOWED_IPS=1.2.3.4,10.0.0.0/8`.
- `restricted_domains` boşdursa bütün panel host-ları yoxlanır; doludursa
  yalnız həmin host-lar (məs. canlı panel domeni yoxlanır, lokal yox).
- Açıq + siyahı boş → **hamı bloklanır** (fail-closed).
- IP `$request->ip()`-dir (TrustProxies). `CF-Connecting-IP` yalnız
  `SECURITY_TRUST_CF_HEADER=true` olanda oxunur - server Cloudflare arxasında
  deyilsə bu başlığı istənilən kəs saxtalaşdırar.

## 4. Dev domen qoruyucusu (`RestrictDevDomain`)

Qlobal middleware (`TrustProxies`-dən sonra). Yalnız `DEV_GUARD_DOMAINS`-dəki
host-lara təsir edir:

- `robots.txt` həmişə `Disallow: /`, hər cavab `X-Robots-Tag: noindex, nofollow, noarchive`;
- icazəli IP (`DEV_GUARD_ALLOWED_IPS`) → sayt adi işləyir;
- gizli açar: `?dev_key=<DEV_GUARD_UNLOCK_KEY>` → 30 günlük cookie (dəyəri
  açarın HMAC-ı), `?dev_key=off` → çıxış;
- qalanları → `DEV_GUARD_REDIRECT_TO` (yol saxlanılır) və ya 403. Özünə
  yönləndirmə aşkarlanır (sonsuz dövrə yaranmır).

Cookie `EncryptCookies::$except`-dədir - qlobal middleware onu şifrələmədən əvvəl oxuyur.

## 5. Sayt statusu (`site.status`)

Panel → Sayt tənzimləmələri → «Sayt statusu: Bağlı» (`site_settings.site_status = 0`).

- `SITE_STATUS_ENFORCE=false` (default): köhnə davranış - layout «təmir»
  blokunu **200** ilə göstərir.
- `true`: ziyarətçiyə **503 + Retry-After** (`site.pages.errors.maintenance`),
  axtarış sistemi təmir səhifəsini indeksləmir; Markdown/RSS kimi layout-suz
  cavablar da bağlanır; `SITE_STATUS_EXCEPT` (default `robots.txt,llms.txt,ads.txt`)
  açıq qalır; panelə daxil olmuş admin saytı adi görür (`siteAdminPreview`).

Status açarlarını kodda oxumaq üçün `SiteStatusService` (`isSiteEnabled`,
`isLoginEnabled`, `isRegisterEnabled`, `isPaymentEnabled`, `toArray`) -
sətir/sütun yoxdursa **açıq** sayılır. `ensure*()` (domen istisnası atan)
metodları layihəyə xasdır və törəmə layihədə yazılır.

## 6. Server-Timing

`SERVER_TIMING_ENABLED=true` **və** `APP_DEBUG=true` olanda hər cavaba:

```text
Server-Timing: boot;dur=42.1, app;dur=118.4, db;dur=37.0;desc="14 sorğu"
```

Brauzerin Network → Timing panelində görünür. `SERVER_TIMING_SLOW_MS=800`
→ həddi aşan sorğu ən yavaş 8 SQL və təkrarlanan şablonlarla (N+1)
`single` jurnalına yazılır. Sönük olanda `DB::listen` belə qoşulmur.

## 7. E-poçt OTP (`OtpService`)

```php
$otp = app(\App\Services\Auth\OtpService::class);

$result = $otp->send($email, 'reset', $user->name); // ['sent' => bool, 'retry_after' => int]
if (!$result['sent']) { /* "{$result['retry_after']} saniyə sonra" */ }

if ($otp->verify($email, $request->code, 'reset')) { ... }
$otp->isVerified($email, 'reset');   // ikinci addım (yeni şifrə) üçün
$otp->clearVerified($email, 'reset'); // axın bitəndə
```

- Kod keşdədir (cədvəl yoxdur) - canlıda keş driver-i `array` olmamalıdır.
- `purpose` axınları ayırır: qeydiyyat kodu şifrə sıfırlamaq üçün işləməz.
- Kod heç bir mühitdə jurnala yazılmır, ünvan maskalanır, keş açarında
  e-poçt hash-lənir. Jurnal kanalı: `OTP_LOG_CHANNEL` (default `gopanel-auth`).
- Mail xətası axını dağıtmır (jurnala yazılır); endpoint-ə `throttle:` yenə lazımdır.

## 8. Mail şablonları

| Metod | Şablon | View |
|---|---|---|
| `sendOtpEmail($to, $otp, $userName = null, $ttlMinutes = 5, $subject = null, $intro = null)` | `OtpMail` | `emails.otp` |
| `sendForgotPasswordEmail($to, $actionUrl, $userName = null, $expiresMinutes = null, $subject = null)` | `ForgotPasswordMail` | `emails.forgot-password` |

Hər ikisi `emails.layouts.branded` karkasını işlədir (brend `config/custom/mail.php`),
`emails.basic` dəyişməyib. Bərpa linkini (token/imzalı URL) çağıran yer qurur.

## 9. `LeanPermissionChecks` (opt-in)

Spatie `hasPermissionTo()` ilk çağırışda bütün rol/icazə bağlantılarını
hidrasiya edir - çox rollu layihədə (yüzlərlə rol) bu yüzlərlə MB-dır.
Trait icazə adlarını iki JOIN ilə oxuyub sorğu boyu keşləyir. Starter-də
heç bir modelə qoşulmayıb; qoşmaq qaydası trait-in docblock-undadır.
Wildcard icazələr və Spatie `teams` rejimi dəstəklənmir.

---

## Mənbələr və qərarlar

| Mənbə | Qərar | Səbəb |
|---|---|---|
| texnotech `AdminPinService`, `RequireAdminPin`, `config/gopanel/pin.php`, profil PIN səhifəsi, modal + JS | **ADAPT** | master açar əlavə olundu; config `custom.security.admin_pin`-ə köçdü; adi GET keçidi üçün təsdiq səhifəsi əlavə olundu (backup endirmə linki AJAX deyil); `protected_routes` config-i yerinə route-a `gopanel.pin` bağlanır |
| texnotech `AdminSecurityController` (başqa adminin PIN-i, bildiriş tənzimləmələri) | SKIP | bildiriş tənzimləmələri o layihəyə xasdır; PIN-i admin özü şifrə ilə sıfırlaya bilir |
| texnotech `OtpService` | **ADAPT** | sabitlər config-ə; `Mail::raw` → `MailService::sendOtpEmail`; `LogChannelEnum` → config kanalı; keş açarında e-poçt hash-lənir; kod uzunluğu config |
| qrgate `OtpMail`, `ForgotPasswordMail` + view-lar | **ADAPT** | hardcode `noreply@qrgate.az`/`env()` çıxarıldı; gopanel brend karkası |
| qrgate `WelcomeMail` | SKIP | paket limitləri/istifadəsi - biznes məntiqi |
| qrgate `CustomMail` | SKIP | `BasicMail`/`HtmlMail` artıq eyni işi görür |
| qrgate `NotificationMail` | SKIP | `NotificationEmailContent` + xəritə şəkli - layihəyə xas |
| qrgate `SiteStatusMiddleware` | **ADAPT** | `qr/*`, `endir` istisnaları config-ə; config ilə qapalı; admin önizləməsi layout-a ötürülür |
| texnotech `SiteStatusService` | **ADAPT** | order/credit açarları və domen istisnaları çıxarıldı; mövcud `SiteSetting::getCached()` keşi |
| qrgate `RestrictDevDomain` | **ADAPT** | config `dev_guard.*`; hardcode domen yox; boş `redirect_to` → 403 |
| app.qrgate `RestrictByIP` | **ADAPT (yenidən yazıldı)** | orijinal yalnız `local` mühitdə işləyirdi və `X-Forwarded-For`-a kor inanırdı; CIDR, fail-closed |
| app.qrgate `ServerTiming` | **ADAPT** | standart `Server-Timing` başlığı; flag + `APP_DEBUG` şərti; slow log opsional |
| app.qrgate `LeanPermissionChecks` | **COPY (kiçik adaptasiya)** | cədvəl adları Spatie config-indən; opt-in |
