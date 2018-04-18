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

        /*
        Array
            (
                [5180910] => Array
                    (
                        [0] => Array
                            (
                                [itemId] => 5180935
                                [title] => Dokumentation der Hospitationen
                            )

                        [1] => Array
                            (
                                [itemId] => 5180919
                                [title] => Meine Ziele &amp; Reflexionen
                            )

                        [2] => Array
                            (
                                [itemId] => 5180962
                                [title] => 3. HSL-Hospi/Peter Koch/Deutsch/01.03.2017
                            )

                        [3] => Array
                            (
                                [itemId] => 5180938
                                [title] => 2. FSL-Hospi/Alexandra Bauer/Sachunterricht/25.04.2017
                            )

                        [4] => Array
                            (
                                [itemId] => 5180959
                                [title] => 3. FSL-Hospi/Irene Strothmann/Deutsch/20.02.2017
                            )

                        [5] => Array
                            (
                                [itemId] => 5180941
                                [title] => 2. HSL-Hospi/Peter Koch/Sachunterricht/26.09.2016
                            )

                        [6] => Array
                            (
                                [itemId] => 5180950
                                [title] => 1. FSL-Hospi/Irene Strothmann/Deutsch/03.06.2016
                            )

                        [7] => Array
                            (
                                [itemId] => 5180916
                                [title] => 1. HSL-Hospi/Peter Koch/Deutsch/12.04.2016
                            )

                        [8] => Array
                            (
                                [itemId] => 5180953
                                [title] => 2. FSL-Hospi/Irene Strothmann/Deutsch/17.11.2016
                            )

                        [9] => Array
                            (
                                [itemId] => 5180956
                                [title] => 1. FSL-Hospi/Kattrin Hennicke/Sachunterricht/15.12.2016
                            )

                        [10] => Array
                            (
                                [itemId] => 5180944
                                [title] => Vorlage 16-02 Hospitationsauswertung 04
                            )

                        [11] => Array
                            (
                                [itemId] => 5180947
                                [title] => Vorlage 16-02 Hospitationsauswertung 05
                            )

                    )

                [5180911] => Array
                    (
                        [0] => Array
                            (
                                [itemId] => 5180962
                                [title] => 3. HSL-Hospi/Peter Koch/Deutsch/01.03.2017
                            )

                        [1] => Array
                            (
                                [itemId] => 5180938
                                [title] => 2. FSL-Hospi/Alexandra Bauer/Sachunterricht/25.04.2017
                            )

                        [2] => Array
                            (
                                [itemId] => 5180959
                                [title] => 3. FSL-Hospi/Irene Strothmann/Deutsch/20.02.2017
                            )

                        [3] => Array
                            (
                                [itemId] => 5180941
                                [title] => 2. HSL-Hospi/Peter Koch/Sachunterricht/26.09.2016
                            )

                        [4] => Array
                            (
                                [itemId] => 5180950
                                [title] => 1. FSL-Hospi/Irene Strothmann/Deutsch/03.06.2016
                            )

                        [5] => Array
                            (
                                [itemId] => 5180916
                                [title] => 1. HSL-Hospi/Peter Koch/Deutsch/12.04.2016
                            )

                        [6] => Array
                            (
                                [itemId] => 5180953
                                [title] => 2. FSL-Hospi/Irene Strothmann/Deutsch/17.11.2016
                            )

                        [7] => Array
                            (
                                [itemId] => 5180956
                                [title] => 1. FSL-Hospi/Kattrin Hennicke/Sachunterricht/15.12.2016
                            )

                        [8] => Array
                            (
                                [itemId] => 5180944
                                [title] => Vorlage 16-02 Hospitationsauswertung 04
                            )

                        [9] => Array
                            (
                                [itemId] => 5180947
                                [title] => Vorlage 16-02 Hospitationsauswertung 05
                            )

                    )

                [5180913] => Array
                    (
                        [0] => Array
                            (
                                [itemId] => 5180919
                                [title] => Meine Ziele &amp; Reflexionen
                            )

                    )

                [5180912] => Array
                    (
                        [0] => Array
                            (
                                [itemId] => 5180935
                                [title] => Dokumentation der Hospitationen
                            )

                    )

            )
        */

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
