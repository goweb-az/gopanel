# İş qaydaları (.claude/rules)

Bu qovluq layihədə kod yazarkən **hər dəfə** riayət olunmalı qaydaları saxlayır.
Qaydalar bir dəfə yazılıb - söhbətdə təkrar xatırlatmağa ehtiyac yoxdur.

| Fayl | Nə üçün |
|---|---|
| [01-umumi.md](01-umumi.md) | Bütün layerlərə aid ümumi qaydalar (layerlər, blade, permission, test, geriyə uyğunluq) |
| [02-gopanel.md](02-gopanel.md) | GoPanel (`gopanel.*`) - admin panel qaydaları |
| [03-site.md](03-site.md) | Sayt tərəfi - dinamik route, SEO, tərcümə |
| [04-api.md](04-api.md) | API - cavab formatı, resource, autentifikasiya |

**Ziddiyyət olarsa sıralama:**
istifadəçinin cari mesajı → bu qaydalar → `.claude/CLAUDE.md` → ümumi vərdişlər.

## Böyük modul qurulanda

Buradakı qaydalar qısadır və **hər dəyişikliyə** aiddir. Konkret bir böyük
modul (bildirişlər, dashboard, istifadəçi idarəetməsi, kateqoriya ağacı, API,
deployment) qurulanda əlavə olaraq [`docs/rules/`](../../docs/rules/README.md)
altındakı addım-addım spesifikasiya oxunur.

## Qaydaya yeni sətir əlavə etmək

Qayda yalnız o zaman yazılır ki, o, **təkrarlanan** bir qərardır. Bir dəfəlik
seçim qayda deyil - onu kodun docblock-una yaz.

Hər qayda üç şeyi cavablandırmalıdır: **nə**, **harada**, **niyə**.
"Niyə" olmayan qayda bir müddət sonra pozulur, çünki heç kim səbəbini bilmir.
