<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Environment;
use Illuminate\Database\Seeder;

class EnvironmentSeeder extends Seeder
{
    public function run(): void
    {
        $environments = [
            ['name' => 'Forêt', 'slug' => 'forest', 'description' => 'Boisements, sous-bois, canopées et clairières', 'color' => '#4A6741', 'icon' => 'tree-pine', 'order' => 1],
            ['name' => 'Océan', 'slug' => 'ocean', 'description' => 'Vagues, marées, ressac et horizons marins', 'color' => '#3B82F6', 'icon' => 'ship', 'order' => 2],
            ['name' => 'Montagne', 'slug' => 'mountain', 'description' => 'Reliefs, vallées hautes, alpages et échos minéraux', 'color' => '#78716C', 'icon' => 'mountain', 'order' => 3],
            ['name' => 'Rivière', 'slug' => 'river', 'description' => 'Cours d’eau, berges, radiers, cascades et ruisseaux', 'color' => '#06B6D4', 'icon' => 'waves', 'order' => 4],
            ['name' => 'Pluie', 'slug' => 'rain', 'description' => 'Averses, gouttes, fronts humides et orages proches', 'color' => '#6366F1', 'icon' => 'cloud-rain', 'order' => 5],
            ['name' => 'Aube', 'slug' => 'dawn', 'description' => 'Premières heures, réveil du vivant et chœurs matinaux', 'color' => '#F59E0B', 'icon' => 'sunrise', 'order' => 6],
            ['name' => 'Crépuscule', 'slug' => 'dusk', 'description' => 'Transitions du soir, baisses de lumière et retours au calme', 'color' => '#D97706', 'icon' => 'sunset', 'order' => 7],
            ['name' => 'Nuit', 'slug' => 'night', 'description' => 'Activités nocturnes, silences et ambiances basses', 'color' => '#1E1B4B', 'icon' => 'moon', 'order' => 8],
            ['name' => 'Nature urbaine', 'slug' => 'urban_nature', 'description' => 'Parcs, jardins, friches et corridors écologiques en ville', 'color' => '#10B981', 'icon' => 'building-2', 'order' => 9],
            ['name' => 'Zone humide', 'slug' => 'wetland', 'description' => 'Marais, roselières, tourbières et milieux inondés', 'color' => '#0D9488', 'icon' => 'droplets', 'order' => 10],
            ['name' => 'Désert', 'slug' => 'desert', 'description' => 'Dunes, rocailles, silences et paysages ouverts très secs', 'color' => '#D4A373', 'icon' => 'sun', 'order' => 11],
            ['name' => 'Prairie', 'slug' => 'meadow', 'description' => 'Prairies naturelles, pâtures, pelouses et milieux herbacés', 'color' => '#84CC16', 'icon' => 'sprout', 'order' => 12],
            ['name' => 'Forêt ancienne', 'slug' => 'foret-ancienne', 'description' => 'Canopées matures, sous-bois profonds et boisements peu fragmentés', 'color' => '#315B3A', 'icon' => 'tree-pine', 'order' => 101],
            ['name' => 'Forêt jeune', 'slug' => 'foret-jeune', 'description' => 'Boisements récents, régénérations et lisières actives', 'color' => '#4A7C45', 'icon' => 'trees', 'order' => 102],
            ['name' => 'Lisière', 'slug' => 'lisiere', 'description' => 'Zones de contact entre bois, prairies, chemins et clairières', 'color' => '#6B8E4E', 'icon' => 'scan-line', 'order' => 103],
            ['name' => 'Prairie détaillée', 'slug' => 'prairie', 'description' => 'Prairies naturelles, pâtures, pelouses et milieux herbacés', 'color' => '#84CC16', 'icon' => 'sprout', 'order' => 104],
            ['name' => 'Bocage', 'slug' => 'bocage', 'description' => 'Haies, talus, petites parcelles et mosaïques agricoles vivantes', 'color' => '#789A3D', 'icon' => 'route', 'order' => 105],
            ['name' => 'Zone humide détaillée', 'slug' => 'zone-humide', 'description' => 'Marais, roselières, tourbières et sols saturés en eau', 'color' => '#0D9488', 'icon' => 'droplets', 'order' => 106],
            ['name' => 'Mare ou étang', 'slug' => 'mare-etang', 'description' => 'Petits plans d’eau, mares temporaires et étangs calmes', 'color' => '#14B8A6', 'icon' => 'circle-dot', 'order' => 107],
            ['name' => 'Rivière ou ruisseau', 'slug' => 'riviere-ruisseau', 'description' => 'Cours d’eau courants, berges, radiers et cascades', 'color' => '#06B6D4', 'icon' => 'waves', 'order' => 108],
            ['name' => 'Lac', 'slug' => 'lac', 'description' => 'Grands plans d’eau, rives ouvertes et anses abritées', 'color' => '#0EA5E9', 'icon' => 'circle', 'order' => 109],
            ['name' => 'Littoral', 'slug' => 'littoral', 'description' => 'Plages, dunes, falaises maritimes, estrans et ports naturels', 'color' => '#3B82F6', 'icon' => 'ship', 'order' => 110],
            ['name' => 'Montagne détaillée', 'slug' => 'montagne', 'description' => 'Reliefs, vallées hautes, pierriers et échos alpins', 'color' => '#78716C', 'icon' => 'mountain', 'order' => 111],
            ['name' => 'Grotte ou cavité', 'slug' => 'grotte-cavite', 'description' => 'Milieux souterrains, cavités, entrées de grotte et résonances minérales', 'color' => '#57534E', 'icon' => 'archive', 'order' => 112],
            ['name' => 'Désert et aride', 'slug' => 'desert-aride', 'description' => 'Dunes, garrigues sèches, rocailles et espaces ouverts très secs', 'color' => '#D4A373', 'icon' => 'sun', 'order' => 113],
            ['name' => 'Ville végétalisée', 'slug' => 'ville-vegetalisee', 'description' => 'Parcs, jardins, cimetières, friches et corridors urbains', 'color' => '#10B981', 'icon' => 'building-2', 'order' => 114],
            ['name' => 'Jardin', 'slug' => 'jardin', 'description' => 'Jardins privés, potagers, vergers et micro-habitats entretenus', 'color' => '#22C55E', 'icon' => 'flower-2', 'order' => 115],
            ['name' => 'Milieu agricole', 'slug' => 'milieu-agricole', 'description' => 'Cultures, vergers, vignes, bandes enherbées et chemins agricoles', 'color' => '#CA8A04', 'icon' => 'wheat', 'order' => 116],
            ['name' => 'Friche', 'slug' => 'friche', 'description' => 'Terrains en transition, délaissés, remblais et végétation spontanée', 'color' => '#A3A33A', 'icon' => 'leaf', 'order' => 117],
            ['name' => 'Infrastructure douce', 'slug' => 'infrastructure-douce', 'description' => 'Chemins, passerelles, observatoires et bordures peu motorisées', 'color' => '#64748B', 'icon' => 'footprints', 'order' => 118],
        ];

        foreach ($environments as $environment) {
            Environment::updateOrCreate(
                ['slug' => $environment['slug']],
                $environment,
            );
        }
    }
}
