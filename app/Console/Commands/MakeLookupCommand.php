<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Yeni «Status və Tiplər» (lookup) tipinin skeletini yaradır.
 *
 * NİYƏ generator: lookup tipi 6-7 fayldan ibarətdir (model, miqrasiya,
 * controller, request, iki blade, reyestr sətri) və onların hamısı eyni
 * şablondur. Əl ilə köçürəndə ad/namespace/cədvəl adı bir yerdə səhv qalırdı.
 * Route, icazə və menyu ayrıca yazılmır - reyestr sətri (`config/gopanel/
 * lookups.php`) onları avtomatik verir.
 *
 *   php artisan gopanel:make-lookup OrderStatus --title="Sifariş statusları" \
 *       --fields=color,class_name --booleans=is_default,is_public,is_active
 *
 * İdempotentdir: mövcud fayl üzərinə yazılmır (`--force` olmadan), reyestr
 * sətri təkrar əlavə olunmur. `--dry-run` heç nə yazmadan planı göstərir.
 */
class MakeLookupCommand extends Command
{
    protected $signature = 'gopanel:make-lookup
        {name : Model adı, StudlyCase (məs. OrderStatus)}
        {--title= : Paneldə görünən ad (məs. "Sifariş statusları")}
        {--fields= : Əlavə sahələr: color,class_name,icon}
        {--booleans=is_active : Bayraqlar: is_default,is_public,is_active}
        {--translated=name,description : Tərcümə olunan sahələr: name,description}
        {--icon=bx bx-list-ul : Bölmədaxili sidebar ikonu}
        {--force : Mövcud faylların üzərinə yaz}
        {--dry-run : Heç nə yazmadan nə ediləcəyini göstər}
        {--base= : Hədəf kök qovluq (yalnız testlər üçün; standart base_path())}';

    protected $description = 'Status/tip (lookup) cədvəli üçün model, miqrasiya, controller, request, blade və reyestr sətri yaradır';

    /** Ortaq forma/siyahının tanıdığı əlavə sahələr və sütun tərifləri. */
    private const FIELDS = [
        'color'      => "\$table->string('color', 30)->nullable();",
        'class_name' => "\$table->string('class_name', 100)->nullable();",
        'icon'       => "\$table->string('icon', 100)->nullable();",
    ];

    private const TRANSLATABLE = ['name', 'description'];

    private const CONFIG_MARKER = '// gopanel:make-lookup:types';

    public function __construct(private readonly Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly((string) $this->argument('name'));

        if (!preg_match('/^[A-Z][A-Za-z0-9]*$/', $name)) {
            $this->error('Model adı StudlyCase olmalıdır, məs. OrderStatus.');

            return self::FAILURE;
        }

        $fields = $this->listOption('fields');
        $booleans = $this->listOption('booleans');
        $translated = $this->listOption('translated');

        if (($unknown = array_diff($fields, array_keys(self::FIELDS))) !== []) {
            $this->error('Dəstəklənməyən sahə: ' . implode(', ', $unknown) . '. Mümkün: ' . implode(', ', array_keys(self::FIELDS)) . '.');
            $this->line('Başqa sütun lazımdırsa: generatoru işlət, sonra miqrasiya/forma/request-ə əl ilə əlavə et.');

            return self::FAILURE;
        }

        if (($badBooleans = array_filter($booleans, fn ($b) => !preg_match('/^is_[a-z][a-z0-9_]*$/', $b))) !== []) {
            $this->error('Bayraq adı `is_` ilə başlamalıdır: ' . implode(', ', $badBooleans) . '.');

            return self::FAILURE;
        }

        if (($badTranslated = array_diff($translated, self::TRANSLATABLE)) !== []) {
            $this->error('Tərcümə sahəsi yalnız: ' . implode(', ', self::TRANSLATABLE) . '.');

            return self::FAILURE;
        }

        $context = [
            'name'       => $name,
            'type'       => Str::kebab(Str::pluralStudly($name)),
            'table'      => Str::snake(Str::pluralStudly($name)),
            'title'      => (string) ($this->option('title') ?: Str::headline(Str::pluralStudly($name))),
            'icon'       => (string) $this->option('icon'),
            'fields'     => $fields,
            'booleans'   => $booleans,
            'translated' => $translated,
        ];

        $base = rtrim((string) ($this->option('base') ?: base_path()), '\\/');

        $plan = [
            "app/Models/Lookups/{$name}.php"                                      => $this->modelStub($context),
            $this->migrationPath($base, $context['table'])                        => $this->migrationStub($context),
            "app/Http/Controllers/Gopanel/Lookups/{$name}Controller.php"          => $this->controllerStub($context),
            "app/Http/Requests/Gopanel/Lookups/{$name}SaveRequest.php"            => $this->requestStub($context),
            "resources/views/gopanel/pages/lookups/{$context['type']}/index.blade.php" => $this->indexViewStub(),
            "resources/views/gopanel/pages/lookups/{$context['type']}/form.blade.php"  => $this->formViewStub(),
        ];

        $dryRun = (bool) $this->option('dry-run');
        $this->info(($dryRun ? '[dry-run] ' : '') . "Lookup «{$context['title']}» ({$context['type']}, cədvəl: {$context['table']})");

        foreach ($plan as $relative => $contents) {
            $this->writeFile($base . '/' . $relative, $relative, $contents, $dryRun);
        }

        $this->registerType($base, $context, $dryRun);

        $this->newLine();
        $this->line('Növbəti addımlar:');
        $this->line('  1) php artisan migrate');
        $this->line('  2) php artisan db:seed --class=PermissionSeeder   (icazələr reyestrdən gəlir)');
        $this->line('  3) Vəzifələrə gopanel.lookups.' . $context['type'] . '.* icazələrini ver');
        $this->line('  İstifadə olunursa: controller-də $usedBy = [\'cədvəl\' => \'xarici_açar\'] yaz (silmə qadağası).');

        if (!$this->option('title')) {
            $this->warn('  --title verilməyib: config/gopanel/lookups.php-də başlığı Azərbaycan dilinə çevir.');
        }

        return self::SUCCESS;
    }

