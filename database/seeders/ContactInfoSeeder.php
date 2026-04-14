<?php

namespace Database\Seeders;

use App\Helpers\Gopanel\TranslationHelper;
use App\Models\Contact\ContactInfo;
use App\Models\Navigation\Menu;
use App\Models\Service\Service;
use App\Models\Translations\Translation;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class ContactInfoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        if (!ContactInfo::query()->exists()) {
            $data = $this->data();
            $contactInfo = ContactInfo::create(Arr::except($data, ['page_title', 'page_description', 'adress']));

            foreach (['page_title', 'page_description', 'adress'] as $key) {
                TranslationHelper::basic($contactInfo, $data[$key], $key);
            }
        }
    }



    private function data()
    {
        return [
            "phone"             => null,
            "mobile"            => "+99455 272-51-95",
            "whatsapp"          => "+99455 272-51-95",
            "info_email"        => "hello@proweb.az",
            "support_email"     => "support@proweb.az",
            "map"               => null,
            'page_title'        => [
                'az' => 'Gəlin <span>birlikdə</span> inkişaf edək',
                'en' => 'Let’s Grow <span>Together</span>',
                'ru' => 'Давайте развиваться <span>вместе</span>',
            ],
            'page_description'         => [
                'az' => "Bu gün bizimlə əlaqə saxlayın və biznesinizi növbəti səviyyəyə daşıyacaq fərdi həll üzərində əməkdaşlığa başlayaq.",
                'en' => "Contact us today and let's start collaborating on a customized solution that will take your business to the next level.",
                'ru' => "Свяжитесь с нами сегодня, и давайте начнем сотрудничество над индивидуальным решением, которое выведет ваш бизнес на новый уровень.",
            ],
            'adress'         => [
                'az' => 'H. Əliyev prospekti 187C, ARENA Plaza, 12-ci mərtəbə',
                'en' => '187C H. Aliyev avenue, ARENA Plaza, 12th floor',
                'ru' => 'проспект Г. Алиева 187C, ARENA Plaza, 12-й этаж',
            ],
        ];
    }
}
