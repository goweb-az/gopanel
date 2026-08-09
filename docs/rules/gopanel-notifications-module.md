# GoPanel Bildirişlər Bölməsi — qurulma qaydası

> **Status:** bu sənəd Gopanel üçün **normativ qaydadır**, ümumi məsləhət deyil.
> Bildiriş bölməsi qurulanda addımlar bu ardıcıllıqla və bu adlarla icra olunur.
>
> **Mənbə:** `app.qrgate.az` layihəsindəki işlək admin bildiriş sistemi
> (`admin_notifications`). Orada sistem uzun müddət canlıda işləyib, aşağıdakı
> «niyə» qeydləri real problemlərdən çıxıb — onlar silinmir.
>
> Sənəddəki `NEW_USER`, `PAYMENT` kimi **tip adları nümunədir**. Hədəf layihədə
> real domen hadisələri ilə əvəz olunur (yeni sifariş, yeni müraciət, moderasiya).

## 0. İki bildiriş sistemi — qarışdırılmır

| | Admin bildirişi | İstifadəçi bildirişi |
|---|---|---|
| Kimə | GoPanel admininə | sayt/mobil istifadəçisinə |
| Cədvəl | `admin_notifications` | `notifications` |
| Guard | `gopanel` | `web` / `sanctum` |
| Kod | `App\Services\Gopanel\Notification\` | `App\Services\Notification\` |

**İki sistem bir cədvəldə birləşdirilmir.** Səbəb: alıcı tipi, icazə modeli,
sorğu profili və UI tamam fərqlidir; birləşdirilən cədvəldə hər sorğuya
`WHERE recipient_type` əlavə olunur və indekslər iki dəfə şişir.

Bu sənəd **admin** tərəfini təsvir edir. İstifadəçi tərəfi üçün:
[notification-system-architecture-and-implementation-guide.md](notification-system-architecture-and-implementation-guide.md).

---

## 1. Hədəf davranış

Bölmə tamamlandıqda:

- domen hadisəsi baş verəndə (yeni qeydiyyat, ödəniş, moderasiya sorğusu)
  **bütün uyğun adminlərə** bildiriş yaranır;
- göndərmə **queue** ilə gedir — istifadəçinin cavabını gecikdirmir;
- hər bildirişin bir `type`-ı var; tip ikon, rəng, etiket və şablonu təyin edir;
- `database` kanalı həmişə işləyir, `mail`/`sms`/`telegram`/`webpush` opsionaldır;
- header-də zəng ikonu altında **oxunmamış sayı (badge)** və son bildirişlərin
  dropdown-u var, dropdown-da infinite scroll işləyir;
- tam siyahı səhifəsində **4 tab** (Hamısı / Oxunmayan / Oxunan / Arxiv),
  tip süzgəci, mətn axtarışı, səhifə ölçüsü seçimi;
- toplu əməliyyat: oxundu / arxivlə / bərpa et / sil;
- hər bildirişin tipinə uyğun **zəngin detal səhifəsi**;
- admin **yalnız öz** bildirişlərini görür (403 ilə qorunur).

---

## 2. Fayl xəritəsi

Yeni yazılacaq fayllar (Gopanel adlandırma konvensiyası ilə):

```text
database/migrations/
  xxxx_create_admin_notifications_table.php

app/Enums/Notification/
  AdminNotificationTypeEnum.php

app/Models/Gopanel/
  AdminNotification.php

app/Support/Gopanel/
  AdminNotificationGate.php          ← «bu bildiriş bu adminə gedirmi?»

app/Services/Gopanel/Notification/
  AdminNotificationService.php       ← göndər / oxundu / arxiv / sil
  AdminNotificationMethodFactory.php ← kanal adı → adapter
  AdminNotificationViewService.php   ← detal səhifəsinin datası
  Methods/
    AdminDatabaseNotification.php
    AdminMailNotification.php
    AdminSmsNotification.php
    AdminTelegramNotification.php    (opsional)
    AdminWebPushNotification.php     (opsional)

