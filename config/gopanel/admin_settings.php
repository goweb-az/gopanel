<?php

/**
 * Adminin ŞƏXSİ tənzimləmələrinin VAHİD MƏNBƏYİ (tipli key-value).
 *
 * Oxu/yazı: `App\Services\Settings\AdminSettingsService`
 * Tərif sinfi: `App\Support\Settings\AdminSettingDefinitions`
 * Panel: Profil → «Şəxsi tənzimləmələr» (`gopanel.profile.settings.index`) -
 * bu massiv boşdursa link görünmür.
 *
 * Dəyərlər `setting_values` cədvəlində saxlanılır (scope = admin). Sətir
 * yoxdursa `default` qüvvədədir - yeni açar üçün miqrasiya/backfill LAZIM
 * DEYİL: bura bir blok yazmaq kifayətdir.
 *
 * Sahələr: type (bool|int|float|string|text|select|json), default, label,
 * hint, group, rules, min, max, options. Tam izah: docs/authors-settings.md
 *
 * Starter-də qəsdən BOŞDUR: istifadə olunmayan tənzimləmə paneldə «işləmir»
 * təəssüratı yaradır. Törəmə layihə öz açarlarını yazır, məsələn:
 *
 *   'notify_new_order' => [
 *       'type'    => 'bool',
 *       'default' => true,
 *       'group'   => 'Bildirişlər',
 *       'label'   => 'Yeni sifariş bildirişi',
 *       'hint'    => 'Yeni sifariş daxil olanda bildiriş gəlsin',
 *   ],
 *
 *   'table_page_length' => [
 *       'type'    => 'select',
 *       'default' => '25',
 *       'group'   => 'Görünüş',
 *       'label'   => 'Cədvəldə sətir sayı',
 *       'options' => ['10' => '10', '25' => '25', '50' => '50', '100' => '100'],
 *   ],
 */
return [

];
