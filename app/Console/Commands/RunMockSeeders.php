<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

// Istifade:
//   php artisan mock:seed              — interaktiv menyu; nomer yazilir, 0=hamisi, vergul ile bir nece (1,3)
//   php artisan mock:seed --list       — yalniz siyahi gosterir, hec ne seed etmir
//   php artisan mock:seed --all-seed   — menyu ve sual olmadan birbasa butun mock seederlari isledir
//   Qorunan: production mühiti ve aquastores.net domeni bloklanib

class RunMockSeeders extends Command
{
    protected $signature = 'mock:seed {--list : Yalniz mock seeder siyahisini goster} {--all-seed : Menyu gostermeden birbasa butun mock seederlari isledir}';

    protected $description = 'database/seeders/mock altindaki mock seeder-leri interaktiv menyu ile isledir';

    private int $_cmdTotal = 0;
    private int $_cmdDone  = 0;

    public function handle(): int
    {
        if ($this->isForbiddenEnvironment()) {
            $this->error('Bu command productionda ve aquastores.net domeninde isledile bilmez.');
            return self::FAILURE;
        }

        $seeders = $this->discoverSeeders();

        if (empty($seeders)) {
            $this->warn('database/seeders/mock altinda mock seeder tapilmadi.');
            return self::SUCCESS;
        }

        // --all-seed: menyu ve sual olmadan birbasa butun seederlari isledir
        if ($this->option('all-seed')) {
            $this->info('--all-seed rejimi: butun mock seederlar islenilir...');
            $this->newLine();
            $this->cmdProgressStart(count($seeders));

            foreach ($seeders as $seeder) {
                $this->newLine();
                $this->line('  Seed edilir: ' . $seeder['name']);
                $this->call('db:seed', ['--class' => $seeder['class']]);
                $this->cmdProgressTick($seeder['name']);
            }

            $this->newLine();
            $this->cmdProgressFinish('Butun mock seederlari tamamlandi.');

            return self::SUCCESS;
        }

        $this->showMenu($seeders);

        if ($this->option('list')) {
            return self::SUCCESS;
        }

        $choice = trim((string) $this->ask('Hansini seed etmek isteyirsiniz?', '0'));

        if ($choice === (string) (count($seeders) + 1)) {
            $this->info('Hech biri secilmedi. Cixilir.');
            return self::SUCCESS;
        }

        $selected = $this->resolveSelection($choice, $seeders);

        if (empty($selected)) {
            $this->error('Secim duzgun deyil.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->cmdProgressStart(count($selected));

        foreach ($selected as $seeder) {
            $this->newLine();
            $this->line('  Seed edilir: ' . $seeder['name']);
            $this->call('db:seed', [
                '--class' => $seeder['class'],
            ]);
            $this->cmdProgressTick($seeder['name']);
        }

        $this->newLine();
        $this->cmdProgressFinish('Butun mock seederlari tamamlandi.');

        return self::SUCCESS;
    }

    private function isForbiddenEnvironment(): bool
    {
        return false;
        $host = parse_url((string) config('app.url'), PHP_URL_HOST);

        return app()->environment('production')
            || in_array($host, ['sizindomen.com', 'www.sizindomen.com', 'basqadomen.net', 'www.basqadomen.net'], true);
    }

    private function discoverSeeders(): array
    {
        $path = database_path('seeders/mock');

        if (!File::isDirectory($path)) {
            return [];
        }

        $seeders = [];

        foreach (File::files($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $class = 'Database\\Seeders\\mock\\' . $file->getFilenameWithoutExtension();

            if (!class_exists($class)) {
                continue;
            }

            $seeders[] = [
                'class' => $class,
                'name'  => $this->displayName($class),
            ];
        }

        usort($seeders, fn($a, $b) => strcasecmp($a['name'], $b['name']));

        return $seeders;
    }

    private function displayName(string $class): string
    {
        $reflection = new ReflectionClass($class);
        $instance = $reflection->newInstance();

        if ($reflection->hasProperty('mockName')) {
            $property = $reflection->getProperty('mockName');
            if ($property->isPublic()) {
                $value = $property->getValue($instance);
                if (is_string($value) && trim($value) !== '') {
                    return $value;
                }
            }
        }

        return $reflection->getShortName();
    }

    private function showMenu(array $seeders): void
    {
        $this->info('Mock seeder siyahisi:');
        $this->line('0 Hamisi');

        foreach ($seeders as $index => $seeder) {
            $this->line(($index + 1) . ' ' . $seeder['name']);
        }

        $this->line((count($seeders) + 1) . ' Hecbiri / cix');
        $this->line('Bir nece seeder ucun vergulle yazmaq olar: 1,3');
    }

    private function cmdProgressStart(int $total): void
    {
        $this->_cmdTotal = $total;
        $this->_cmdDone  = 0;
        $bar = str_repeat('░', 20);
        $this->line("  [{$bar}]   0%  ({$total} seeder)");
    }

    private function cmdProgressTick(string $label): void
    {
        $this->_cmdDone++;
        $pct    = $this->_cmdTotal > 0
            ? (int) round($this->_cmdDone / $this->_cmdTotal * 100)
            : 100;
        $filled = (int) round($pct / 5);
        $bar    = str_repeat('█', $filled) . str_repeat('░', 20 - $filled);
        $this->line("  [{$bar}] {$pct}%  {$label}");
    }

    private function cmdProgressFinish(string $summary = ''): void
    {
        $bar  = str_repeat('█', 20);
        $text = $summary ?: "Tamamlandi! {$this->_cmdDone} seeder.";
        $this->info("  [{$bar}] 100% — {$text}");
    }

    private function resolveSelection(string $choice, array $seeders): array
    {
        if ($choice === '0') {
            return $seeders;
        }

        $indexes = collect(explode(',', $choice))
            ->map(fn($item) => (int) trim($item))
            ->filter(fn($item) => $item > 0 && $item <= count($seeders))
            ->unique()
            ->values();

        return $indexes
            ->map(fn($index) => $seeders[$index - 1])
            ->all();
    }
}
