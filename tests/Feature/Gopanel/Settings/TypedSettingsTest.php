<?php

namespace Tests\Feature\Gopanel\Settings;

use App\Models\Settings\SettingValue;
use App\Services\Settings\AdminSettingsService;
use App\Services\Settings\SettingsService;
use App\Support\Settings\AdminSettingDefinitions;
use Tests\Feature\Gopanel\Concerns\BootsPanelSqlite;
use Tests\Feature\Gopanel\Settings\Fixtures\ShopSettingDefinitions;
use Tests\TestCase;

/**
 * Tipli key-value tənzimləmələr: tərif → cast / validasiya / saxlama / panel ekranı.
 * Baza: yaddaşda SQLite.
 */
class TypedSettingsTest extends TestCase
{
    use BootsPanelSqlite;

    protected function setUp(): void
    {
        parent::setUp();

        $this->bootPanelSqlite();
        (require base_path('database/migrations/2026_10_25_100002_create_setting_values_table.php'))->up();
        SettingsService::flushMemo();

        config([
            'testing.shop_settings' => [
                'min_order'  => ['type' => 'int', 'default' => 10, 'min' => 1, 'max' => 500, 'label' => 'Minimum sifariş', 'group' => 'Satış'],
                'delivery'   => ['type' => 'bool', 'default' => true, 'label' => 'Çatdırılma'],
                'commission' => ['type' => 'float', 'default' => 2.5, 'label' => 'Komissiya'],
                'currency'   => ['type' => 'select', 'default' => 'AZN', 'options' => ['AZN' => 'Manat', 'USD' => 'Dollar'], 'label' => 'Valyuta'],
                'banner'     => ['type' => 'string', 'default' => null, 'rules' => ['nullable', 'string', 'max:5'], 'label' => 'Banner'],
                'zones'      => ['type' => 'json', 'default' => [], 'label' => 'Zonalar'],
            ],
            'gopanel.admin_settings' => [
                'notify_new_order' => ['type' => 'bool', 'default' => true, 'group' => 'Bildirişlər', 'label' => 'Yeni sifariş bildirişi', 'hint' => 'Bildiriş gəlsin'],
                'page_length'      => ['type' => 'int', 'default' => 25, 'min' => 10, 'max' => 100, 'group' => 'Görünüş', 'label' => 'Sətir sayı'],
            ],
        ]);
    }

    public function test_defaults_are_returned_without_rows_and_values_are_cast(): void
    {
        $store = SettingsService::for(ShopSettingDefinitions::class);

        $this->assertSame(
            ['min_order' => 10, 'delivery' => true, 'commission' => 2.5, 'currency' => 'AZN', 'banner' => null, 'zones' => []],
            $store->all(),
        );

        $store->saveMany([
            'min_order'  => '42',
            'delivery'   => '0',
            'commission' => '3.75',
            'zones'      => ['Bakı', 'Sumqayıt'],
            'unknown'    => 'yazılmamalıdır',
        ]);

        SettingsService::flushMemo();
        $fresh = SettingsService::for(ShopSettingDefinitions::class);

        $this->assertSame(42, $fresh->get('min_order'));
        $this->assertFalse($fresh->get('delivery'));
        $this->assertSame(3.75, $fresh->get('commission'));
        $this->assertSame(['Bakı', 'Sumqayıt'], $fresh->get('zones'));
        $this->assertNull($fresh->get('unknown'));
        $this->assertSame(4, SettingValue::query()->count(), 'Tərifdə olmayan açar yazılmır.');
        $this->assertSame('0', SettingValue::query()->where('key', 'delivery')->value('value'));

        $fresh->reset('min_order');
        $this->assertSame(10, $fresh->get('min_order'), 'Sıfırlanan açar default-a qayıdır.');
    }

