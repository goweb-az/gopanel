<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Seo\PageMetaData;
use App\Models\Site\Blog;
use App\Models\Translations\FieldTranslation;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'Blog';

    public function run()
    {
        $blogs = [
            [
                'image'     => $this->placeholderImage('blogs', 'Website Development', 800, 520),
                'date_time' => now()->subDays(1)->format('Y-m-d H:i:s'),
                'is_active' => true,
                'views'     => rand(10, 500),
                'title'     => [
                    'az' => 'Veb sayt hazırlanmasının əsas mərhələləri',
                    'en' => 'Key stages of website development',
                    'ru' => 'Основные этапы создания веб-сайта',
                ],
                'slug'      => [
                    'az' => 'veb-sayt-hazirlanmasinin-esas-merhelele',
                    'en' => 'key-stages-of-website-development',
                    'ru' => 'osnovnye-etapy-sozdaniya-veb-sajta',
                ],
                'description' => [
                    'az' => '<p>Veb sayt hazırlanması prosesi bir neçə mühüm mərhələni əhatə edir. İlk addım müştəri ilə görüşmə, tələblərin müəyyənləşdirilməsi və texniki tapşırığın hazırlanması ilə başlayır.</p><p>Dizayn mərhələsində UI/UX prinsipləri əsasında wireframe və mockup-lar hazırlanır. Frontend inkişaf mərhələsində isə responsive və cross-browser uyğun kod yazılır.</p><p>Backend inkişaf, verilənlər bazası arxitekturası və API inteqrasiyaları da bu prosesinn vacib hissəsidir.</p>',
                    'en' => '<p>The website development process covers several important stages. The first step begins with meeting the client, defining requirements and preparing technical specifications.</p><p>At the design stage, wireframes and mockups are prepared based on UI/UX principles. The frontend development stage involves writing responsive and cross-browser compatible code.</p><p>Backend development, database architecture and API integrations are also important parts of this process.</p>',
                    'ru' => '<p>Процесс создания веб-сайта охватывает несколько важных этапов. Первый шаг начинается с встречи с клиентом, определения требований и подготовки технического задания.</p><p>На этапе дизайна подготавливаются wireframes и макеты на основе принципов UI/UX. На этапе фронтенд-разработки пишется адаптивный и кроссбраузерный код.</p>',
                ],
                'meta' => [
                    'title' => 'Veb sayt hazırlanması | Blog',
                    'description' => 'Veb sayt hazırlanmasının əsas mərhələləri haqqında ətraflı məqalə',
                    'keywords' => 'veb sayt, web development, sayt hazırlanması',
                ],
            ],
            [
                'image'     => $this->placeholderImage('blogs', 'SEO Optimization', 800, 520),
                'date_time' => now()->subDays(3)->format('Y-m-d H:i:s'),
                'is_active' => true,
                'views'     => rand(10, 500),
                'title'     => [
                    'az' => 'SEO optimizasiya: Google-da üst sıralarda yer almaq',
                    'en' => 'SEO optimization: Ranking at the top of Google',
                    'ru' => 'SEO оптимизация: попасть в топ Google',
                ],
                'slug'      => [
                    'az' => 'seo-optimizasiya-googleda-ust-siralarda',
                    'en' => 'seo-optimization-ranking-top-google',
                    'ru' => 'seo-optimizaciya-popast-v-top-google',
                ],
                'description' => [
                    'az' => '<p>SEO (Search Engine Optimization) veb saytınızın axtarış motorlarında daha yüksək sıralarda yer almasını təmin edən texnikalar toplusudur.</p><p>On-page SEO, meta teqlər, başlıq iyerarxiyası, daxili linklər və content optimizasiyasını əhatə edir. Off-page SEO isə backlink strategiyası, sosial siqnallar və domain autoritetini artıran üsulları nəzərdə tutur.</p>',
                    'en' => '<p>SEO (Search Engine Optimization) is a set of techniques that ensure your website ranks higher in search engines.</p><p>On-page SEO covers meta tags, heading hierarchy, internal links and content optimization. Off-page SEO refers to backlink strategy, social signals and methods to increase domain authority.</p>',
                    'ru' => '<p>SEO (Search Engine Optimization) — это набор методов, обеспечивающих более высокий рейтинг вашего сайта в поисковых системах.</p><p>On-page SEO охватывает мета-теги, иерархию заголовков, внутренние ссылки и оптимизацию контента.</p>',
                ],
                'meta' => [
                    'title' => 'SEO optimizasiya | Blog',
                    'description' => 'SEO optimizasiya haqqında ətraflı bələdçi - Google-da üst sıralarda yer almaq üçün',
                    'keywords' => 'seo, google, axtarış motoru, optimizasiya',
                ],
            ],
            [
                'image'     => $this->placeholderImage('blogs', 'Mobile App Development', 800, 520),
                'date_time' => now()->subDays(5)->format('Y-m-d H:i:s'),
                'is_active' => true,
                'views'     => rand(10, 500),
                'title'     => [
                    'az' => 'Mobil tətbiq inkişafında müasir yanaşmalar',
                    'en' => 'Modern approaches in mobile app development',
                    'ru' => 'Современные подходы в разработке мобильных приложений',
                ],
                'slug'      => [
                    'az' => 'mobil-tetbiq-inkishafinda-muasir-yanashmalar',
                    'en' => 'modern-approaches-mobile-app-development',
                    'ru' => 'sovremennye-podhody-razrabotke-mobilnyh',
                ],
                'description' => [
                    'az' => '<p>Mobil tətbiq inkişafı son illərdə böyük transformasiya keçirib. Native, hybrid və cross-platform yanaşmalar arasında seçim etmək layihənin tələblərindən asılıdır.</p><p>Flutter və React Native kimi framework-lər bir kod bazasından həm iOS, həm Android üçün tətbiq yaratmağa imkan verir.</p>',
                    'en' => '<p>Mobile app development has undergone a major transformation in recent years. Choosing between native, hybrid and cross-platform approaches depends on project requirements.</p><p>Frameworks like Flutter and React Native allow creating apps for both iOS and Android from a single codebase.</p>',
                    'ru' => '<p>Разработка мобильных приложений претерпела серьезную трансформацию в последние годы. Выбор между нативным, гибридным и кроссплатформенным подходами зависит от требований проекта.</p>',
                ],
                'meta' => [
                    'title' => 'Mobil tətbiq inkişafı | Blog',
                    'description' => 'Mobil tətbiq inkişafında müasir yanaşmalar - Flutter, React Native və native development',
                    'keywords' => 'mobil tətbiq, flutter, react native, ios, android',
                ],
            ],
            [
                'image'     => $this->placeholderImage('blogs', 'CRM Systems', 800, 520),
                'date_time' => now()->subDays(7)->format('Y-m-d H:i:s'),
                'is_active' => true,
                'views'     => rand(10, 500),
                'title'     => [
                    'az' => 'CRM sistemləri biznesi necə dəyişir?',
                    'en' => 'How CRM systems transform business?',
                    'ru' => 'Как CRM системы меняют бизнес?',
                ],
                'slug'      => [
                    'az' => 'crm-sistemleri-biznesi-nece-deyishir',
                    'en' => 'how-crm-systems-transform-business',
                    'ru' => 'kak-crm-sistemy-menyayut-biznes',
                ],
                'description' => [
                    'az' => '<p>CRM (Customer Relationship Management) sistemləri müştəri münasibətlərinin idarə edilməsi üçün əvəzolunmaz alətdir.</p><p>Müasir CRM həlləri satış, marketinq və müştəri dəstəyi proseslərini avtomatlaşdırır, analitik hesabatlar təqdim edir və komanda əməkdaşlığını gücləndirir.</p>',
                    'en' => '<p>CRM (Customer Relationship Management) systems are indispensable tools for managing customer relationships.</p><p>Modern CRM solutions automate sales, marketing and customer support processes, provide analytical reports and strengthen team collaboration.</p>',
                    'ru' => '<p>CRM (Customer Relationship Management) системы являются незаменимым инструментом для управления взаимоотношениями с клиентами.</p>',
                ],
                'meta' => [
                    'title' => 'CRM sistemləri | Blog',
                    'description' => 'CRM sistemlərinin biznesə təsiri və müasir CRM həlləri haqqında',
                    'keywords' => 'crm, müştəri, biznes, avtomatlaşdırma',
                ],
            ],
        ];

        foreach ($blogs as $data) {
            $blog = $this->findBlogBySlug($data['slug']['az'] ?? null);

            if ($blog) {
                if (method_exists($blog, 'restore') && $blog->trashed()) {
                    $blog->restore();
                }

                $blog->update([
                    'image'     => $data['image'],
                    'date_time' => $data['date_time'],
                    'is_active' => $data['is_active'],
                    'views'     => $data['views'],
                ]);
                $this->command?->line('  - movcuddur, yenilendi: ' . ($data['slug']['az'] ?? '-'));
            } else {
                $blog = Blog::create([
                    'image'     => $data['image'],
                    'date_time' => $data['date_time'],
                    'is_active' => $data['is_active'],
                    'views'     => $data['views'],
                ]);
                $this->command?->line('  + elave edildi: ' . ($data['slug']['az'] ?? '-'));
            }

            TranslationHelper::basic($blog, $data['title'], 'title');
            TranslationHelper::basic($blog, $data['slug'], 'slug');
            TranslationHelper::basic($blog, $data['description'], 'description');

            // Meta data
            if (isset($data['meta'])) {
                PageMetaData::updateOrCreate(
                    [
                        'model_type' => Blog::class,
                        'model_id'   => $blog->id,
                        'locale'     => 'az',
                    ],
                    [
                        'title'       => $data['meta']['title'],
                        'description' => $data['meta']['description'],
                        'keywords'    => $data['meta']['keywords'],
                    ]
                );
            }
        }
    }

    private function findBlogBySlug(?string $slug): ?Blog
    {
        if (!$slug) {
            return null;
        }

        $translation = FieldTranslation::where('model_type', Blog::class)
            ->where('key', 'slug')
            ->where('locale', 'az')
            ->where('value', $slug)
            ->first();

        return $translation ? Blog::withTrashed()->find($translation->model_id) : null;
    }
}
