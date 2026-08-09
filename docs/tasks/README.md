# docs/tasks — tamamlanmış tapşırıqların arxivi

Bu qovluqdakı sənədlər **artıq icra olunmuş** implementasiya tapşırıqlarıdır.
Hər faylın başında nə vaxt və nə edildiyini göstərən banner var:

```markdown
> ✅ **DONE — 2026-07-11.** Implemented in this codebase: ...
```

## Nə üçün saxlanılır

Tapşırıq mətni silinmir, çünki:

- kodun **niyə belə yazıldığını** izah edir (commit mesajından qat-qat geniş);
- eyni modul başqa layihəyə köçürüləndə hazır spesifikasiya rolunu oynayır;
- banner-dəki «nə dəyişdirildi» qeydi tətbiqin orijinal tapşırıqdan harada
  ayrıldığını göstərir — bu, sonrakı refaktorda ən lazımlı məlumatdır.

## Sənədlər

| Sənəd | Nə edildi |
|---|---|
| [gopanel-language-and-menu-management-architecture.md](gopanel-language-and-menu-management-architecture.md) | Dillər üçün drag-and-drop sıralama (`languages.sort` + `LanguageSortService`), menyular üçün real drag-and-drop ağac (`MenuTreeService`, cross-parent move, cycle yoxlaması), `NavigationCacheService` ilə hədəfli keş invalidasiyası. |
| [gopanel-translation-management-architecture.md](gopanel-translation-management-architecture.md) | Səhifə əsaslı tərcümə təşkili (`TranslationPageRegistry`), JSON/XLSX toplu idxal, deterministik JSON ixrac, `import`/`export` icazələri, keş invalidasiyası. |

## Bura fayl əlavə etmək

`docs/rules/` altındakı spesifikasiya tam icra olunanda:

1. faylı bura köçür;
2. başına `> ✅ **DONE — <tarix>.**` banneri əlavə et və **nəyin real olaraq
   yazıldığını** (tapşırıqdan fərqləri ilə) qısa yaz;
3. yuxarıdakı cədvələ bir sətir əlavə et;
4. [../rules/README.md](../rules/README.md) siyahısından həmin sətri sil.
