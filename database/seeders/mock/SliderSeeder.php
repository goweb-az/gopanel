<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Site\Slider;
use App\Models\Translations\FieldTranslation;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class SliderSeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'Slider';

    public function run(): void
    {
        $sliders = [
            [
                'sort_order' => 1,
                'is_active'  => true,
                'link'       => '/services',
                'image'      => $this->placeholderImage('sliders', 'Modern Web Solutions', 1600, 700),
                'title' => [
                    'az' => 'Müasir veb həllər',
                    'en' => 'Modern web solutions',
                    'ru' => 'Современные веб-решения',
                ],
                'description' => [
                    'az' => 'Sürətli, təhlükəsiz və SEO uyğun saytlar.',
                    'en' => 'Fast, secure and SEO-friendly websites.',
                    'ru' => 'Быстрые, безопасные и SEO-оптимизированные сайты.',
                ],
                'link_title' => [
                    'az' => 'Daha ətraflı',
                    'en' => 'Learn more',
                    'ru' => 'Узнать больше',
                ],
            ],
            [
                'sort_order' => 2,
                'is_active'  => true,
                'link'       => '/about-us',
                'image'      => $this->placeholderImage('sliders', 'Our Mission', 1600, 700),
                'title' => [
                    'az' => 'Bizim missiyamız',
                    'en' => 'Our mission',
                    'ru' => 'Наша миссия',
                ],
                'description' => [
                    'az' => 'Biznesinizi rəqəmsal məkanda gücləndirmək.',
                    'en' => 'Empower your business in the digital space.',
                    'ru' => 'Усиливаем ваш бизнес в цифровом пространстве.',
                ],
                'link_title' => [
                    'az' => 'Haqqımızda',
                    'en' => 'About us',
                    'ru' => 'О нас',
                ],
            ],
            [
                'sort_order' => 3,
                'is_active'  => true,
                'link'       => '/contact',
                'image'      => $this->placeholderImage('sliders', 'Get In Touch', 1600, 700),
                'title' => [
                    'az' => 'Bizimlə əlaqə saxlayın',
                    'en' => 'Get in touch',
                    'ru' => 'Свяжитесь с нами',
                ],
                'description' => [
                    'az' => 'İdeyalarınızı reallığa çevirək.',
                    'en' => 'Let\'s turn your ideas into reality.',
                    'ru' => 'Превратим ваши идеи в реальность.',
                ],
                'link_title' => [
                    'az' => 'Əlaqə',
                    'en' => 'Contact',
                    'ru' => 'Контакт',
                ],
            ],
        ];

        foreach ($sliders as $data) {
            $slider = $this->findSliderByTitle($data['title']['az']);
            $payload = [
                'sort_order' => $data['sort_order'],
                'is_active'  => $data['is_active'],
                'link'       => $data['link'],
                'image'      => $data['image'],
            ];

            if ($slider) {
                if (method_exists($slider, 'restore') && $slider->trashed()) {
                    $slider->restore();
                }
                $slider->update($payload);
                $this->command?->line('  - movcuddur, yenilendi: ' . $data['title']['az']);
            } else {
                $slider = Slider::create($payload);
                $this->command?->line('  + elave edildi: ' . $data['title']['az']);
            }

            TranslationHelper::basic($slider, $data['title'], 'title');
            TranslationHelper::basic($slider, $data['description'], 'description');
            TranslationHelper::basic($slider, $data['link_title'], 'link_title');
        }
    }

    private function findSliderByTitle(string $title): ?Slider
    {
        $translation = FieldTranslation::where('model_type', Slider::class)
            ->where('key', 'title')
            ->where('locale', 'az')
            ->where('value', $title)
            ->first();

        return $translation ? Slider::withTrashed()->find($translation->model_id) : null;
    }
}
