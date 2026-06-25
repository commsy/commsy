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

use App\Utils\ReaderService;

/** class for an discussionarticle
 * this class implements a discussionarticle item.
 */
class cs_discussionarticle_item extends cs_item
{
    /** constructor
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = 'discarticle';
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array): void
    {
        // not yet implemented
        $this->_data = $data_array;
    }

     /**
      * Returns whether this item's content should get overwritten with some placeholder text.
      *
      * @return bool Whether this item's content should get overwritten (true), or not (false)
      */
     public function getHasOverwrittenContent(): bool
     {
         // NOTE: `public = -2` gets used for articles with answers which were "deleted" but should
         // instead have their content overwritten to keep the discussion hierarchy intact
         return '-2' == $this->getPublic();
     }

    public function getPosition()
    {
        return $this->_getValue('position');
    }

    public function setPosition($value)
    {
        $this->_setValue('position', $value);
    }

    /**
     * Returns the description of this discussion article.
     *
     * @return string|null description of the discussion article
     */
    public function getDescription(): ?string
    {
        $public = $this->getPublic();
        if ('-1' == $public || '-2' == $public) {
            $translator = $this->_environment;
            $message = ('-1' == $public) ? 'COMMON_AUTOMATIC_DELETE_DESCRIPTION' : 'COMMON_DELETED_DISCARTICLE_WITH_ANSWERS_DESC';

            return $translator->translate($message);
        }

        return $this->_getValue('description');
    }

    /** set description of a discussionarticle
     * this method sets the description of the discussionarticle.
     *
     * @param string $value description of the discussionarticle
     *
     * @author CommSy Development Group
     */
    public function setDescription(string $value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    /** get discussion id
     * this method returns the discussion id of the article.
     *
     * @return int discussion id of the article
     *
     * @author CommSy Development Group
     */
    public function getDiscussionID()
    {
        return $this->_getValue('discussion_id');
    }

     public function getLinkedItem(): ?cs_discussion_item
     {
         $item_id = $this->getDiscussionID();
         if (!empty($item_id)) {
             $type_manager = $this->_environment->getManager(CS_DISCUSSION_TYPE);
             return $type_manager->getItem($item_id);
         }

         return null;
     }

    /** set discussion id
     * this method sets the discussion id of the article.
     *
     * @param int value discussion id of the article
     *
     * @author CommSy Development Group
     */
    public function setDiscussionID($value)
    {
        $this->_setValue('discussion_id', $value);
    }

    /** set materials of the discussionarticle item
     * this method sets a list of materials which are linked to the discussionarticle item.
     *
     * @param cs_list list of cs_material_item
     *
     * @author CommSy Development Group
     */
    public function setMaterialList($value)
    {
        $this->_setObject(CS_MATERIAL_TYPE, $value, false);
    }

    /** set materials of a discussionarticle item by id
     * this method sets a list of group item_ids which are linked to the discussionarticle.
     *
     * @param array of group ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     */
    public function setMaterialListByID($value)
    {
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    /** get materials of the discussionarticle item
     * this method returns a list of materials which are linked to the discussionarticle.
     *
     * @return object cs_list a list of cs_material_item
     */
    public function getMaterialList()
    {
        return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
    }

    /** save discussion article
     * this methode saves the discussion article.
     */
    public function save(): void
    {
        $discussion_manager = $this->_environment->getDiscussionArticlesManager();
        $this->_save($discussion_manager);

        // Update the discussion regarding the latest article informations...
        $discussion_manager = $this->_environment->getDiscussionManager();
        $discussion_item = $discussion_manager->getItem($this->getDiscussionID());
        $current_user = $this->_environment->getCurrentUserItem();
        $discussion_item->setModificatorItem($current_user);
        if (!$discussion_item->isNotActivated()) {
            $discussion_item->setModificationDate($this->getModificationDate());
        }
        $discussion_item->setLatestArticleID($this->getItemID());
        $discussion_item->setLatestArticleModificationDate($this->getModificationDate());

        $itemManager = $this->_environment->getItemManager();
        $articleItem = $itemManager->getItem($this->getItemID());

        if (!$articleItem->isDraft()) {
            $discussion_item->save();
        }

        $this->_saveFiles();     // this must be done before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id

        /** @var ReaderService $readerService */
        $readerService = $this->_environment->getSymfonyContainer()->get(ReaderService::class);
        $readerService->markItemAsRead($this);
    }

    public function saveWithoutChangingModificationInformation(): void
    {
        $manager = $this->_environment->getManager($this->_type);
        $manager->saveWithoutChangingModificationInformation();
        $this->_save($manager);
    }

}
