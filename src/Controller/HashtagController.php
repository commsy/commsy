<?php

namespace App\Controller;

use App\Form\Model\MergeHashtags;
use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\HashtagEditType;
use App\Form\Type\HashtagMergeType;
use App\Entity\Labels;

use App\Event\CommsyEditEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class HashtagController extends Controller
{
    /**
     * @Route("/room/{roomId}/hashtag/add")
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function addAction(
        $roomId,
        Request $request,
        LegacyEnvironment $legacyEnvironment
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        $labelManager = $legacyEnvironment->getLabelManager();

        if (!$roomItem->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        $buzzwordId = null;
        $buzzwordItem = null;

        if ($request->request->get('title')) {
            $hashtagTitle = $request->request->get('title');

            $buzzwordItem = $labelManager->getNewItem();

            $buzzwordItem->setLabelType('buzzword');
            $buzzwordItem->setContextID($roomId);
            $buzzwordItem->setCreatorItem($legacyEnvironment->getCurrentUserItem());
            $buzzwordItem->setName($hashtagTitle);

            $buzzwordItem->save();

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
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function editAction(
        $roomId,
        $labelId = null,
        Request $request,
        LegacyEnvironment $legacyEnvironment,
        EventDispatcherInterface $eventDispatcher
    ) {
        $legacyEnvironment = $legacyEnvironment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if ($roomItem !== null && !$roomItem->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('App:Labels');

        if ($labelId) {
            $hashtag = $repository->findOneByItemId($labelId);
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
                $buzzwordItem = $labelManager->getNewItem();

                $buzzwordItem->setLabelType('buzzword');
                $buzzwordItem->setContextID($hashtag->getContextId());
                $buzzwordItem->setCreatorItem($legacyEnvironment->getCurrentUserItem());
                $buzzwordItem->setName($hashtag->getName());

                $buzzwordItem->save();
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

        $hashtags = $repository->findRoomHashtags($roomId);
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
            $buzzwordItemTwo->delete();

            return $this->redirectToRoute('app_hashtag_edit', [
                'roomId' => $roomId,
            ]);
        }

        $eventDispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));

        return [
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'hashtags' => $hashtags,
            'labelId' => $labelId,
            'mergeForm' => $mergeForm->createView(),
        ];
    }
}
