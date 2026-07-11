# Laravel bildiriş sistemi arxitekturası və tətbiq bələdçisi

## Sənədin məqsədi

Bu sənəd başqa Laravel layihəsində tam bildiriş sistemi quracaq proqramçı və ya süni intellekt modeli üçün texniki tapşırıqdır. Sənəd `app.qrgate.loc` layihəsindəki bildiriş axınlarının, mail və push kanallarının, verilənlər bazası bildirişlərinin, şablonların, şirkət ayarlarının, queue və scheduler mexanizmlərinin, kampaniyaların və Gopanel izləmə ekranlarının analizinə əsaslanır.

Məqsəd mövcud layihədə işləyən yanaşmaları ümumiləşdirmək, aşkarlanan zəiflikləri yeni layihəyə daşımamaq və domen hadisəsindən son istifadəçiyə çatdırılmaya qədər bütün bildiriş həyat dövrünü vahid qaydalarla müəyyən etməkdir.

Bu sənəddə `Attendance`, `Company`, `EmployeeLeave` və `Campaign` kimi adlar nümunədir. Hədəf layihədə onlar real domen obyektləri (`Order`, `Invoice`, `Ticket`, `Appointment` və s.) ilə əvəz edilməlidir.

Normativ sözlər:

- **MÜTLƏQ** — tələbdir;
- **OLMAMALIDIR** — qadağadır;
- **TÖVSİYƏ EDİLİR** — əsas seçimdir, fərqli seçim əsaslandırılmalıdır;
- **OLA BİLƏR** — layihənin ehtiyacından asılıdır.

## Mövcud layihənin qısa analizi

Mövcud sistemdə əsas giriş nöqtəsi `NotificationService`-dir. Service alıcını, kanalları, başlıq, mətn, əlavə data və təkrarı önləyən `event_key` qəbul edir. Kanal adı `NotificationMethodFactory` vasitəsilə uyğun adapterə çevrilir:

```text
database   -> DatabaseNotification
mail       -> EmailNotification
mobilepush -> MobilePushNotification -> FirebaseService -> FCM HTTP v1
webpush    -> WebPushNotification -> broadcast event
sms        -> SmsNotification -> SmsService/provider
telegram   -> TelegramNotification (hazırda yalnız skelet)
```

Sinxron göndəriş üçün `send()`, queue üçün `sendJob()` istifadə edilir. `config/queue.php` kanal adını ayrıca queue adına çevirir. Məsələn, mail `email_queue`, mobil push `push_queue`, database isə `default` queue-da işləyir.

Planlaşdırılmış və kütləvi bildirişlər ayrıca pipeline-dan keçir:

```text
Domain service / campaign
  -> ScheduledNotificationService
  -> scheduled_notifications (hər alıcı üçün ayrıca qeyd)
  -> Laravel Scheduler (hər dəqiqə)
  -> ProcessCompanyNotificationsJob (şirkət üzrə lock)
  -> SendNotificationJob (alıcı + kanal üzrə)
  -> kanal adapteri
```

Sistemdə iki fərqli inzibati görünüş mövcuddur:

- istifadəçinin öz panelində inbox: oxunmamış, oxunmuş, arxiv, silmə və bərpa;
- Gopanel-də qlobal nəzarət: database bildirişləri, planlaşdırılmış bildirişlər, kampaniyalar, mesaj şablonları, mail görünüş ayarları və SMS logları.

Müsbət cəhətlər:

- kanallar ortaq interface arxasında ayrılıb;
- domen service-i alıcını və kanalı həll edir, controller yalnız use-case başladır;
- hər kanal ayrıca queue-ya yönləndirilə bilir;
- database bildirişləri morph əlaqəsi və metadata ilə domen obyektinə bağlanır;
- kütləvi planlama chunk və bulk insert ilə aparılır;
- scheduler şirkət üzrə emal edir və lock tətbiq edir;
- `event_key + channel + user` kombinasiyası qısa müddətli duplicate qoruması verir;
- Gopanel-də planlaşdırılmış qeydlər status, tip və şirkət üzrə izlənə bilir;
- kampaniya alıcıları “hamı”, “işdə olan”, “işdə olmayan”, “seçilmiş” kimi resolver vasitəsilə hesablanır;
- şirkətə görə hadisə-kanal-alıcı ayarları saxlanır;
- rich notification görünüşləri üçün `template_type`, metadata və canlı morph data birləşdirilir.

Yeni layihəyə olduğu kimi daşınmamalı hissələr:

- service və adapterlərin dependency-ləri `new` ilə yaratması;
- adapterlərin exception-u loglayıb udması və yuxarı qata uğursuzluq bildirməməsi;
- planlaşdırılmış qeydin kanal job-ları yalnız dispatch edilən kimi `sent` sayılması;
- bir bildirişin hər kanal üzrə ayrıca çatdırılma statusunun saxlanmaması;
- yalnız ən son FCM tokeninə göndərilməsi;
- etibarsız FCM tokenlərinin avtomatik deaktiv edilməməsi;
- FCM project ID və credentials yolunun kodda hardcode edilməsi;
- queue `after_commit` parametrinin `false` olması;
- template save əməliyyatında ayrıca Form Request, unikal key və placeholder validation olmaması;
- `MessageService`-in model class və attribute yollarını birbaşa config-dən oxuması;
- bəzi domen bildirişlərində şirkət ayarları tətbiq edilərkən, bəzilərində kanalların hardcode yazılması;
- `send_to` sahəsinin model `fillable` siyahısı ilə migration arasında uyğunsuzluğu;
- factory-də `whatsapp` adapteri olmadığı halda ayarlarda bu kanalın mövcudluğu;
- Telegram adapterinin faktiki göndəriş etməməsi;
- kanal adlarının `push`, `mobilepush`, `email`, `mail` kimi iki fərqli lüğətlə işləməsi;
- Gopanel-də database inbox qeydi ilə real mail/push çatdırılmasının eyni şey kimi görünə bilməsi;
- test mail zamanı real model dəyişənləri verilmədiyi üçün placeholder-ların boş qalması;
- kampaniya status statistikasında `processing` və `cancelled` hallarının tam nəzərə alınmaması.