    public function test_owners_are_isolated_and_cache_is_invalidated_on_write(): void
    {
        $first = $this->makePanelAdmin('s1@example.com');
        $second = $this->makePanelAdmin('s2@example.com');
        $service = new AdminSettingsService();

        $this->assertTrue($service->get($first, 'notify_new_order'));

        $service->saveMany($first, ['notify_new_order' => false]);

        $this->assertFalse($service->get($first, 'notify_new_order'), 'Yazandan sonra köhnə keş qalmamalıdır.');
        $this->assertTrue($service->get($second->id, 'notify_new_order'), 'Başqa adminə təsir etmir (id ilə də).');
        $this->assertTrue(SettingsService::for(ShopSettingDefinitions::class)->get('delivery'), 'Qlobal dəstə təsir etmir.');

        // Int id ilə yazılan dəyər model ilə oxunur - eyni morph tipi
        $service->saveMany($second->id, ['page_length' => 50]);
        AdminSettingsService::flush();
        $this->assertSame(50, $service->get($second, 'page_length'));

        $this->assertSame(1, $service->seedDefaults($first), 'Yalnız çatışmayan açar yaradılır.');
        $this->assertSame(0, $service->seedDefaults($first), 'İdempotentdir.');
        $this->assertFalse($service->get($first, 'notify_new_order'), 'Mövcud dəyərə toxunulmur.');
    }

    public function test_validation_rules_form_conversion_and_rows_come_from_definitions(): void
    {
        $rules = ShopSettingDefinitions::validationRules('settings');

        $this->assertSame(['nullable', 'integer', 'min:1', 'max:500'], $rules['settings.min_order']);
        $this->assertSame(['nullable', 'boolean'], $rules['settings.delivery']);
        $this->assertSame(['nullable', 'string', 'in:AZN,USD'], $rules['settings.currency']);
        $this->assertSame(['nullable', 'string', 'max:5'], $rules['settings.banner'], 'Açıq `rules` tipdən qurulanı əvəz edir.');
        $this->assertSame(['nullable', 'json'], $rules['settings.zones']);

        $this->assertSame(
            ['min_order' => 7, 'delivery' => false, 'banner' => null, 'zones' => ['a']],
            ShopSettingDefinitions::fromForm(['min_order' => '7', 'delivery' => '0', 'banner' => '', 'zones' => '["a"]', 'hack' => 1]),
        );

        $groups = ShopSettingDefinitions::groupedFormRows(['min_order' => 15]);
        $this->assertSame(['Satış', 'Ümumi'], array_keys($groups));
        $this->assertSame('number', $groups['Satış'][0]['input']);
        $this->assertSame('15', $groups['Satış'][0]['value']);

        $byName = array_column($groups['Ümumi'], null, 'name');
        $this->assertSame('switch', $byName['delivery']['input']);
        $this->assertTrue($byName['delivery']['checked']);
        $this->assertSame('select', $byName['currency']['input']);
        $this->assertSame('any', $byName['commission']['step']);
    }

    public function test_profile_settings_screen_renders_and_saves(): void
    {
        $admin = $this->makePanelAdmin('profile@example.com');

        $this->actingAs($admin, 'gopanel')->get(route('gopanel.profile.settings.index'))
            ->assertOk()
            ->assertSee('Şəxsi tənzimləmələr')
            ->assertSee('Bildirişlər')
            ->assertSee('Yeni sifariş bildirişi')
            ->assertSee('name="settings[page_length]"', false);

        $this->actingAs($admin, 'gopanel')->postJson(route('gopanel.profile.settings.update'), [
            'settings' => ['notify_new_order' => '0', 'page_length' => '500'],
        ])->assertStatus(422);

        $this->actingAs($admin, 'gopanel')->postJson(route('gopanel.profile.settings.update'), [
            'settings' => ['notify_new_order' => '0', 'page_length' => '50'],
        ])->assertOk()->assertJson(['status' => 'success']);

        AdminSettingsService::flush();
        $this->assertSame(
            ['notify_new_order' => false, 'page_length' => 50],
            (new AdminSettingsService())->all($admin),
        );
    }

    public function test_empty_admin_definitions_show_placeholder(): void
    {
        config(['gopanel.admin_settings' => []]);
        $admin = $this->makePanelAdmin('empty@example.com');

        $this->assertSame([], AdminSettingDefinitions::keys());

        $this->actingAs($admin, 'gopanel')->get(route('gopanel.profile.settings.index'))
            ->assertOk()
            ->assertSee('Hələ heç bir şəxsi tənzimləmə təyin edilməyib.');
    }
}
