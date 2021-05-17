<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type as Types;
use App\Entity\Tag;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\RoomService;

class CategoryController extends AbstractController
{
    /**
     * @Template("category/show.html.twig")
     * @param CategoryService $categoryService
     * @param int $roomId
     * @return array
     */
    public function show(
        CategoryService $categoryService,
        int $roomId
    ) {
        // get categories from CategoryManager
        $roomTags = $categoryService->getTags($roomId);

        $defaultData = array(
            'roomId' => $roomId,
        );
        $form = $this->createForm(Types\TagType::class, $defaultData, array(
            'action' => $this->generateUrl('app_category_new', array('roomId' => $roomId)),
        ));

        return array(
            'tags' => $roomTags,
            'form' => $form->createView(),
        );
    }


    /**
     * @Template("category/showDetail.html.twig")
     * @param CategoryService $categoryService
     * @param int $roomId
     * @return array
     */
    public function showDetail(
        CategoryService $categoryService,
        int $roomId
    ) {
        // get categories
        $roomTags = $categoryService->getTags($roomId);

        $defaultData = array(
            'roomId' => $roomId,
        );
        $form = $this->createForm(Types\TagType::class, $defaultData, array(
            'action' => $this->generateUrl('app_category_new', array('roomId' => $roomId)),
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
     * @param Request $request
     * @param CategoryService $categoryService
     * @param $roomId
     * @return RedirectResponse
     */
    public function new(
        Request $request,
        CategoryService $categoryService,
        $roomId
    ) {
        $form = $this->createForm(Types\TagType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // persist new category
            $categoryService->addTag($data['title'], $roomId);

            return $this->redirectToRoute('app_room_home', array('roomId' => $roomId));
        }
    }

    /**
     * @Route("/room/{roomId}/category/delete/{categoryId}")
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function delete($roomId, $categoryId, CategoryService $categoryService)
    {
        $categoryService->removeTag($categoryId, $roomId);

        return $this->redirectToRoute('app_category_edit', [
            'roomId' => $roomId,
        ]);
    }

    /**
     * @Route("/room/{roomId}/category/edit/{categoryId}")
     * @Template()
     * @Security("is_granted('CATEGORY_EDIT')")
     * @param Request $request
     * @param RoomService $roomService
     * @param CategoryService $categoryService
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int $categoryId
     * @return array|RedirectResponse
     */
    public function edit(
        Request $request,
        RoomService $roomService,
        CategoryService $categoryService,
        LegacyEnvironment $legacyEnvironment,
        int $roomId,
        int $categoryId = null
    ) {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Tag');

        // create new form
        $category = new Tag();
        if ($categoryId) {
            $category = $repository->findOneByItemId($categoryId);
            $category->setTitle(html_entity_decode($category->getTitle()));
        }

        $createNewForm = $this->createForm(Types\CategoryNewType::class, $category);

        $categoryEditTitle = '';

        if($createNewForm->has('new')){
            $categoryEditTitle = 'Create new category';
        } elseif ($createNewForm->has('update')) {
            $categoryEditTitle = 'Edit category';
        }
        
        $createNewForm->handleRequest($request);
        if ($createNewForm->isSubmitted() && $createNewForm->isValid()) {
            if ($createNewForm->has('new') && $createNewForm->get('new')->isClicked()) {
                $categoryService->addTag($category->getTitle(), $roomId);
            }

            if ($createNewForm->has('update') && $createNewForm->get('update')->isClicked()) {
                $categoryService->updateTag($category->getItemId(), $category->getTitle());
            }
            
            return $this->redirectToRoute('app_category_edit', [
                'roomId' => $roomId,
                'editTitle' => $categoryEditTitle,
            ]);
        }

        // edit form
        $roomTags = $categoryService->getTags($roomId);

        $editForm = $this->createForm(Types\CategoryEditType::class, null, [
            'categories' => $roomTags,
        ]);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
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

            return $this->redirectToRoute('app_category_edit', [
                'roomId' => $roomId,
                'editTitle' => $categoryEditTitle,
            ]);
        }


        $mergeForm = $this->createForm(Types\CategoryMergeType::class, null, ['roomId'=>$roomId]);

        $mergeForm->handleRequest($request);
        if ($mergeForm->isSubmitted() && $mergeForm->isValid()) {
            $mergeData = $mergeForm->getData();
            $tagIdOne = $mergeData['first']->getItemId();
            $tagIdTwo = $mergeData['second']->getItemId();

            $legacyEnvironment = $legacyEnvironment->getEnvironment();

            $tagManager = $legacyEnvironment->getTagManager();
            $tag2TagManager = $legacyEnvironment->getTag2TagManager();

            if ($tag2TagManager->isASuccessorOfB($tagIdOne, $tagIdTwo)) {
                $tagIdOneTemp = $tagIdOne;
                $tagIdOne = $tagIdTwo;
                $tagIdTwo = $tagIdOneTemp;
            }
            
            // get both
            $tagItemOne = $tagManager->getItem($tagIdOne);
            $tagItemTwo = $tagManager->getItem($tagIdTwo);
            
            // we put the combined tag under the parent of the first one
            $putId = $tag2TagManager->getFatherItemId($tagIdOne);
            
            // merge them
            $tag2TagManager->combine($tagIdOne, $tagIdTwo, $putId);

            return $this->redirectToRoute('app_category_edit', [
                'roomId' => $roomId,
            ]);
        }

        return [
            'newForm' => $createNewForm->createView(),
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'editTitle' => $categoryEditTitle,
            'mergeForm' => $mergeForm->createView(),
        ];
    }
}
