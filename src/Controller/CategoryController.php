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

use App\Entity\Tag;
use App\Form\Type as Types;
use App\Services\LegacyEnvironment;
use App\Utils\CategoryService;
use App\Utils\RoomService;
use Doctrine\Persistence\ManagerRegistry;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class CategoryController extends AbstractController
{
    public function show(
        CategoryService $categoryService,
        int $roomId
    ): \Symfony\Component\HttpFoundation\Response {
        // get categories from CategoryManager
        $roomTags = $categoryService->getTags($roomId);

        $defaultData = ['roomId' => $roomId];
        $form = $this->createForm(Types\TagType::class, $defaultData, ['action' => $this->generateUrl('app_category_new', ['roomId' => $roomId])]);

        return $this->render('category/show.html.twig', ['tags' => $roomTags, 'form' => $form->createView()]);
    }

    public function showDetail(
        CategoryService $categoryService,
        int $roomId
    ): \Symfony\Component\HttpFoundation\Response {
        // get categories
        $roomTags = $categoryService->getTags($roomId);

        $defaultData = ['roomId' => $roomId];
        $form = $this->createForm(Types\TagType::class, $defaultData, ['action' => $this->generateUrl('app_category_new', ['roomId' => $roomId])]);

        return $this->render('category/showDetail.html.twig', ['tags' => $roomTags, 'form' => $form->createView()]);
    }

    #[Route(path: '/room/{roomId}/category/add')]
    #[Security("is_granted('CATEGORY_EDIT')")]
    public function add(
        $roomId,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        CategoryService $categoryService
    ): \Symfony\Component\HttpFoundation\Response {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
        }

        if ($request->request->get('title')) {
            $categoryTitle = $request->request->get('title');
            $categoryItem = $categoryService->addTag($categoryTitle, $roomId);
            $categoryId = $categoryItem->getItemID();

            return $this->json([
                'categoryId' => $categoryId,
                'categoryTitle' => $categoryItem->getTitle(),
            ]);
        } else {
            throw $this->createAccessDeniedException('Title is empty');
        }
    }

    #[Route(path: '/room/{roomId}/category/new', methods: ['POST'])]
    #[Security("is_granted('CATEGORY_EDIT')")]
    public function new(
        Request $request,
        CategoryService $categoryService,
        $roomId
    ): RedirectResponse {
        $form = $this->createForm(Types\TagType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // persist new category
            $categoryService->addTag($data['title'], $roomId);

            return $this->redirectToRoute('app_room_home', ['roomId' => $roomId]);
        }
    }

    #[Route(path: '/room/{roomId}/category/delete/{categoryId}')]
    #[Security("is_granted('CATEGORY_EDIT')")]
    public function delete($roomId, $categoryId, CategoryService $categoryService): RedirectResponse
    {
        $categoryService->removeTag($categoryId, $roomId);

        return $this->redirectToRoute('app_category_edit', [
            'roomId' => $roomId,
        ]);
    }

    #[Route(path: '/room/{roomId}/category/edit/{categoryId}')]
    #[Security("is_granted('CATEGORY_EDIT')")]
    public function edit(
        Request $request,
        RoomService $roomService,
        CategoryService $categoryService,
        LegacyEnvironment $legacyEnvironment,
        ManagerRegistry $doctrine,
        int $roomId,
        int $categoryId = null
    ): \Symfony\Component\HttpFoundation\Response {
        $roomItem = $roomService->getRoomItem($roomId);

        if (!$roomItem->withTags()) {
            throw $this->createAccessDeniedException('The requested room does not have categories enabled.');
        }

        $repository = $doctrine->getRepository(Tag::class);

        // create new form
        $category = new Tag();
        if ($categoryId) {
            $category = $repository->findOneByItemId($categoryId);
            $category->setTitle(html_entity_decode($category->getTitle()));
        }

        $createNewForm = $this->createForm(Types\CategoryNewType::class, $category);

        $categoryEditTitle = '';

        if ($createNewForm->has('new')) {
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
                $structure = json_decode($structure, true, 512, JSON_THROW_ON_ERROR);

                $categoryService->updateStructure($structure, $roomId);
            }

            return $this->redirectToRoute('app_category_edit', [
                'roomId' => $roomId,
                'editTitle' => $categoryEditTitle,
            ]);
        }

        $mergeForm = $this->createForm(Types\CategoryMergeType::class, null, ['roomId' => $roomId]);

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

        return $this->render('Category/edit.html.twig', [
            'newForm' => $createNewForm->createView(),
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'editTitle' => $categoryEditTitle,
            'mergeForm' => $mergeForm->createView(),
        ]);
    }
}
