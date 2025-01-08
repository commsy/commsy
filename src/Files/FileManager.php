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

namespace App\Files;

use App\Entity\ItemLinkFile;
use App\Services\LegacyEnvironment;
use cs_environment;
use DateTime;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Query\Parameter;

class FileManager
{
    private cs_environment $legacyEnvironment;

    public function __construct(
        LegacyEnvironment $environment,
        private readonly EntityManagerInterface $entityManager,
    ) {
        $this->legacyEnvironment = $environment->getEnvironment();
    }

    public function softDeleteFileLink(int $itemId, int $versionId, ?int $fileId = null): void
    {
        $currentUser = $this->legacyEnvironment->getCurrentUser();

        $repository = $this->entityManager->getRepository(ItemLinkFile::class);
        $qb = $repository->createQueryBuilder('ilf')
            ->update()
            ->set('ilf.deletionDate', ':deletionDate')
            ->set('ilf.deleterId', ':deleterId')
            ->andWhere('ilf.itemId = :itemId')
            ->andWhere('ilf.versionId = :versionId')
            ->setParameters(new ArrayCollection([
                new Parameter('deletionDate', new DateTime(), Types::DATETIME_MUTABLE),
                new Parameter('deleterId', $currentUser->getItemID()),
                new Parameter('itemId', $itemId),
                new Parameter('versionId', $versionId),
            ]));

        if ($fileId) {
            $qb->andWhere('ilf.fileId = :fileId')
                ->setParameter('fileId', $fileId);
        }

        $qb->getQuery()->execute();
    }
}
