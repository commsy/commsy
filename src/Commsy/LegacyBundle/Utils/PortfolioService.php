<?php

namespace Commsy\LegacyBundle\Utils;

use Symfony\Component\Form\Form;

use Commsy\LegacyBundle\Services\LegacyEnvironment;

class PortfolioService
{
    private $legacyEnvironment;

    private $portfolioManager;

    public function __construct(LegacyEnvironment $legacyEnvironment)
    {
        $this->legacyEnvironment = $legacyEnvironment;

        $this->portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        $this->portfolioManager->reset();
    }


    public function getPortfolio($itemId)
    {
        $portfolio = $this->portfolioManager->getItem($itemId);
        return $portfolio;
    }

    public function getPortfolioList()
    {
        $return = array(
            "myPortfolios"			=> array(),
            "activatedPortfolios"	=> array()
        );

        $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUser();
        $privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();

        $this->portfolioManager->reset();
        $this->portfolioManager->setUserLimit($privateRoomUser->getItemID());
        $this->portfolioManager->select();
        $portfolioList = $this->portfolioManager->get();

        $myPortfolios = array();
        $portfolioItem = $portfolioList->getFirst();
        while ($portfolioItem) {
            $externalViewer = $this->portfolioManager->getExternalViewer($portfolioItem->getItemID());
            $externalViewerString = implode(";", $externalViewer);

            $myPortfolios[] = array(
                "id"		=> $portfolioItem->getItemID(),
                "title"		=> $portfolioItem->getTitle(),
                "external"  => $externalViewerString != "" ? $externalViewer : array()
            );

            $portfolioItem = $portfolioList->getNext();
        }
        $return["myPortfolios"] = $myPortfolios;

        $activatedPortfolios = array();
        $activatedIdArray = $this->portfolioManager->getActivatedIDArray($privateRoomUser->getUserID());

        if (!empty($activatedIdArray)) {
            $this->portfolioManager->reset();
            $this->portfolioManager->setIDArrayLimit($activatedIdArray);
            $this->portfolioManager->select();
            $portfolioList = $this->portfolioManager->get();

            if (!$portfolioList->isEmpty()) {
                $portfolioItem = $portfolioList->getFirst();
                while ($portfolioItem) {
                    $activatedPortfolios[] = array(
                        "id"		=> $portfolioItem->getItemID(),
                        "title"		=> $portfolioItem->getTitle()
                    );

                    $portfolioItem = $portfolioList->getNext();
                }
            }
        }
        $return["activatedPortfolios"] = $activatedPortfolios;

        return $return;
    }
}
