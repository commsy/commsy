<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

class FeedController extends Controller
{
    /**
     * @Template("CommsyBundle:Feed:list.html.twig")
     */
    public function homeFeedAction($roomId, $max = 10)
    {
        // collect information for feed panel
        $roomFeedGenerator = $this->get('commsy.room_feed_generator');
        $feedList = $roomFeedGenerator->getFeedList($roomId, $max);

        return array(
            'feedList' => $feedList
        );
    }
}