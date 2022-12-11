<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Finder\Finder;

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