## Əsas arxitektura qərarı: bildiriş niyyəti ilə çatdırılmanı ayır

Bildiriş sistemi iki fərqli anlayışı ayırmalıdır:

1. **Notification intent** — hansı hadisəyə görə, kimə, hansı məzmunla və hansı kanallardan bildiriş getməlidir.
2. **Delivery attempt** — konkret alıcıya konkret kanal üzrə nə vaxt cəhd edildi və nəticə nə oldu.

Bir `notifications` qeydi istifadəçinin inbox obyektidir. O, mailin və ya push-un həqiqətən çatdırıldığını sübut etmir. Real izləmə üçün ayrıca `notification_deliveries` cədvəli MÜTLƏQ yaradılmalıdır.

Tövsiyə edilən qatlar:

```text
Domain event / use-case
  -> Notification policy/orchestrator
  -> recipient resolver
  -> preference resolver
  -> template renderer
  -> notification + delivery records
  -> queue jobs
  -> channel drivers
  -> provider
  -> delivery status/log/metrics
```

Controller kanal seçməməli, HTML qurmamalı, provider çağırmamalı və queue job yaratmamalıdır.

## Tövsiyə edilən qovluq strukturu

```text
app/
├── Notifications/
│   ├── Contracts/
│   │   ├── ChannelDriver.php
│   │   ├── TemplateRenderer.php
│   │   └── RecipientResolver.php
│   ├── DTOs/
│   │   ├── NotificationMessage.php
│   │   ├── NotificationContext.php
│   │   └── DeliveryResult.php
│   ├── Channels/
│   │   ├── DatabaseChannel.php
│   │   ├── MailChannel.php
│   │   ├── FcmChannel.php
│   │   ├── WebSocketChannel.php
│   │   └── SmsChannel.php
│   ├── Events/
│   ├── Jobs/
│   │   ├── CreateNotificationDeliveriesJob.php
│   │   └── SendNotificationDeliveryJob.php
│   ├── Policies/
│   ├── Recipients/
│   ├── Services/
│   │   ├── NotificationDispatcher.php
│   │   ├── PreferenceResolver.php
│   │   ├── NotificationScheduler.php
│   │   └── CampaignService.php
│   ├── Templates/
│   └── ValueObjects/
├── Models/Notifications/
├── Http/Controllers/Gopanel/Notifications/
├── Http/Requests/Gopanel/Notifications/
├── Queries/Gopanel/Notifications/
└── Enums/Notifications/
```

Kiçik layihədə qovluqlar sadələşdirilə bilər, lakin interface, orchestrator, channel driver, template renderer və delivery log məsuliyyətləri qarışdırılmamalıdır.

## Vahid termin və enum qaydası

Sistemin hər qatında eyni kanal açarları işlədilməlidir:

```php
enum NotificationChannel: string
{
    case DATABASE = 'database';
    case MAIL = 'mail';
    case MOBILE_PUSH = 'mobile_push';
    case WEB_PUSH = 'web_push';
    case SMS = 'sms';
    case TELEGRAM = 'telegram';
    case WHATSAPP = 'whatsapp';
}
```

DB sütunu, config, request, queue mapping və driver registry eyni enum dəyərindən istifadə etməlidir. `push`/`mobilepush`, `email`/`mail` kimi alias-lar yalnız köhnə məlumatı migrate edən adapterdə ola bilər.

Tövsiyə edilən statuslar:

```text
notification: draft | scheduled | processing | completed | partially_failed | cancelled
delivery:     pending | queued | sending | sent | delivered | failed | skipped | cancelled
campaign:     draft | scheduled | processing | completed | partially_failed | cancelled
```

`sent` provider-in request-i qəbul etdiyini, `delivered` isə provider webhook-u ilə cihaz/mail serverinə çatmanı ifadə etməlidir. Provider delivery webhook vermirsə `delivered` iddia edilməməlidir.

## Verilənlər bazası sxemi

### `notifications`

Bir alıcı üçün məntiqi bildiriş/inbox qeydi:

```text
id, uid
tenant_id/company_id nullable
recipient_type, recipient_id
notification_type
title, body
action_url nullable
level
metadata json nullable
notifiable_type, notifiable_id nullable
read_at, archived_at
created_at, updated_at, deleted_at
```

İndekslər:

```text
(recipient_type, recipient_id, read_at, created_at)
(tenant_id, created_at)
(notifiable_type, notifiable_id)
(notification_type, created_at)
```

### `notification_deliveries`

Hər `notification + channel` üçün ayrıca izləmə qeydi:

