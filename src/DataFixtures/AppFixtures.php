<?php

namespace App\DataFixtures;

use Symfony\Component\Finder\Finder;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

class LoadInitialData extends AbstractFixture implements OrderedFixtureInterface
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

    public function getOrder()
    {
        return 1;
    }
}