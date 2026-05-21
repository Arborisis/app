<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\SpeciesFact;
use Illuminate\Database\Seeder;

class SpeciesEcologySeeder extends Seeder
{
    /**
     * Seeds a curated list of common French wildlife species with short ecology hooks.
     * Used by StorytellingEnricher to ground LLM-generated DJ scripts in verified facts.
     */
    public function run(): void
    {
        foreach ($this->species() as $entry) {
            SpeciesFact::query()->updateOrCreate(
                ['latin_name' => $entry['latin_name']],
                $entry,
            );
        }
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    private function species(): array
    {
        return [
            // ── Oiseaux forestiers
            ['latin_name' => 'Turdus merula', 'common_name_fr' => 'Merle noir', 'group' => 'oiseau', 'fact_fr' => 'Le mâle entonne ses sifflées riches dès la fin de l\'hiver, souvent perché en hauteur.', 'habitat' => 'jardins, lisières, parcs urbains', 'seasonality' => 'chante de février à juillet', 'source' => 'INPN'],
            ['latin_name' => 'Erithacus rubecula', 'common_name_fr' => 'Rouge-gorge familier', 'group' => 'oiseau', 'fact_fr' => 'Discret et solitaire, il chante aussi en plein hiver et défend son territoire toute l\'année.', 'habitat' => 'sous-bois, bocages, jardins', 'seasonality' => 'chant audible toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Fringilla coelebs', 'common_name_fr' => 'Pinson des arbres', 'group' => 'oiseau', 'fact_fr' => 'Son chant en cascade descendante structure le paysage sonore des forêts caduques en France.', 'habitat' => 'forêts, parcs, haies', 'seasonality' => 'chante de mars à juillet', 'source' => 'INPN'],
            ['latin_name' => 'Sylvia atricapilla', 'common_name_fr' => 'Fauvette à tête noire', 'group' => 'oiseau', 'fact_fr' => 'Son chant flûté et improvisé est un des plus mélodieux des sous-bois clairs.', 'habitat' => 'forêts mixtes, lisières', 'seasonality' => 'présent d\'avril à septembre', 'source' => 'INPN'],
            ['latin_name' => 'Phylloscopus collybita', 'common_name_fr' => 'Pouillot véloce', 'group' => 'oiseau', 'fact_fr' => 'Son chant onomatopéique "tchif-tchaf" annonce traditionnellement le retour du printemps.', 'habitat' => 'forêts feuillues', 'seasonality' => 'chante de mars à juin', 'source' => 'INPN'],
            ['latin_name' => 'Troglodytes troglodytes', 'common_name_fr' => 'Troglodyte mignon', 'group' => 'oiseau', 'fact_fr' => 'Tout petit oiseau au chant explosif disproportionné, qu\'on entend dans les broussailles humides.', 'habitat' => 'sous-bois denses, ripisylves', 'seasonality' => 'chant toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Parus major', 'common_name_fr' => 'Mésange charbonnière', 'group' => 'oiseau', 'fact_fr' => 'Son "ti-tu ti-tu" métronomique en strophes brèves est l\'un des chants les plus diffus d\'Europe.', 'habitat' => 'tous milieux boisés', 'seasonality' => 'chante de janvier à juin', 'source' => 'INPN'],
            ['latin_name' => 'Cyanistes caeruleus', 'common_name_fr' => 'Mésange bleue', 'group' => 'oiseau', 'fact_fr' => 'Vocalise des trilles aigus très rapides, souvent en compagnie d\'autres mésanges en hiver.', 'habitat' => 'forêts caduques, jardins', 'seasonality' => 'chant printanier marqué', 'source' => 'INPN'],
            ['latin_name' => 'Sitta europaea', 'common_name_fr' => 'Sittelle torchepot', 'group' => 'oiseau', 'fact_fr' => 'Seul oiseau européen capable de descendre la tête en bas le long des troncs.', 'habitat' => 'forêts de feuillus', 'seasonality' => 'chant audible de février à mai', 'source' => 'INPN'],
            ['latin_name' => 'Dendrocopos major', 'common_name_fr' => 'Pic épeiche', 'group' => 'oiseau', 'fact_fr' => 'Son tambourinage rapide marque son territoire, surtout à la fin de l\'hiver.', 'habitat' => 'forêts mixtes', 'seasonality' => 'tambourine de février à mai', 'source' => 'INPN'],
            ['latin_name' => 'Picus viridis', 'common_name_fr' => 'Pic vert', 'group' => 'oiseau', 'fact_fr' => 'Son cri ressemble à un éclat de rire descendant, audible à grande distance.', 'habitat' => 'lisières, vergers, prairies bocagères', 'seasonality' => 'cri toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Garrulus glandarius', 'common_name_fr' => 'Geai des chênes', 'group' => 'oiseau', 'fact_fr' => 'Son cri rauque alerte la forêt entière du passage d\'un intrus.', 'habitat' => 'forêts de feuillus, chênaies', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Corvus corone', 'common_name_fr' => 'Corneille noire', 'group' => 'oiseau', 'fact_fr' => 'Ses croassements basse-fréquence dominent les ambiances de bocage en hiver.', 'habitat' => 'bocages, plaines cultivées', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],

            // ── Rapaces nocturnes & diurnes
            ['latin_name' => 'Strix aluco', 'common_name_fr' => 'Chouette hulotte', 'group' => 'oiseau', 'fact_fr' => 'Le mâle ulule en cascade ("hou… hou-hou-hou-hou") dès le crépuscule, surtout en hiver.', 'habitat' => 'forêts caduques, vieux parcs', 'seasonality' => 'chant nocturne d\'octobre à février', 'source' => 'INPN'],
            ['latin_name' => 'Athene noctua', 'common_name_fr' => 'Chevêche d\'Athéna', 'group' => 'oiseau', 'fact_fr' => 'Petite chouette des bocages, son "kiou" miaulé se détache la nuit dans les milieux ouverts.', 'habitat' => 'vergers, bocages, vieux bâtiments', 'seasonality' => 'chant d\'avril à juin', 'source' => 'INPN'],
            ['latin_name' => 'Tyto alba', 'common_name_fr' => 'Effraie des clochers', 'group' => 'oiseau', 'fact_fr' => 'Son cri rauque ressemble à un chuintement, jamais à un hululement.', 'habitat' => 'granges, clochers, plaines agricoles', 'seasonality' => 'présente toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Buteo buteo', 'common_name_fr' => 'Buse variable', 'group' => 'oiseau', 'fact_fr' => 'Son miaulement plaintif en vol identifie immédiatement ce rapace planeur commun.', 'habitat' => 'plaines, lisières', 'seasonality' => 'cri toute l\'année', 'source' => 'INPN'],

            // ── Aube / dawn chorus headliners
            ['latin_name' => 'Luscinia megarhynchos', 'common_name_fr' => 'Rossignol philomèle', 'group' => 'oiseau', 'fact_fr' => 'Son chant nocturne mêle sifflées, gazouillis et silences expressifs : signature de la fin du printemps.', 'habitat' => 'taillis, friches arbustives', 'seasonality' => 'chante d\'avril à juin', 'source' => 'INPN'],
            ['latin_name' => 'Turdus philomelos', 'common_name_fr' => 'Grive musicienne', 'group' => 'oiseau', 'fact_fr' => 'Répète chaque motif deux à quatre fois, ce qui aide à la reconnaître à l\'oreille.', 'habitat' => 'forêts, parcs, jardins', 'seasonality' => 'chante de février à juin', 'source' => 'INPN'],
            ['latin_name' => 'Turdus iliacus', 'common_name_fr' => 'Grive mauvis', 'group' => 'oiseau', 'fact_fr' => 'Hivernante venue du nord, on l\'entend en vol nocturne par bandes dès l\'automne.', 'habitat' => 'bocages, prairies', 'seasonality' => 'octobre à mars', 'source' => 'INPN'],
            ['latin_name' => 'Turdus pilaris', 'common_name_fr' => 'Grive litorne', 'group' => 'oiseau', 'fact_fr' => 'Ses cris "tchak-tchak" en groupe résonnent dans les vergers et bocages en hiver.', 'habitat' => 'bocages, vergers, prairies', 'seasonality' => 'octobre à avril', 'source' => 'INPN'],

            // ── Zones humides
            ['latin_name' => 'Acrocephalus scirpaceus', 'common_name_fr' => 'Rousserolle effarvatte', 'group' => 'oiseau', 'fact_fr' => 'Chant graillonnant en boucle continue depuis les roselières, typique des zones humides.', 'habitat' => 'roselières, marais', 'seasonality' => 'chante de mai à juillet', 'source' => 'INPN'],
            ['latin_name' => 'Cetti cetti', 'common_name_fr' => 'Bouscarle de Cetti', 'group' => 'oiseau', 'fact_fr' => 'Son cri explosif court, comme une claque, résonne dans les broussailles humides.', 'habitat' => 'ripisylves, fourrés humides', 'seasonality' => 'chante toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Botaurus stellaris', 'common_name_fr' => 'Butor étoilé', 'group' => 'oiseau', 'fact_fr' => 'Son "boom" sourd porte à plusieurs kilomètres dans les grandes roselières au printemps.', 'habitat' => 'grandes roselières', 'seasonality' => 'chant de mars à juin', 'source' => 'INPN'],
            ['latin_name' => 'Anas platyrhynchos', 'common_name_fr' => 'Canard colvert', 'group' => 'oiseau', 'fact_fr' => 'Le cancanage rauque des femelles structure l\'ambiance sonore des étangs urbains et ruraux.', 'habitat' => 'plans d\'eau, étangs', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Ardea cinerea', 'common_name_fr' => 'Héron cendré', 'group' => 'oiseau', 'fact_fr' => 'Son cri rauque "krarrh" en vol surprend dans le silence des marais.', 'habitat' => 'cours d\'eau, étangs', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],

            // ── Aube/crépuscule
            ['latin_name' => 'Cuculus canorus', 'common_name_fr' => 'Coucou gris', 'group' => 'oiseau', 'fact_fr' => 'Le "cou-cou" lointain est l\'un des signaux acoustiques les plus emblématiques du printemps européen.', 'habitat' => 'lisières, bocages', 'seasonality' => 'chante d\'avril à juin', 'source' => 'INPN'],
            ['latin_name' => 'Caprimulgus europaeus', 'common_name_fr' => 'Engoulevent d\'Europe', 'group' => 'oiseau', 'fact_fr' => 'Sa stridulation continue et mécanique commence au crépuscule dans les landes sèches.', 'habitat' => 'landes, garrigues, clairières', 'seasonality' => 'chant de mai à août', 'source' => 'INPN'],
            ['latin_name' => 'Apus apus', 'common_name_fr' => 'Martinet noir', 'group' => 'oiseau', 'fact_fr' => 'Ses cris stridents en vol annoncent les longues soirées d\'été dans les villes.', 'habitat' => 'villes, falaises', 'seasonality' => 'présent de mai à août', 'source' => 'INPN'],
            ['latin_name' => 'Hirundo rustica', 'common_name_fr' => 'Hirondelle rustique', 'group' => 'oiseau', 'fact_fr' => 'Ses gazouillis depuis les fils électriques et les granges signent les paysages ruraux d\'été.', 'habitat' => 'bocages, hameaux', 'seasonality' => 'présente d\'avril à septembre', 'source' => 'INPN'],

            // ── Insectes & amphibiens
            ['latin_name' => 'Bufo bufo', 'common_name_fr' => 'Crapaud commun', 'group' => 'amphibien', 'fact_fr' => 'Son chant nuptial est un "côk-côk-côk" sec audible en mars-avril autour des mares.', 'habitat' => 'mares, fossés, prairies humides', 'seasonality' => 'mars à mai', 'source' => 'INPN'],
            ['latin_name' => 'Hyla arborea', 'common_name_fr' => 'Rainette verte', 'group' => 'amphibien', 'fact_fr' => 'Son "crouac-crouac" puissant porte loin la nuit pour un animal de moins de 5 cm.', 'habitat' => 'mares, roselières', 'seasonality' => 'avril à juin', 'source' => 'INPN'],
            ['latin_name' => 'Pelophylax kl. esculentus', 'common_name_fr' => 'Grenouille verte', 'group' => 'amphibien', 'fact_fr' => 'Leurs chœurs polyrythmiques rythment les soirs d\'été sur les étangs.', 'habitat' => 'étangs, mares ensoleillées', 'seasonality' => 'mai à août', 'source' => 'INPN'],
            ['latin_name' => 'Tettigonia viridissima', 'common_name_fr' => 'Grande sauterelle verte', 'group' => 'insecte', 'fact_fr' => 'Sa stridulation continue, type "scie", est l\'une des bandes-son les plus reconnaissables de l\'été.', 'habitat' => 'prairies, friches', 'seasonality' => 'juillet à septembre', 'source' => 'INPN'],
            ['latin_name' => 'Oecanthus pellucens', 'common_name_fr' => 'Grillon d\'Italie', 'group' => 'insecte', 'fact_fr' => 'Sa note pure et flûtée, étirée la nuit, est très commune en région méditerranéenne.', 'habitat' => 'friches sèches', 'seasonality' => 'juillet à octobre', 'source' => 'INPN'],
            ['latin_name' => 'Gryllus campestris', 'common_name_fr' => 'Grillon champêtre', 'group' => 'insecte', 'fact_fr' => 'Sa stridulation cyclique est emblématique des prairies sèches au printemps.', 'habitat' => 'prairies sèches, friches', 'seasonality' => 'mai à juillet', 'source' => 'INPN'],
            ['latin_name' => 'Cicada orni', 'common_name_fr' => 'Cigale grise', 'group' => 'insecte', 'fact_fr' => 'Son chant continu de fin de matinée signe les paysages méditerranéens en été.', 'habitat' => 'pinèdes, garrigues', 'seasonality' => 'juin à août', 'source' => 'INPN'],

            // ── Mammifères
            ['latin_name' => 'Cervus elaphus', 'common_name_fr' => 'Cerf élaphe', 'group' => 'mammifère', 'fact_fr' => 'Le brame des mâles à l\'automne est l\'un des phénomènes acoustiques les plus puissants de la faune européenne.', 'habitat' => 'forêts mixtes', 'seasonality' => 'brame mi-septembre à mi-octobre', 'source' => 'INPN'],
            ['latin_name' => 'Capreolus capreolus', 'common_name_fr' => 'Chevreuil européen', 'group' => 'mammifère', 'fact_fr' => 'Son aboiement guttural surprend souvent les promeneurs en lisière, surtout au printemps.', 'habitat' => 'lisières, bocages', 'seasonality' => 'cris d\'avril à août', 'source' => 'INPN'],
            ['latin_name' => 'Vulpes vulpes', 'common_name_fr' => 'Renard roux', 'group' => 'mammifère', 'fact_fr' => 'Ses cris stridents nocturnes en hiver participent au paysage sonore rural.', 'habitat' => 'milieux variés', 'seasonality' => 'cris de décembre à février', 'source' => 'INPN'],

            // ── Maritime / littoral
            ['latin_name' => 'Larus argentatus', 'common_name_fr' => 'Goéland argenté', 'group' => 'oiseau', 'fact_fr' => 'Ses cris rythment toutes les ambiances portuaires et littorales atlantiques.', 'habitat' => 'côtes, ports', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Chroicocephalus ridibundus', 'common_name_fr' => 'Mouette rieuse', 'group' => 'oiseau', 'fact_fr' => 'Son rire grinçant signe les ambiances de bord d\'eau, mer comme étangs.', 'habitat' => 'littoral, plans d\'eau intérieurs', 'seasonality' => 'présente toute l\'année', 'source' => 'INPN'],

            // ── Espèces rares ou marqueurs
            ['latin_name' => 'Lagopus muta', 'common_name_fr' => 'Lagopède alpin', 'group' => 'oiseau', 'fact_fr' => 'Ses ronflements gutturaux émergent du silence des hauts massifs alpins en hiver.', 'habitat' => 'alpages, pierriers d\'altitude', 'seasonality' => 'présent toute l\'année', 'source' => 'INPN'],
            ['latin_name' => 'Aegolius funereus', 'common_name_fr' => 'Nyctale de Tengmalm', 'group' => 'oiseau', 'fact_fr' => 'Petite chouette des forêts boréales, son chant rapide "pou-pou-pou-pou" se détecte au printemps.', 'habitat' => 'forêts de résineux d\'altitude', 'seasonality' => 'chant de février à avril', 'source' => 'INPN'],
            ['latin_name' => 'Tetrao urogallus', 'common_name_fr' => 'Grand Tétras', 'group' => 'oiseau', 'fact_fr' => 'Le mâle pratique une parade nuptiale silencieuse puis bruyante au lever du jour, dans les vieilles forêts.', 'habitat' => 'vieilles pessières d\'altitude', 'seasonality' => 'parade d\'avril à mai', 'source' => 'INPN'],
        ];
    }
}
