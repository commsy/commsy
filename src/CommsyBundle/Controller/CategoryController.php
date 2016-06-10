<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type\TagType;

class CategoryController extends Controller
{
    /**
     * @Template("CommsyBundle:Category:show.html.twig")
     */
    public function showAction($roomId, Request $request)
    {
        // get categories from CategoryManager
        $tagManager = $this->get('commsy_legacy.category_service');
        $roomTags = $tagManager->getTags($roomId);

        $defaultData = array(
            'roomId' => $roomId,
        );
        $form = $this->createForm(TagType::class, $defaultData, array(
            'action' => $this->generateUrl('commsy_category_new', array('roomId' => $roomId)),
        ));

        return array(
            'tags' => $roomTags,
            'form' => $form->createView(),
        );
    }


    /**
     * @Template("CommsyBundle:Category:showDetail.html.twig")
     */
    public function showDetailAction($roomId, Request $request)
    {
        // get categories from CategoryManager
        $tagManager = $this->get('commsy_legacy.category_service');
        $roomTags = $tagManager->getTags($roomId);

        $defaultData = array(
            'roomId' => $roomId,
        );
        $form = $this->createForm(TagType::class, $defaultData, array(
            'action' => $this->generateUrl('commsy_category_new', array('roomId' => $roomId)),
        ));

        return array(
            'tags' => $roomTags,
            'form' => $form->createView(),
        );
    }


    /**
     * @Route("/room/{roomId}/categoy/new")
     * @Method("POST")
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function newAction($roomId, Request $request)
    {
        $form = $this->createForm(TagType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            // persist new tag
            $tagManager = $this->get('commsy_legacy.category_service');
            $tagManager->addTag($data['title'], $roomId);

            return $this->redirectToRoute('commsy_room_home', array('roomId' => $roomId));
        }
    }
}
