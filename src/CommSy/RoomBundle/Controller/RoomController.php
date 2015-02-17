<?php

namespace CommSy\RoomBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use CommSy\RoomBundle\Filter\RoomFilterType;
use CommSy\RoomBundle\Entity\Room;
use CommSy\RoomBundle\Form\Type\RoomType;

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

        return $this->render('CommSyRoomBundle:Room:list.html.twig', array(
            'pagination' => $pagination,
            'form' => $form->createView(),
        ));
    }

    public function createAction(Request $request)
    {
        $room = new Room();

        $form = $this->createForm(new RoomType(), $room);

        $form->handleRequest($request);
        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            $em->persist($room);
            $em->flush();

            return $this->redirect($this->generateUrl('commsy_room_list'));
        }

        return $this->render('CommSyRoomBundle:Room:create.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