```text
id, uid
notification_id
channel
status
destination_masked nullable
provider nullable
provider_message_id nullable
attempts default 0
queued_at, started_at, sent_at, delivered_at, failed_at nullable
next_retry_at nullable
last_error_code nullable
last_error_message nullable
request_payload json nullable
response_payload json nullable
created_at, updated_at
```

Unikal indeks:

```text
(notification_id, channel)
```

Əgər eyni kanala bir neçə cihaz/token üzrə göndəriş ayrıca izlənəcəksə unikal açara `endpoint_id` əlavə edilməlidir.

### `notification_endpoints`

Mobil token, web push subscription və digər ünvanlar vahid modeldə saxlanıla bilər:

```text
id, user_id, channel, token_hash, encrypted_token
device_id, platform, app_version
is_active, last_seen_at, invalidated_at
created_at, updated_at
```

Token plaintext loglara yazılmamalıdır. Eyni token üçün unique constraint olmalıdır. Logout zamanı cari endpoint deaktiv edilməli, provider `UNREGISTERED`/invalid-token cavabı verdikdə avtomatik invalid edilməlidir.

### `notification_templates`

```text
id, uid, tenant_id nullable
key, channel nullable, locale
subject nullable, title nullable, body
format: text | html | markdown
allowed_variables json
version
is_active, is_system, is_locked
created_by, updated_by
created_at, updated_at, deleted_at
```

Unikal indeks ən azı `(tenant_id, key, channel, locale, version)` olmalıdır. Aktiv versiya ayrıca işarələnə və ya template revision cədvəlində saxlanıla bilər.

### `notification_preferences`

Şirkət/domen hadisəsi üzrə kanal və alıcı siyasəti:

```text
id, tenant_id
event_key
is_active
channels json
recipient_strategy
recipient_ids json nullable
quiet_hours json nullable
created_at, updated_at
```

Boolean sütunlarla kanal saxlamaq kiçik sistemdə mümkündür, lakin yeni kanal əlavə etdikcə migration tələb edir. Genişlənən sistemdə enum array/əlaqəli cədvəl daha uyğundur.

### `scheduled_notifications`

Planlaşdırılmış işin durable mənbəyi:

```text
id, uid, tenant_id, recipient_id
notification_type, template_key nullable
title/body snapshot və ya template_version
channels json
context json
event_key/idempotency_key
scheduled_at
status, attempts
claimed_at, sent_at, failed_at nullable
last_error nullable
created_at, updated_at
```

`event_key` üçün use-case-ə uyğun unique indeks qurulmalıdır. Sadəcə `whereDate` ilə duplicate yoxlama race condition-u tam həll etmir.

### Kampaniyalar

Kampaniya cədvəli draft məlumatını saxlamalıdır; faktiki alıcılar göndəriş anında snapshot edilməlidir. Sonradan istifadəçi qrupu dəyişsə belə audit pozulmamalıdır.

```text
notification_campaigns
campaign_recipients
campaign_notifications/deliveries
```

## Bildiriş payload müqaviləsi

Array-lərin sərbəst ötürülməsi əvəzinə DTO istifadə edilməlidir:

```php
final readonly class NotificationMessage
{
    public function __construct(
        public string $type,
        public string $title,
        public string $body,
        public array $channels,
        public array $context = [],
        public ?string $actionUrl = null,
        public ?string $idempotencyKey = null,
        public ?MorphReference $subject = null,
        public NotificationLevel $level = NotificationLevel::LOW,
    ) {}
}
```

`context` yalnız serializable primitive məlumat saxlamalıdır. Password, OTP-dən başqa secret, access token, tam FCM token, kart məlumatı və həssas şəxsi məlumat payload və loglarda olmamalıdır.

FCM `data` dəyərləri string olmalıdır. Array/object JSON-a çevrilirsə mobil müqavilədə bu açıq sənədləşdirilməlidir.

## Bildiriş göndərilməsinin tam axını

### Ani domen bildirişi

```text
HTTP/command use-case
  -> DB transaction
  -> domain state saved
  -> domain event afterCommit
  -> notification policy
  -> recipients + preferences
  -> template render
  -> notification/delivery rows
  -> channel jobs
  -> provider
  -> delivery status
```

Xarici mail, FCM, SMS və broadcast transaction daxilində çağırılmamalıdır. Event/job MÜTLƏQ commit-dən sonra dispatch edilməlidir.

### Planlaşdırılmış bildiriş

```text
Scheduler/command
  -> idempotent scheduled row creation
  -> due-row dispatcher
  -> atomic claim (pending -> processing)
  -> notification + delivery rows
  -> channel jobs
  -> aggregate status update
```

`withoutOverlapping()` və `onOneServer()` tətbiq səviyyəsində faydalıdır, lakin DB claim-i əvəz etmir. Worker `lockForUpdate`, `SKIP LOCKED` dəstəyi və ya atomik status update ilə eyni row-u yalnız bir dəfə götürməlidir.

### Kütləvi kampaniya

```text
Gopanel form
  -> Form Request validation + permission
  -> draft campaign
  -> recipient preview/count
  -> send/schedule confirmation
  -> recipient snapshot (chunkById/cursor)
  -> per-recipient notification/delivery creation
  -> rate-limited queues
  -> progress aggregation
```

100 min alıcını Eloquent collection-a bütöv yükləmək olmaz. `chunkById`, `lazyById`, cursor və bulk insert tətbiq edilməlidir.

## Orchestrator və kanal driver-ləri

Vahid driver contract:

