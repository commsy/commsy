<?php

namespace App\Controller;

use App\Repository\LabelRepository;
use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use App\Form\Model\MergeHashtags;
use App\Utils\LabelService;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\HashtagEditType;
use App\Form\Type\HashtagMergeType;
use App\Entity\Labels;

use App\Event\CommsyEditEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HashtagController extends AbstractController
{
    /**
     * @Template("hashtag/show.html.twig")
     * @param int $roomId
     * @param LabelRepository $labelRepository
     * @return array
     */
    public function showAction(
        int $roomId,
        LabelRepository $labelRepository
    ) {
        return [
            'hashtags' => $labelRepository->findRoomHashtags($roomId),
        ];
    }

    /**
     * @Template("hashtag/showDetail.html.twig")
     */
    public function showDetailAction(
        int $roomId,
        LabelRepository $labelRepository
    ) {
        return [
            'hashtags' => $labelRepository->findRoomHashtags($roomId),
        ];
    }

    /**
     * @Template("hashtag/showDetailShort.html.twig")
     */
    public function showDetailShortAction(
        int $roomId,
        LabelRepository $labelRepository
    ) {
        return [
            'hashtags' => $labelRepository->findRoomHashtags($roomId),
        ];
    }

    /**
     * @Route("/room/{roomId}/hashtag/add")
     * @Security("is_granted('HASHTAG_EDIT')")
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @return JsonResponse
     */
    public function addAction(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        LabelService $labelService,
        int $roomId
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        if ($request->request->get('title')) {
            $hashtagTitle = $request->request->get('title');
            $buzzwordItem = $labelService->getNewHashtag($hashtagTitle, $roomId);
            $buzzwordId = $buzzwordItem->getItemID();

            return $this->json([
                'buzzwordId' => $buzzwordId,
                'buzzwordTitle' => $buzzwordItem->getName(),
            ]);
        } else {
            throw $this->createAccessDeniedException('Title is empty');
        }
    }

    /**
     * @Route("/room/{roomId}/hashtag/edit/{labelId}")
     * @Template()
     * @Security("is_granted('HASHTAG_EDIT')")
     * @param Request $request
     * @param LegacyEnvironment $legacyEnvironment
     * @param int $roomId
     * @param int|null $labelId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher,
        LabelService $labelService,
        LabelRepository $labelRepository,
        int $roomId,
        int $labelId = null
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem !== null && !$roomItem->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        if ($labelId) {
            $hashtag = $labelRepository->findOneByItemId($labelId);
            $hashtag->setName(html_entity_decode($hashtag->getName()));
        } else {
            $hashtag = new Labels();
            $hashtag->setContextId($roomId);
            $hashtag->setType('buzzword');
        }

        $editForm = $this->createForm(HashtagEditType::class, $hashtag);

        $editForm->handleRequest($request);
        if ($editForm->isSubmitted() && $editForm->isValid()) {
            // persist changes / delete hashtag
            $labelManager = $legacyEnvironment->getLabelManager();

            if ($editForm->has('delete') && $editForm->get('delete')->isClicked()) {
                $buzzwordItem = $labelManager->getItem($hashtag->getItemId());
                $buzzwordItem->delete();
            }

            if ($editForm->has('new') && $editForm->get('new')->isClicked()) {
                $labelService->getNewHashtag($hashtag->getName(), $hashtag->getContextId());
            }

            if ($editForm->has('update') && $editForm->get('update')->isClicked()) {
                $buzzwordItem = $labelManager->getItem($hashtag->getItemId());
                $buzzwordItem->setName($hashtag->getName());
                $buzzwordItem->save();
            }

            return $this->redirectToRoute('app_hashtag_edit', [
                'roomId' => $roomId,
            ]);
        }

        $hashtags = $labelRepository->findRoomHashtags($roomId);
        foreach ($hashtags as $hashtag) {
            $hashtag->setName(html_entity_decode($hashtag->getName()));
        }

        $mergeData = new MergeHashtags();

        $mergeForm = $this->createForm(HashtagMergeType::class, $mergeData, [
            'roomId' => $roomId
        ]);

        $mergeForm->handleRequest($request);
        if ($mergeForm->isSubmitted() && $mergeForm->isValid()) {
            // persist changes / delete hashtag
            $labelManager = $legacyEnvironment->getLabelManager();

            $firstId = $mergeData->getFirst()->getItemId();
            $secondId = $mergeData->getSecond()->getItemId();

            $buzzwordItemOne = $labelManager->getItem($firstId);
            $buzzwordItemTwo = $labelManager->getItem($secondId);

            // change name of item one, save it and delete the item two
            $buzzwordOne = $buzzwordItemOne->getName();
            $buzzwordTwo = $buzzwordItemTwo->getName();

            $newName = $buzzwordOne. "/" . $buzzwordTwo;

            $buzzwordItemOne->setName($newName);
            $buzzwordItemOne->setModificationDate(getCurrentDateTimeInMySQL());
            $buzzwordItemOne->save();
            //Get links to create new hashtag links
            $managerLink = $legacyEnvironment->getLinkManager();
            $links = $managerLink->getLinksTo2('buzzword_for', $secondId);
            foreach ($links as $link) {
                $link_array = array();
                $link_array['room_id'] = $roomId;
                $link_array['from_item_id'] = $link['from_item_id'];
                $link_array['to_item_id'] = $buzzwordItemOne->getItemID();
                $link_array['to_version_id'] = $link['to_version_id'];
                $link_array['from_version_id'] = $link['from_version_id'];
                $link_array['link_type'] = $link['link_type'];
                $managerLink->save($link_array);
            }
            $buzzwordItemTwo->delete();

            return $this->redirectToRoute('app_hashtag_edit', [
                'roomId' => $roomId,
            ]);
        }

        $eventDispatcher->dispatch(new CommsyEditEvent(null), CommsyEditEvent::EDIT);

        return [
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'hashtags' => $hashtags,
            'labelId' => $labelId,
            'mergeForm' => $mergeForm->createView(),
        ];
    }
}
