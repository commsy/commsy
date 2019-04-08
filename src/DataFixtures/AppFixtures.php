<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Symfony\Component\Finder\Finder;
use Doctrine\Common\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $finder = new Finder();
        $finder->files()->in('src/Resources/fixtures');

        foreach ($finder as $file) {
            $sql = $file->getContents();

            $manager->getConnection()->exec($sql);

            $manager->flush();
        }
    }
}