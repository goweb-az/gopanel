<?php

namespace Tests\Feature\Gopanel\Lookups;

use App\Models\Gopanel\Admin;
use App\Models\Gopanel\CustomPermission;
use App\Services\Gopanel\Lookup\LookupRegistry;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\Feature\Gopanel\Lookups\Fixtures\TestStatus;
use Tests\Feature\Gopanel\Lookups\Fixtures\TestStatusController;
use Tests\TestCase;

/**
 * «Status və Tiplər» ortaq qatı - atılan `test_statuses` lookup-u üzərində.
 *
 * Baza: yaddaşda SQLite (layihənin MySQL bazasına toxunulmur). Lazım olan
 * cədvəllər burada minimal yaradılır, çünki bütün miqrasiyalar SQLite-da
 * işləmir (MySQL-ə xas `UUID()` default-u və s.).
 */
class LookupModuleTest extends TestCase
{
    private const TYPE = 'test-statuses';

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.connections.lookup_testing' => [
                'driver'                  => 'sqlite',
                'database'                => ':memory:',
                'prefix'                  => '',
                'foreign_key_constraints' => false,
            ],
            'database.default'   => 'lookup_testing',
            'activitylog.enabled' => false,
        ]);
        DB::purge('lookup_testing');

        $this->createSchema();
        $this->registerLookup();

        $this->admin = $this->makeAdmin('lookup@example.com', LookupRegistry::ACTIONS);
    }

    public function test_index_renders_rows_sidebar_and_form(): void
    {
        $first = $this->makeStatus('pending', ['color' => '#ff0000']);
        $this->makeStatus('approved');

        $response = $this->actingAs($this->admin, 'gopanel')->get($this->url('index'));

        $response->assertOk()
            ->assertSee('Test statusları')
            ->assertSee('pending')
            ->assertSee('approved')
            ->assertSee('--lookup-color: #ff0000', false)
            ->assertSee($this->url('sort'), false)
            ->assertSee('assets/gopanel/css/lookups.css', false)
            ->assertSee('id="cerate-modal"', false);

        $form = $this->actingAs($this->admin, 'gopanel')
            ->getJson($this->url('get.form', $first->uid));

        $form->assertOk()->assertJson(['status' => 'success']);
        $this->assertStringContainsString('name[az]', $form->json('html'));
        $this->assertStringContainsString('name="color"', $form->json('html'));
    }

    public function test_save_creates_row_with_translations_and_next_sort_order(): void
    {
        $this->makeStatus('first');

        $response = $this->actingAs($this->admin, 'gopanel')->postJson($this->url('save'), [
            'key'  => 'pending_review',
            'name' => ['az' => 'Yoxlanılır', 'en' => 'Under review'],
            'is_active' => '1',
        ]);

        $response->assertStatus(201)->assertJson(['status' => 'success']);

        $item = TestStatus::query()->where('key', 'pending_review')->firstOrFail();
        $this->assertSame(2, $item->sort_order);
        $this->assertTrue($item->is_active);
        $this->assertFalse($item->is_default, 'Göndərilməyən checkbox false olmalıdır.');
        $this->assertSame('Yoxlanılır', $item->getTranslation('name', 'az', true));
        $this->assertSame('Under review', $item->getTranslation('name', 'en', true));

        // Edit: tərcümə yenilənir, sətir sayı artmır
        $this->actingAs($this->admin, 'gopanel')->postJson($this->url('save', $item->uid), [
            'key'  => 'pending_review',
            'name' => ['az' => 'Baxışda'],
        ])->assertOk();

        $this->assertSame('Baxışda', $item->fresh()->getTranslation('name', 'az', true));
        $this->assertSame(2, TestStatus::query()->count());
    }

    public function test_save_validates_key_default_language_and_language_whitelist(): void
    {
        $this->makeStatus('taken');
        $post = fn (array $data) => $this->actingAs($this->admin, 'gopanel')->postJson($this->url('save'), $data);

        $post(['key' => 'taken', 'name' => ['az' => 'X']])->assertStatus(422)->assertJsonValidationErrors('key');
        $post(['key' => 'Bad Key', 'name' => ['az' => 'X']])->assertStatus(422)->assertJsonValidationErrors('key');
        $post(['key' => 'no_az', 'name' => ['en' => 'Only english']])->assertStatus(422)->assertJsonValidationErrors('name');
        $post(['key' => 'odd_lang', 'name' => ['az' => 'X', 'xx' => 'Y']])->assertStatus(422)->assertJsonValidationErrors('name');
        $post(['key' => 'bad_color', 'name' => ['az' => 'X'], 'color' => 'red'])->assertStatus(422)->assertJsonValidationErrors('color');
    }

    public function test_only_one_default_is_kept_and_default_is_protected(): void
    {
        $old = $this->makeStatus('old', ['is_default' => true]);
        $new = $this->makeStatus('new', ['is_active' => false]);

        $this->actingAs($this->admin, 'gopanel')->postJson($this->url('save', $new->uid), [
            'key'        => 'new',
            'name'       => ['az' => 'Yeni'],
            'is_default' => '1',
        ])->assertOk();

        $this->assertFalse($old->fresh()->is_default);
        $this->assertTrue($new->fresh()->is_default);
        $this->assertTrue($new->fresh()->is_active, 'Default sətir avtomatik aktiv olmalıdır.');
        $this->assertSame(1, TestStatus::query()->where('is_default', true)->count());

        // Default-u əl ilə çıxarmaq olmaz - yalnız başqası seçilərək
        $this->actingAs($this->admin, 'gopanel')->postJson($this->url('save', $new->uid), [
            'key'  => 'new',
            'name' => ['az' => 'Yeni'],
        ])->assertStatus(422)->assertJsonValidationErrors('is_default');

        // Default sətir siyahıdan da deaktiv edilmir
        $this->actingAs($this->admin, 'gopanel')->postJson($this->url('toggle'), [
            'id' => $new->uid, 'row' => 'is_active', 'status' => 'false',
        ])->assertStatus(422);
        $this->assertTrue($new->fresh()->is_active);

        // Bayraq olmayan sütun toggle ilə dəyişdirilmir
        $this->actingAs($this->admin, 'gopanel')->postJson($this->url('toggle'), [
            'id' => $old->uid, 'row' => 'key', 'status' => 'true',
        ])->assertStatus(422);
    }

    public function test_sort_persists_whole_order_in_one_request_and_one_update(): void
    {
        $a = $this->makeStatus('a');
        $b = $this->makeStatus('b');
        $c = $this->makeStatus('c');

        $updates = 0;
        DB::listen(function ($query) use (&$updates) {
            if (stripos(ltrim($query->sql), 'update') === 0) {
                $updates++;
            }
        });

        $this->actingAs($this->admin, 'gopanel')
            ->patchJson($this->url('sort'), ['orders' => [$c->uid, $a->uid, $b->uid]])
            ->assertOk()
            ->assertJson(['status' => 'success']);

        $this->assertSame(1, $updates, 'Bütün sıra tək UPDATE sorğusu ilə yazılmalıdır.');
        $this->assertSame(1, $c->fresh()->sort_order);
        $this->assertSame(2, $a->fresh()->sort_order);
        $this->assertSame(3, $b->fresh()->sort_order);

        // Tanınmayan açar varsa heç nə yazılmır
        $this->actingAs($this->admin, 'gopanel')
            ->patchJson($this->url('sort'), ['orders' => [$a->uid, '00000000-0000-0000-0000-000000000000']])
            ->assertStatus(422);
        $this->assertSame(1, $c->fresh()->sort_order);

        // Təkrarlanan açar qəbul edilmir
        $this->actingAs($this->admin, 'gopanel')
            ->patchJson($this->url('sort'), ['orders' => [$a->uid, $a->uid]])
            ->assertStatus(422);
    }

    public function test_admin_without_permission_is_denied_on_every_endpoint(): void
    {
        $item = $this->makeStatus('locked');
        $viewer = $this->makeAdmin('viewer@example.com', []);

        $this->actingAs($viewer, 'gopanel')->get($this->url('index'))->assertForbidden();
        $this->actingAs($viewer, 'gopanel')->getJson($this->url('get.form'))->assertForbidden();
        $this->actingAs($viewer, 'gopanel')->postJson($this->url('save'), ['key' => 'x', 'name' => ['az' => 'X']])->assertForbidden();
        $this->actingAs($viewer, 'gopanel')->patchJson($this->url('sort'), ['orders' => [$item->uid]])->assertForbidden();
        $this->actingAs($viewer, 'gopanel')->postJson($this->url('toggle'), ['id' => $item->uid, 'row' => 'is_active', 'status' => 'false'])->assertForbidden();
        $this->actingAs($viewer, 'gopanel')->deleteJson($this->url('destroy', $item->uid))->assertForbidden();

        // Yalnız `add` icazəsi olan admin mövcud sətri redaktə edə bilmir
        $adder = $this->makeAdmin('adder@example.com', ['add' => '']);
        $this->actingAs($adder, 'gopanel')->getJson($this->url('get.form'))->assertOk();
        $this->actingAs($adder, 'gopanel')->getJson($this->url('get.form', $item->uid))->assertForbidden();
        $this->actingAs($adder, 'gopanel')
            ->postJson($this->url('save', $item->uid), ['key' => 'locked', 'name' => ['az' => 'X']])
            ->assertForbidden();

        $this->assertSame('locked', $item->fresh()->key);
        $this->assertNotNull($item->fresh());
    }

    public function test_delete_soft_deletes_and_blocks_default_and_used_rows(): void
    {
        $free = $this->makeStatus('free');
        $default = $this->makeStatus('main', ['is_default' => true]);
        $used = $this->makeStatus('used');
        DB::table('test_orders')->insert(['test_status_id' => $used->id]);

        $this->actingAs($this->admin, 'gopanel')->deleteJson($this->url('destroy', $free->uid))
            ->assertOk()->assertJson(['status' => 'success']);
        $this->assertSoftDeleted('test_statuses', ['id' => $free->id]);

        $this->actingAs($this->admin, 'gopanel')->deleteJson($this->url('destroy', $default->uid))->assertStatus(422);
        $this->actingAs($this->admin, 'gopanel')->deleteJson($this->url('destroy', $used->uid))->assertStatus(422);

        $this->assertNotSoftDeleted('test_statuses', ['id' => $default->id]);
        $this->assertNotSoftDeleted('test_statuses', ['id' => $used->id]);

        $this->actingAs($this->admin, 'gopanel')->deleteJson($this->url('destroy', 'missing-uid'))->assertNotFound();
    }

    public function test_registry_drives_permissions_and_sidebar_and_starter_ships_no_types(): void
    {
        $raw = require base_path('config/gopanel/lookups.php');
        $this->assertSame([], LookupRegistry::types($raw), 'Starter-də biznes lookup-u olmamalıdır.');
        $this->assertSame([], LookupRegistry::sidebarItems($raw));
        $this->assertSame([], LookupRegistry::permissionGroups($raw));

        $config = ['types' => [self::TYPE => ['title' => 'Test statusları', 'controller' => TestStatusController::class]]];
        $names = collect(LookupRegistry::permissionGroups($config))->flatten(1)->pluck('name')->all();

        $this->assertSame([
            'gopanel.lookups.test-statuses.index',
            'gopanel.lookups.test-statuses.add',
            'gopanel.lookups.test-statuses.edit',
            'gopanel.lookups.test-statuses.delete',
            'gopanel.lookups.test-statuses.sort',
        ], $names);

        $sidebar = LookupRegistry::sidebarItems($config);
        $this->assertSame('gopanel.lookups.test-statuses.index', $sidebar[0]['inner'][0]['route']);
        $this->assertSame('gopanel.lookups.test-statuses.index', $sidebar[0]['inner'][0]['can']);

        // Route adı = icazə adı (01-umumi.md § 5)
        foreach (['index', 'get.form', 'save', 'destroy', 'sort', 'toggle'] as $action) {
            $this->assertTrue(Route::has('gopanel.lookups.test-statuses.' . $action), $action);
        }
    }

    public function test_generator_scaffolds_files_and_registers_type_idempotently(): void
    {
        $base = storage_path('framework/testing/lookup-generator-' . uniqid());
        File::ensureDirectoryExists($base . '/config/gopanel');
        File::copy(base_path('config/gopanel/lookups.php'), $base . '/config/gopanel/lookups.php');

        try {
            $args = [
                'name'       => 'OrderStatus',
                '--title'    => 'Sifariş statusları',
                '--fields'   => 'color,class_name',
                '--booleans' => 'is_default,is_active',
                '--base'     => $base,
            ];

            $this->assertSame(0, Artisan::call('gopanel:make-lookup', $args));
            $this->assertSame(0, Artisan::call('gopanel:make-lookup', $args));

            foreach ([
                'app/Models/Lookups/OrderStatus.php',
                'app/Http/Controllers/Gopanel/Lookups/OrderStatusController.php',
                'app/Http/Requests/Gopanel/Lookups/OrderStatusSaveRequest.php',
                'resources/views/gopanel/pages/lookups/order-statuses/index.blade.php',
                'resources/views/gopanel/pages/lookups/order-statuses/form.blade.php',
            ] as $file) {
                $this->assertFileExists($base . '/' . $file);
            }

            $this->assertCount(1, glob($base . '/database/migrations/*_create_order_statuses_table.php'));
            $this->assertStringContainsString(
                "protected array \$booleans = ['is_default', 'is_active'];",
                File::get($base . '/app/Http/Controllers/Gopanel/Lookups/OrderStatusController.php'),
            );

            $registered = LookupRegistry::types(require $base . '/config/gopanel/lookups.php');
            $this->assertSame(['order-statuses'], array_keys($registered));
            $this->assertSame('Sifariş statusları', $registered['order-statuses']['title']);

            $this->assertSame(1, Artisan::call('gopanel:make-lookup', ['name' => 'Foo', '--fields' => 'price', '--base' => $base]));
        } finally {
            File::deleteDirectory($base);
        }
    }

    /* ------------------------------------------------------------------ */

    private function url(string $action, mixed $parameters = []): string
    {
        return route('gopanel.lookups.' . self::TYPE . '.' . $action, $parameters);
    }

    private function makeStatus(string $key, array $attributes = []): TestStatus
    {
        return TestStatus::query()->create(array_merge(['key' => $key, 'is_active' => true], $attributes));
    }

    /**
     * @param  array<string, string>  $actions  LookupRegistry::ACTIONS formatında (açarlar istifadə olunur)
     */
    private function makeAdmin(string $email, array $actions): Admin
    {
        $admin = Admin::query()->create([
            'full_name' => 'Test Admin',
            'email'     => $email,
            'password'  => bcrypt('secret'),
            'is_active' => true,
            'is_super'  => false,
        ]);

        foreach (array_keys($actions) as $action) {
            $permission = CustomPermission::query()->firstOrCreate([
                'name'       => LookupRegistry::permission(self::TYPE, $action),
                'guard_name' => 'gopanel',
            ]);
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    /**
     * Reyestrə atılan tipi yazır və route faylını real `gopanel` qrupu ilə qoşur -
     * yəni production-dakı eyni route/middleware zənciri yoxlanılır.
     */
    private function registerLookup(): void
    {
        config(['gopanel.lookups.types' => [
            self::TYPE => [
                'title'      => 'Test statusları',
                'controller' => TestStatusController::class,
                'icon'       => 'bx bx-flag',
            ],
        ]]);

        Route::middleware('web')->prefix('gopanel')->name('gopanel.')->group(function () {
            Route::middleware('gopanel')->group(base_path('routes/gopanel/lookups.php'));
        });

        app('router')->getRoutes()->refreshNameLookups();
        app('router')->getRoutes()->refreshActionLookups();
    }

    private function createSchema(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_id')->default(0);
            $table->string('code', 10);
            $table->string('name', 100);
            $table->boolean('default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_show')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        DB::table('languages')->insert([
            ['code' => 'az', 'name' => 'Azərbaycan', 'default' => true, 'sort_order' => 1],
            ['code' => 'en', 'name' => 'English', 'default' => false, 'sort_order' => 2],
        ]);

        Schema::create('admins', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('full_name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_super')->default(false);
            $table->string('image')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('gopanel_logo')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        DB::table('site_settings')->insert(['gopanel_logo' => null]);

        (require base_path('database/migrations/2025_03_25_180012_create_field_translations_table.php'))->up();
        (require base_path('database/migrations/2025_06_14_165226_create_permission_tables.php'))->up();
        (require base_path('database/migrations/2025_06_25_171734_add_group_and_title_to_permissions_table.php'))->up();

        Schema::create('test_statuses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uid')->unique();
            $table->string('key', 50)->unique();
            $table->string('color', 30)->nullable();
            $table->string('class_name', 100)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_default')->default(false);
            $table->boolean('is_public')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('test_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('test_status_id');
        });
    }
}
