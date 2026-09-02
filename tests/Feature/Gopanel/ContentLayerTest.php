<?php

namespace Tests\Feature\Gopanel;

use App\DTOs\Gopanel\ContentPayload;
use App\DTOs\Gopanel\FileField;
use App\Rules\TranslatedRequired;
use Tests\TestCase;

/**
 * Yeni qatların (DTO / Rule / Repository) davranışı.
 *
 * Bu testlər bazaya toxunmur - məqsəd qaydanın özünü yoxlamaqdır.
 */
class ContentLayerTest extends TestCase
{
    public function test_payload_is_immutable_and_separates_layers(): void
    {
        $payload = new ContentPayload(
            attributes: ['is_active' => 1],
            translations: ['title' => ['az' => 'Başlıq']],
            meta: ['title' => ['az' => 'SEO']],
        );

        $changed = $payload->withAttribute('image', 'site/blogs/a.png');

        // Orijinal obyekt dəyişmir
        $this->assertNull($payload->attribute('image'));
        $this->assertSame('site/blogs/a.png', $changed->attribute('image'));
        $this->assertSame(1, $changed->attribute('is_active'));

        // Tərcümə və meta sütunlara qarışmır
        $this->assertArrayNotHasKey('title', $payload->attributes);
        $this->assertSame(['az' => 'Başlıq'], $payload->translations['title']);
        $this->assertSame(['az' => 'SEO'], $payload->meta['title']);
    }

    public function test_payload_can_drop_attribute_to_keep_existing_file(): void
    {
        $payload = (new ContentPayload(['icon' => '', 'icon_type' => 'image']))
            ->withoutAttribute('icon');

        $this->assertArrayNotHasKey('icon', $payload->attributes);
        $this->assertSame('image', $payload->attribute('icon_type'));
    }

    public function test_name_source_prefers_translations_for_file_names(): void
    {
        $payload = new ContentPayload(
            attributes: ['title' => 'sütun dəyəri'],
            translations: ['title' => ['az' => 'Tərcümə dəyəri']],
        );

        $this->assertSame(['az' => 'Tərcümə dəyəri'], $payload->nameSource()['title']);
    }

    public function test_file_field_describes_where_the_upload_goes(): void
    {
        $field = new FileField('icon_image', 'icon', prefix: 'category-icon', typeColumn: 'icon_type');

        $this->assertSame('icon_image', $field->input);
        $this->assertSame('icon', $field->column);
        $this->assertSame('category-icon', $field->prefix);
        $this->assertSame('icon_type', $field->typeColumn);
        $this->assertNull($field->folder);
    }

    public function test_translated_required_only_demands_the_default_locale(): void
    {
        $rule = new TranslatedRequired('az');

        $this->assertTrue($this->passes($rule, ['az' => 'Dolu', 'en' => '']));
        $this->assertFalse($this->passes($rule, ['en' => 'Only english']));
        $this->assertFalse($this->passes($rule, ['az' => '   ']));
        $this->assertFalse($this->passes($rule, []));
    }

    private function passes(TranslatedRequired $rule, mixed $value): bool
    {
        $failed = false;

        $rule->validate('title', $value, function () use (&$failed) {
            $failed = true;
        });

        return !$failed;
    }
}
