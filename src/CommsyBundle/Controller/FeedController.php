<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FeedController extends Controller
{
    /**
     * @Route("/room/{roomId}/feed/{start}")
     * @Template("CommsyBundle:Feed:list.html.twig")
     */
    public function homeFeedAction($roomId, $max = 10, $start = 0)
    {
        // collect information for feed panel
        $roomFeedGenerator = $this->get('commsy.room_feed_generator');
        $feedList = $roomFeedGenerator->getFeedList($roomId, $max, $start);

        return array(
            'feedList' => $feedList
        );
    }
}