```php
interface ChannelDriver
{
    public function send(NotificationDelivery $delivery): DeliveryResult;
}
```

`DeliveryResult` uğur, retry edilə bilən xəta və daimi xəta fərqini qaytarmalıdır:

```php
final readonly class DeliveryResult
{
    public function __construct(
        public bool $accepted,
        public bool $retryable,
        public ?string $providerMessageId = null,
        public ?string $errorCode = null,
        public ?string $errorMessage = null,
    ) {}
}
```

Driver exception-u səssiz udmamalıdır. Job nəticəni delivery cədvəlinə yazmalı, retryable xəta üçün exception atmalı, permanent xəta üçün `failed` yazıb retry etməməlidir.

Driver-lər container registry ilə həll edilməlidir:

```php
'drivers' => [
    'database'    => DatabaseChannel::class,
    'mail'        => MailChannel::class,
    'mobile_push' => FcmChannel::class,
    'web_push'    => WebSocketChannel::class,
    'sms'         => SmsChannel::class,
],
```

Factory daxilində əl ilə `new` yazmaq əvəzinə container driver dependency-lərini inject etməlidir.

## Kanal qaydaları

### Database/in-app

- title və body plain text kimi ayrıca saxlanmalıdır;
- rich görünüş üçün `notification_type`, `metadata` və optional morph subject saxlanmalıdır;
- presentation zamanı morph obyektindən canlı data oxuna bilər, lakin audit üçün vacib məlumat snapshot metadata-da qalmalıdır;
- action URL server-side route map-dən hazırlanmalıdır, istifadəçidən gələn təsadüfi URL etibarlı sayılmamalıdır;
- inbox query-si recipient ownership-i MÜTLƏQ yoxlamalıdır;
- read, archive, restore və delete endpoint-ləri idempotent olmalıdır;
- istifadəçi başqa istifadəçinin bildiriş ID-si ilə məlumat ala bilməməlidir;
- unread count cache edilirsə mutation zamanı atomik invalidasiya edilməlidir.

### Mail

- mail transport credentials `.env`/secret manager-də saxlanmalıdır;
- branding (`from`, logo, footer, support contact) template məzmunundan ayrılmalıdır;
- HTML Blade layout ortaq header/footer istifadə etməlidir;
- subject, preheader, plain-text fallback və HTML body dəstəklənməlidir;
- istifadəçi tərəfindən dəyişən HTML sanitize edilməlidir;
- mail queue-da göndərilməli və provider message ID saxlanmalıdır;
- bounce, complaint və unsubscribe webhook-ları dəstəklənməlidir;
- transactional və marketing mail kateqoriyaları ayrılmalıdır;
- marketing mail üçün consent və unsubscribe hüquqi tələblərə uyğun olmalıdır.

Mövcud layihədə `EmailNotification` `notification_type` əsasında `device_change`, `coordinate_blocked`, `leave`, `timeoff`, `attendance`, `cooperation` və basic mail seçir. Yeni layihədə uzun `if/elseif` zənciri əvəzinə template registry istifadə edilməlidir.

### Mobil push/FCM

- Firebase project ID config/env-dən gəlməlidir;
- service-account JSON repoda saxlanmamalıdır;
- bütün aktiv cihaz tokenlərinə və ya məhsul siyasətinə uyğun seçilmiş endpoint-ə göndərilməlidir;
- token register endpoint-i auth, validation, platform, device ID və unique upsert tətbiq etməlidir;
- token rotation və logout nəzərə alınmalıdır;
- invalid token permanent xəta kimi deaktiv edilməlidir;
- notification payload və data payload mobil komanda ilə versiyalanmış müqavilə olmalıdır;
- deep link üçün `type`, public UID və route parameters istifadə edilməli, daxili DB ID məcbur edilməməlidir;
- title/body provider limitlərinə uyğun kəsilməli, şəkil və action imkanları platformaya görə test edilməlidir;
- yüksək həcm üçün provider quota, backoff və jitter tətbiq edilməlidir.

### Web push və real-time

WebSocket broadcast ilə brauzer açıq olduğu zaman real-time xəbər vermək, standart Web Push subscription ilə brauzer bağlı olanda push çatdırmaq eyni deyil. Kanal adları bunu aydın ayırmalıdır:

```text
realtime_websocket
browser_web_push
```

Broadcast channel private olmalı, authorization istifadəçi UID-si üzrə yoxlanmalıdır. Event payload minimal olmalı, client detalları API-dən götürməlidir.

### SMS

- provider adapter arxasında olmalıdır;
- telefon E.164 formatına normalize edilməlidir;
- mesaj uzunluğu, transliterasiya və segment sayı əvvəlcədən hesablanmalıdır;
- OTP və bildiriş SMS-ləri ayrıca rate limit və log type almalıdır;
- provider response ID, xərc/segment və status callback saxlanmalıdır;
- tam telefon Gopanel listində maskalanmalıdır.

### Telegram və WhatsApp

Config/UI-da yalnız faktiki driver və credentials mövcud olan kanal göstərilməlidir. Boş adapteri aktiv kanal kimi təqdim etmək olmaz. WhatsApp üçün approved template ID, locale, variable contract və opt-in saxlanmalıdır.

## Alıcıların həll edilməsi

Alıcı seçimi channel driver-in işi deyil. `RecipientResolver` aşağıdakı strategiyaları dəstəkləyə bilər:

```text
resource_owner
company_owner
selected_users
role_members
all_active_employees
currently_at_work
not_at_work
all_tenant_users
```

