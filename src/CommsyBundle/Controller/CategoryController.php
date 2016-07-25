<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type as Types;

use CommsyBundle\Entity\Tag;

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
        $form = $this->createForm(Types\TagType::class, $defaultData, array(
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
        $form = $this->createForm(Types\TagType::class, $defaultData, array(
            'action' => $this->generateUrl('commsy_category_new', array('roomId' => $roomId)),
        ));

        return array(
            'tags' => $roomTags,
            'form' => $form->createView(),
        );
    }


    /**
     * @Route("/room/{roomId}/category/new")
     * @Method("POST")
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function newAction($roomId, Request $request)
    {
        $form = $this->createForm(Types\TagType::class);

        $form->handleRequest($request);

        if ($form->isValid()) {
            $data = $form->getData();

            // persist new tag
            $tagManager = $this->get('commsy_legacy.category_service');
            $tagManager->addTag($data['title'], $roomId);

            return $this->redirectToRoute('commsy_room_home', array('roomId' => $roomId));
        }
    }

    /**
     * @Route("/room/{roomId}/category/edit/{categoryId}")
     * @Template()
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function editAction($roomId, $categoryId = null, Request $request)
    {
        $roomService = $this->get('commsy_legacy.room_service');
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
        }

        $categoryService = $this->get('commsy_legacy.category_service');

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Tag');

        // create new form
        $category = new Tag();
        if ($categoryId) {
            $category = $repository->findOneByItemId($categoryId);
        }

        $createNewForm = $this->createForm(Types\CategoryNewType::class, $category);
        
        $createNewForm->handleRequest($request);
        if ($createNewForm->isValid()) {
            if ($createNewForm->has('new') && $createNewForm->get('new')->isClicked()) {
                $categoryService->addTag($category->getTitle(), $roomId);
            }

            if ($createNewForm->has('update') && $createNewForm->get('update')->isClicked()) {
                $categoryService->updateTag($category->getItemId(), $category->getTitle());
            }
            
            return $this->redirectToRoute('commsy_category_edit', [
                'roomId' => $roomId,
            ]);
        }

        // edit form
        $roomTags = $categoryService->getTags($roomId);

        $editForm = $this->createForm(Types\CategoryEditType::class, null, [
            'categories' => $roomTags,
        ]);

        $editForm->handleRequest($request);
        if ($editForm->isValid()) {
            $data = $editForm->getData();

            $delete = $data['category'];
            if ($delete) {
                $id = $delete[0];

                $categoryService->removeTag($id, $roomId);
            }

            $structure = $data['structure'];
            if ($structure) {
                // decode into array
                $structure = json_decode($structure, true);

                $categoryService->updateStructure($structure, $roomId);
            }

            return $this->redirectToRoute('commsy_category_edit', [
                'roomId' => $roomId,
            ]);
        }

        return [
            'newForm' => $createNewForm->createView(),
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
        ];
    }
}
