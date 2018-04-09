<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type\HashtagEditType;
use CommsyBundle\Form\Type\HashtagMergeType;
use CommsyBundle\Entity\Labels;

use CommsyBundle\Event\CommsyEditEvent;

class HashtagController extends Controller
{
    /**
     * @Template("CommsyBundle:Hashtag:show.html.twig")
     */
    public function showAction($roomId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('CommsyBundle:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Template("CommsyBundle:Hashtag:showDetail.html.twig")
     */
    public function showDetailAction($roomId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('CommsyBundle:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Template("CommsyBundle:Hashtag:showDetailShort.html.twig")
     */
    public function showDetailShortAction($roomId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $hashtags = $em->getRepository('CommsyBundle:Labels')
            ->findRoomHashtags($roomId);

        return array(
            'hashtags' => $hashtags
        );
    }

    /**
     * @Route("/room/{roomId}/hashtag/add")
     * @Security("is_granted('CATEGORY_EDIT')")
     */
    public function addAction($roomId, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

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
    public function editAction($roomId, $labelId = null, Request $request)
    {
        $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

        $roomManager = $legacyEnvironment->getRoomManager();
        $roomItem = $roomManager->getItem($roomId);

        if (!$roomItem->withBuzzwords()) {
            throw $this->createAccessDeniedException('The requested room does not have hashtags enabled.');
        }

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository('CommsyBundle:Labels');

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

            return $this->redirectToRoute('commsy_hashtag_edit', [
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

            return $this->redirectToRoute('commsy_hashtag_edit', [
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