    /** @return list<string> */
    private function listOption(string $option): array
    {
        $value = (string) ($this->option($option) ?? '');

        return array_values(array_unique(array_filter(array_map('trim', explode(',', $value)))));
    }

    private function writeFile(string $path, string $relative, string $contents, bool $dryRun): void
    {
        $exists = $this->files->exists($path);

        if ($exists && !$this->option('force')) {
            $this->line("  <comment>ötürüldü</comment>  {$relative} (artıq var; --force ilə üzərinə yaz)");

            return;
        }

        if (!$dryRun) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $contents);
        }

        $this->line('  <info>' . ($exists ? 'yeniləndi' : 'yaradıldı') . "</info>  {$relative}");
    }

    /**
     * Eyni cədvəlin miqrasiyası artıq varsa onun yolu qaytarılır - təkrar
     * işlətmədə ikinci `create_*_table` faylı yaranmasın.
     */
    private function migrationPath(string $base, string $table): string
    {
        $existing = glob($base . "/database/migrations/*_create_{$table}_table.php") ?: [];

        if ($existing !== []) {
            return 'database/migrations/' . basename($existing[0]);
        }

        return 'database/migrations/' . date('Y_m_d_His') . "_create_{$table}_table.php";
    }

    /**
     * Reyestrə sətir əlavə edir (marker şərhinin üstünə).
     */
    private function registerType(string $base, array $context, bool $dryRun): void
    {
        $path = $base . '/config/gopanel/lookups.php';
        $relative = 'config/gopanel/lookups.php';

        if (!$this->files->exists($path)) {
            $this->warn("  {$relative} tapılmadı - reyestr sətrini əl ilə əlavə et.");

            return;
        }

        $config = $this->files->get($path);

        // Faylın mətnində yox, icra olunmuş massivində yoxlanılır: şərhdəki
        // nümunə sətir (`'order-statuses' => [...]`) qeydiyyat sayılmasın.
        $registered = (array) ((require $path)['types'] ?? []);

        if (array_key_exists($context['type'], $registered)) {
            $this->line("  <comment>ötürüldü</comment>  {$relative} («{$context['type']}» artıq qeydiyyatdadır)");

            return;
        }

        if (!str_contains($config, self::CONFIG_MARKER)) {
            $this->warn("  {$relative}-də «" . self::CONFIG_MARKER . '» markeri yoxdur - sətri əl ilə əlavə et:');
            $this->line($this->configEntry($context, '        '));

            return;
        }

        $marker = self::CONFIG_MARKER;
        $updated = preg_replace_callback(
            '/^([ \t]*)' . preg_quote($marker, '/') . '/m',
            fn (array $m) => $this->configEntry($context, $m[1]) . "\n" . $m[1] . $marker,
            $config,
            1,
        );

        if (!$dryRun) {
            $this->files->put($path, (string) $updated);
        }

        $this->line("  <info>qeydiyyat</info>  {$relative} → '{$context['type']}'");
    }

    private function configEntry(array $context, string $indent): string
    {
        return $indent . "'{$context['type']}' => [\n"
            . $indent . "    'title'      => " . var_export($context['title'], true) . ",\n"
            . $indent . "    'controller' => \\App\\Http\\Controllers\\Gopanel\\Lookups\\{$context['name']}Controller::class,\n"
            . $indent . "    'icon'       => " . var_export($context['icon'], true) . ",\n"
            . $indent . '],';
    }

    /* ------------------------------------------------------------------ */
    /* Şablonlar                                                           */
    /* ------------------------------------------------------------------ */

    private function modelStub(array $c): string
    {
        $fillable = array_merge(['uid', 'key'], $c['fields'], ['sort_order'], $c['booleans']);
        $fillableCode = implode("\n", array_map(fn ($f) => "        '{$f}',", $fillable));

        $casts = ["        'sort_order' => 'integer',"];
        foreach ($c['booleans'] as $boolean) {
            $casts[] = "        '{$boolean}' => 'boolean',";
        }
        $castsCode = implode("\n", $casts);

        $translated = implode(', ', array_map(fn ($f) => "'{$f}'", $c['translated']));

        $scopes = '';
        if (in_array('is_active', $c['booleans'], true)) {
            $scopes .= <<<'PHP'

    /** Yeni seçimlərdə istifadə oluna bilənlər. */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

PHP;
        }
        if (in_array('is_public', $c['booleans'], true)) {
            $scopes .= <<<'PHP'

    /** Son istifadəçiyə göstərilə bilənlər (admin seçimi ilə eyni deyil). */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

PHP;
        }

        return <<<PHP
<?php

namespace App\Models\Lookups;

use App\Models\BaseModel;
use App\Traits\Content\Translation;
use App\Traits\System\AddUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * {$c['title']} - «Status və Tiplər» bölməsinin lookup cədvəli.
 *
 * `key` texniki müqavilədir (kodda ona müraciət olunur) - istifadəyə
 * başladıqdan sonra dəyişdirilmir. Yaradılıb: php artisan gopanel:make-lookup
 */
class {$c['name']} extends BaseModel
{
    use AddUuid, SoftDeletes, Translation;

    public \$translatedAttributes = [{$translated}];

    protected \$fillable = [
{$fillableCode}
    ];

    protected \$casts = [
{$castsCode}
    ];
{$scopes}
    public function scopeOrdered(Builder \$query): Builder
    {
        return \$query->orderBy('sort_order')->orderBy('id');
    }
}

PHP;
    }

    private function migrationStub(array $c): string
    {
        $columns = [];
        foreach ($c['fields'] as $field) {
            $columns[] = '            ' . self::FIELDS[$field];
        }
        $columns[] = "            \$table->unsignedInteger('sort_order')->default(0);";
        foreach ($c['booleans'] as $boolean) {
            $default = $boolean === 'is_active' ? 'true' : 'false';
            $columns[] = "            \$table->boolean('{$boolean}')->default({$default});";
        }
        $columnsCode = implode("\n", $columns);

        return <<<PHP
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * {$c['title']} (lookup). Ad/təsvir `field_translations`-dadır (Translation trait).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('{$c['table']}', function (Blueprint \$table) {
            \$table->id();
            \$table->uuid('uid')->unique();
            \$table->string('key', 50)->unique();
{$columnsCode}
            \$table->timestamps();
            \$table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('{$c['table']}');
    }
};

PHP;
    }

    private function controllerStub(array $c): string
    {
        $fields = $this->arrayLiteral($c['fields']);
        $booleans = $this->arrayLiteral($c['booleans']);

        return <<<PHP
<?php

namespace App\Http\Controllers\Gopanel\Lookups;

use App\Http\Requests\Gopanel\Lookups\\{$c['name']}SaveRequest;
use App\Models\Lookups\\{$c['name']};

/**
 * {$c['title']}. Bütün davranış BaseLookupController-dədir; burada yalnız
 * konfiqurasiya. Route/icazə/menyu config/gopanel/lookups.php reyestrindən gəlir.
 */
class {$c['name']}Controller extends BaseLookupController
{
    protected string \$model = {$c['name']}::class;

    protected string \$request = {$c['name']}SaveRequest::class;

    protected array \$extraFields = {$fields};

    protected array \$booleans = {$booleans};

    /**
     * Bu sətrə istinad edən cədvəllər - istifadə olunan sətir silinmir.
     * Nümunə: ['orders' => '{$this->foreignKey($c['name'])}']
     */
    protected array \$usedBy = [];
}

PHP;
    }

    private function requestStub(array $c): string
    {
        return <<<PHP
<?php

namespace App\Http\Requests\Gopanel\Lookups;

use App\Models\Lookups\\{$c['name']};

/**
 * {$c['title']} forması. Ortaq qaydalar (key, ad, rəng, dil whitelist-i) və
 * icazə (gopanel.lookups.{$c['type']}.add|edit) BaseLookupSaveRequest-dədir.
 */
class {$c['name']}SaveRequest extends BaseLookupSaveRequest
{
    protected string \$model = {$c['name']}::class;

    /** Modulun əlavə qaydaları. */
    protected function extraRules(): array
    {
        return [];
    }
}

PHP;
    }

    private function indexViewStub(): string
    {
        return <<<'BLADE'
{{-- Fərdiləşdirmə lazım deyilsə bu faylı silmək olar - ortaq səhifə işlədilir. --}}
@extends('gopanel.layouts.main')
@section('content')
@include('gopanel.pages.lookups.partials.index')
@endsection

BLADE;
    }

    private function formViewStub(): string
    {
        return <<<'BLADE'
{{-- Fərdiləşdirmə lazım deyilsə bu faylı silmək olar - ortaq forma işlədilir. --}}
@include('gopanel.pages.lookups.partials.form')

BLADE;
    }

    private function arrayLiteral(array $values): string
    {
        return '[' . implode(', ', array_map(fn ($v) => "'{$v}'", $values)) . ']';
    }

    private function foreignKey(string $name): string
    {
        return Str::snake($name) . '_id';
    }
}
