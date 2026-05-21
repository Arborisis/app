<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Oiseaux', 'slug' => 'oiseaux', 'description' => 'Chants, cris, envols et chœurs aviaires', 'color' => '#7BAF6A', 'icon' => 'bird', 'order' => 1],
            ['name' => 'Mammifères', 'slug' => 'mammiferes', 'description' => 'Brames, appels, passages et présences animales', 'color' => '#8B6F47', 'icon' => 'paw-print', 'order' => 2],
            ['name' => 'Amphibiens', 'slug' => 'amphibiens', 'description' => 'Grenouilles, crapauds et ambiances de reproduction', 'color' => '#38A169', 'icon' => 'waves', 'order' => 3],
            ['name' => 'Insectes', 'slug' => 'insectes', 'description' => 'Stridulations, bourdonnements et micro-rythmes', 'color' => '#D9A441', 'icon' => 'bug', 'order' => 4],
            ['name' => 'Eau douce', 'slug' => 'eau-douce', 'description' => 'Ruisseaux, rivières, cascades, mares et étangs', 'color' => '#06B6D4', 'icon' => 'waves', 'order' => 5],
            ['name' => 'Mer et littoral', 'slug' => 'mer-littoral', 'description' => 'Vagues, marées, ressac, ports et côtes', 'color' => '#3B82F6', 'icon' => 'ship', 'order' => 6],
            ['name' => 'Vent', 'slug' => 'vent', 'description' => 'Souffles, rafales, canopées et reliefs balayés', 'color' => '#94A3B8', 'icon' => 'wind', 'order' => 7],
            ['name' => 'Pluie et orage', 'slug' => 'pluie-orage', 'description' => 'Averses, gouttes, tonnerre et fronts météo', 'color' => '#6366F1', 'icon' => 'cloud-rain', 'order' => 8],
            ['name' => 'Forêt', 'slug' => 'foret', 'description' => 'Sons de forêts, bois et sous-bois', 'color' => '#4A6741', 'icon' => 'tree-pine', 'order' => 9],
            ['name' => 'Montagne', 'slug' => 'montagne', 'description' => 'Cimes, échos, alpages et ambiances alpines', 'color' => '#78716C', 'icon' => 'mountain', 'order' => 10],
            ['name' => 'Prairie et bocage', 'slug' => 'prairie-bocage', 'description' => 'Champs, haies, lisières, steppes et plaines herbeuses', 'color' => '#84CC16', 'icon' => 'sprout', 'order' => 11],
            ['name' => 'Zone humide', 'slug' => 'zone-humide', 'description' => 'Marais, tourbières, roselières et milieux inondés', 'color' => '#0D9488', 'icon' => 'droplets', 'order' => 12],
            ['name' => 'Nuit', 'slug' => 'nuit', 'description' => 'Chants nocturnes, silences et activités crépusculaires', 'color' => '#1E1B4B', 'icon' => 'moon', 'order' => 13],
            ['name' => 'Aube', 'slug' => 'aube', 'description' => 'Réveil du vivant, dawn chorus et premiers mouvements', 'color' => '#F59E0B', 'icon' => 'sunrise', 'order' => 14],
            ['name' => 'Crépuscule', 'slug' => 'crepuscule', 'description' => 'Transitions du soir, retours au gîte et ambiances basses', 'color' => '#D97706', 'icon' => 'sunset', 'order' => 15],
            ['name' => 'Nature urbaine', 'slug' => 'nature-urbaine', 'description' => 'Parcs, jardins, friches et espaces verts en ville', 'color' => '#10B981', 'icon' => 'building-2', 'order' => 16],
            ['name' => 'Désert et milieux arides', 'slug' => 'desert-milieux-arides', 'description' => 'Silences, vents secs, pierres et paysages ouverts', 'color' => '#D4A373', 'icon' => 'sun', 'order' => 17],
            ['name' => 'Ambiance humaine discrète', 'slug' => 'ambiance-humaine-discrete', 'description' => 'Présences humaines lointaines intégrées au paysage sonore', 'color' => '#A78BFA', 'icon' => 'footprints', 'order' => 18],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']],
                $category,
            );
        }
    }
}
