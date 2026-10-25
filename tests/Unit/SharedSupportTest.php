<?php

namespace Tests\Unit;

use App\Helpers\Common\StringHelpers;
use App\Support\Contact\EmailAddress;
use App\Support\Contact\PhoneNumber;
use App\Support\Gopanel\GuideText;
use App\Support\Geo\Distance;
use App\Support\Http\PreviewBotDetector;
use PHPUnit\Framework\TestCase;

/**
 * Kənar asılılığı olmayan saf köməkçilər (Laravel app tələb etmir).
 */
class SharedSupportTest extends TestCase
{
    public function test_phone_number_normalizes_common_formats(): void
    {
        foreach (['+994552725195', '00994 55 272 51 95', '0552725195', '(055) 272-51-95', '552725195'] as $raw) {
            $this->assertSame('994552725195', PhoneNumber::normalize($raw), $raw);
        }

        $this->assertSame('12345', PhoneNumber::normalize('12345'), 'Tanınmayan format olduğu kimi qalır');
        $this->assertSame('', PhoneNumber::normalize(null));
    }

    public function test_phone_number_normalizes_nested_paths(): void
    {
        $data = PhoneNumber::normalizeIn(
            ['owner' => ['phone' => '0552725195'], 'items' => [['phone' => '+994 50 111 22 33']]],
            ['owner.phone', 'items.*.phone'],
        );

        $this->assertSame('994552725195', $data['owner']['phone']);
        $this->assertSame('994501112233', $data['items'][0]['phone']);
    }

    public function test_email_address_normalization_and_mask(): void
    {
        $this->assertSame('test@mail.az', EmailAddress::normalize('  Test@Mail.AZ '));
        $this->assertNull(EmailAddress::normalize('yanlış'));
        $this->assertSame('t***@mail.az', EmailAddress::mask('test@mail.az'));
    }

    public function test_distance_in_meters(): void
    {
        // Bakı: Qız qalası → Heydər Əliyev Mərkəzi ≈ 3.6 km
        $meters = Distance::meters(40.3661, 49.8372, 40.3959, 49.8678);

        $this->assertGreaterThan(3500, $meters);
        $this->assertLessThan(4500, $meters);
        $this->assertNull(Distance::between(null, 1.0, 2.0, 3.0));
        $this->assertSame('320 m', Distance::label(320.4));
    }

    public function test_preview_bot_detection(): void
    {
        $this->assertSame('WhatsApp', PreviewBotDetector::label('WhatsApp/2.23.20.0 A'));
        $this->assertTrue(PreviewBotDetector::isBot(''));
        $this->assertFalse(PreviewBotDetector::isBot('Mozilla/5.0 (Windows NT 10.0) Chrome/120.0 Safari/537.36'));
    }

    public function test_guide_text_escapes_html_before_markup(): void
    {
        $html = GuideText::inline('**qalın** `kod` <script>x</script>');

        $this->assertStringContainsString('<strong>qalın</strong>', $html);
        $this->assertStringContainsString('<code>kod</code>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_string_helpers_mask_personal_data(): void
    {
        $this->assertSame('99455*****95', StringHelpers::obfuscatePhone('994552725195'));
        $this->assertNull(StringHelpers::obfuscateEmail(null));
        $this->assertStringContainsString('@', StringHelpers::obfuscateEmail('kamran@gmail.com'));
    }
}