app/Jobs/
  SendAdminNotificationJob.php

app/Queries/Gopanel/Notification/
  AdminNotificationQuery.php         ← tab / tip / axtarış / say

app/Http/Controllers/Gopanel/Admins/
  AdminNotificationController.php

app/Http/Requests/Gopanel/Notification/
  AdminNotificationBulkRequest.php

resources/views/gopanel/
  blocks/notification-header.blade.php          ← MÖVCUD statik blok əvəzlənir
  pages/admin-notifications/
    index.blade.php
    show.blade.php
    partials/list.blade.php
    partials/header-list.blade.php
    partials/field.blade.php
    templates/<type>.blade.php                  ← compact (siyahı/dropdown)
    templates/default.blade.php
    templates/detail/<type>.blade.php           ← zəngin (detal səhifəsi)
    templates/detail/default.blade.php

public/assets/gopanel/js/modules/
  admin-notifications.js        ← header badge + dropdown + infinite scroll
  admin-notifications-page.js   ← siyahı səhifəsi (tab, seçim, toplu əməliyyat)

public/assets/gopanel/css/
  admin-notifications.css
```

Toxunulan mövcud fayllar: `routes/gopanel.php`,
`config/gopanel/permission_list.php`, `config/gopanel/sidebar_menu_list.php`,
`config/logging.php`, `resources/views/gopanel/blocks/header.blade.php`.

---

## 3. Addım-addım

### Addım 1 — Migration

```php
Schema::create('admin_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('admin_id')->constrained('admins')->cascadeOnDelete();
    $table->nullableMorphs('notifiable');            // notifiable_type + notifiable_id
    $table->string('type', 50)->default('default');  // enum dəyəri
    $table->smallInteger('level')->default(1);       // önəm/prioritet
    $table->string('title');
    $table->text('message');
    $table->string('action_url', 500)->nullable();
    $table->json('data')->nullable();
    $table->timestamp('read_at')->nullable();
    $table->timestamp('archived_at')->nullable()->index();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['admin_id', 'read_at']);      // oxunmamış sayı
    $table->index(['admin_id', 'created_at']);   // siyahı / dropdown
    $table->index('type');                       // tip süzgəci
});
```

**Qaydalar:**

- `admin_id` üzərində `cascadeOnDelete` — admin silinəndə bildirişləri qalmır.
- **Kompozit indekslər mütləqdir.** `admin_id` tək indeksi ilə oxunmamış sayı
  hər header sorğusunda min sətir skan edir; header isə hər səhifə açılışında
  çağırılır.
- `type` `string(50)`-dir, **database ENUM deyil** — yeni tip əlavə etmək
  migration tələb etməsin.
- `data` JSON: tipə xas əlavə sahələr (şirkət adı, məbləğ, link). Şablonlar
  bu massivi oxuyur.
- `level` — 1 adi, 2 vacib, 3+ kritik. Detal səhifəsində nişan kimi görünür.

### Addım 2 — Tip enum

`app/Enums/Notification/AdminNotificationTypeEnum.php`. Hər tip **dörd şey**
verir: `label()`, `icon()`, `color()`, `template()` + `detailTemplate()`.

```php
enum AdminNotificationTypeEnum: string
{
    case DEFAULT     = 'default';
    case NEW_USER    = 'new_user';
    case PAYMENT     = 'payment';
    case SYSTEM_ALERT = 'system_alert';

    public function label(): string
    {
        return match ($this) {
            self::DEFAULT      => 'Ümumi',
            self::NEW_USER     => 'Yeni istifadəçi',
            self::PAYMENT      => 'Ödəniş',
            self::SYSTEM_ALERT => 'Sistem xəbərdarlığı',
        };
    }

