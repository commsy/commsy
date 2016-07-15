<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type\HashtagEditType;
use CommsyBundle\Entity\Labels;

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
     * @Route("/room/{roomId}/hashtag/edit/{labelId}")
     * @Template()
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
        } else {
            $hashtag = new Labels();
            $hashtag->setContextId($roomId);
            $hashtag->setType('buzzword');
        }

        $form = $this->createForm(HashtagEditType::class, $hashtag);

        $form->handleRequest($request);
        if ($form->isValid()) {
            // persist changes / delete hashtag
            $labelManager = $legacyEnvironment->getLabelManager();

            if ($form->has('delete') && $form->get('delete')->isClicked()) {
                $buzzwordItem = $labelManager->getItem($hashtag->getItemId());
                $buzzwordItem->delete();
            }

            if ($form->has('new') && $form->get('new')->isClicked()) {
                $buzzwordItem = $labelManager->getNewItem();

                $buzzwordItem->setLabelType('buzzword');
                $buzzwordItem->setContextID($hashtag->getContextId());
                $buzzwordItem->setCreatorItem($legacyEnvironment->getCurrentUserItem());
                $buzzwordItem->setName($hashtag->getName());

                $buzzwordItem->save();
            }

            if ($form->has('update') && $form->get('update')->isClicked()) {
                $buzzwordItem = $labelManager->getItem($hashtag->getItemId());

                $buzzwordItem->setName($hashtag->getName());

                $buzzwordItem->save();
            }

            return $this->redirectToRoute('commsy_hashtag_edit', [
                'roomId' => $roomId,
            ]);
        }

        $hashtags = $repository->findRoomHashtags($roomId);

        return [
            'form' => $form->createView(),
            'roomId' => $roomId,
            'hashtags' => $hashtags,
            'labelId' => $labelId,
        ];
    }
}
