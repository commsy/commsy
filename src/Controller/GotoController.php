<?php

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
     * @Route("/goto/{itemId}")
     * @param EntityManager $entityManager
     * @param int $itemId
     * @return RedirectResponse
     * @throws Exception
     */
    public function gotoAction(
        EntityManagerInterface $entityManager,
        int $itemId
    ) {
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
            throw $this->createNotFoundException('No item found for id ' . $itemId);
        }

        if (in_array($item['type'], ['project', 'community'])) {
            // redirect to room
            return $this->redirectToRoute('app_room_home', [
                'roomId' => $item['item_id']
            ]);
        } else {
            // redirect to detail
            return $this->redirectToRoute('app_' . $item['type'] . '_detail', [
                'roomId' => $item['context_id'],
                'itemId' => $item['item_id'],
            ]);
        }
    }
}