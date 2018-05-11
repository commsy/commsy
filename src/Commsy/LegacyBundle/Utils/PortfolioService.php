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
        $portfolioItem = $this->portfolioManager->getItem($itemId);

        $userManager = $this->legacyEnvironment->getEnvironment()->getUserManager();
        $userItem = $userManager->getItem($portfolioItem->getCreatorId());
        $privateRoom = $userItem->getOwnRoom();

        // gather tag information
        $tags = $this->portfolioManager->getPortfolioTags($itemId);
        $tagIdArray = array();
        foreach ($tags as $tag) {
            $tagIdArray[] = $tag["t_id"];
        }

        // gather linked cell information
        $linkManager = $this->legacyEnvironment->getEnvironment()->getLinkItemManager();
        $links = $linkManager->getALlLinksByTagIDArray($privateRoom->getItemID(), $tagIdArray);

        $rubricArray = array();

        // structure links by rubric
        if (is_array($links)) {
            foreach ($links as $link) {
                if ($link["first_item_type"] === CS_TAG_TYPE) {
                    $rubricArray[$link["second_item_type"]][$link["first_item_id"]][] = $link["second_item_id"];
                } else if ($link["second_item_type"] === CS_TAG_TYPE) {
                    $rubricArray[$link["first_item_type"]][$link["second_item_id"]][] = $link["first_item_id"];
                }
            }
        }

        // fetch items
        $linkArray = array();
        foreach ($rubricArray as $rubric => $tagArray) {
            foreach($tagArray as $tagId => $idArray) {
                $manager = $this->legacyEnvironment->getEnvironment()->getManager($rubric);
                $manager->resetLimits();
                $manager->setIDArrayLimit($idArray);
                $manager->setContextLimit($privateRoom->getItemID());
                $manager->select();

                $itemList = $manager->get();
                $item = $itemList->getFirst();

                while ($item) {
                    $itemInformation = array(
                        "itemId"	=> $item->getItemId(),
                        "title"		=> $item->getTitle(),
                        "itemType"      => $item->getItemType()
                    );

                    $linkArray[$tagId][] = $itemInformation;

                    $item = $itemList->getNext();
                }
            }
        }

        $translator = $this->legacyEnvironment->getEnvironment()->getTranslationObject();
        $creatorItem = $portfolioItem->getCreatorItem();
        if (isset($creatorItem) && !$creatorItem->isDeleted()) {
            if ($creatorItem->isGuest() && $modificator->isVisibleForLoggedIn()) {
                $fullname = $translator->getMessage("COMMON_USER_NOT_VISIBLE");
            } else {
                $fullname = $creatorItem->getFullName();
            }
        } else {
            $fullname = $translator->GetMessage("COMMON_DELETED_USER");
        }

        $externalViewer = $this->portfolioManager->getExternalViewer($itemId);
        $externalViewerString = implode(";", $externalViewer);

        $externalTemplate = $this->portfolioManager->getExternalTemplate($itemId);
        $externalTemplateString = implode(";", $externalTemplate);

        $template = $portfolioItem->isTemplate();

        $return = array(
            "contextId"			=> $privateRoom->getItemID(),
            "title"				=> $portfolioItem->getTitle(),
            "description"		=> $portfolioItem->getDescription(),
            "externalViewer"	=> $externalViewerString,
            "externalTemplate"	=> $externalTemplateString,
            "template"			=> $template,
            "creator"			=> $fullname,
            "tags"				=> $tags,
            "links"				=> $linkArray,
            "numAnnotations"	=> $this->portfolioManager->getAnnotationCountForPortfolio($itemId)
        );

        return $return;
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

    public function getCellCoordinatesForTagIds ($portfolioId, $firstTagId, $secondTagId) {
        $portfolio = $this->getPortfolio($portfolioId);
        $columnCounter = -1;
        foreach ($portfolio['tags'] as $firstTag) {
            $rowCounter = 0;
            foreach ($portfolio['tags'] as $secondTag) {
                if ($firstTag['t_id'] == $firstTagId && $secondTag['t_id'] == $secondTagId) {
                    return [$columnCounter, $rowCounter];
                }
                $rowCounter++;
            }
            $columnCounter++;
        }
        return [];
    }

    function setPortfolioAnnotation($portfolioId, $annotationId, $portfolioRow, $portfolioColumn) {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        $portfolioManager->setPortfolioAnnotation($portfolioId, $annotationId, $portfolioRow, $portfolioColumn);
    }

    function getAnnotationIdsForPortfolioCell($portfolioId, $row, $column) {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        return $portfolioManager->getAnnotationIdsForPortfolioCell($portfolioId, $row, $column);
    }
}
