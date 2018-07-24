<?php

namespace CommsyBundle\Controller;

use CommsyBundle\Form\Type\LicenseSortType;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use CommsyBundle\Form\Type\PortalAnnouncementsType;
use CommsyBundle\Form\Type\PortalHelpType;
use CommsyBundle\Form\Type\PortalTermsType;
use CommsyBundle\Form\Type\RoomCategoriesEditType;
use CommsyBundle\Form\Type\RoomCategoriesLinkType;
use CommsyBundle\Form\Type\TranslationType;
use CommsyBundle\Entity\RoomCategories;
use CommsyBundle\Entity\License;
use CommsyBundle\Form\Type\LicenseNewEditType;
use CommsyBundle\Entity\Terms;
use CommsyBundle\Form\Type\TermType;

use CommsyBundle\Event\CommsyEditEvent;

/**
 * Class PortalController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class PortalController extends Controller
{
    /**
     * @Route("/portal/{roomId}/room/categories/{roomCategoryId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function roomcategoriesAction($roomId, $roomCategoryId = null, Request $request)
    {
        $portalId = $roomId;

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:RoomCategories');

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
                $roomCategoriesService = $this->get('commsy.roomcategories_service');
                $roomCategoriesService->removeRoomCategory($roomCategory);
            } else {
                $em->persist($roomCategory);
            }

            // actually executes the queries (i.e. the INSERT query)
            $em->flush();

            return $this->redirectToRoute('commsy_portal_roomcategories', [
                'roomId' => $roomId,
            ]);
        }

        $roomCategories = $repository->findBy(array('context_id' => $portalId));

        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));

        // mandatory links form
        $linkForm = $this->createForm(RoomCategoriesLinkType::class, ['mandatory' => $portalItem->isTagMandatory()], []);

        $linkForm->handleRequest($request);

        if ($linkForm->isSubmitted() && $linkForm->isValid() && $linkForm->getClickedButton()->getName() == 'save') {
            $formData = $linkForm->getData();

            if($formData['mandatory']) {
                $portalItem->setTagMandatory();
            }
            else {
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
     * @Route("/portal/{roomId}/announcements")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function announcementsAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $portalAnnouncementData = [];
        $portalAnnouncementData['text'] = $portalItem->getServerNewsText();
        $portalAnnouncementData['link'] = $portalItem->getServerNewsLink();
        $portalAnnouncementData['show'] = $portalItem->showServerNews();
        $portalAnnouncementData['title'] = $portalItem->getServerNewsTitle();
        $portalAnnouncementData['showServerInfos'] = $portalItem->showNewsFromServer();

        $announcementsForm = $this->createForm(PortalAnnouncementsType::class, $portalAnnouncementData, []);

        $announcementsForm->handleRequest($request);
        if ($announcementsForm->isSubmitted() && $announcementsForm->isValid()) {
            if ($announcementsForm->getClickedButton()->getName() == 'save') {
                $formData = $announcementsForm->getData();
                $portalItem->setServerNewsText($formData['text']);
                $portalItem->setServerNewsLink($formData['link']);
                $portalItem->setServerNewsTitle($formData['title']);
                if ($formData['show']) {
                    $portalItem->setShowServerNews();
                }
                else {
                    $portalItem->setDontShowServerNews();
                }
                if ($formData['showServerInfos']) {
                    $portalItem->setShowNewsFromServer();
                }
                else {
                    $portalItem->setDontShowNewsFromServer();
                }
                $portalItem->save();
            }
        }

        return [
            'form' => $announcementsForm->createView(),
        ];
    }

    /**
     * Handles portal terms configuration
     *
     * @Route("/portal/{roomId}/terms")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function termsAction($roomId, Request $request) {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $portalTerms = $portalItem->getAGBTextArray();
        $portalTerms['status'] = $portalItem->getAGBStatus();

        $termsForm = $this->createForm(PortalTermsType::class, $portalTerms, []);

        $termsForm->handleRequest($request);
        if ($termsForm->isSubmitted() && $termsForm->isValid()) {
            if ($termsForm->getClickedButton()->getName() == 'save') {
                $formData = $termsForm->getData();

                $portalItem->setAGBTextArray(array_filter($formData, function($key) {
                    return $key == 'DE' || $key == 'EN';
                }, ARRAY_FILTER_USE_KEY));
                $portalItem->setAGBStatus($formData['status']);
                $portalItem->setAGBChangeDate();
                $portalItem->save();
            }
        }

        return [
            'form' => $termsForm->createView(),
            'portal' => $portalItem,
        ];

    }

    /**
     * Handles portal terms templates for use inside rooms
     *
     * @Route("/portal/{roomId}/roomTermsTemplates/{termId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function roomTermsTemplatesAction($roomId, $termId = null, Request $request)
    {
        $portalId = $roomId;

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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

            return $this->redirectToRoute('commsy_portal_roomtermstemplates', [
                'roomId' => $roomId,
            ]);
        }

        /** @noinspection PhpUndefinedMethodInspection */
        $terms = $repository->findByContextId($portalId);

        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));

        return [
            'form' => $form->createView(),
            'roomId' => $portalId,
            'terms' => $terms,
            'termId' => $termId,
            'item' => $legacyEnvironment->getCurrentPortalItem(),
        ];
    }

    /**
     * @Route("/portal/{roomId}/help")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function helpAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $portalHelp = [];
        $portalHelp['link'] = $portalItem->getSupportPageLink();
        $portalHelp['alt'] = $portalItem->getSupportPageLinkTooltip();

        $helpForm = $this->createForm(PortalHelpType::class, $portalHelp, []);

        $helpForm->handleRequest($request);
        if ($helpForm->isSubmitted() && $helpForm->isValid()) {
            if ($helpForm->getClickedButton()->getName() == 'save') {
                $formData = $helpForm->getData();

                $portalItem->setSupportPageLink($formData['link']);
                $portalItem->setSupportPageLinkTooltip($formData['alt']);

                $portalItem->save();
            }
        }

        return [
            'form' => $helpForm->createView(),
        ];

    }

    /**
     * @Route("/portal/{roomId}/legacysettings")
     */
    public function legacysettingsAction($roomId, Request $request)
    {
        return $this->redirect('/?cid='.$roomId.'&mod=configuration&fct=index');
    }

    /**
     * @Route("/portal/{roomId}/translations/{translationId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function translationsAction($roomId, $translationId = null, Request $request)
    {
        $portalId = $roomId;

        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $portalItem = $legacyEnvironment->getCurrentPortalItem();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Translation');

        $form = null;

        if ($translationId) {
            $translation = $repository->findOneById($translationId);

            $editForm = $this->createForm(TranslationType::class, $translation, []);

            $editForm->handleRequest($request);
            if ($editForm->isValid()) {

                // tells Doctrine you want to (eventually) save the Product (no queries yet)
                $em->persist($translation);

                // actually executes the queries (i.e. the INSERT query)
                $em->flush();

                return $this->redirectToRoute('commsy_portal_translations', [
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


    /**
     * @Route("/portal/{roomId}/licenses/{licenseId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     */
    public function licensesAction($roomId, $licenseId = null, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(License::class);

        $license = new License();
        if ($licenseId) {
            $license = $repository->findOneById($licenseId);
            $license->setTitle(html_entity_decode($license->getTitle()));
        }

        $newEditForm = $this->createForm(LicenseNewEditType::class, $license);

        // determine title
        $pageTitle = '';
        if ($newEditForm->has('new')) {
            $pageTitle = 'Create new license';
        } elseif($newEditForm->has('update')) {
            $pageTitle = 'Edit license';
        }

        // handle new/edit form
        $newEditForm->handleRequest($request);
        if ($newEditForm->isSubmitted() && $newEditForm->isValid()) {
            if (!$newEditForm->has('cancel') || !$newEditForm->get('cancel')->isClicked()) {
                $license->setContextId($roomId);

                if (!$license->getPosition()) {
                    $position = 0;
                    $highestPosition = $repository->findHighestPosition($roomId);

                    if ($highestPosition) {
                        $highestPosition = $highestPosition[0];
                        $position = $highestPosition['position'] + 1;
                    }

                    $license->setPosition($position);
                }

                $em->persist($license);
                $em->flush();

                $dispatcher = $this->get('event_dispatcher');
                $dispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));
            }

            return $this->redirectToRoute('commsy_portal_licenses', [
                'roomId' => $roomId,
            ]);
        }

        // sort form
        $sortForm = $this->createForm(LicenseSortType::class, null, [
            'portalId' => $roomId,
        ]);
        $sortForm->handleRequest($request);

        if ($sortForm->isSubmitted() && $sortForm->isValid()) {
            $data = $sortForm->getData();

            /** @var ArrayCollection $delete */
            $delete = $data['license'];
            if (!$delete->isEmpty()) {
                $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

                $materialManager = $legacyEnvironment->getMaterialManager();
                $materialManager->unsetLicenses($delete->get(0));

                $zzzMaterialManager = $legacyEnvironment->getZzzMaterialManager();
                $zzzMaterialManager->unsetLicenses($delete->get(0));

                $em->remove($delete->get(0));
                $em->flush();
            }

            $structure = $data['structure'];
            if ($structure) {
                $structure = json_decode($structure, true);

                // update position
                $repository->updatePositions($structure, $roomId);
            }

            return $this->redirectToRoute('commsy_portal_licenses', [
                'roomId' => $roomId,
            ]);
        }

        return [
            'newEditForm' => $newEditForm->createView(),
            'sortForm' => $sortForm->createView(),
            'portalId' => $roomId,
            'pageTitle' => $pageTitle,
        ];
    }
}
