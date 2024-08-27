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

declare(strict_types=1);

namespace App\Migrations\Factory;

use App\Contract\ParameterBagAwareInterface;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\Migrations\Version\MigrationFactory;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AsDecorator('doctrine.migrations.migrations_factory')]
readonly class MigrationFactoryDecorator implements MigrationFactory
{
    public function __construct(
        private MigrationFactory $migrationFactory,
        private ParameterBagInterface $parameterBag
    ) {
    }

    public function createVersion(string $migrationClassName): AbstractMigration
    {
        $instance = $this->migrationFactory->createVersion($migrationClassName);

        if ($instance instanceof ParameterBagAwareInterface) {
            $instance->setParameterBag($this->parameterBag);
        }

        return $instance;
    }
}