Resolver nəticəsi təkrarsız user ID siyahısı qaytarmalıdır. Tenant sərhədi hər query-də tətbiq edilməlidir. Deaktiv, silinmiş, bloklanmış və consent-i olmayan alıcılar preference resolver tərəfindən `skipped` səbəbi ilə qeyd edilməlidir.

Mövcud attendance nümunəsində hadisə tipi (`checkin`, `checkout`, `late`, `early`, `overtime`, `coordinate`) şirkət ayarındakı `send_to` və `send_to_list` vasitəsilə owner və ya seçilmiş istifadəçilərə yönəlir. Bu yanaşma generic strategy enum-a çevrilməlidir.

## Kanal və istifadəçi ayarları

Effektiv kanal seçimi aşağıdakı ardıcıllıqla hesablanmalıdır:

```text
system channel availability
  ∩ event allowed channels
  ∩ tenant/company preference
  ∩ recipient preference/consent
  ∩ recipient endpoint availability
  ∩ quiet-hours/urgency policy
```

`database` kanalı məhsul qərarına görə həmişə əlavə edilə bilər, amma bu davranış gizli olmamalı, policy-də açıq yazılmalıdır. Məsələn, mövcud kampaniya kodunda mobil push seçildikdə database avtomatik əlavə olunur; yeni sistem bunu `CampaignNotificationPolicy` daxilində etməlidir.

Default ayarlar qeydiyyat platformasına görə yaradıla bilər:

- mobil qeydiyyat: mobile push aktiv, mail optional;
- web qeydiyyat: mail və ya in-app aktiv;
- gizli/implement edilməmiş kanallar deaktiv.

Default yaratma idempotent `upsert` olmalıdır və `(tenant_id, event_key)` unique constraint ilə qorunmalıdır.

## Şablon sistemi

### Şablon açarı

Key stabil və domen yönümlü olmalıdır:

```text
attendance.checkin.owner
leave.approved.employee
security.device_changed
subscription.expires_soon
campaign.custom
```

Key dəyişmək mövcud kod çağırışlarını poza bilər. System template key-ləri silinməməli; deactivate və version edilməlidir.

### Placeholder qaydası

Yalnız allowlist dəyişənlər istifadə edilməlidir:

```text
{{ user.full_name }}
{{ company.name }}
{{ attendance.operation_at }}
{{ action.url }}
```

Template renderer arbitrary model attribute və method çağırmağa icazə verməməlidir. Context DTO əvvəlcədən təhlükəsiz associative array yaratmalıdır. Naməlum placeholder save zamanı validation xətası verməlidir; səssiz boş string-ə çevrilməməlidir.

```php
$context = [
    'user' => ['full_name' => $user->full_name],
    'company' => ['name' => $company->name],
    'attendance' => ['operation_at' => $attendance->operation_at->toIso8601String()],
];
```

### Render qaydaları

- locale əvvəl recipient, sonra tenant, sonra application fallback ilə seçilməlidir;
- title, subject, text body və HTML body kanal üzrə ayrı render edilə bilər;
- HTML escape default olmalıdır;
- yalnız xüsusi rich-text sahəsi sanitize edilərək raw render edilə bilər;
- render nəticəsində həll olunmamış placeholder qalarsa göndəriş dayandırılmalıdır;
- provider limitləri render-dən sonra yoxlanmalıdır;
- istifadə edilən template ID və version delivery auditində saxlanmalıdır.

### Gopanel template idarəetməsi

Form aşağıdakı sahələri verməlidir:

```text
key (system template-də readonly)
name/title
channel
locale
subject/title/body
format
allowed variables paneli
status
test recipient
preview context
```

Save MÜTLƏQ Form Request ilə validate edilməlidir:

- key formatı və unique scope;
- channel enum;
- aktiv locale;
- body ölçüsü;
- yalnız icazəli placeholder-lar;
- HTML sanitization;
- system/locked template üçün permission.

Test göndərişi production alıcısına təsadüfən getməməli, `test=true` metadata ilə audit edilməli və test context-i göstərilməlidir.

## Domen bildiriş service-i necə yazılmalıdır

Hər domen hadisəsi üçün nazik policy/orchestrator yaradılmalıdır. O:

- hadisə tipini müəyyən edir;
- alıcı strategiyasını seçir;
- preference-ləri tətbiq edir;
- təhlükəsiz template context yaradır;
- action/deep link hazırlayır;
- idempotency key yaradır;
- dispatcher-i çağırır.

```php
final class AttendanceNotification
{
    public function __construct(
        private NotificationDispatcher $dispatcher,
        private PreferenceResolver $preferences,
    ) {}

    public function checkInRecorded(Attendance $attendance): void
    {
        $recipients = $this->preferences->recipientsFor(
            tenantId: $attendance->company_id,
            eventKey: 'attendance.checkin',
        );

        foreach ($recipients as $recipient) {
            $this->dispatcher->dispatch(new NotificationMessage(
                type: 'attendance.checkin',
                title: 'attendance.checkin.owner',
                body: 'attendance.checkin.owner',
                channels: $recipient->channels,
                context: AttendanceContext::from($attendance)->toArray(),
                actionUrl: route('attendance.show', $attendance->uid),
                idempotencyKey: "attendance.checkin:{$attendance->id}:{$recipient->id}",
                subject: MorphReference::fromModel($attendance),
            ));
        }
    }
}
```

