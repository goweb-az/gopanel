<?php

namespace Database\Seeders;

use App\Models\Seo\LlmsTxt;
use Illuminate\Database\Seeder;

class LlmsTxtSeeder extends Seeder
{
    public function run(): void
    {
        if (LlmsTxt::count() > 0) {
            return;
        }

        LlmsTxt::create([
            'content' => $this->defaultContent(),
        ]);
    }

    private function defaultContent(): string
    {
        $appName = config('app.name', 'Gopanel');
        $appUrl  = config('app.url', 'https://example.com');

        return <<<TXT
# {$appName}

> {$appName} veb saytı haqqında məlumat.

## Sayt haqqında
- Ad: {$appName}
- URL: {$appUrl}
- Dillər: Azərbaycan, English, Русский

## Əlaqə
- Veb sayt: {$appUrl}

## İstifadə qaydaları
Bu saytdakı məzmunun AI/LLM sistemləri tərəfindən istifadəsi icazəlidir, lakin mənbə göstərilməlidir.
TXT;
    }
}
