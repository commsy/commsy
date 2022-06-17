<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, José Manuel González Vázquez
//
//    This file is part of CommSy.
//
//    CommSy is free software; you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation; either version 2 of the License, or
//    (at your option) any later version.
//
//    CommSy is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You have received a copy of the GNU General Public License
//    along with CommSy.

/** upper class of the annotation item
 */
include_once('classes/cs_item.php');

/** class for an annotation
 * this class implements a annotation item
 */
class cs_annotation_item extends cs_item {

    /** constructor: cs_annotation_item
     * the only available constructor, initial values for internal variables
     */
    public function __construct($environment)
    {
        parent::__construct($environment);

        $this->_type = CS_ANNOTATION_TYPE;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        // annotations do not have a title anymore
        return '';
    }

    /** get description of an annotation
     * this method returns the description of the annotation
     *
     * @return string description of an annotation
     *
     * @author CommSy Development Group
     */
    public function getDescription(): string
    {
        if ($this->getPublic() == '-1') {
            $translator = $this->_environment->getTranslationObject();
            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        } else {
            return $this->_getValue('description');
        }
    }

    /** set description of an annotation
     * this method sets the description of the annotation
     *
     * @param string value description of the annotation
     *
     * @author CommSy Development Group
     */
    public function setDescription(string $value)
    {
        $this->_setValue('description', $value);
    }

    /** get linked item id of an annotation
     * this method gets the linked item id of the annotation
     *
     * @return int value linked item id of the annotation
     *
     * @author CommSy Development Group
     */
    public function getLinkedItemID()
    {
        return $this->_getValue('linked_item_id');
    }

    public function getLinkedItem()
    {
        $retour = null;
        $item_id = $this->getLinkedItemID();
        if (!empty($item_id)) {
            $manager = $this->_environment->getItemManager();
            $item_ref = $manager->getItem($item_id);
            $type = $item_ref->getItemType();
            $type_manager = $this->_environment->getManager($type);
            $retour = $type_manager->getItem($item_id);
        }
        return $retour;
    }

    /** set linked item id of an annotation
     * this method sets the linked item id of the annotation
     *
     * @param int value linked item id of the annotation
     *
     * @author CommSy Development Group
     */
    public function setLinkedItemID($value)
    {
        $this->_setValue('linked_item_id', $value);
    }

    /** get linked version id of an annotation
     * this method gets the linked version id of the annotation
     *
     * @return int value linked version id of the annotation
     *
     * @author CommSy Development Group
     */
    public function getLinkedVersionID()
    {
        return $this->_getValue('linked_version_id');
    }

    /** set linked version id of an annotation
     * this method sets the linked version id of the annotation
     *
     * @param int value linked version id of the annotation
     *
     * @author CommSy Development Group
     */
    public function setLinkedVersionID($value)
    {
        $this->_setValue('linked_version_id', $value);
    }

    /** get materials of a announcement
     * this method returns a list of materials which are linked to the announcement
     *
     * @return object cs_list a list of materials (cs_material_item)
     *
     * @author CommSy Development Group
     */
    public function getMaterialList()
    {
        return $this->getLinkedItemList(CS_MATERIAL_TYPE);
    }

    /** set materials of a announcement item by item id and version id
     * this method sets a list of material item_ids and version_ids which are linked to the announcement
     *
     * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    public function setMaterialListByID($value)
    {
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    /** set materials of a announcement
     * this method sets a list of materials which are linked to the news
     *
     * @param string value title of the news
     *
     * @author CommSy Development Group
     */
    public function setMaterialList($value)
    {
        $this->_setObject(CS_MATERIAL_TYPE, $value, false);
    }

    /** set the version id of the annotated item
     * @param integer version id of the annotated item
     * @author CommSy Development Group
     */
    public function setAnnotatedVersionID($vid)
    {
#      $this->_setValue('annotated_version', $vid);
        $this->_anno_version = $vid;
    }

    /** get the version id of the annotated item
     * @return integer version id of the annotated item
     * @author CommSy Development Group
     */
    public function getAnnotatedVersionID()
    {
        return $this->_getValue('linked_version_id');
    }

    /**
     * save
     */
    public function save()
    {
        $annotation_manager = $this->_environment->getAnnotationManager();
        $this->_save($annotation_manager);
        $this->_saveFiles();     // this must be done before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving item so we can be sure to have an item id

        // update linked item in elastic
        $linkedItem = $this->getLinkedItem();
        $linkedItem->updateElastic();
    }

    public function delete()
    {
        $annotation_manager = $this->_environment->getAnnotationManager();
        $this->_delete($annotation_manager);
    }

    public function mayEdit(cs_user_item $user_item)
    {
        $access = false;
        if (!$user_item->isOnlyReadUser()) {
            if ($user_item->isRoot() ||
                ($user_item->getContextID() == $this->getContextID() && $user_item->isModerator()) ||
                ($user_item->isUser() && ($user_item->getItemID() === $this->getCreatorID() || $this->isPublic()))
            ) {
                $access = true;
            }
        }
        if (!$access) {
            $item_manager = $this->_environment->getItemManager();
            $item = $this->getLinkedItem();
            $access = $item_manager->getExternalViewerForItem($item->getItemID(),
                $this->_environment->getCurrentUserID());
        }
        return $access;
    }

    /** \brief    check via portfolio permission
     *
     * This Method checks for item <=> activated portfolio - relationships
     */
    public function mayPortfolioSee(string $username)
    {
        $portfolioManager = $this->_environment->getPortfolioManager();

        // get portfolio id for this annotation
        $portfolioId = $portfolioManager->getPortfolioId($this->getItemId());

        // get all ids from portfolios we are allow to see
        $portfolioIds = $portfolioManager->getPortfolioForExternalViewer($username);

        // if the portfolio this annotation belongs to is in the list, we are allowed to see
        return in_array($portfolioId, $portfolioIds);
    }
}