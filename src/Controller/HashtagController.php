<?php

namespace App\Controller;

use App\Services\LegacyEnvironment;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use App\Form\Type\HashtagEditType;
use App\Form\Type\HashtagMergeType;
use App\Entity\Labels;

use App\Event\CommsyEditEvent;

class HashtagController extends AbstractController
{
    /**
     * @Template("hashtag/show.html.twig")
     * @param int $roomId
     * @return array
     */
    public function showAction(
        int $roomId
    ) {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('App:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Template("hashtag/showDetail.html.twig")
     */
    public function showDetailAction(
        int $roomId
    ) {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('App:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Template("hashtag/showDetailShort.html.twig")
     */
    public function showDetailShortAction(
        int $roomId
    ) {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('App:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Route("/room/{roomId}/hashtag/add")
     * @Security("is_granted('CATEGORY_EDIT')")
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @return JsonResponse
     */
    public function addAction(
        Request $request,
        LegacyEnvironment $environment,
        int $roomId
    ) {
        $legacyEnvironment = $environment->getEnvironment();

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
     * @param Request $request
     * @param LegacyEnvironment $environment
     * @param int $roomId
     * @param int|null $labelId
     * @return array|RedirectResponse
     */
    public function editAction(
        Request $request,
        LegacyEnvironment $environment,
        int $roomId,
        int $labelId = null
    ) {
        $legacyEnvironment = $environment->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem->withBuzzwords()) {
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

        $mergeForm = $this->createForm(HashtagMergeType::class, null, ['roomId'=>$roomId]);

        $mergeForm->handleRequest($request);
        if ($mergeForm->isSubmitted() && $mergeForm->isValid()) {
            // persist changes / delete hashtag
            $labelManager = $legacyEnvironment->getLabelManager();

            $mergeData=$mergeForm->getData();
            $firstID=$mergeData['first']->getItemId();
            $secondID=$mergeData['second']->getItemId();

            $buzzwordItemOne = $labelManager->getItem($firstID);
            $buzzwordItemTwo = $labelManager->getItem($secondID);
                    
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

        $dispatcher = $this->get('event_dispatcher');
        $dispatcher->dispatch('commsy.edit', new CommsyEditEvent(null));

        return [
            'editForm' => $editForm->createView(),
            'roomId' => $roomId,
            'hashtags' => $hashtags,
            'labelId' => $labelId,
            'mergeForm' => $mergeForm->createView(),
        ];
    }
}
