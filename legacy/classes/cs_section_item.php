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

/** class for a section
 * this class implements a section item.
 */
class cs_section_item extends cs_item
{
    /** constructor: cs_section_item
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = 'section';
    }

    public $_version_id_changed = false;

    public $_oldnumber = null;

    /** get title of a section
     * this method returns the title of the section.
     *
     * @return string title of a section
     *
     * @author CommSy Development Group
     */
    public function getTitle()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return $this->_getValue('title');
        }
    }

    /** set title of a section
     * this method sets the title of the section.
     *
     * @param string value title of the section
     *
     * @author CommSy Development Group
     */
    public function setTitle(string $title)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $title = htmlentities($title);
        $title = $converter->sanitizeHTML($title);
        $this->_setValue('title', $title);
    }

    /** get id of a linked material.
     *
     * @return int id of a material
     *
     * @author CommSy Development Group
     */
    public function getLinkedItemID()
    {
        return $this->_getValue('material_item_id');
    }

    public function getLinkedItem(): ?cs_material_item
    {
        $retour = null;
        $item_id = $this->getLinkedItemID();
        if (!empty($item_id)) {
            $type_manager = $this->_environment->getManager(CS_MATERIAL_TYPE);
            $retour = $type_manager->getItem($item_id);
        }

        return $retour;
    }

    /** set id of a linked material.
     *
     * @author CommSy Development Group
     */
    public function setLinkedItemID($value)
    {
        $this->_setValue('material_item_id', $value);
    }

    /** get description of a section
     * this method returns the description of the section.
     *
     * @return string description of a section
     *
     * @author CommSy Development Group
     */
    public function getDescription()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        } else {
            return $this->_getValue('description');
        }
    }

    /** set description of a section
     * this method sets the description of the section.
     *
     * @param string value description of the section
     *
     * @author CommSy Development Group
     */
    public function setDescription($description)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $description = $converter->sanitizeFullHTML($description);
        $this->_setValue('description', $description);
    }

    /** get version id of a material
     * this method returns the version id of the material.
     *
     * @return int version of the material
     *
     * @author CommSy Development Group
     */
    public function getVersionID()
    {
        return $this->_getValue('version_id');
    }

    /** set version id of a material
     * this method sets the version id of the material WITH marking the version id as 'changed'.
     * This is for loading initial values into the item.
     *
     * @return bool true: version id has changed -> new version of material
     *              false: some version of material
     *
     * @author CommSy Development Group
     */
    public function setVersionID($value)
    {
        $this->_version_id_changed = true;
        $this->_setValue('version_id', $value);
    }

    /** is the material a new version ???
     * this method returns a boolean whether it is an new version or not
     * This is for loading initial values into the item.
     *
     * @param string value title of the material
     *
     * @author CommSy Development Group
     */
    public function newVersion()
    {
        $this->_version_id_changed = true;
    }

    /** set materials of the section item
     * this method sets a list of materials which are linked to the section item.
     *
     * @param cs_list list of cs_material_item
     *
     * @author CommSy Development Group
     */
    public function setMaterialList($value)
    {
        $this->_setObject('CS_MATERIAL_TYPE', $value, false);
    }

    /** set materials of a section item by id
     * this method sets a list of group item_ids which are linked to the section.
     *
     * @param array of group ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    public function setMaterialListByID($value)
    {
        // $this->_setValue('material_for', $value, FALSE);
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    /** get materials of the section item
     * this method returns a list of materials which are linked to the section.
     *
     * @return object cs_list a list of cs_material_item
     *
     * @author CommSy Development Group
     */
    public function getMaterialList()
    {
        return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
    }

     public function getNumber(): int
     {
         return (int) $this->_getValue('number');
     }

    public function getOldNumber()
    {
        return $this->_getValue('oldnumber');
    }

    /** set groups of a section
     * this method sets a list of groups which are linked to the section.
     *
     * @param string value title of the section
     *
     * @author CommSy Development Group
     */
    public function setNumber($value)
    {
        $this->_setValue('oldnumber', $this->getNumber());
        $this->_setValue('number', $value);
    }

    /**
    save
     */
    public function save()
    {
        $section_manager = $this->_environment->getSectionManager();
        $this->_save($section_manager);
        $this->_saveFiles();     // this must be before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
    }

    public function save_without_date()
    {
        $section_manager = $this->_environment->getSectionManager();
        $section_manager->setSaveSectionWithoutDate();
        $this->_save($section_manager);
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
    }

    public function delete($version = '')
    {
        $section_manager = $this->_environment->getSectionManager();
        if (!empty($version) and 'current' == $version) {
            $section_manager->delete($this->getItemID(), $this->getVersionID());
        } elseif (isset($version)
                   and CS_ALL != $version
                   and is_int((int) $version)
        ) {
            $section_manager->delete($this->getItemID(), $version);
        } else {
            $section_manager->delete($this->getItemID());
        }

        // delete links
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

        // delete links to files
        $link_manager = $this->_environment->getLinkItemFileManager();
        if (!empty($version) and 'current' == $version) {
            $link_manager->deleteByItem($this->getItemID(), $this->getVersionID());
        } elseif (isset($version)
                   and CS_ALL != $version
                   and is_int((int) $version)
        ) {
            $link_manager->deleteByItem($this->getItemID(), $version);
        } else {
            $link_manager->deleteByItem($this->getItemID());
        }
    }

    public function deleteVersion()
    {
        $section_manager = $this->_environment->getSectionManager();
        $section_manager->delete($this->getItemID(), $this->getVersionID());
    }

    /** Checks and sets the data of the section_item.
     *
     * @param $data_array
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array)
    {
        // TBD: check data before setting
        $this->_data = $data_array;
    }

     public function isLocked()
     {
         return $this->getLinkedItem()->isLocked();
     }
}
