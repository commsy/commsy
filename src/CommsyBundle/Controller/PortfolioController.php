<?php

namespace CommsyBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use Symfony\Component\HttpFoundation\Request;

use CommsyBundle\Form\Type\CalendarEditType;
use CommsyBundle\Entity\Calendars;

use CommsyBundle\Event\CommsyEditEvent;

/**
 * Class CalendarController
 * @package CommsyBundle\Controller
 * @Security("is_granted('ITEM_ENTER', roomId)")
 */
class PortfolioController extends Controller
{
    /**
     * @Route("/room/{roomId}/portfolio/")
     * @Template()
     */
    public function indexAction($roomId, Request $request)
    {

    }
}