Başlıq və mətn service constant-larında saxlanıla bilər, lakin Gopanel-dən idarə olunması tələb edilirsə template key istifadə edilməlidir. Eyni hadisənin mətnini həm constant, həm DB template-də paralel saxlamaq olmaz.

## Queue, retry və idempotency

Hər kanal ayrıca queue ala bilər:

```php
'channel_queues' => [
    'database'    => 'notifications-database',
    'mail'        => 'notifications-mail',
    'mobile_push' => 'notifications-push',
    'sms'         => 'notifications-sms',
],
```

Job qaydaları:

- yalnız delivery ID serialize edilməlidir, böyük User model/payload deyil;
- job DB-dən fresh delivery və recipient oxumalıdır;
- artıq terminal statusdadırsa idempotent çıxmalıdır;
- `tries`, `timeout`, exponential backoff və jitter kanal üzrə müəyyən edilməlidir;
- `failed()` metodu delivery-ni failed etməlidir;
- retryable və permanent provider xətaları ayrılmalıdır;
- transaction-dan `afterCommit` dispatch edilməlidir;
- queue worker sayı provider rate limitinə uyğun olmalıdır;
- Horizon/Supervisor/systemd ilə daimi worker işlədilməlidir;
- `--stop-when-empty` production daemon strategiyası kimi istifadə edilməməlidir.

10 saniyəlik cache lock yalnız yaxın duplicate-ləri azaldır. Əsas qoruma DB unique idempotency key və terminal delivery statusudur.

## Scheduler qaydaları

Server cron-u hər dəqiqə `php artisan schedule:run` çağırmalıdır. Laravel schedule:

- due notification dispatcher-i hər dəqiqə;
- köhnə delivery/scheduled audit cleanup-u retention siyasətinə görə;
- reminder generator-ları idempotent şəkildə;
- kampaniya status reconciliation-u lazım olduqda;
- webhook reconciliation/backfill lazım olduqda işlədə bilər.

`onOneServer()` shared cache tələb edir. `withoutOverlapping()` lock müddəti job-un maksimum müddətinə uyğun seçilməlidir. Timezone application, tenant və istifadəçi səviyyəsində açıq müəyyən edilməlidir; DB tarixləri UTC saxlamalıdır.

## Gopanel-də bildirişlər necə idarə və izlənməlidir

Gopanel bir neçə ayrı ekran verməlidir. Onları tək “Bildirişlər” cədvəlinə qarışdırmaq olmaz.

### 1. Bildiriş inbox qeydləri

Sütunlar:

```text
UID, alıcı, şirkət, tip, başlıq, səviyyə,
oxunma statusu, arxiv statusu, yaradılma tarixi
```

Filtrlər:

```text
şirkət, alıcı, tip, level, read/unread, tarix aralığı
```

Detail modal/səhifə title, body, metadata, notifiable link, action URL və delivery-lərin xülasəsini göstərməlidir.

### 2. Delivery monitor

Bu ekran mail/push/SMS real nəticəsini göstərir:

```text
notification UID
recipient
channel/provider
masked destination
status
attempts
provider message ID
queued/sent/delivered/failed timestamps
last error code/message
```

Filtrlər:

```text
channel, provider, status, campaign, event type,
tenant/company, date range, error code
```

Əməliyyatlar:

- detail/audit baxışı;
- yalnız retryable failed delivery üçün retry;
- pending/queued delivery-ni cancel;
- payload-a maskalanmış baxış;
- CSV export (permission və audit ilə).

Admin “retry” etdikdə yeni cəhd tarixi yazılmalı, köhnə error silinməməli, attempt history ayrıca saxlanmalıdır.

### 3. Planlaşdırılmış bildirişlər

Mövcud layihədə olduğu kimi ən azı bunlar görünməlidir:

```text
alıcı, şirkət, tip, başlıq, kanallar,
scheduled_at, status, attempts, error, notes, created_at
```

Status kartları `pending`, `processing`, `sent/completed`, `failed`, `cancelled` saylarını göstərməlidir. Şirkət, status, tip və tarix filtrləri olmalıdır.

Yalnız pending qeyd ləğv edilməlidir. `processing` ləğvi cooperative cancellation tələb edir; sadəcə statusu dəyişmək artıq çalışan job-u dayandırmaya bilər. Toplu silmə audit və kampaniya əlaqəsini pozmamalıdır. Audit tələb olunan sistemdə hard delete əvəzinə retention/soft delete seçilməlidir.

### 4. Kampaniyalar

Gopanel kampaniyası üçün:

```text
title, message/template, channels
all companies / selected companies
owners / all employees / selected employees
send now / scheduled_at
recipient preview/count
status and progress
```

Kampaniya göndərildikdən sonra məzmun və hədəf snapshot-u dəyişdirilməməlidir. Draft dəyişə bilər. Scheduled kampaniya cancel ediləndə yalnız pending delivery-lər cancel edilməli, artıq sent delivery-lər tarixçədə qalmalıdır.

Progress real delivery aggregate-dən hesablanmalıdır:

```text
total recipients
total deliveries
pending/queued/sending
sent/delivered
failed/skipped/cancelled
success rate
```

Bir alıcıya üç kanal seçilibsə recipient count ilə delivery count eyni deyil.

### 5. Şablonlar

Template listində key, channel, locale, version, status, locked/system, updated_by və updated_at görünməlidir. Edit, preview, clone, deactivate, version history və test send permission-lə qorunmalıdır.

