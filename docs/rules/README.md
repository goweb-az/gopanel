# docs/rules — modul qurulma qaydaları

Bu qovluq **böyük modulların necə qurulacağını** təsvir edir: bildirişlər,
dashboard, istifadəçi idarəetməsi, kateqoriya ağacı, status/tip lüğətləri, API.

## Bu qovluq ilə `.claude/rules/` arasındakı fərq

| | `.claude/rules/` | `docs/rules/` (bu qovluq) |
|---|---|---|
| Nə | **Hər dəfə** riayət olunan qısa qaydalar | **Bir modul** qurulanda oxunan uzun spesifikasiya |
| Ölçü | 100-200 sətir | 200-1700 sətir |
| Nə vaxt | Hər kod dəyişikliyindən əvvəl | Yalnız həmin modul üzərində işləyəndə |
| Nümunə | «Blade-də `@php` yazılmır» | «Bildiriş bölməsi 13 addımda belə qurulur» |

**Ziddiyyət olarsa:** istifadəçinin cari mesajı → `.claude/rules/` →
`docs/rules/` → ümumi vərdişlər.

## Sənədlər

### İcra qaydaları (addım-addım, bu layihəyə uyğunlaşdırılmış)

| Sənəd | Nə vaxt oxunur |
|---|---|
| [gopanel-notifications-module.md](gopanel-notifications-module.md) | **Admin bildiriş bölməsi** qurulanda. Migration → enum → model → gate → kanallar → job → query → controller → route → icazə → blade → JS. |
| [domains-cdn-gopanel.md](domains-cdn-gopanel.md) | Layihə canlıya çıxanda: domen planı, CDN, nginx, `CDN_URL` ↔ `filesystems.public.url` uzlaşması. |
| [gopanel-status-and-type-cruds.md](gopanel-status-and-type-cruds.md) | Status/tip lüğəti lazım olanda (əvvəlcə: Enum kifayət edirmi?). |
| [gopanel-category-crud-architecture.md](gopanel-category-crud-architecture.md) | Parent/child ağac strukturlu CRUD lazım olanda. |
| [gopanel-dashboard-architecture.md](gopanel-dashboard-architecture.md) | Dashboard/widget bölməsi qurulanda (AJAX widget-lər, dövr müqayisəsi). |
| [gopanel-user-management-architecture.md](gopanel-user-management-architecture.md) | Son istifadəçi (`users`) idarəetmə bölməsi qurulanda. |

### Arxitektura və arxa plan (qərar verərkən)

| Sənəd | Nə üçün |
|---|---|
| [application-layering-support-query-traits-helpers-enums.md](application-layering-support-query-traits-helpers-enums.md) | «Bu sinif hara yazılmalıdır?» — Support / Query / Service / Trait / Helper / Enum / DTO sərhədləri. |
| [notification-system-architecture-and-implementation-guide.md](notification-system-architecture-and-implementation-guide.md) | **İstifadəçi** (sayt/mobil) bildirişləri — admin bildirişlərindən AYRI sistem. |
| [api-architecture-and-implementation-guide.md](api-architecture-and-implementation-guide.md) | Tam API qatı açılanda: route, resource, response contract, versiyalama. |
| [gopanel-admin-notifications-architecture.md](gopanel-admin-notifications-architecture.md) | Admin bildirişləri — geniş arxitektura analizi (EN). |
| [gopanel-admin-notifications-architecture-2.md](gopanel-admin-notifications-architecture-2.md) | Admin bildirişləri — geniş arxitektura analizi (AZ). |

> Sonuncu iki sənəd **arxa plandır**. Bildiriş bölməsi qurulanda əvvəlcə
> [gopanel-notifications-module.md](gopanel-notifications-module.md) oxunur.

## Sənəd yazma qaydası

Bura yeni sənəd əlavə edəndə:

1. **Model adları nümunədir** — sənədin başında bunu açıq yaz. Hədəf layihədə
   `Listing`/`Tender` olmaya bilər.
2. **Hər qərarın «niyə»-si yazılır.** Səbəbi olmayan qayda bir müddət sonra
   pozulur, çünki heç kim onu müdafiə edə bilmir.
3. **Real problemlərdən çıxan qeydlər silinmir** (məs. «`delete-all` route-u
   `{id}`-dən əvvəl yazılır», «queue-da `can()` işləmir»). Bunlar sənədin ən
   dəyərli hissəsidir.
4. Layihədə **artıq mövcud** olan həll varsa, sənədin başında ona istinad ver —
   yenidən yazılmasın (bax: [../shared-layer.md](../shared-layer.md)).
5. Bu fayldakı cədvələ bir sətir əlavə et.
