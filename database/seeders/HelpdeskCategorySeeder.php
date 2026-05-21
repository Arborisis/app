<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\HelpdeskCategory;
use Illuminate\Database\Seeder;

class HelpdeskCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'slug' => 'technical',
                'name' => 'Support technique',
                'description' => 'Problèmes techniques, bugs, erreurs',
                'color' => 'cyan',
                'sort_order' => 1,
            ],
            [
                'slug' => 'account',
                'name' => 'Compte & Accès',
                'description' => 'Problèmes de connexion, suppression de compte',
                'color' => 'amber',
                'sort_order' => 2,
            ],
            [
                'slug' => 'billing',
                'name' => 'Facturation & ECHO',
                'description' => 'Questions sur les dons, le portefeuille ECHO',
                'color' => 'emerald',
                'sort_order' => 3,
            ],
            [
                'slug' => 'content',
                'name' => 'Contenu & Modération',
                'description' => 'Signalement de contenu, demandes de suppression',
                'color' => 'red',
                'sort_order' => 4,
            ],
            [
                'slug' => 'feature',
                'name' => 'Idées & Améliorations',
                'description' => 'Suggestions de nouvelles fonctionnalités',
                'color' => 'firefly',
                'sort_order' => 5,
            ],
        ];

        foreach ($categories as $category) {
            HelpdeskCategory::firstOrCreate(
                ['slug' => $category['slug']],
                $category
            );
        }
    }
}
