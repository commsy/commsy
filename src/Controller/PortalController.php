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
}
