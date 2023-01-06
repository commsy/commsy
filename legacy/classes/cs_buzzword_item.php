<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 */
class cs_buzzword_item extends cs_label_item
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param string label_type type of the label
     */
    public function __construct($environment)
    {
        parent::__construct($environment, CS_BUZZWORD_TYPE);
    }

    /** save news item
     * this methode save the news item into the database.
     */
    public function saveMaterialLinksByIDArray($array)
    {
        $links_manager = $this->_environment->getLinkManager();
        $links_manager->saveLinksMaterialToBuzzword($array, $this->getItemID());
    }

    public function saveRubricLinksByIDArray($array, $rubric)
    {
        $links_manager = $this->_environment->getLinkManager();
        $links_manager->saveLinksRubricToBuzzword($array, $this->getItemID(), $rubric);
    }

     /**
      * Sets linked items for this buzzword.
      *
      * @param  array $rubricIdArray Array of item id's to set
      */
     public function saveLinksByIDArray($rubricIdArray)
     {
         $itemManager = $this->_environment->getItemManager();

         /**
          * We need to store all links per rubric, so we iterate $rubricIdArray and build up
          * a per rubric array.
          */
         $rubricUpdateArray = [];

         if (!empty($rubricIdArray)) {
             $newLinkItemList = $itemManager->getItemList($rubricIdArray);

             if ($newLinkItemList && $newLinkItemList->isNotEmpty()) {
                 $newItem = $newLinkItemList->getFirst();
                 while ($newItem) {
                     $rubricUpdateArray[$newItem->getItemType()][] = $newItem->getItemID();

                     $newItem = $newLinkItemList->getNext();
                 }
             }
         }

         // check update types and convert "label" types to real modules
         if (!empty($rubricUpdateArray['label'])) {
             $labelManager = $this->_environment->getLabelManager();
             $labelManager->setIdArrayLimit($rubricUpdateArray['label']);
             $labelManager->select();

             $labelList = $labelManager->get();
             if ($labelList && $labelList->isNotEmpty()) {
                 $labelItem = $labelList->getFirst();
                 while ($labelItem) {
                     $rubricUpdateArray[$labelItem->getLabelType()][] = $labelItem->getItemID();

                     $labelItem = $labelList->getNext();
                 }
             }
         }

         /**
          * Compare the update array with the currently linked items to add rubrics that
          * may be deleted completly.
          */
         $currentLinkIdArray = $this->getAllLinkedItemIDArrayLabelVersion();
         $currentLinkItemList = $itemManager->getItemList($currentLinkIdArray);

         if ($currentLinkItemList && $currentLinkItemList->isNotEmpty()) {
             $currentItem = $currentLinkItemList->getFirst();
             while ($currentItem) {
                 if (!isset($rubricUpdateArray[$currentItem->getItemType()])) {
                     $rubricUpdateArray[$currentItem->getItemType()] = [];
                 }

                 $currentItem = $currentLinkItemList->getNext();
             }
         }

         // save per rubric
         if (!empty($rubricUpdateArray)) {
             foreach ($rubricUpdateArray as $rubric => $idArray) {
                 $this->saveRubricLinksByIDArray($idArray, $rubric);
             }
         }
     }

    public function getAllLinkedItemIDArrayLabelVersion()
    {
        $retour = [];
        $manager = $this->_environment->getLinkManager();
        $links = $manager->getLinks('buzzword_for', $this);
        if (!empty($links)) {
            foreach ($links as $link) {
                if (!empty($link['from_item_id'])
                     and !empty($link['to_item_id'])
                ) {
                    if ($link['from_item_id'] == $this->getItemID()) {
                        $retour[] = $link['to_item_id'];
                    } else {
                        $retour[] = $link['from_item_id'];
                    }
                }
            }
        }

        return $retour;
    }
}
