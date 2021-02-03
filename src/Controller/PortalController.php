<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Form\Model\Csv\Base64CsvFile;
use App\Form\Model\CsvImport;
use App\Form\Type\CsvImportType;
use App\Form\Type\LicenseSortType;
use App\Services\LegacyEnvironment;
use App\Services\RoomCategoriesService;
use App\User\UserBuilder;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\NonUniqueResultException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;

use App\Form\Type\AnnouncementsType;
use App\Form\Type\PortalTermsType;
use App\Form\Type\RoomCategoriesEditType;
use App\Form\Type\RoomCategoriesLinkType;
use App\Form\Type\TranslationType;
use App\Entity\RoomCategories;
use App\Entity\License;
use App\Form\Type\LicenseNewEditType;
use App\Entity\Terms;
use App\Form\Type\TermType;

use App\Event\CommsyEditEvent;
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
     * @Route("/portal/{roomId}/impressum")
     * @Template()
     */
    public function impressum()
    {
        return [];
    }

    /**
     * @Route("/portal/{roomId}/dp")
     * @Template()
     */
    public function dataPrivacy()
    {
        return [];
    }

    /**
     * @Route("/portal/{roomId}/tou")
     * @Template()
     */
    public function termsOfUse()
    {
        return [];
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
     * Handles portal terms configuration
     *
     * @Route("/portal/{roomId}/terms")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @return array
     */
    public function termsAction(
        Request $request,
        LegacyEnvironment $environment
    ) {
        $legacyEnvironment = $environment->getEnvironment();

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
        return $this->redirect('/?cid='.$roomId.'&mod=configuration&fct=index');
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


    /**
     * @Route("/portal/{roomId}/licenses/{licenseId}")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     * @param Request $request
     * @param EventDispatcherInterface $dispatcher
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int|null $licenseId
     * @return array|RedirectResponse
     */
    public function licensesAction(
        Request $request,
        EventDispatcherInterface $dispatcher,
        LegacyEnvironment $environment,
        int $roomId,
        int $licenseId = null
    ) {
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

                $dispatcher->dispatch(new CommsyEditEvent(null), 'commsy.edit');
            }

            return $this->redirectToRoute('app_portal_licenses', [
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
                $legacyEnvironment = $environment->getEnvironment();

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

            return $this->redirectToRoute('app_portal_licenses', [
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

    /**
     * @Route("/portal/{roomId}/csvimport")
     * @Template()
     * @Security("is_granted('ITEM_MODERATE', roomId)")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return array
     */
    public function csvImportAction(
        Request $request,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $portal = null;
        try {
            $portal = $this->getDoctrine()->getRepository(Portal::class)
                ->findActivePortal($roomId);
        } catch (NonUniqueResultException $e) {
        }

        if (!$portal) {
            throw $this->createNotFoundException();
        }

        $importForm = $this->createForm(CsvImportType::class, [], [
            'uploadUrl' => $this->generateUrl('app_upload_base64upload', [
                'roomId' => $roomId,
            ]),
            'portal' => $portal,
            'translator' => $this->get('translator'),
        ]);

        $importForm->handleRequest($request);
        if ($importForm->isSubmitted() && $importForm->isValid()) {
            if ($importForm->get('cancel')->isClicked()) {
                return $this->redirectToRoute('app_portal_csvimport', [
                    'roomId' => $roomId,
                ]);
            }

            $data = $importForm->getData();
            /** @var Base64CsvFile[] $base64CsvFiles */
            $base64CsvFiles = $data['base64'];

            $userDatasets = [];
            if ($base64CsvFiles) {
                foreach ($base64CsvFiles as $base64CsvFile) {
                    if ($base64CsvFile->getChecked()) {
                        $rows = $base64CsvFile->getBase64Content();
                        foreach ($rows as $row) {
                            $userDatasets[] = $row;
                        }
                    }
                }

                $legacyEnvironment = $environment->getEnvironment();
                $authSourceManager = $legacyEnvironment->getAuthSourceManager();
                $authSourceItem = $authSourceManager->getItem($data['auth_sources']->getItemId());

                $userBuilder = $this->get(UserBuilder::class);
                $userBuilder->createFromCsvDataset($authSourceItem, $userDatasets);
            }
        }

        return [
            'form' => $importForm->createView(),
        ];
    }
}
