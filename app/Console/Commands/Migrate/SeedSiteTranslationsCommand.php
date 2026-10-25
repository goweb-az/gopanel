<?php

declare(strict_types=1);

namespace App\Console\Commands\Migrate;

use App\Helpers\Gopanel\TranslationPageRegistry;
use App\Models\Geography\Language;
use App\Services\Gopanel\Translations\TranslationWriteService;
use App\Support\Translation\UiTranslationRow;
use Illuminate\Console\Command;

/**
 * Saytın UI mətnlərini (header, düymələr, başlıqlar, forma etiketləri) JSON-dan
 * tərcümə sisteminə yazır: `translations` cədvəli → resources/lang/<dil>/website.php.
 *
 * Mənbə: database/data/translations/website.json - hər sətir
 * `{page, group, key, values: {az, en, ru}}`. Blade-də açar
 * `@lang('website.<page>.<group>.<key>')` kimi çağırılır; `group` qısa mətn
 * üçün `title`, uzun mətn üçün `content`-dir. Qayda: docs/translations.md.
 *
 * NİYƏ seeder yox, command: frontend implement olunanda şablondakı sözlər bu
 * JSON-a yığılır; production-a çıxanda və sonra yeni söz əlavə olunanda eyni
 * fayl təkrar işlədilir. `db:seed` isə production-da işlədilmir.
 *
 * İdempotentdir - yalnız ÇATIŞMAYAN sətirləri yazır, adminin paneldə
 * dəyişdirdiyi mətn əzilmir (`--force` ilə əzilir). Paneldə silinmiş sətir
 * yenidən bərpa olunur.
 */
final class SeedSiteTranslationsCommand extends Command
{
    protected $signature = 'translations:seed-site
        {--file= : JSON faylı (default: database/data/translations/website.json)}
        {--platform=website : Sətirdə platform yoxdursa işlədilən platforma}
        {--force : Mövcud dəyərləri də JSON-dakı ilə əvəz et}
        {--dry-run : Bazaya yazmadan nə ediləcəyini göstər}';

    protected $description = 'Saytın UI tərcümələrini JSON faylından translations cədvəlinə yazır (yalnız çatışmayanlar)';

    public function handle(TranslationWriteService $writer, TranslationPageRegistry $pages): int
    {
        $path = (string) ($this->option('file') ?: database_path('data/translations/website.json'));

        if (!is_file($path)) {
            $this->error("Fayl tapılmadı: {$path}");

            return self::FAILURE;
        }

        $items = json_decode((string) file_get_contents($path), true);

        if (!is_array($items) || !array_is_list($items)) {
            $this->error("JSON massiv olmalıdır ([{page, group, key, values}]): {$path}");

            return self::FAILURE;
        }

        $languages = Language::query()->where('is_active', true)->orderByDesc('default')->orderBy('sort_order')->pluck('code')->all();

        if ($languages === []) {
            $this->error('Aktiv dil yoxdur - əvvəlcə languages cədvəlini doldurun.');

            return self::FAILURE;
        }

        $defaultLocale = $languages[0];
        $rows          = [];
        $invalid       = 0;

        $this->info(sprintf('%d açar oxunur (%s)...', count($items), implode(', ', $languages)));
        $bar = $this->output->createProgressBar(count($items));
        $bar->start();

        foreach ($items as $index => $item) {
            $bar->advance();
            $row    = UiTranslationRow::normalize(['platform' => $item['platform'] ?? $this->option('platform')] + (array) $item);
            $errors = UiTranslationRow::errors($row, $pages);

            if (!is_array($item['values'] ?? null)) {
                $errors[] = 'values obyekti yoxdur';
            }

            if ($errors !== []) {
                $invalid++;
                $this->newLine();
                $this->warn(sprintf('#%d %s: %s', $index + 1, UiTranslationRow::langKey($row), implode('; ', $errors)));
                continue;
            }

            foreach ($languages as $locale) {
                // Dil üçün mətn yoxdursa standart dilin mətni yazılır - sayt boş açar göstərməsin
                $value = $item['values'][$locale] ?? $item['values'][$defaultLocale] ?? null;

                if ($value === null || $value === '') {
                    continue;
                }

                $rows[] = $row + ['locale' => $locale, 'value' => (string) $value];
            }
        }

        $bar->finish();
        $this->newLine();

        $dryRun = (bool) $this->option('dry-run');
        $stats  = $writer->write($rows, (bool) $this->option('force'), $dryRun);

        $this->info(($dryRun ? '[dry-run] ' : '') . sprintf(
            'Açar: %d | yeni sətir: %d | yenilənən: %d | toxunulmayan: %d | səhv: %d',
            count($items),
            $stats['created'],
            $stats['updated'],
            $stats['skipped'],
            $invalid,
        ));

        return $invalid > 0 ? self::FAILURE : self::SUCCESS;
    }
}
