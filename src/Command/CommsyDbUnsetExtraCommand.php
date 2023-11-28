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

namespace App\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'commsy:db:unset-extra',
    description: 'Unset values in the extra column of an entity',
)]
class CommsyDbUnsetExtraCommand extends Command
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('type', InputArgument::REQUIRED, 'Entity type')
            ->addArgument('name', InputArgument::REQUIRED, 'Extra name')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $validTypes = ['room', 'community', 'project'];
        $typeArg = $input->getArgument('type');
        if (!in_array($typeArg, $validTypes)) {
            $io->error('The argument "type" must be one of the following values: ' . implode(', ', $validTypes));
            return Command::INVALID;
        }

        switch ($typeArg) {
            case 'room':
                $query = $this->entityManager->createQuery('SELECT r FROM App\Entity\Room r');
                break;
            case 'community':
                $query = $this->entityManager->createQuery('SELECT r FROM App\Entity\Room r WHERE r.type = :type');
                $query->setParameter('type', 'community');
                break;
            case 'project':
                $query = $this->entityManager->createQuery('SELECT r FROM App\Entity\Room r WHERE r.type = :type');
                $query->setParameter('type', 'project');
                break;
        }

        if (!isset($query)) {
            return COMMAND::FAILURE;
        }

        $progressBar = new ProgressBar($output, iterator_count($query->toIterable()));
        $progressBar->start();

        $i = 0;
        foreach ($query->toIterable() as $entity) {
            if (method_exists($entity, 'getExtras') && method_exists($entity, 'setExtras')) {
                $extras = $entity->getExtras();
                unset($extras[$input->getArgument('name')]);
                $entity->setExtras($extras);
            }

            ++$i;
            if (($i % self::BATCH_SIZE) === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
                $progressBar->advance(self::BATCH_SIZE);
            }
        }

        $this->entityManager->flush();
        $progressBar->finish();

        return Command::SUCCESS;
    }
}
