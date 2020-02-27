<?php

namespace App\Controller;

use App\Entity\Portal;
use App\Entity\Translation;
use App\Form\Type\Portal\AnnouncementsType;
use App\Form\Type\Portal\GeneralType;
use App\Form\Type\Portal\PortalhomeType;
use App\Form\Type\Portal\SupportType;
use App\Form\Type\TranslationType;
use App\Services\LegacyEnvironment;
use Doctrine\ORM\EntityManagerInterface;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;


class PortalSettingsController extends AbstractController
{
    /**
     * @Route("/portal/{portalId}/settings")
     */
    public function index(int $portalId)
    {
        return $this->redirectToRoute('app_portalsettings_general', [
            'portalId' => $portalId,
        ]);
    }

    /**
     * @Route("/portal/{portalId}/settings/general")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function general(Portal $portal, Request $request)
    {
        $form = $this->createForm(GeneralType::class, $portal);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/support")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function support(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
        $form = $this->createForm(SupportType::class, $portal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($portal);
            $entityManager->flush();
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/portalhome")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @ParamConverter("environment", class="App\Services\LegacyEnvironment")
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function portalhome(Portal $portal, Request $request, EntityManagerInterface $entityManager, LegacyEnvironment $environment)
    {
        $form = $this->createForm(PortalhomeType::class, $portal);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $legacyEnvironment = $environment->getEnvironment();
            $room_item = $legacyEnvironment->getCurrentPortalItem();
            $choice = $portal->getConfigurationSelection();
            if($choice == 1){
                $room_item->setShowRoomsOnHome('preselectcommunityrooms');
            }elseif($choice == 2){
                $room_item->setShowRoomsOnHome('onlycommunityrooms');
            }elseif($choice == 3){
                $room_item->setShowRoomsOnHome('onlyprojectrooms');
            }else{
                $room_item->setShowRoomsOnHome('normal');
            }

            $chosen_templates = $portal->hasConfigurationRoomListTemplates();
            if($chosen_templates){
                $room_item->setShowTemplatesInRoomListON();
            }else{
                $room_item->setShowTemplatesInRoomListOFF();
            }

            $entityManager->persist($portal);
            $entityManager->flush();
        }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/announcements")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function announcements(Portal $portal, Request $request, EntityManagerInterface $entityManager)
    {
            $form = $this->createForm(AnnouncementsType::class, $portal);
            $form->handleRequest($request);
            if ($form->isSubmitted() && $form->isValid()) {
                $entityManager->persist($portal);
                $entityManager->flush();
            }

        return [
            'form' => $form->createView(),
        ];
    }

    /**
     * @Route("/portal/{portalId}/settings/accounts")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     */
    public function accounts(Portal $portal, Request $request)
    {

    }

    /**
     * @Route("/portal/{portalId}/settings/translations/{translationId?}")
     * @ParamConverter("portal", class="App\Entity\Portal", options={"id" = "portalId"})
     * @IsGranted("PORTAL_MODERATOR", subject="portal")
     * @Template()
     * @param Portal $portal
     * @param int $translationId
     * @param Request $request
     * @param EntityManagerInterface $entityManager
     * @return array|\Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function translations(
        Portal $portal,
        $translationId,
        Request $request,
        EntityManagerInterface $entityManager
    ) {
        $editForm = null;

        $repository = $entityManager->getRepository(Translation::class);

        $translation = null;
        if ($translationId) {
            $translation = $repository->find($translationId);

            if (!$translation) {
                throw new NotFoundHttpException('No translation found for given id');
            }

            $editForm = $this->createForm(TranslationType::class, $translation, []);

            $editForm->handleRequest($request);
            if ($editForm->isSubmitted() && $editForm->isValid()) {
                $entityManager->persist($translation);
                $entityManager->flush();

                return $this->redirectToRoute('app_portalsettings_translations', [
                    'portalId' => $portal->getId(),
                ]);
            }
        }

        $translations = $repository->findBy([
            'contextId' => $portal->getId(),
        ]);

        return [
            'form' => $editForm ? $editForm->createView() : null,
            'portal' => $portal,
            'translations' => $translations,
            'selectedTranslation' => $translation,
        ];
    }
}