### 6. Kanal ayarları və sağlamlıq

Gopanel secret-in özünü göstərməməlidir. Yalnız:

```text
configured/not configured
provider name
from/sender identity
last successful send
last failure
queue backlog
worker heartbeat
webhook health
```

Mail branding və transport credentials ayrı ekran/məsuliyyət olmalıdır.

### Permission nümunələri

```text
gopanel.notifications.view
gopanel.notifications.delete
gopanel.deliveries.view
gopanel.deliveries.retry
gopanel.scheduled.view
gopanel.scheduled.cancel
gopanel.campaigns.create
gopanel.campaigns.send
gopanel.campaigns.cancel
gopanel.templates.view
gopanel.templates.edit
gopanel.templates.test
gopanel.channels.manage
```

Send, retry, cancel, template edit və export əməliyyatları admin activity log-a yazılmalıdır.

## İstifadəçi paneli və mobil API

İstifadəçi inbox endpoint-ləri:

```text
GET    /api/v1/notifications
GET    /api/v1/notifications/unread-count
GET    /api/v1/notifications/{notification:uid}
PUT    /api/v1/notifications/{notification:uid}/read
PUT    /api/v1/notifications/read-all
PUT    /api/v1/notifications/{notification:uid}/archive
DELETE /api/v1/notifications/{notification:uid}
POST   /api/v1/notification-endpoints
DELETE /api/v1/notification-endpoints/{endpoint:uid}
```

List response minimal olmalıdır. Detail response `type`, display data və client deep-link contract-ı verməlidir. Daxili morph class adı public API-yə çıxarılmamalıdır.

```json
{
  "uid": "01...",
  "type": "attendance.checkin",
  "title": "Giriş qeydə alındı",
  "body": "Əli Məmmədov giriş etdi",
  "level": "low",
  "is_read": false,
  "action": {
    "type": "attendance_detail",
    "params": {"attendance_uid": "..."}
  },
  "created_at": "2026-07-11T10:00:00Z"
}
```

## Log, audit və monitorinq

Application log debugging üçündür; delivery cədvəli biznes auditidir. Biri digərini əvəz etmir.

Structured log context:

```text
request_id/correlation_id
notification_uid
delivery_uid
campaign_uid
tenant_id
recipient_id
channel
provider
attempt
status
error_code
duration_ms
```

Loglarda tam email, telefon, token, message body və secret olmamalıdır. PII maskalanmalıdır.

Metrics:

```text
notifications_created_total
notification_deliveries_total{channel,status}
notification_delivery_duration_seconds
notification_queue_lag_seconds
notification_retry_total
notification_invalid_endpoint_total
notification_template_render_failures_total
```

Alert nümunələri:

- queue backlog həddi keçib;
- mail/push failure faizi artıb;
- heç bir worker heartbeat vermir;
- scheduled notification gecikməsi SLA-nı keçib;
- provider auth/config xətası yaranıb;
- webhook uzun müddətdir gəlmir.

## Təhlükəsizlik və məxfilik

- bütün Gopanel route-ları auth və granular permission ilə qorunmalıdır;
- tenant data isolation query və policy səviyyəsində tətbiq edilməlidir;
- template HTML sanitize edilməlidir;
- action URL allowlist və ya server route map ilə yaradılmalıdır;
- provider credentials secret manager/.env-də saxlanmalıdır;
- endpoint tokenləri şifrələnməli və logda maskalanmalıdır;
- notification metadata data-minimization prinsipinə uyğun olmalıdır;
- retention müddəti kanal və hüquqi tələbə görə müəyyən edilməlidir;
- admin export və test send audit olunmalıdır;
- rate limit həm public token endpoint-lərinə, həm admin send/retry əməliyyatlarına tətbiq edilməlidir;
- template preview XSS-dən qorunmuş sandbox/iframe daxilində göstərilməlidir.

## Xəta idarəetməsi

Xətalar üç qrupa ayrılmalıdır:

```text
validation/configuration: retry yoxdur
permanent provider/recipient: retry yoxdur, endpoint disable ola bilər
transient provider/network/rate-limit: backoff ilə retry
```

Bir kanalın uğursuzluğu digər kanalın nəticəsini silməməlidir. Aggregate notification `partially_failed` ola bilər. Driver xətanı udmamalıdır; UI “göndərildi” yazmaq üçün real delivery nəticəsini gözləməlidir.

Dead-letter/failed job replay idempotent olmalıdır. Manual retry yalnız permission, audit reason və retryable status ilə mümkündür.

## Test strategiyası

### Unit testlər

- recipient resolver strategiyaları;
- preference/channel intersection;
- placeholder allowlist və render;
- FCM payload string conversion;
- provider error classification;
- idempotency key;
- campaign progress aggregation;
- notification metadata/context builder.

### Feature testlər

- başqa istifadəçinin inbox qeydinə girişin bloklanması;
- read/archive/delete/read-all;
- FCM endpoint register/upsert/deactivate;
- Gopanel permission-ləri;
- template save-də invalid placeholder;
- campaign create/send/cancel;
- scheduled filter/stats/cancel/retry;
- tenant isolation.

### Queue və integration testləri