    /** GoPanel Boxicons işlədir (`bx bx-*`), Font Awesome deyil. */
    public function icon(): string
    {
        return match ($this) {
            self::DEFAULT      => 'bx bx-bell',
            self::NEW_USER     => 'bx bx-user-plus',
            self::PAYMENT      => 'bx bx-credit-card',
            self::SYSTEM_ALERT => 'bx bx-cog',
        };
    }

    /** Bootstrap tonu: primary / success / info / warning / danger */
    public function color(): string { /* match */ }

    /** Siyahı və dropdown üçün compact şablon */
    public function template(): string
    {
        $base = 'gopanel.pages.admin-notifications.templates.';

        return match ($this) {
            self::NEW_USER => $base . 'new_user',
            self::PAYMENT  => $base . 'payment',
            default        => $base . 'default',
        };
    }

    /** Detal səhifəsi üçün zəngin şablon */
    public function detailTemplate(): string
    {
        $base = 'gopanel.pages.admin-notifications.templates.detail.';
        /* eyni struktur, `default` fallback-i ilə */
    }
}
```

**Qaydalar:**

- Hər `match`-də `default` budağı **mütləqdir**. Bazada köhnə tip qalıbsa və
  enum-dan silinibsə, siyahı partlamamalıdır.
- İkon adı **yoxlanır**. Boxicons buraxılışında olmayan ikon (`bx-handshake`
  kimi) boş dairə göstərir — bu, qrgate-də real problem olub.
- Yeni tip əlavə etmək = **1 case + hər match-ə 1 sətir + 2 blade**. Bundan
  artıq iş görülürsə, struktur pozulub.

### Addım 3 — Model

`app/Models/Gopanel/AdminNotification.php`.

**Scope-lar:** `unread()`, `forAdmin($id)`, `byType($type)`, `notArchived()`,
`onlyArchived()`.

**Metodlar:** `isRead()`, `isArchived()`, `markAsRead()`, `archive()`,
`restoreFromArchive()`.

**Təqdimat accessor-ları** — blade-də `if`/`match` yazılmasın deyə:
`type_enum`, `type_icon`, `type_color`, `type_label`, `detail_template`,
`detail_page_template`.

```php
public function getTypeEnumAttribute(): AdminNotificationTypeEnum
{
    return AdminNotificationTypeEnum::tryFrom($this->type) ?? AdminNotificationTypeEnum::DEFAULT;
}
```

`tryFrom(...) ?? DEFAULT` — bazadakı naməlum tip 500 xətası vermir.

**Arxivləmə həm də oxundu sayılır:**

```php
public function archive(): void
{
    $this->update([
        'archived_at' => Carbon::now(),
        'read_at'     => $this->read_at ?? Carbon::now(),
    ]);
}
```

Səbəb: arxivdə «oxunmamış» qalan bildiriş badge-i şişirdir, amma admin onu
görə bilmir — badge heç vaxt sıfırlanmır.

### Addım 4 — Gate (kimə gedir?)

`app/Support/Gopanel/AdminNotificationGate.php` — bildirişin bu adminə
gedib-getməyəcəyini həll edir. **Üç səviyyə:**

1. **İcazə:** adminin həmin bölməyə icazəsi yoxdursa bildiriş də getmir
   (ödənişləri görməyən admin ödəniş bildirişi almır).
2. **Şəxsi tənzimləmə:** admin həmin tipi söndürübsə getmir.
3. **Kanal tənzimləməsi:** `allowsChannel()` — admin brauzer bildirişini
   söndürsə `database` gəlməyə davam edir, yalnız push dayanır.

```php
/**
 * DİQQƏT: burada `$admin->can()` İŞLƏNMİR.
 *
 * `Gate::before` yalnız gopanel guard-ı ilə LOGIN olunmuş sorğuda işləyir;
 * bildirişlər isə queue-da (autentifikasiyasız) göndərilir — orada `can()`
 * hamıya `false` qaytarır və heç kimə bildiriş çatmır. Ona görə birbaşa
 * super-admin bayrağı + spatie-nin sorğu səviyyəli yoxlaması işlədilir.
 */
