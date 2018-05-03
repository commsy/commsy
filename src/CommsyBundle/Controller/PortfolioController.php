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

    /**
     * @Route("/room/{roomId}/portfolio/{portfolioId}", requirements={
     *     "portfolioId": "\d+"
     * }))
     * @Template()
     */
    public function portfolioAction($roomId, $portfolioId = null, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolio = $portfolioService->getPortfolio($portfolioId);

        $linkItemIds = [];
        foreach ($portfolio['links'] as $linkArray) {
            foreach ($linkArray as $link) {
                $linkItemIds[] = $link['itemId'];
            }
        }
        $linkItemIds = array_unique($linkItemIds);

        $linkPositions = [];
        foreach ($linkItemIds as $linkItemId) {
            foreach ($portfolio['tags'] as $firstTag) {
                foreach ($portfolio['tags'] as $secondTag) {
                    if ($firstTag['t_id'] != $secondTag['t_id']) {
                        $foundFirstTag = false;
                        $foundSecondTag = false;
                        foreach ($portfolio['links'] as $tagId => $linkArray) {
                            if ($tagId == $firstTag['t_id'] || $tagId == $secondTag['t_id']) {
                                foreach ($linkArray as $link) {
                                    if ($linkItemId = $link['itemId']) {
                                        if ($tagId == $firstTag['t_id']) {
                                            $foundFirstTag = true;
                                        }
                                        if ($tagId == $secondTag['t_id']) {
                                            $foundSecondTag = true;
                                        }
                                    }
                                }
                            }
                        }
                        if ($foundFirstTag && $foundSecondTag) {
                            $positionFound = false;
                            if (isset($linkPositions[$linkItemId])) {
                                foreach ($linkPositions[$linkItemId] as $tempPosition) {
                                    if (($tempPosition[0] == $firstTag['t_id'] && $tempPosition[1] == $secondTag['t_id']) || ($tempPosition[0] == $secondTag['t_id'] && $tempPosition[1] == $firstTag['t_id'])) {
                                        $positionFound = true;
                                    }
                                }
                            }
                            if (!$positionFound) {
                                $linkPositions[$linkItemId][] = [$firstTag['t_id'], $secondTag['t_id']];
                            }
                        }
                    }
                }
            }
        }

        return array(
            'roomId' => $roomId,
            'portfolio' => $portfolio,
            'linkPositions' => $linkPositions,
        );
    }

    /**
     * @Route("/room/{roomId}/portfolio/portfoliosource/{source}")
     * @Template()
     */
    public function portfolioTabsAction($roomId, $source = null, Request $request)
    {
        $portfolioService = $this->get('commsy_legacy.portfolio_service');
        $portfolioList = $portfolioService->getPortfolioList();

        $portfolios = [];
        if ($source == 'my-portfolios') {
            $portfolios = $portfolioList['myPortfolios'];
        } else if ($source == "activated-portfolios") {
            $portfolios = $portfolioList['activatedPortfolios'];
        }

        return array(
            'portfolios' => $portfolios,
        );
    }
}
