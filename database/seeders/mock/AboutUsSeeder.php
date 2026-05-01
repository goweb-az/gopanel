<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Seo\PageMetaData;
use App\Models\Site\AboutUs;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class AboutUsSeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'About Us';

    public function run(): void
    {
        $data = [
            'image' => $this->placeholderImage('about-us', 'About Us', 900, 600),
            'title' => [
                'az' => 'Haqqımızda',
                'en' => 'About us',
                'ru' => 'About us',
            ],
            'description' => [
                'az' => '<p>Biz rəqəmsal həllər, veb saytlar və idarəetmə panelləri hazırlayan peşəkar komandayıq. Məqsədimiz bizneslərin proseslərini sadələşdirmək və onların onlayn görünürlüğünü gücləndirməkdir.</p>',
                'en' => '<p>We are a professional team building digital solutions, websites and management panels. Our goal is to simplify business processes and strengthen online presence.</p>',
                'ru' => '<p>We are a professional team building digital solutions, websites and management panels. Our goal is to simplify business processes and strengthen online presence.</p>',
            ],
            'meta' => [
                'title' => [
                    'az' => 'Haqqımızda | Şirkət haqqında',
                    'en' => 'About us | Company profile',
                    'ru' => 'About us | Company profile',
                ],
                'description' => [
                    'az' => 'Komandamız, dəyərlərimiz və təqdim etdiyimiz rəqəmsal həllər haqqında məlumat.',
                    'en' => 'Information about our team, values and digital solutions.',
                    'ru' => 'Information about our team, values and digital solutions.',
                ],
                'keywords' => [
                    'az' => 'haqqımızda, şirkət, komanda, rəqəmsal həllər',
                    'en' => 'about us, company, team, digital solutions',
                    'ru' => 'about us, company, team, digital solutions',
                ],
            ],
        ];

        $item = AboutUs::query()->first() ?? AboutUs::create(['image' => $data['image']]);
        $item->update(['image' => $data['image']]);

        TranslationHelper::basic($item, $data['title'], 'title');
        TranslationHelper::basic($item, $data['description'], 'description');

        foreach ($data['title'] as $locale => $title) {
            PageMetaData::updateOrCreate(
                [
                    'model_type' => AboutUs::class,
                    'model_id' => $item->id,
                    'locale' => $locale,
                ],
                [
                    'source' => $item->getTable(),
                    'title' => $data['meta']['title'][$locale],
                    'description' => $data['meta']['description'][$locale],
                    'keywords' => $data['meta']['keywords'][$locale],
                ]
            );
        }

        $this->command?->line('  - haqqimizda melumati yenilendi');
    }
}