private function hasAbility(Admin $admin, AdminNotificationTypeEnum $type): bool
{
    $ability = $this->abilityFor($type);

    if ($ability === null || $admin->is_super) {
        return true;
    }

    return $admin->hasPermissionTo($ability, 'gopanel');
}
```

Bu, qayda sənədində **ən çox unudulan** məqamdır: queue-da `can()` işləmir.

### Addım 5 — Kanal adapterləri + factory

Hər kanal bir sinifdir, `Methods/` altında. `database` **kanonik** kanaldır —
inbox record-unu yalnız o yaradır.

```php
// AdminDatabaseNotification
return AdminNotification::create([
    'admin_id' => $admin->id,
    'type'     => $type->value,
    // Stored-XSS qorunması: başlıq/mətn domen servisindən gəlir, orada HTML ola bilər
    'title'    => html_entity_decode(strip_tags($title)),
    'message'  => html_entity_decode(strip_tags($message)),
    'data'     => !empty($data) ? $data : null,
    ...
]);
```

Digər kanallar Gopanel-in mövcud ortaq servislərini çağırır — yeni SMTP/SMS
kodu yazılmır:

| Kanal | Nə işlədir |
|---|---|
| `mail` | `App\Services\Mail\MailService` (`enableQueue(true)` ilə) |
| `sms` | `App\Services\Sms\SmsService` |
| `telegram` | `Http::post()` + `config/custom/notification.php` |
| `webpush` | web-push kitabxanası + VAPID açarları |

Factory sadədir:

```php
return match ($channel) {
    'database' => new AdminDatabaseNotification(),
    'mail'     => new AdminMailNotification(),
    'sms'      => new AdminSmsNotification(),
    default    => throw new InvalidArgumentException("Naməlum kanal: {$channel}"),
};
```

**Qayda: hər kanal öz try/catch-i içindədir.** SMTP düşəndə database record-u
yenə yaranmalıdır — bir kanalın xətası bildirişi tamamilə yox etmir.

### Addım 6 — Servis

`AdminNotificationService` iki məsuliyyət daşıyır: **göndərmə** və
**oxu/idarəetmə**.

```php
public const DEFAULT_CHANNELS = ['database'];
public const ALL_CHANNELS     = ['database', 'mail', 'sms', 'telegram', 'webpush'];

// Domen servisləri BUNU çağırır - sinxron send() yox:
AdminNotificationService::dispatch(
    AdminNotificationTypeEnum::NEW_USER,
    'Yeni istifadəçi',
    "{$user->name} qeydiyyatdan keçdi",
    channels: ['database', 'mail'],
    data: ['user_id' => $user->id, 'email' => $user->email],
    notifiableId: $user->id,
    notifiableType: User::class,
    actionUrl: route('gopanel.users.view', $user->uid),
);
```

Oxu/idarəetmə metodları: `unreadCount()`, `getRecent()`, `markRead()`,
`markAllRead()`, `archive()`, `restore()`, `archiveAll()`, `delete()`,
`deleteAllRead()`, `deleteAll()`, `bulk()`.

`archiveAll()` və toplu `archive` `read_at`-i də doldurur:

```php
->update([
    'archived_at' => Carbon::now(),
    'read_at'     => DB::raw('COALESCE(read_at, NOW())'),
]);
```

### Addım 7 — Job

`SendAdminNotificationJob` bütün adminləri gəzir, Gate-dən keçirir, kanalları çağırır.

```php
public int $tries = 3;

