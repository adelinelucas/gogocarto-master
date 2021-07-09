<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Category;
use App\Document\Option;
use App\Document\Taxonomy;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadTaxonomy implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $c = []; // colors
        $s = []; // softColors

        $c[''] = '';
        $s[''] = '';
        $c['green'] = '#98a100';
        $s['green'] = '#8c9221';
        $c['brown'] = '#7e3200';
        $s['brown'] = '#864c26';
        $c['yellow'] = '#ab7100';
        $s['yellow'] = '#9d7424';
        $c['lightblue'] = '#009a9c';
        $s['lightblue'] = '#138c8e';
        $c['blue'] = '#00537e';
        $s['blue'] = '#22698e';
        $c['purple'] = '#8e36a5'; /*#6d1a82*/		$s['purple'] = '#7d398d';
        $c['pink'] = '#ab0061';
        $s['pink'] = '#a4307c';
        $c['red'] = '#cc3125';
        $s['red'] = '#ce3a2f';
        $c['darkgreen'] = '#1e8065';
        $s['darkgreen'] = '#1e8065';

        // main
        $mainCategory = new Category();
        $mainCategory->setName('Catégories Principales'); // TODO translate
        $mainCategory->setPickingOptionText('Une catégorie principale'); // TODO translate
        $mainCategory->setIndex(1);
        $mainCategory->setSingleOption(false);
        $mainCategory->setEnableDescription(false);
        $mainCategory->setUnexpandable(true);
        $mainCategory->setIsFixture(true);

        $manager->persist($mainCategory);

        // Liste des noms de catégorie à ajouter
        $mains = [
            ['Agriculture & Alimentation', 'fa fa-envira', 'green', '', 'Agriculture', true], // TODO  translate
            ['Habitat', 'fa fa-home', 'brown', '', '', false], // TODO  translate
            ['Education & Formation', 'fa fa-graduation-cap', 'blue', '', 'Education', false], // TODO  translate
            ['Mobilité', 'fa fa-paper-plane', 'lightblue', '', '', false], // TODO  translate
            ['Sortie & Culture', 'fa fa-coffee', 'pink', '', 'Sorties', false], // TODO  translate
            ['Voyages', 'fa fa-suitcase', 'darkgreen', '', '', false], // TODO  translate
            ['Economie & Finance', 'fa fa-euro', 'yellow', '', 'Economie/Finance', false], // TODO  translate
        ];

        foreach ($mains as $key => $main) {
            $new_main = new Option();
            $new_main->setName($main[0]);

            $new_main->setIcon($main[1]);
            $new_main->setColor($c[$main[2]]);
            $new_main->setSoftColor($s[$main[2]]);

            if ('' == $main[4]) {
                $new_main->setNameShort($main[0]);
            } else {
                $new_main->setNameShort($main[4]);
            }

            $new_main->setTextHelper('');

            $new_main->setUseIconForMarker(true);
            $new_main->setUseColorForMarker(true);
            $new_main->setIsFixture(true);
            $new_main->setIndex($key);

            $new_main->setShowOpenHours($main[5]);

            $mainCategory->addOption($new_main);
        }

        // On déclenche l'enregistrement de toutes les catégories
        $manager->flush();

        $taxonomy = new Taxonomy();
        $manager->persist($taxonomy);
        $manager->flush();
    }
}
