<?php

namespace App\Controller;

use App\Entity\License;
use App\Entity\RoomCategories;
use App\Entity\Terms;
use App\Event\CommsyEditEvent;
use App\Form\Model\CsvImport;
use App\Form\Type\AnnouncementsType;
use App\Form\Type\LicenseNewEditType;
use App\Form\Type\LicenseSortType;
use App\Form\Type\PortalTermsType;
use App\Form\Type\RoomCategoriesEditType;
use App\Form\Type\RoomCategoriesLinkType;
use App\Form\Type\TermType;
use App\Form\Type\TranslationType;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\User\UserCreatorFacade;
use Doctrine\Common\Collections\ArrayCollection;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Class PortalController
 * @package App\Controller
 */
class PortalController extends AbstractController
{
    /**
     * @Route("/portal/goto/{portalId}", name="app_portal_goto")
     */
    public function gotoAction(string $portalId, Request $request)
    {
        return $this->redirect($request->getBaseUrl() . '?cid=' . $portalId);
    }

    /**
     * @Route("/portal/{roomId}/room/categories/{roomCategoryId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     * @param Request $request
     * @param RoomCategoriesService $roomCategoriesService
     * @param EventDispatcherInterface $dispatcher
     * @param LegacyEnvironment $environment
     * @param $roomId
     * @param null $roomCategoryId
     * @return array|RedirectResponse
     */
    public function roomcategoriesAction(
        Request $request,
        RoomCategoriesService $roomCategoriesService,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment,
        $roomId,
        $roomCategoryId = null
    ) {
        $portalId = $roomId;

        $legacyEnvironment = $environment->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:RoomCategories');

        if ($roomCategoryId) {
            $roomCategory = $repository->findOneById($roomCategoryId);
        } else {
            $roomCategory = new RoomCategories();
            $roomCategory->setContextId($portalId);
        }

        $editForm = $this->createForm(RoomCategoriesEditType::class, $roomCategory, []);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {

            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ($editForm->getClickedButton()->getName() == 'delete') {
                $roomCategoriesService->removeRoomCategory($roomCategory);
            } else {
                $em->persist($roomCategory);
            }

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('app_portal_roomcategories', [
                'roomId' => $roomId,
            ]);
        }

        $roomCategories = $repository->findBy(array('context_id' => $portalId));

        $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');

        // mandatory links form
        $linkForm = $this->createForm(RoomCategoriesLinkType::class, ['mandatory' => $portalItem->isTagMandatory()],
            []);

        $linkForm->handleRequest($request);

        if ($linkForm->isSubmitted() && $linkForm->isValid() && $linkForm->getClickedButton()->getName() == 'save') {
            $formData = $linkForm->getData();

            if ($formData['mandatory']) {
                $portalItem->setTagMandatory();
            } else {
                $portalItem->unsetTagMandatory();
            }
            $portalItem->save();
        }

        return [
            'editForm' => $editForm->createView(),
            'linkForm' => $linkForm->createView(),
            'roomId' => $portalId,
            'roomCategories' => $roomCategories,
            'roomCategoryId' => $roomCategoryId,
            'item' => $legacyEnvironment->getCurrentPortalItem(),
        ];
    }

    /**
     * Handles portal terms templates for use inside rooms
     *
     * @Route("/portal/{roomId}/roomTermsTemplates/{termId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int|null $termId
     * @return array|RedirectResponse
     */
    public function roomTermsTemplatesAction(
        Request $request,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $termId = null
    ) {
        $portalId = $roomId;

        $legacyEnvironment = $environment->getEnvironment();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(Terms::class);

        if ($termId) {
            /** @noinspection PhpUndefinedMethodInspection */
            $term = $repository->findOneById($termId);
        } else {
            $term = new Terms();
            $term->setContextId($portalId);
        }

        $form = $this->createForm(TermType::class, $term, []);

        $form->handleRequest($request);
        if ($form->isValid()) {

            // tells Doctrine you want to (eventually) save the Product (no queries yet)
            if ($form->getClickedButton()->getName() == 'delete') {
                $em->remove($term);
                $em->flush();
            } else {
                $em->persist($term);
            }

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('app_portal_roomtermstemplates', [
                'roomId' => $roomId,
            ]);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $terms = $repository->findByContextId($portalId);

        $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');

        return [
            'form' => $form->createView(),
            'roomId' => $portalId,
            'terms' => $terms,
            'termId' => $termId,
            'item' => $legacyEnvironment->getCurrentPortalItem(),
        ];
    }

    /**
     * @Route("/portal/{roomId}/legacysettings")
     * @param int $roomId
     * @return RedirectResponse
     */
    public function legacysettingsAction(
        int $roomId
    ) {
        return $this->redirect('/?cid=' . $roomId . '&mod=configuration&fct=index');
    }

    /**
     * @Route("/portal/{roomId}/translations/{translationId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function translationsAction($roomId, LegacyEnvironment $environment, $translationId = null, Request $request)
    {
        $portalId = $roomId;

        $legacyEnvironment = $environment->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Translation');

        $form = null;

        if ($translationId) {
            $translation = $repository->findOneById($translationId);

            $editForm = $this->createForm(TranslationType::class, $translation, []);

            $editForm->handleRequest($request);
            if ($editForm->isSubmitted() && $editForm->isValid()) {

                // tells Doctrine you want to (eventually) save the Product (no queries yet)
                $em->persist($translation);

                // actually executes the queries (i.e. the INSERT query)
                $em->flush();

                return $this->redirectToRoute('app_portal_translations', [
                    'roomId' => $roomId,
                ]);
            }

            $form = $editForm->createView();
        }

        $translations = $repository->findBy(array('contextId' => $portalId));

        return [
            'form' => $form,
            'roomId' => $portalId,
            'translations' => $translations,
            'translationId' => $translationId,
            'item' => $portalItem,
        ];
    }
}
