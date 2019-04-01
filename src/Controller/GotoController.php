<?php

namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class GotoController extends Controller
{
    /**
     * @Route("/goto/{itemId}")
     */
    public function gotoAction($itemId)
    {
        // for now, we are using DBAL here, instead of ORM Entities
        $dbConnection = $this->get('database_connection');
        $queryBuilder = $dbConnection->createQueryBuilder();

        $queryBuilder
            ->select('item_id', 'context_id', 'type')
            ->from('items')
            ->where('deletion_date IS NULL')
            ->andWhere('item_id = :item_id')
            ->setParameter(':item_id', $itemId);

        $stmt = $queryBuilder->execute();
        $item = $stmt->fetch();

        if (!$item) {
            throw $this->createNotFoundException('No item found for id ' . $itemId);
        }

        if (in_array($item['type'], ['project', 'community'])) {
            // redirect to room
            return $this->redirectToRoute('commsy_room_home', [
                'roomId' => $item['item_id']
            ]);
        } else {
            // redirect to detail
            return $this->redirectToRoute('commsy_' . $item['type'] . '_detail', [
                'roomId' => $item['context_id'],
                'itemId' => $item['item_id'],
            ]);
        }
    }
}