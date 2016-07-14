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
     * @Route("/room/{roomId}/hashtag/edit")
     * @Template()
     */
    public function editAction($roomId, Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        $hashtag = new Labels();
        $hashtag->setContextId($roomId);
        $hashtag->setType('buzzword');

        $form = $this->createForm(HashtagEditType::class, $hashtag);

        $form->handleRequest($request);
        if ($form->isValid()) {
            // persist new hashtag
            $legacyEnvironment = $this->get('commsy_legacy.environment')->getEnvironment();

            $labelManager = $legacyEnvironment->getLabelManager();

            $buzzwordItem = $labelManager->getNewItem();
            $buzzwordItem->setLabelType('buzzword');
            $buzzwordItem->setName($hashtag->getName());
            $buzzwordItem->setContextID($hashtag->getContextId());
            $buzzwordItem->setCreatorItem($legacyEnvironment->getCurrentUserItem());

            $buzzwordItem->save();
        }

        $hashtags = $em->getRepository('CommsyBundle:Labels')->findRoomHashtags($roomId);

        return [
            'form' => $form->createView(),
            'hashtags' => $hashtags,
        ];
    }
}
