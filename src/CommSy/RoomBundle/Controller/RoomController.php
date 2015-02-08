<?php

namespace CommSy\RoomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CommSy\RoomBundle\Filter\RoomFilterType;

class RoomController extends Controller
{
    public function listAction(Request $request)
    {
        $form = $this->get('form.factory')->create(new RoomFilterType());

        if ($request->query->has($form->getName())) {
            // manually bind values from the request
            $form->submit($request->query->get($form->getName()));
        }

        $em = $this->get('doctrine.orm.entity_manager');
        $dql = "SELECT r FROM CommSyRoomBundle:Room r";
        $query = $em->createQuery($dql);

        $paginator = $this->get('knp_paginator');
        $pagination = $paginator->paginate(
            $query,
            $request->query->get('page', 1),
            10
        );

        return $this->render('CommSyRoomBundle:List:index.html.twig', array(
            'pagination' => $pagination,
            'form' => $form->createView()
        ));
    }
}
