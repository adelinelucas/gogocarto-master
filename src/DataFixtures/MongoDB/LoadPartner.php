<?php

namespace App\DataFixtures\MongoDB;

use App\Document\Partner;
use App\Document\PartnerImage;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;

class LoadPartner implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $lipsum = new LoremIpsum();

        $new_partner = new Partner();

        $new_partner->setName('Explications'); // TODO  translate
        $new_partner->setContent("Cette page permet de lister des informations comme la description du projet, des partenaires etc.. Vous pouvez modifier le contenu depuis l'interface admin dans \"Contenu\" puis \"Qui sommes nous\""); // TODO  translate
        $new_partner->setWebsiteUrl('https://github.com/pixelhumain/GoGoCarto'); // TODO  translate
        $manager->persist($new_partner);

        $names = ['Texte numéro un', 'Texte numéro deux']; // TODO  translate
        foreach ($names as $key => $name) {
            $new_partner = new Partner();

            $new_partner->setName($name);
            $new_partner->setContent($lipsum->words(rand(30, 100)));
            $new_partner->setLogo(new PartnerImage('http://lorempixel.com/300/300/abstract/'.$key));
            $new_partner->setWebsiteUrl('www.partenaire.com'); // TODO  translate ?
            $manager->persist($new_partner);
        }

        // we trigger saving of all partners
        $manager->flush();
    }
}