- `Queue::fake()` ilə düzgün channel job-ları;
- `Mail::fake()` ilə subject/template/context;
- FCM HTTP fake ilə payload və invalid token;
- transaction rollback olduqda job dispatch edilməməsi;
- eyni idempotency key ilə ikinci delivery yaranmaması;
- transient xəta retry, permanent xəta no-retry;
- iki worker eyni scheduled row-u emal etmir;
- kampaniya chunk-ları alıcı itirmir və təkrarlamır.

### Gopanel acceptance testləri

- list/filter/search/pagination;
- delivery detail və masked destination;
- retry/cancel status keçidləri;
- template preview və test send;
- campaign recipient preview və progress;
- queue/provider health görünüşü.

## Addım-addım tətbiq planı

1. Kanal, status, type və level enum-larını müəyyən et.
2. `notifications`, `notification_deliveries`, endpoint, template, preference və campaign sxemlərini yarat.
3. DTO, `ChannelDriver`, `DeliveryResult` və driver registry-ni yaz.
4. Database driver-i və istifadəçi inbox API-sini tamamla.
5. Mail driver, layout, template renderer və Gopanel template idarəetməsini qur.
6. FCM endpoint lifecycle və mobil push driver-i qur.
7. Digər kanalları yalnız real provider inteqrasiyası olduqda əlavə et.
8. Notification dispatcher və idempotent delivery creation yaz.
9. Domain event-ləri `afterCommit` notification policy-lərinə bağla.
10. Scheduled pipeline, atomic claim, retry və cleanup qur.
11. Campaign recipient snapshot, chunking və progress aggregate qur.
12. Gopanel inbox, delivery monitor, scheduled, campaign, templates və health ekranlarını qur.
13. Structured log, metrics, alert və audit əlavə et.
14. Unit, feature, queue, provider və race-condition testlərini yaz.
15. Load test və provider quota yoxlamasından sonra production worker/scheduler konfiqurasiyasını aktiv et.

## Yeni bildiriş tipi əlavə etmə checklist-i

- [ ] Stabil `notification_type/event_key` seçildi.
- [ ] Domen hadisəsi transaction commit-dən sonra yaranır.
- [ ] Recipient resolver və tenant sərhədi müəyyən edildi.
- [ ] Şirkət və istifadəçi preference qaydası müəyyən edildi.
- [ ] Kanal allowlist-i müəyyən edildi.
- [ ] Template key, locale-lər və allowed variables yaradıldı.
- [ ] Təhlükəsiz context DTO yazıldı.
- [ ] Action/deep-link public UID ilə quruldu.
- [ ] Idempotency key müəyyən edildi.
- [ ] Database inbox metadata/presentation yazıldı.
- [ ] Hər kanal üçün delivery status izlənir.
- [ ] Gopanel filter/detail görünüşü tipi tanıyır.
- [ ] Log və metric context əlavə edildi.
- [ ] Unit, feature və queue testləri yazıldı.
- [ ] Retry/permanent error davranışı test edildi.

## Production readiness checklist

- [ ] `schedule:run` cron işləyir.
- [ ] Bütün kanal queue worker-ləri process manager ilə işləyir.
- [ ] Queue `after_commit` və ya job `afterCommit()` aktivdir.
- [ ] Failed jobs və delivery failures monitor olunur.
- [ ] Provider credentials repodan kənardadır.
- [ ] FCM invalid-token cleanup işləyir.
- [ ] Mail bounce/complaint webhook-ları işləyir.
- [ ] Rate limit, retry, exponential backoff və jitter qurulub.
- [ ] DB unique idempotency constraint mövcuddur.
- [ ] Scheduled claim race-condition testindən keçib.
- [ ] Gopanel permissions və audit log tamamdır.
- [ ] Template HTML sanitization və preview XSS qoruması var.
- [ ] PII loglarda maskalanır.
- [ ] Retention və cleanup siyasəti sənədləşdirilib.
- [ ] Queue lag, failure rate və provider auth alert-ləri qurulub.
- [ ] Kampaniya və notification progress real delivery-lərdən hesablanır.

## Qəbul meyarları

Sistem yalnız aşağıdakıların hamısı ödənəndə tamamlanmış sayılır:

- eyni use-case bütün kanalları vahid dispatcher üzərindən işlədə bilir;
- hər alıcı və kanal üçün ayrıca, audit edilə bilən delivery nəticəsi var;
- transaction rollback bildiriş yaratmır;
- duplicate event eyni notification/delivery-ni ikinci dəfə yaratmır;
- istifadəçi yalnız öz inbox-unu görə və idarə edə bilir;
- tenant administratoru yalnız öz tenant məlumatını görür;
- Gopanel mail, push, SMS və database nəticələrini ayrıca statusla izləyə bilir;
- şablonlar version, locale, placeholder validation, preview və test send dəstəkləyir;
- scheduled və campaign göndərişləri cancel, retry və progress izləməsini düzgün aparır;
- provider və queue xətaları görünür, ölçülür və uyğun retry olunur;
- heç bir secret/token və həssas payload loglara sızmır;
- kritik axınlar avtomatlaşdırılmış testlərlə qorunur.

Bu sənəd tətbiq edilərkən konkret layihənin domen adları, provider-ləri, hüquqi consent tələbləri, tenant modeli və client deep-link müqaviləsi ayrıca dəqiqləşdirilməlidir. Arxitekturanın əsas prinsipi dəyişməməlidir: **domen niyyəti, şablon render-i, kanal çatdırılması və inzibati izləmə ayrı məsuliyyətlərdir; hər real göndəriş kanal səviyyəsində audit edilməlidir.**
