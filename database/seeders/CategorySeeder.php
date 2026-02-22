<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            // Expense categories (matching Flutter's DefaultCategories)
            ['id' => 1,  'name' => 'Makan',        'icon' => 0xe532, 'color' => 0xFFFF6B6B, 'type' => 'expense'],
            ['id' => 2,  'name' => 'Transportasi',  'icon' => 0xe1d7, 'color' => 0xFF4ECDC4, 'type' => 'expense'],
            ['id' => 3,  'name' => 'Belanja',       'icon' => 0xf37c, 'color' => 0xFFFFBE0B, 'type' => 'expense'],
            ['id' => 4,  'name' => 'Tagihan',       'icon' => 0xe4c0, 'color' => 0xFF845EC2, 'type' => 'expense'],
            ['id' => 5,  'name' => 'Hiburan',       'icon' => 0xe40c, 'color' => 0xFFFF9671, 'type' => 'expense'],
            ['id' => 6,  'name' => 'Kesehatan',     'icon' => 0xf109, 'color' => 0xFF00C9A7, 'type' => 'expense'],
            ['id' => 7,  'name' => 'Pendidikan',    'icon' => 0xe559, 'color' => 0xFF4D8076, 'type' => 'expense'],
            ['id' => 8,  'name' => 'Lainnya',       'icon' => 0xe400, 'color' => 0xFF8E8E93, 'type' => 'expense'],

            // Income categories
            ['id' => 9,  'name' => 'Gaji',          'icon' => 0xe850, 'color' => 0xFF00C853, 'type' => 'income'],
            ['id' => 10, 'name' => 'Freelance',     'icon' => 0xe3e9, 'color' => 0xFF2196F3, 'type' => 'income'],
            ['id' => 11, 'name' => 'Investasi',     'icon' => 0xe8e5, 'color' => 0xFFFF9800, 'type' => 'income'],
            ['id' => 12, 'name' => 'Hadiah',        'icon' => 0xe8f6, 'color' => 0xFFE91E63, 'type' => 'income'],
            ['id' => 13, 'name' => 'Lainnya',       'icon' => 0xe400, 'color' => 0xFF8E8E93, 'type' => 'income'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['id' => $category['id']],
                [
                    'user_id' => null, // Global categories
                    'name'    => $category['name'],
                    'icon'    => $category['icon'],
                    'color'   => $category['color'],
                    'type'    => $category['type'],
                ]
            );
        }
    }
}
