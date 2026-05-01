<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Seo\PageMetaData;
use App\Models\Site\Service;
use App\Models\Translations\FieldTranslation;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'Services';

    public function run(): void
    {
        $services = [
            [
                'sort_order' => 1,
                'icon_type' => 'font',
                'icon' => 'fas fa-code',
                'image' => $this->placeholderImage('services', 'Website Development', 800, 520),
                'title' => ['az' => 'Veb sayt hazırlanması', 'en' => 'Website development', 'ru' => 'Website development'],
                'short_description' => ['az' => 'Korporativ və funksional veb saytların hazırlanması.', 'en' => 'Corporate and functional website development.', 'ru' => 'Corporate and functional website development.'],
                'description' => ['az' => '<p>Biznesinizə uyğun sürətli, təhlükəsiz və idarə edilə bilən veb saytlar hazırlayırıq.</p>', 'en' => '<p>We build fast, secure and manageable websites tailored to your business.</p>', 'ru' => '<p>We build fast, secure and manageable websites tailored to your business.</p>'],
            ],
            [
                'sort_order' => 2,
                'icon_type' => 'font',
                'icon' => 'fas fa-mobile-alt',
                'image' => $this->placeholderImage('services', 'Mobile Applications', 800, 520),
                'title' => ['az' => 'Mobil tətbiqlər', 'en' => 'Mobile applications', 'ru' => 'Mobile applications'],
                'short_description' => ['az' => 'iOS və Android üçün müasir mobil tətbiqlər.', 'en' => 'Modern mobile apps for iOS and Android.', 'ru' => 'Modern mobile apps for iOS and Android.'],
                'description' => ['az' => '<p>İstifadəçi yönümlü, stabil və genişlənə bilən mobil tətbiq həlləri təqdim edirik.</p>', 'en' => '<p>We deliver user-focused, stable and scalable mobile application solutions.</p>', 'ru' => '<p>We deliver user-focused, stable and scalable mobile application solutions.</p>'],
            ],
            [
                'sort_order' => 3,
                'icon_type' => 'font',
                'icon' => 'fas fa-search',
                'image' => $this->placeholderImage('services', 'SEO Optimization', 800, 520),
                'title' => ['az' => 'SEO optimizasiya', 'en' => 'SEO optimization', 'ru' => 'SEO optimization'],
                'short_description' => ['az' => 'Axtarış sistemlərində görünürlüğün artırılması.', 'en' => 'Improving visibility in search engines.', 'ru' => 'Improving visibility in search engines.'],
                'description' => ['az' => '<p>Texniki SEO, kontent optimizasiyası və analitik izləmə ilə saytınızı gücləndiririk.</p>', 'en' => '<p>We strengthen your website with technical SEO, content optimization and analytics tracking.</p>', 'ru' => '<p>We strengthen your website with technical SEO, content optimization and analytics tracking.</p>'],
            ],
            [
                'sort_order' => 4,
                'icon_type' => 'font',
                'icon' => 'fas fa-chart-line',
                'image' => $this->placeholderImage('services', 'Digital Marketing', 800, 520),
                'title' => ['az' => 'Rəqəmsal marketinq', 'en' => 'Digital marketing', 'ru' => 'Digital marketing'],
                'short_description' => ['az' => 'Kampaniya planlama və performans izləmə.', 'en' => 'Campaign planning and performance tracking.', 'ru' => 'Campaign planning and performance tracking.'],
                'description' => ['az' => '<p>Hədəf auditoriyanıza çatmaq üçün ölçülə bilən marketinq strategiyaları qururuq.</p>', 'en' => '<p>We build measurable marketing strategies to reach your target audience.</p>', 'ru' => '<p>We build measurable marketing strategies to reach your target audience.</p>'],
            ],
        ];

        foreach ($services as $data) {
            $service = $this->findServiceByTitle($data['title']['az']);
            $payload = [
                'sort_order' => $data['sort_order'],
                'icon_type' => $data['icon_type'],
                'icon' => $data['icon'],
                'image' => $data['image'],
            ];

            if ($service) {
                if (method_exists($service, 'restore') && $service->trashed()) {
                    $service->restore();
                }
                $service->update($payload);
                $this->command?->line('  - movcuddur, yenilendi: ' . $data['title']['az']);
            } else {
                $service = Service::create($payload);
                $this->command?->line('  + elave edildi: ' . $data['title']['az']);
            }

            TranslationHelper::basic($service, $data['title'], 'title');
            TranslationHelper::basic($service, $data['short_description'], 'short_description');
            TranslationHelper::basic($service, $data['description'], 'description');
            $this->saveMetaData($service, $data);
        }
    }

    private function saveMetaData(Service $service, array $data): void
    {
        foreach (($data['title'] ?? []) as $locale => $title) {
            PageMetaData::updateOrCreate(
                [
                    'model_type' => Service::class,
                    'model_id' => $service->id,
                    'locale' => $locale,
                ],
                [
                    'source' => $service->getTable(),
                    'title' => "{$title} | Xidmət",
                    'description' => $data['short_description'][$locale] ?? null,
                    'keywords' => implode(', ', array_filter([$title, 'xidmət', 'service'])),
                ]
            );
        }
    }

    private function findServiceByTitle(string $title): ?Service
    {
        $translation = FieldTranslation::where('model_type', Service::class)
            ->where('key', 'title')
            ->where('locale', 'az')
            ->where('value', $title)
            ->first();

        return $translation ? Service::withTrashed()->find($translation->model_id) : null;
    }
}
