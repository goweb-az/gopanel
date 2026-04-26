<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;

class RunMockSeeders extends Command
{
    protected $signature = 'mock:seed {--list : Yalniz mock seeder siyahisini goster}';

    protected $description = 'database/seeders/mock altindaki mock seeder-leri interaktiv menyu ile isledir';

    public function handle(): int
    {
        $seeders = $this->discoverSeeders();

        if (empty($seeders)) {
            $this->warn('database/seeders/mock altinda mock seeder tapilmadi.');
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

        foreach ($selected as $seeder) {
            $this->newLine();
            $this->info('Seed edilir: ' . $seeder['name']);
            $this->call('db:seed', [
                '--class' => $seeder['class'],
            ]);
        }

        $this->newLine();
        $this->info('Mock seed prosesi tamamlandi.');

        return self::SUCCESS;
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

        usort($seeders, fn ($a, $b) => strcasecmp($a['name'], $b['name']));

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

    private function resolveSelection(string $choice, array $seeders): array
    {
        if ($choice === '0') {
            return $seeders;
        }

        $indexes = collect(explode(',', $choice))
            ->map(fn ($item) => (int) trim($item))
            ->filter(fn ($item) => $item > 0 && $item <= count($seeders))
            ->unique()
            ->values();

        return $indexes
            ->map(fn ($index) => $seeders[$index - 1])
            ->all();
    }
}
