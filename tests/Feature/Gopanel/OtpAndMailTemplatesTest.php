<?php

namespace Tests\Feature\Gopanel;

use App\Services\Auth\OtpService;
use App\Services\Mail\MailService;
use App\Services\Mail\Templates\ForgotPasswordMail;
use App\Services\Mail\Templates\OtpMail;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * OtpService (keşdə kod + brendli məktub) və yeni mail şablonları.
 *
 * Real məktub getmir: `Mail::fake()` (phpunit.xml-də MAIL_MAILER=array).
 * OTP jurnalı DB-yə yazılmasın deyə kanal `null`-a yönləndirilir.
 */
class OtpAndMailTemplatesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Mail::fake();
        config([
            'custom.security.otp.log_channel'     => 'null',
            'custom.security.otp.ttl'             => 300,
            'custom.security.otp.resend_cooldown' => 60,
            'custom.security.otp.max_attempts'    => 3,
            'custom.security.otp.length'          => 6,
        ]);
    }

    public function test_send_mails_a_branded_code_and_verify_consumes_it(): void
    {
        $otp = new OtpService();

        $result = $otp->send('  User@Example.COM ', 'reset', 'Orxan');

        $this->assertSame(['sent' => true, 'retry_after' => 60], $result);

        $code = $this->sentCode('user@example.com');
        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);

        $this->assertFalse($otp->verify('user@example.com', $code, 'verify'), 'Başqa məqsədin kodu işləməməlidir.');
        $this->assertTrue($otp->verify('USER@example.com', $code, 'reset'));
        $this->assertTrue($otp->isVerified('user@example.com', 'reset'));

        // Kod bir dəfəlikdir
        $this->assertFalse($otp->verify('user@example.com', $code, 'reset'));

        $otp->clearVerified('user@example.com', 'reset');
        $this->assertFalse($otp->isVerified('user@example.com', 'reset'));
    }

    public function test_resend_is_blocked_during_cooldown(): void
    {
        $otp = new OtpService();

        $this->assertTrue($otp->send('a@example.com')['sent']);

        $second = $otp->send('a@example.com');
        $this->assertFalse($second['sent']);
        $this->assertGreaterThan(0, $second['retry_after']);
        Mail::assertSent(OtpMail::class, 1);

        $this->travel(61)->seconds();
        $this->assertTrue($otp->send('a@example.com')['sent']);
    }

    public function test_code_is_revoked_after_max_wrong_attempts(): void
    {
        $otp = new OtpService();
        $otp->send('b@example.com');
        $code = $this->sentCode('b@example.com');
        $wrong = $code === '000000' ? '111111' : '000000';

        foreach (range(1, 3) as $ignored) {
            $this->assertFalse($otp->verify('b@example.com', $wrong));
        }

        // Limit doldu - düzgün kod da artıq qəbul edilmir
        $this->assertFalse($otp->verify('b@example.com', $code));
    }

    public function test_code_expires_after_ttl(): void
    {
        $otp = new OtpService();
        $otp->send('c@example.com');
        $code = $this->sentCode('c@example.com');

        $this->travel(301)->seconds();

        $this->assertFalse($otp->verify('c@example.com', $code));
    }

    public function test_otp_template_renders_code_and_branding(): void
    {
        config(['custom.mail.branding.title' => 'Test Brend']);

        (new MailService())->sendOtpEmail('d@example.com', '123456', 'Leyla', 5);

        Mail::assertSent(OtpMail::class, function (OtpMail $mail) {
            $html = $mail->render();

            return $mail->hasTo('d@example.com')
                && $mail->subject === 'Təsdiq kodu'
                && str_contains($html, '123456')
                && str_contains($html, 'Leyla')
                && str_contains($html, 'Test Brend')
                && str_contains($html, '5 dəqiqə');
        });
    }

    public function test_forgot_password_template_renders_action_link(): void
    {
        $link = 'https://example.az/reset/abc?token=xyz';

        (new MailService())->sendForgotPasswordEmail('e@example.com', $link, 'Elvin', 60);

        Mail::assertSent(ForgotPasswordMail::class, function (ForgotPasswordMail $mail) use ($link) {
            $html = $mail->render();

            return $mail->hasTo('e@example.com')
                && $mail->subject === 'Şifrənin bərpası'
                && str_contains($html, e($link))
                && str_contains($html, '60 dəqiqə');
        });
    }

    public function test_existing_basic_mail_api_is_unchanged(): void
    {
        $this->assertTrue(method_exists(MailService::class, 'sendBasicEmail'));
        $this->assertTrue(method_exists(MailService::class, 'sendHtmlEmail'));

        (new MailService('Mövzu'))->sendBasicEmail('f@example.com', 'Salam');

        Mail::assertSent(\App\Services\Mail\Templates\BasicMail::class, fn ($m) => $m->hasTo('f@example.com'));
    }

    private function sentCode(string $email): string
    {
        $code = null;

        Mail::assertSent(OtpMail::class, function (OtpMail $mail) use ($email, &$code) {
            if ($mail->hasTo($email)) {
                $code = (string) $mail->viewData['otp'];

                return true;
            }

            return false;
        });

        return (string) $code;
    }
}
