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

    public function getPortfolioTags($portfolioId)
    {
        return $this->portfolioManager->getPortfolioTags($portfolioId);
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
            $fullname = $creatorItem->getFullName();
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
            "numAnnotations"	=> $this->portfolioManager->getAnnotationCountForPortfolio($itemId),
            "creatorId"		    => $portfolioItem->getCreatorID()
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
            if (!$portfolioItem->isDraft()) {
                $externalViewer = $this->portfolioManager->getExternalViewer($portfolioItem->getItemID());
                $externalViewerString = implode(";", $externalViewer);

                $myPortfolios[] = array(
                    "id" => $portfolioItem->getItemID(),
                    "title" => $portfolioItem->getTitle(),
                    "external" => $externalViewerString != "" ? $externalViewer : array()
                );
            }
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

    /**
     * Returns available templates for the current user
     *
     * @return array
     */
    public function getPortfolioTemplates(): array
    {
        $currentUser = $this->legacyEnvironment->getEnvironment()->getCurrentUser();

        $templateIds = $this->portfolioManager->getPortfolioForExternalTemplate($currentUser->getUserID());
        $templates = [];

        foreach ($templateIds as $templateId) {
            $portfolio = $this->portfolioManager->getItem($templateId);
            $templates[] = [
                'id' => $templateId,
                'title' => $portfolio->getTitle(),
            ];
        }

        $privateRoom = $currentUser->getOwnRoom();
        $privateRoomUserItem = $currentUser->getRelatedUserItemInContext($privateRoom->getItemId());

        $ownTemplates = $this->portfolioManager->getTemplatePortfoliosByCreatorID($privateRoomUserItem->getItemID());
        foreach ($ownTemplates as $portfolioId => $title) {
            $templates[] = [
                'id' => $portfolioId,
                'title' => $title,
            ];
        }

        return $templates;
    }

    public function prepareFromTemplate(int $templateId, \cs_portfolio_item $portfolioItem)
    {
        $legacyEnvironment = $this->legacyEnvironment->getEnvironment();

        // get the template portfolio
        $templatePortfolioItem = $this->portfolioManager->getItem($templateId);
        $templatePortfolioCreator = $templatePortfolioItem->getCreator();
        $templatePortfolioContext = $templatePortfolioCreator->getOwnRoom();

        // create a portfolio tag under "ROOT" for the template in the users "private" context
        $tagManager = $legacyEnvironment->getTagManager();

        $currentUser = $legacyEnvironment->getCurrentUserItem();
        $privateRoom = $currentUser->getOwnRoom();
        $privateRoomUser = $currentUser->getRelatedPrivateRoomUserItem();
        $rootTagItem = $tagManager->getRootTagItemFor($privateRoom->getItemId());

        $legacyEnvironment->changeContextToPrivateRoom($privateRoom->getItemId());

        $newPortfolioTag = $tagManager->getNewItem();
        $newPortfolioTag->setTitle("Portfolio Import: " . $templatePortfolioItem->getTitle());
        $newPortfolioTag->setContextID($privateRoom->getItemId());
        $newPortfolioTag->setCreatorItem($privateRoomUser);
        $newPortfolioTag->setCreationDate(getCurrentDateTimeInMySQL());
        $newPortfolioTag->setPosition($rootTagItem->getItemID(), $rootTagItem->getChildrenList()->getCount() + 1);
        $newPortfolioTag->save();

        // gather template tag information and create new tags for all
        // portfolio template tags under the created one
        $templatePortfolioTags = $this->portfolioManager->getPortfolioTags($templateId);
        $templateTagIdArray = array();
        $tagMapping = array();
        foreach ($templatePortfolioTags as $templatePortfolioTag) {
            $templateTag = $tagManager->getItem($templatePortfolioTag['t_id']);

            $newTag = $tagManager->getNewItem();
            $newTag->setTitle($templateTag->getTitle());
            $newTag->setContextID($privateRoom->getItemId());
            $newTag->setCreatorItem($privateRoomUser);
            $newTag->setCreationDate(getCurrentDateTimeInMySQL());
            $newTag->setPosition($newPortfolioTag->getItemID());
            $newTag->save();

            // add tags to new portfolio
            $this->portfolioManager->addTagToPortfolio(
                $portfolioItem->getItemID(),
                $newTag->getItemID(),
                $templatePortfolioTag['column'] == "0" ? "row" : "column",
                $templatePortfolioTag['column'] == "0" ? (int) $templatePortfolioTag['row'] : (int) $templatePortfolioTag['column'],
                $templatePortfolioTag['description']);

            $templateTagIdArray[] = $templatePortfolioTag['t_id'];
            $tagMapping[$templatePortfolioTag['t_id']] = $newTag->getItemID();
        }

        // gather linked cell information
        $linkManager = $legacyEnvironment->getLinkItemManager();
        $links = $linkManager->getALlLinksByTagIDArray($templatePortfolioContext->getItemID(), $templateTagIdArray);

        $rubricArray = array();

        // structure links by rubric
        foreach ($links as $link) {
            if ($link["first_item_type"] === CS_TAG_TYPE) {
                if (!isset($rubricArray[$link["second_item_type"]])) {
                    continue;
                }

                if (in_array($link["second_item_id"], $rubricArray[$link["second_item_type"]])) {
                    continue;
                }

                $rubricArray[$link["second_item_type"]][] = $link["second_item_id"];
            } else if($link["second_item_type"] === CS_TAG_TYPE) {
                if (!isset($rubricArray[$link["first_item_type"]])) {
                    continue;
                }

                if (in_array($link["first_item_id"], $rubricArray[$link["first_item_type"]])) {
                    continue;
                }

                $rubricArray[$link["first_item_type"]][] = $link["first_item_id"];
            }
        }

        // copy items
        $linkArray = array();
        foreach ($rubricArray as $rubric => $itemArray) {
            foreach($itemArray as $itemId) {
                $legacyEnvironment->changeContextToPrivateRoom($templatePortfolioContext->getItemId());

                $manager = $legacyEnvironment->getManager($rubric);
                $templateItem = $manager->getItem($itemId);
                $templateItemTagList = $templateItem->getTagList();

                $legacyEnvironment->changeContextToPrivateRoom($privateRoom->getItemId());

                $copyItem = $templateItem->copy();

                $templateItemTag = $templateItemTagList->getFirst();

                $copyTagArray = array();
                while ($templateItemTag) {
                    $templateItemTagId = $templateItemTag->getItemID();

                    if ($tagMapping[$templateItemTagId]) {
                        $copyTagArray[] = $tagMapping[$templateItemTagId];
                    }

                    $templateItemTag = $templateItemTagList->getNext();
                }

                $copyItem->setTagListByID($copyTagArray);
                $copyItem->save();
            }
        }
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

    function addTagToPortfolio($portfolioId, $tagId, $position, $index, $description) {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        $portfolioManager->addTagToPortfolio($portfolioId, $tagId, $position, $index, $description);
    }

    function deletePortfolioTag($portfolioId, $tagId) {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        $portfolioManager->deletePortfolioTag($portfolioId, $tagId);
    }

    function getNewItem() {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        return $portfolioManager->getNewItem();
    }

    function replaceTagForPortfolio($portfolioId, $tagId, $oldTagId, $description) {
        $portfolioManager = $this->legacyEnvironment->getEnvironment()->getPortfolioManager();
        $portfolioManager->replaceTagForPortfolio($portfolioId, $tagId, $oldTagId, $description);
    }

}
