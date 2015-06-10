<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
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
        $tagManager = $this->get('commsy.tag_manager');
        $roomTags = $tagManager->getTags($roomId);

        $form = $this->createForm(new TagType(), null, array(
            'action' => $this->generateUrl('commsy_category_new'),
        ));

        return array(
            'tags' => $roomTags,
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/categoy/new")
     * @Method("POST")
     */
    public function newAction(Request $request)
    {
        $form = $this->createForm(new TagType());

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            // create the new tag

            var_dump($data); exit;
        }
    }
}
