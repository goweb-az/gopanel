<?php

namespace Database\Seeders\mock;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Seo\PageMetaData;
use App\Models\Site\Product;
use App\Models\Translations\FieldTranslation;
use Database\Seeders\mock\Concerns\CreatesPlaceholderImages;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    use CreatesPlaceholderImages;

    public string $mockName = 'Products';

    public function run(): void
    {
        $products = [
            [
                'price'    => 199.99,
                'discount' => 49.99,
                'image'    => $this->placeholderImage('products', 'Premium Headphones', 800, 800),
                'title' => [
                    'az' => 'Premium Qulaqlıq',
                    'en' => 'Premium Headphones',
                    'ru' => 'Премиум наушники',
                ],
                'short_description' => [
                    'az' => 'Aktiv səs-küy izolyasiyası ilə kabel-siz qulaqlıq.',
                    'en' => 'Wireless headphones with active noise cancellation.',
                    'ru' => 'Беспроводные наушники с активным шумоподавлением.',
                ],
                'description' => [
                    'az' => '<p>Yüksək keyfiyyətli səs, uzun ömürlü batareya və rahat dizayn.</p>',
                    'en' => '<p>High-quality sound, long-lasting battery and comfortable design.</p>',
                    'ru' => '<p>Высокое качество звука, долговечный аккумулятор и удобный дизайн.</p>',
                ],
            ],
            [
                'price'    => 79.50,
                'discount' => null,
                'image'    => $this->placeholderImage('products', 'Smart Watch', 800, 800),
                'title' => [
                    'az' => 'Smart saat',
                    'en' => 'Smart watch',
                    'ru' => 'Смарт-часы',
                ],
                'short_description' => [
                    'az' => 'Sağlamlıq və fitness izləyici funksiyaları.',
                    'en' => 'Health and fitness tracking features.',
                    'ru' => 'Функции отслеживания здоровья и фитнеса.',
                ],
                'description' => [
                    'az' => '<p>Ürək döyüntüsü, addım sayğacı və yuxu izləmə bir cihazda.</p>',
                    'en' => '<p>Heart rate monitor, pedometer and sleep tracking in one device.</p>',
                    'ru' => '<p>Пульсометр, шагомер и отслеживание сна в одном устройстве.</p>',
                ],
            ],
            [
                'price'    => 1299.00,
                'discount' => 199.00,
                'image'    => $this->placeholderImage('products', 'Laptop Pro', 800, 800),
                'title' => [
                    'az' => 'Laptop Pro 15"',
                    'en' => 'Laptop Pro 15"',
                    'ru' => 'Ноутбук Pro 15"',
                ],
                'short_description' => [
                    'az' => 'Peşəkar iş üçün güclü laptop.',
                    'en' => 'Powerful laptop for professional work.',
                    'ru' => 'Мощный ноутбук для профессиональной работы.',
                ],
                'description' => [
                    'az' => '<p>Müasir prosessor, sürətli SSD və yüksək keyfiyyətli ekran.</p>',
                    'en' => '<p>Modern processor, fast SSD and high-quality display.</p>',
                    'ru' => '<p>Современный процессор, быстрый SSD и качественный дисплей.</p>',
                ],
            ],
            [
                'price'    => 24.99,
                'discount' => 4.99,
                'image'    => $this->placeholderImage('products', 'Wireless Mouse', 800, 800),
                'title' => [
                    'az' => 'Kabel-siz Mouse',
                    'en' => 'Wireless Mouse',
                    'ru' => 'Беспроводная мышь',
                ],
                'short_description' => [
                    'az' => 'Erqonomik dizayn, uzun ömürlü batareya.',
                    'en' => 'Ergonomic design, long battery life.',
                    'ru' => 'Эргономичный дизайн, длительный срок службы батареи.',
                ],
                'description' => [
                    'az' => '<p>Sürətli reaksiya, dəqiq nişan alma və rahat tutuş.</p>',
                    'en' => '<p>Fast response, precise targeting and comfortable grip.</p>',
                    'ru' => '<p>Быстрый отклик, точное прицеливание и удобный хват.</p>',
                ],
            ],
            [
                'price'    => 149.00,
                'discount' => null,
                'image'    => $this->placeholderImage('products', 'Bluetooth Speaker', 800, 800),
                'title' => [
                    'az' => 'Bluetooth Səsucaldan',
                    'en' => 'Bluetooth Speaker',
                    'ru' => 'Bluetooth колонка',
                ],
                'short_description' => [
                    'az' => 'Səs-küylü mühitlər üçün güclü səs.',
                    'en' => 'Powerful sound for noisy environments.',
                    'ru' => 'Мощный звук для шумных помещений.',
                ],
                'description' => [
                    'az' => '<p>Sudan qoruyucu, daşına bilən və kompakt dizayn.</p>',
                    'en' => '<p>Water-resistant, portable and compact design.</p>',
                    'ru' => '<p>Водостойкий, портативный и компактный дизайн.</p>',
                ],
            ],
        ];

        foreach ($products as $data) {
            $product = $this->findProductByTitle($data['title']['az']);
            $payload = [
                'price'     => $data['price'],
                'discount'  => $data['discount'],
                'image'     => $data['image'],
                'is_active' => true,
            ];

            if ($product) {
                if (method_exists($product, 'restore') && $product->trashed()) {
                    $product->restore();
                }
                $product->update($payload);
                $this->command?->line('  - movcuddur, yenilendi: ' . $data['title']['az']);
            } else {
                $product = Product::create($payload);
                $this->command?->line('  + elave edildi: ' . $data['title']['az']);
            }

            TranslationHelper::basic($product, $data['title'], 'title');
            TranslationHelper::basic($product, $data['short_description'], 'short_description');
            TranslationHelper::basic($product, $data['description'], 'description');
            $this->saveMetaData($product, $data);
        }
    }

    private function saveMetaData(Product $product, array $data): void
    {
        foreach (($data['title'] ?? []) as $locale => $title) {
            PageMetaData::updateOrCreate(
                [
                    'model_type' => Product::class,
                    'model_id'   => $product->id,
                    'locale'     => $locale,
                ],
                [
                    'source'      => $product->getTable(),
                    'title'       => "{$title} | Məhsul",
                    'description' => $data['short_description'][$locale] ?? null,
                    'keywords'    => implode(', ', array_filter([$title, 'məhsul', 'product'])),
                ]
            );
        }
    }

    private function findProductByTitle(string $title): ?Product
    {
        $translation = FieldTranslation::where('model_type', Product::class)
            ->where('key', 'title')
            ->where('locale', 'az')
            ->where('value', $title)
            ->first();

        return $translation ? Product::withTrashed()->find($translation->model_id) : null;
    }
}