public function handle(): void
{
    $gate      = new AdminNotificationGate();
    $companyId = $gate->resolveCompanyId($this->notifiableType, $this->notifiableId, $this->data);

    // chunkById - 500 admin olan sistemdə hamısını yaddaşa yığmır
    Admin::query()->where('is_active', true)->chunkById(200, function ($admins) use ($gate, $companyId) {
        foreach ($admins as $admin) {
            if (!$gate->allows($admin, $this->type, $companyId)) {
                continue;
            }
            // ... hər kanal ayrı try/catch
        }
    });
}
```

**Job konstruktoruna model obyekti verilmir** — yalnız `notifiableId` +
`notifiableType` skalyarları. Səbəb: `SerializesModels` job payload-una model
sorğusu qoyur; model silinsə job `ModelNotFoundException` ilə düşür.

### Addım 8 — Query sinfi

`AdminNotificationQuery` — bütün SELECT-lər burada, controller-də sorğu yoxdur.

```php
public const TABS = ['all', 'unread', 'read', 'archived'];
public const DEFAULT_TAB      = 'unread';
public const PER_PAGE_DEFAULT = 20;
public const PER_PAGE_MIN     = 5;
public const PER_PAGE_MAX     = 100;
public const PER_PAGE_OPTIONS = [10, 20, 30, 50, 100];
```

**Tab sayları bir sorğudan gəlir** — dörd ayrı `count()` yox:

```php
$row = AdminNotification::forAdmin($this->adminId)
    ->selectRaw('
        SUM(CASE WHEN archived_at IS NULL THEN 1 ELSE 0 END) as all_count,
        SUM(CASE WHEN archived_at IS NULL AND read_at IS NULL THEN 1 ELSE 0 END) as unread_count,
        SUM(CASE WHEN archived_at IS NULL AND read_at IS NOT NULL THEN 1 ELSE 0 END) as read_count,
        SUM(CASE WHEN archived_at IS NOT NULL THEN 1 ELSE 0 END) as archived_count
    ')->first();
```

**Saylar süzgəcdən asılı deyil.** Axtarış yazanda tab rəqəmləri dəyişməməlidir —
əks halda «rəqəmlər yox oldu» təəssüratı yaranır.

**Tip süzgəcinin siyahısı sabitdir** — enum-un bütün case-ləri göstərilir,
yalnız mövcud olanlar yox. Əks halda siyahıda 2 sətir görünür və «bizdə tiplər
çoxdur, niyə ikisi var?» sualı yaranır.

**Toplu əməliyyat üçün sahiblik burada süzülür:**

```php
public function ownedByIds(array $ids): Builder
{
    $ids = array_values(array_filter(array_map('intval', $ids)));

    // `?: [0]` - boş massivdə `whereIn()` heç nə süzmür və HAMISI silinir
    return AdminNotification::forAdmin($this->adminId)->whereIn('id', $ids ?: [0]);
}
```

`?: [0]` qoruyucusu unudulmur — bu, «hamısını sil» qəzasının qarşısını alır.

### Addım 9 — Controller

Nazik. Hər tək-sətir əməliyyatında sahiblik yoxlanır:

```php
abort_if($notification->admin_id !== $this->admin()->id, 403);
```

`index()` həm HTML, həm AJAX qaytarır:

```php
if ($request->ajax() || $request->wantsJson()) {
    return response()->json([
        'html'   => view('...partials.list', compact('notifications', 'tab'))->render(),
        'counts' => $counts,
    ]);
}
```

`view()` açılanda bildiriş avtomatik **oxundu** işarələnir.

`header()` dropdown üçün JSON qaytarır:
`['html', 'unread_count', 'has_more', 'next_page']`.

### Addım 10 — Route-lar

```php
Route::prefix('admin-notifications')->name('admin-notifications.')->group(function () {
    Route::get('/', [AdminNotificationController::class, 'index'])->name('index');
    Route::get('/header', [AdminNotificationController::class, 'header'])->name('header');
    Route::get('/view/{notification}', [AdminNotificationController::class, 'view'])->name('view');

    Route::post('/{notification}/read', [AdminNotificationController::class, 'markRead'])->name('read');
    Route::post('/read-all', [AdminNotificationController::class, 'markAllRead'])->name('read-all');
    Route::post('/{notification}/archive', [AdminNotificationController::class, 'archive'])->name('archive');
    Route::post('/{notification}/restore', [AdminNotificationController::class, 'restore'])->name('restore');
    Route::post('/archive-all', [AdminNotificationController::class, 'archiveAll'])->name('archive-all');
    Route::post('/bulk', [AdminNotificationController::class, 'bulk'])->name('bulk');
    Route::post('/delete-read', [AdminNotificationController::class, 'deleteAllRead'])->name('delete-read');

    // ⚠ SIRA VACİBDİR: `delete-all` `{notification}`-dan ƏVVƏL yazılır.
    // Əks halda Laravel "delete-all" sətrini model id kimi qəbul edir və 404 verir.
    Route::delete('/delete-all', [AdminNotificationController::class, 'deleteAll'])->name('delete-all');
    Route::delete('/{notification}', [AdminNotificationController::class, 'delete'])->name('delete');
});
```

**Bildiriş bölməsində `can:` middleware qoyulmur** — hər admin öz bildirişlərini
görür, icazə filtri Gate-də (göndərmə anında) tətbiq olunur. Yalnız
`index` route-una `gopanel.admin-notifications.index` icazəsi verilə bilər.

### Addım 11 — İcazə + sidebar

`config/gopanel/permission_list.php` → `gopanel` guard-ı altında:

```php
'Bildirişlər' => [
    ['name' => 'gopanel.admin-notifications.index', 'title' => 'Bildirişlər siyahısı'],
],
```

`config/gopanel/sidebar_menu_list.php`:

```php
[
    'icon'  => '<i class="bx bx-bell"></i>',
    'title' => 'Bildirişlər',
    'route' => 'gopanel.admin-notifications.index',
    'can'   => 'gopanel.admin-notifications.index',
],
```

Sonra: `php artisan db:seed --class=PermissionSeeder`

`config/logging.php`-ə ayrıca kanal:

```php
'admin-notifications' => [
    'driver' => 'daily',
    'path'   => storage_path('logs/admin-notifications/admin-notifications-day.log'),
    'days'   => 30,
    'manual' => true,
    'name'   => 'Admin bildirişləri',
],
```

### Addım 12 — Blade

**Header bloku:** `resources/views/gopanel/blocks/notification-header.blade.php`
hazırda **Skote şablonunun statik demo dropdown-udur** (sabit «3» badge-i,
ingilis mətnlər) və `blocks/header.blade.php`-də şərh içindədir. Bölmə
qurulanda həmin fayl real bloka əvəzlənir və header-də şərh açılır.

Blokun tələbləri:

- `id="admin-notif-badge"` — badge, ilkin `display:none`;
- `id="admin-notif-list"` — siyahı konteyneri, JS AJAX ilə doldurur;
- `<meta name="admin-notif-url" content="{{ route('gopanel.admin-notifications.header') }}">`
  → `blocks/head.blade.php`-ə əlavə olunur;
- «Hamısını oxundu» düyməsi: `id="btn-header-mark-all"`.

**Siyahı səhifəsi** (`index.blade.php`): tab başlıqları saylarla, tip `<select>`,
axtarış, səhifə ölçüsü, checkbox sütunu + toplu əməliyyat paneli,
`@include('...partials.list')`.

**Şablon seçimi blade-də `match` ilə edilmir** — modeldəki accessor işlədilir:

```blade
@include($notification->detail_template, ['notification' => $notification])
```

Tipə uyğun şablon yoxdursa `default` işə düşür (enum-dakı `default` budağı).

### Addım 13 — JS

**`admin-notifications.js`** (header): badge + dropdown + infinite scroll.

- URL `<meta name="admin-notif-url">`-dən oxunur, digər endpoint-lər ondan
  `replace()` ilə düzəldilir — JS-ə blade dəyəri inline yazılmır.
- İlk yükləmə səhifə açılışında.
- Infinite scroll: konteynerin `scrollTop + clientHeight >= scrollHeight - 60`
  şərtində növbəti səhifə yüklənir; `_notifLoading` bayrağı ikiqat sorğunu bloklayır.

**Periodik polling barədə xəbərdarlıq (qrgate-də real problem):**

> `setInterval` ilə badge yeniləməsi `file` session driver-i ilə birlikdə
> **təsadüfi logout** yarada bilər — arxa fon AJAX sorğuları sessiya faylına
> paralel yazır və yarış yaranır. Polling yalnız `redis`/`database` session
> driver-i ilə açılır və interval **60 saniyədən az olmur**.

**`admin-notifications-page.js`** (siyahı səhifəsi): tab keçidi, checkbox
«hamısını seç», toplu əməliyyat sorğusu, silmə təsdiqi (SweetAlert2).

Toplu əməliyyat **bir sorğu** ilə gedir — 100 sətir seçiləndə 100 AJAX atılmır.

---

## 4. Yeni tip əlavə etmə (yoxlama siyahısı)

1. `AdminNotificationTypeEnum`-a `case` əlavə et;
2. `label()` / `icon()` / `color()` match-lərinə sətir əlavə et;
3. lazımdırsa `template()` və `detailTemplate()`-ə sətir (yoxdursa `default` işləyir);
4. `templates/<type>.blade.php` və `templates/detail/<type>.blade.php` yarat;
5. `AdminNotificationGate::abilityFor()` — tipin aid olduğu icazəni yaz;
6. lazımdırsa `settingKeyFor()` — adminin söndürə biləcəyi tənzimləmə açarı;
7. domen servisində `AdminNotificationService::dispatch(...)` çağır.

Migration **lazım deyil** — `type` sütunu `string`-dir.

---

## 5. Qadağalar

- ❌ Domen servisindən **sinxron** `send()` çağırmaq. Həmişə `dispatch()` —
  əks halda 300 admini olan sistemdə istifadəçi qeydiyyatı 20 saniyə çəkir.
- ❌ Job konstruktoruna Eloquent modeli ötürmək (yalnız id + type).
- ❌ Blade-də `@php` ilə şablon seçmək və ya `match` yazmaq.
- ❌ `title`/`message`-i `strip_tags` etmədən bazaya yazmaq.
- ❌ Sahiblik yoxlamasını (`abort_if ... 403`) atlamaq.
- ❌ `whereIn('id', $ids)`-i boş massiv qoruyucusu olmadan yazmaq.
- ❌ Queue-da `$admin->can()` işlətmək.
- ❌ Admin və istifadəçi bildirişlərini bir cədvəldə saxlamaq.
- ❌ `delete-all` route-unu `{notification}`-dan sonra yazmaq.

---

## 6. Test (minimum)

`tests/Feature/Gopanel/AdminNotificationTest.php`:

- admin başqasının bildirişini aça bilmir (403);
- `view()` açılışı `read_at`-i doldurur;
- arxivləmə `read_at`-i də doldurur;
- tab sayları süzgəcdən asılı deyil;
- `bulk` yalnız öz sətirlərinə təsir edir (başqasının id-si ötürüləndə 0);
- boş `ids` massivi heç nəyi silmir;
- Gate icazəsi olmayan adminə bildiriş yaratmır;
- bir kanal exception atanda `database` record-u yenə yaranır.

---

## 7. Əlaqəli sənədlər

- [01-umumi.md](../../.claude/rules/01-umumi.md) — ümumi layer qaydaları
- [02-gopanel.md](../../.claude/rules/02-gopanel.md) — GoPanel CRUD ardıcıllığı
- [shared-layer.md](../shared-layer.md) — `MailService`, `SmsService`, `LogService`
- [gopanel-admin-notifications-architecture.md](gopanel-admin-notifications-architecture.md) — geniş arxitektura analizi (arxa plan)
- [notification-system-architecture-and-implementation-guide.md](notification-system-architecture-and-implementation-guide.md) — **istifadəçi** bildirişləri
