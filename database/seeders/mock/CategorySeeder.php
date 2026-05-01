<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Navigation\Category;
use App\Models\Seo\PageMetaData;
use App\Models\Translations\FieldTranslation;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'Category';

    public function run(): void
    {
        $categories = [
            [
                'icon' => 'fas fa-laptop-code',
                'color' => '#556ee6',
                'sort_order' => 1,
                'show_in_home' => true,
                'show_in_menu' => true,
                'home_order' => 1,
                'name' => [
                    'az' => 'Texnologiya',
                    'en' => 'Technology',
                    'ru' => 'Technology',
                ],
                'slug' => [
                    'az' => 'texnologiya',
                    'en' => 'technology',
                    'ru' => 'technology',
                ],
                'description' => [
                    'az' => 'Texnologiya xeberleri ve meqaleleri',
                    'en' => 'Technology news and articles',
                    'ru' => 'Technology news and articles',
                ],
                'children' => [
                    [
                        'icon' => 'fas fa-code',
                        'color' => '#34c38f',
                        'sort_order' => 1,
                        'name' => [
                            'az' => 'Proqramlasdirma',
                            'en' => 'Programming',
                            'ru' => 'Programming',
                        ],
                        'slug' => [
                            'az' => 'proqramlasdirma',
                            'en' => 'programming',
                            'ru' => 'programming',
                        ],
                        'description' => [
                            'az' => 'Kodlasdirma ve proqram temalari',
                            'en' => 'Coding and software topics',
                            'ru' => 'Coding and software topics',
                        ],
                    ],
                    [
                        'icon' => 'fas fa-shield-alt',
                        'color' => '#f46a6a',
                        'sort_order' => 2,
                        'name' => [
                            'az' => 'Kiber tehlukesizlik',
                            'en' => 'Cybersecurity',
                            'ru' => 'Cybersecurity',
                        ],
                        'slug' => [
                            'az' => 'kiber-tehlukesizlik',
                            'en' => 'cybersecurity',
                            'ru' => 'cybersecurity',
                        ],
                        'description' => [
                            'az' => 'Tehlukesizlik meslehetleri',
                            'en' => 'Security tips and guidance',
                            'ru' => 'Security tips and guidance',
                        ],
                    ],
                ],
            ],
            [
                'icon' => 'fas fa-briefcase',
                'color' => '#f1b44c',
                'sort_order' => 2,
                'show_in_home' => true,
                'show_in_menu' => true,
                'home_order' => 2,
                'name' => [
                    'az' => 'Biznes',
                    'en' => 'Business',
                    'ru' => 'Business',
                ],
                'slug' => [
                    'az' => 'biznes',
                    'en' => 'business',
                    'ru' => 'business',
                ],
                'description' => [
                    'az' => 'Biznes ve startap movzulari',
                    'en' => 'Business and startup topics',
                    'ru' => 'Business and startup topics',
                ],
                'children' => [
                    [
                        'icon' => 'fas fa-chart-line',
                        'color' => '#34c38f',
                        'sort_order' => 1,
                        'name' => ['az' => 'Startaplar', 'en' => 'Startups', 'ru' => 'Startups'],
                        'slug' => ['az' => 'startaplar', 'en' => 'startups', 'ru' => 'startups'],
                        'description' => ['az' => 'Startap ideyalari ve inkisaf', 'en' => 'Startup ideas and growth', 'ru' => 'Startup ideas and growth'],
                    ],
                    [
                        'icon' => 'fas fa-coins',
                        'color' => '#f1b44c',
                        'sort_order' => 2,
                        'name' => ['az' => 'Maliyye', 'en' => 'Finance', 'ru' => 'Finance'],
                        'slug' => ['az' => 'maliyye', 'en' => 'finance', 'ru' => 'finance'],
                        'description' => ['az' => 'Maliyye ve investisiya movzulari', 'en' => 'Finance and investment topics', 'ru' => 'Finance and investment topics'],
                    ],
                    [
                        'icon' => 'fas fa-users',
                        'color' => '#50a5f1',
                        'sort_order' => 3,
                        'name' => ['az' => 'Idareetme', 'en' => 'Management', 'ru' => 'Management'],
                        'slug' => ['az' => 'idareetme', 'en' => 'management', 'ru' => 'management'],
                        'description' => ['az' => 'Komanda ve proses idareetmesi', 'en' => 'Team and process management', 'ru' => 'Team and process management'],
                    ],
                ],
            ],
            [
                'icon' => 'fas fa-bullhorn',
                'color' => '#ff6f61',
                'sort_order' => 3,
                'show_in_home' => true,
                'show_in_menu' => true,
                'home_order' => 3,
                'name' => ['az' => 'Marketinq', 'en' => 'Marketing', 'ru' => 'Marketing'],
                'slug' => ['az' => 'marketinq', 'en' => 'marketing', 'ru' => 'marketing'],
                'description' => ['az' => 'Marketinq strategiyalari', 'en' => 'Marketing strategies', 'ru' => 'Marketing strategies'],
                'children' => [
                    [
                        'icon' => 'fas fa-hashtag',
                        'color' => '#556ee6',
                        'sort_order' => 1,
                        'name' => ['az' => 'Sosial media', 'en' => 'Social media', 'ru' => 'Social media'],
                        'slug' => ['az' => 'sosial-media', 'en' => 'social-media', 'ru' => 'social-media'],
                        'description' => ['az' => 'Sosial media planlama', 'en' => 'Social media planning', 'ru' => 'Social media planning'],
                    ],
                    [
                        'icon' => 'fas fa-search',
                        'color' => '#34c38f',
                        'sort_order' => 2,
                        'name' => ['az' => 'SEO', 'en' => 'SEO', 'ru' => 'SEO'],
                        'slug' => ['az' => 'seo', 'en' => 'seo', 'ru' => 'seo'],
                        'description' => ['az' => 'Axtaris optimizasiyasi', 'en' => 'Search optimization', 'ru' => 'Search optimization'],
                    ],
                    [
                        'icon' => 'fas fa-envelope-open-text',
                        'color' => '#f46a6a',
                        'sort_order' => 3,
                        'name' => ['az' => 'Email marketinq', 'en' => 'Email marketing', 'ru' => 'Email marketing'],
                        'slug' => ['az' => 'email-marketinq', 'en' => 'email-marketing', 'ru' => 'email-marketing'],
                        'description' => ['az' => 'Email kampaniyalari', 'en' => 'Email campaigns', 'ru' => 'Email campaigns'],
                    ],
                ],
            ],
            [
                'icon' => 'fas fa-palette',
                'color' => '#9b59b6',
                'sort_order' => 4,
                'show_in_home' => true,
                'show_in_menu' => true,
                'home_order' => 4,
                'name' => ['az' => 'Dizayn', 'en' => 'Design', 'ru' => 'Design'],
                'slug' => ['az' => 'dizayn', 'en' => 'design', 'ru' => 'design'],
                'description' => ['az' => 'Dizayn ve UX movzulari', 'en' => 'Design and UX topics', 'ru' => 'Design and UX topics'],
                'children' => [
                    [
                        'icon' => 'fas fa-object-group',
                        'color' => '#50a5f1',
                        'sort_order' => 1,
                        'name' => ['az' => 'UI dizayn', 'en' => 'UI design', 'ru' => 'UI design'],
                        'slug' => ['az' => 'ui-dizayn', 'en' => 'ui-design', 'ru' => 'ui-design'],
                        'description' => ['az' => 'Interfeys dizayni', 'en' => 'Interface design', 'ru' => 'Interface design'],
                    ],
                    [
                        'icon' => 'fas fa-route',
                        'color' => '#34c38f',
                        'sort_order' => 2,
                        'name' => ['az' => 'UX araşdırma', 'en' => 'UX research', 'ru' => 'UX research'],
                        'slug' => ['az' => 'ux-arasdirma', 'en' => 'ux-research', 'ru' => 'ux-research'],
                        'description' => ['az' => 'Istifadeci tecrubesi arasdirmasi', 'en' => 'User experience research', 'ru' => 'User experience research'],
                    ],
                ],
            ],
            [
                'icon' => 'fas fa-graduation-cap',
                'color' => '#34c38f',
                'sort_order' => 5,
                'show_in_home' => false,
                'show_in_menu' => true,
                'home_order' => 5,
                'name' => ['az' => 'Tehsil', 'en' => 'Education', 'ru' => 'Education'],
                'slug' => ['az' => 'tehsil', 'en' => 'education', 'ru' => 'education'],
                'description' => ['az' => 'Tehsil ve telim materiallari', 'en' => 'Education and learning materials', 'ru' => 'Education and learning materials'],
                'children' => [
                    [
                        'icon' => 'fas fa-book-open',
                        'color' => '#f1b44c',
                        'sort_order' => 1,
                        'name' => ['az' => 'Kurslar', 'en' => 'Courses', 'ru' => 'Courses'],
                        'slug' => ['az' => 'kurslar', 'en' => 'courses', 'ru' => 'courses'],
                        'description' => ['az' => 'Online ve offline kurslar', 'en' => 'Online and offline courses', 'ru' => 'Online and offline courses'],
                    ],
                    [
                        'icon' => 'fas fa-lightbulb',
                        'color' => '#556ee6',
                        'sort_order' => 2,
                        'name' => ['az' => 'Meslehetler', 'en' => 'Tips', 'ru' => 'Tips'],
                        'slug' => ['az' => 'meslehetler', 'en' => 'tips', 'ru' => 'tips'],
                        'description' => ['az' => 'Oyrenme meslehetleri', 'en' => 'Learning tips', 'ru' => 'Learning tips'],
                    ],
                ],
            ],
            [
                'icon' => 'fas fa-heartbeat',
                'color' => '#f46a6a',
                'sort_order' => 6,
                'show_in_home' => false,
                'show_in_menu' => true,
                'home_order' => 6,
                'name' => ['az' => 'Saglamliq', 'en' => 'Health', 'ru' => 'Health'],
                'slug' => ['az' => 'saglamliq', 'en' => 'health', 'ru' => 'health'],
                'description' => ['az' => 'Saglam heyat movzulari', 'en' => 'Healthy lifestyle topics', 'ru' => 'Healthy lifestyle topics'],
                'children' => [
                    [
                        'icon' => 'fas fa-running',
                        'color' => '#34c38f',
                        'sort_order' => 1,
                        'name' => ['az' => 'Fitness', 'en' => 'Fitness', 'ru' => 'Fitness'],
                        'slug' => ['az' => 'fitness', 'en' => 'fitness', 'ru' => 'fitness'],
                        'description' => ['az' => 'Mesq ve aktiv heyat', 'en' => 'Training and active life', 'ru' => 'Training and active life'],
                    ],
                    [
                        'icon' => 'fas fa-apple-alt',
                        'color' => '#f1b44c',
                        'sort_order' => 2,
                        'name' => ['az' => 'Qidalanma', 'en' => 'Nutrition', 'ru' => 'Nutrition'],
                        'slug' => ['az' => 'qidalanma', 'en' => 'nutrition', 'ru' => 'nutrition'],
                        'description' => ['az' => 'Saglam qidalanma', 'en' => 'Healthy nutrition', 'ru' => 'Healthy nutrition'],
                    ],
                ],
            ],
        ];

        foreach ($categories as $data) {
            $parent = $this->saveCategory($data);

            foreach ($data['children'] as $childData) {
                $childData['parent_id'] = $parent->id;
                $this->saveCategory($childData);
            }
        }
    }

    private function saveCategory(array $data): Category
    {
        $category = $this->findCategoryBySlug($data['slug']['az'] ?? null);
        $payload = [
            'parent_id' => $data['parent_id'] ?? null,
            'icon' => $data['icon'] ?? null,
            'color' => $data['color'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
            'is_active' => $data['is_active'] ?? true,
            'show_in_home' => $data['show_in_home'] ?? false,
            'show_in_menu' => $data['show_in_menu'] ?? true,
            'home_order' => $data['home_order'] ?? 0,
        ];

        if ($category) {
            if (method_exists($category, 'restore') && $category->trashed()) {
                $category->restore();
            }

            $category->update($payload);
            $this->command?->line('  - movcuddur, yenilendi: ' . ($data['slug']['az'] ?? '-'));
        } else {
            $category = Category::create($payload);
            $this->command?->line('  + elave edildi: ' . ($data['slug']['az'] ?? '-'));
        }

        TranslationHelper::basic($category, $data['name'], 'name');
        TranslationHelper::basic($category, $data['slug'], 'slug');
        TranslationHelper::basic($category, $data['description'], 'description');
        $this->saveMetaData($category, $data);

        return $category;
    }

    private function saveMetaData(Category $category, array $data): void
    {
        foreach (($data['name'] ?? []) as $locale => $name) {
            $description = $data['description'][$locale] ?? $data['description']['az'] ?? null;
            $slug = $data['slug'][$locale] ?? $data['slug']['az'] ?? null;

            PageMetaData::updateOrCreate(
                [
                    'model_type' => Category::class,
                    'model_id' => $category->id,
                    'locale' => $locale,
                ],
                [
                    'source' => $category->getTable(),
                    'title' => $data['meta']['title'][$locale] ?? "{$name} | Kateqoriya",
                    'description' => $data['meta']['description'][$locale] ?? $description,
                    'keywords' => $data['meta']['keywords'][$locale] ?? implode(', ', array_filter([$name, $slug, 'kateqoriya'])),
                    'image' => $data['meta']['image'][$locale] ?? $this->placeholderImage('categories', $name, 600, 600),
                ]
            );
        }
    }

    private function findCategoryBySlug(?string $slug): ?Category
    {
        if (!$slug) {
            return null;
        }

        $translation = FieldTranslation::where('model_type', Category::class)
            ->where('key', 'slug')
            ->where('locale', 'az')
            ->where('value', $slug)
            ->first();

        return $translation ? Category::withTrashed()->find($translation->model_id) : null;
    }
}
