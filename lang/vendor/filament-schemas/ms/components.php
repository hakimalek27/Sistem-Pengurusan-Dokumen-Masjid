<?php

/*
|--------------------------------------------------------------------------
| Override terjemahan vendor `filament-schemas` (ms) — F3 §4.4
|--------------------------------------------------------------------------
|
| Vendor menghantar 'Seterus'/'Sebelum' (bentuk pendek). Bahasa Melayu
| Malaysia standard ialah 'Seterusnya'/'Sebelumnya' — dan itulah yang
| digunakan oleh pagination Laravel serta arahan dalam katalog panduan,
| jadi ejaan pendek membuatkan UI tidak konsisten dengan dirinya sendiri.
|
| Namespace disahkan: SchemasServiceProvider ->name('filament-schemas')
| + hasTranslations() → laluan override = lang/vendor/filament-schemas/ms/.
| Struktur mesti KEKAL sama dengan vendor; hanya nilai label ditukar.
|
*/

return [

    'wizard' => [

        'actions' => [

            'previous_step' => [
                'label' => 'Sebelumnya',
            ],

            'next_step' => [
                'label' => 'Seterusnya',
            ],

        ],

    ],

];
