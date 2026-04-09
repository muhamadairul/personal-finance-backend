<?php

return [
    'plans' => [
        'monthly' => [
            'id'            => 'monthly',
            'name'          => 'Bulanan',
            'price'         => 15000,
            'duration_days' => 30,
            'description'   => 'Akses semua fitur Pro selama 1 bulan',
            'benefits'      => [
                'Dompet tanpa batas',
                'Kategori kustom',
                'Ekspor laporan PDF & Excel',
                'Tanpa iklan',
            ],
        ],
        'yearly' => [
            'id'            => 'yearly',
            'name'          => 'Tahunan',
            'price'         => 150000,
            'duration_days' => 365,
            'description'   => 'Akses semua fitur Pro selama 1 tahun — hemat 17%!',
            'save_percent'  => 17,
            'benefits'      => [
                'Dompet tanpa batas',
                'Kategori kustom',
                'Ekspor laporan PDF & Excel',
                'Tanpa iklan',
            ],
        ],
    ],
    'invoice_duration' => 86400, // 24 jam (detik) sebelum invoice expired
];
