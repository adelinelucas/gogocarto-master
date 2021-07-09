<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Wrapper;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LoadWrapper implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $new_wrapper = new Wrapper();
        $new_wrapper->setTitle('Bienvenue sur GoGoCarto !'); // TODO  translate
        $new_wrapper->setContent("Ces bandeaux sont éditables depuis l'interface admin, dans le menu <b>Contenu</b> puis <b>Bandeaux de la page d'accueil</b>. Vous pouvez insérer des balises html si vous le souhaitez, par example pour créer un <a href=\"https://github.com/pixelhumain/GoGoCarto\" style=\"font-weight:bold;color: #bdc900;\" target=\"_blank\">lien vers le dépo Github du projet</a>"); // TODO  translate
        $new_wrapper->setBackgroundColor('ffffff');
        $new_wrapper->setTextColor('inherit');

        $manager->persist($new_wrapper);
        $new_wrapper = new Wrapper();
        $new_wrapper->setTitle('Un autre bandeau!'); // TODO  translate
        $new_wrapper->setRawContent('La couleur de fond et de texte sont également paramétrables'); // TODO  translate
        $new_wrapper->setBackgroundColor('6b7e9b');
        $new_wrapper->setTextColor('ffffff');

        $manager->persist($new_wrapper);

        // we trigger saving of all wrappers
        $manager->flush();
    }
}
