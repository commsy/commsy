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

namespace App\Controller;

use Doctrine\DBAL\Exception;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;

class GotoController extends AbstractController
{
    /**
     * @param EntityManager $entityManager
     *
     * @throws Exception
     */
    #[Route(path: '/goto/{itemId}')]
    public function gotoAction(
        EntityManagerInterface $entityManager,
        int $itemId
    ): RedirectResponse {
        // for now, we are using DBAL here, instead of ORM Entities
        $dbConnection = $entityManager->getConnection();
        $queryBuilder = $dbConnection->createQueryBuilder();

        $queryBuilder
            ->select('item_id', 'context_id', 'type')
            ->from('items')
            ->where('deletion_date IS NULL')
            ->andWhere('item_id = :item_id')
            ->setParameter(':item_id', $itemId);

        $stmt = $queryBuilder->executeQuery();
        $item = $stmt->fetchAssociative();

        if (!$item) {
            throw $this->createNotFoundException('No item found for id '.$itemId);
        }

        if (in_array($item['type'], ['project', 'community'])) {
            // redirect to room
            return $this->redirectToRoute('app_room_home', [
                'roomId' => $item['item_id'],
            ]);
        } else {
            // redirect to detail
            return $this->redirectToRoute('app_'.$item['type'].'_detail', [
                'roomId' => $item['context_id'],
                'itemId' => $item['item_id'],
            ]);
        }
    }
}
