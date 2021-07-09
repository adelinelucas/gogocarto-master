<?php

namespace App\DataFixtures\MongoDB;

use App\Document\About;
use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;

class LoadAbout implements FixtureInterface
{
    public function load(ObjectManager $manager)
    {
        $lipsum = new LoremIpsum();

        $new_about = new About();
        $new_about->setName('Crédits'); // TODO  translate
        $new_about->setContent($lipsum->paragraph());
        $manager->persist($new_about);

        $new_about = new About();
        $new_about->setName('Mentions légales'); // TODO  translate
        $new_about->setContent($lipsum->paragraphs(3, 'p'));
        $manager->persist($new_about);

        $new_about = new About();
        $new_about->setName('Contact'); // TODO  translate
        $new_about->setContent($lipsum->sentences(4, ['p']));
        $manager->persist($new_about);

        // we trigger saving of all abouts
        $manager->flush();
    }
}
