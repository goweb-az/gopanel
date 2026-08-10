<?php

namespace App\Services\Sms\Providers;

use App\Services\Activity\LogService;
use App\Services\Sms\ProviderInterface;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * LSIM (sendsms.az) SMS provayderi - Azərbaycan bazarı üçün nümunə adapter.
 *
 * Bütün açarlar `config/custom/sms.php` → `lsim` bölməsindən gəlir; kodda
 * hardcode edilmir. Başqa provayder əlavə edəndə bu sinif nümunə götürülür:
 * `ProviderInterface` implement edilir, uğursuzluqda exception atılır.
 */
class Lsim implements ProviderInterface
{
    protected string $login;
    protected string $password;
    protected string $url;
    protected string $title;

    public mixed $response = null;
    public array $payload  = [];

    protected LogService $logging;

    public function __construct()
    {
        $this->login    = (string) config('custom.sms.lsim.login', '');
        $this->password = (string) config('custom.sms.lsim.password', '');
        $this->url      = (string) config('custom.sms.lsim.url', 'https://www.sendsms.az/smxml/api');
        $this->title    = (string) config('custom.sms.lsim.title', config('app.name', 'Gopanel'));
        $this->logging  = new LogService('sms');
    }

    public function send($phone, $message)
    {
        try {
            $this->response = $this->sendRequest($this->individualPayload($message, $phone));

            return $this;
        } catch (Throwable $th) {
            $this->logging->error("SMS göndərilərkən xəta: {$phone} - {$th->getMessage()}", [
                'phone'   => $phone,
                'message' => $message,
            ]);

            throw $th;
        }
    }

    /**
     * Toplu göndəriş payload-u - eyni mətn çox nömrəyə.
     *
     * @param  array<int, string>  $numbers
     * @return array<string, mixed>
     */
    public function bulkPayload(string $message, array $numbers): array
    {
        return $this->payload = [
            'request' => [
                'head' => $this->head() + ['bulkmessage' => $message, 'isbulk' => true],
                'body' => array_map(static fn ($number) => ['msisdn' => $number], $numbers),
            ],
        ];
    }

    /**
     * Tək nömrə payload-u.
     *
     * @return array<string, mixed>
     */
    public function individualPayload(string $message, string $number): array
    {
        return $this->payload = [
            'request' => [
                'head' => $this->head() + ['isbulk' => false],
                'body' => ['msisdn' => $number, 'message' => $message],
            ],
        ];
    }

    /** @return array<string, mixed> */
    protected function head(): array
    {
        return [
            'operation' => 'submit',
            'login'     => $this->login,
            'password'  => $this->password,
            'controlid' => Str::uuid()->toString(),
            'title'     => $this->title,
            'scheduled' => 'NOW',
        ];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    protected function sendRequest(array $payload): mixed
    {
        $result = Http::withHeaders(['Content-Type' => 'application/json'])
            ->timeout((int) config('custom.sms.timeout', 15))
            ->post($this->url, $payload);

        if ($result->status() !== 200) {
            $this->logging->error("LSIM API xəta - status: {$result->status()}", ['status' => $result->status()]);

            throw new Exception('Lsim API xətası', $result->status());
        }

        return $result->json();
    }
}
