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

/** class for a step
 * this class implements a step item.
 */
class cs_step_item extends cs_item
{
    /** constructor: cs_step_item
     * the only available constructor, initial values for internal variables.
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = 'step';
    }

    public $_version_id_changed = false;

    /** get title of a step
     * this method returns the title of the step.
     *
     * @return string title of a step
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

    /** set title of a step
     * this method sets the title of the step.
     *
     * @param string value title of the step
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
    public function getTodoID()
    {
        return $this->_getValue('todo_item_id');
    }

    public function getLinkedItem()
    {
        $retour = null;
        $item_id = $this->getTodoID();
        if (!empty($item_id)) {
            $type_manager = $this->_environment->getManager(CS_TODO_TYPE);
            $retour = $type_manager->getItem($item_id);
        }

        return $retour;
    }

     public function getLinkedItemId()
     {
         return $this->getLinkedItem()->getItemId();
     }

    /** set id of a linked material.
     *
     * @author CommSy Development Group
     */
    public function setTodoID($value)
    {
        $this->_setValue('todo_item_id', $value);
    }

    public function setTimeType($type)
    {
        $this->_setValue('time_type', $type);
    }

    public function getTimeType()
    {
        $retour = 1;
        $r = $this->_getValue('time_type');
        if (isset($r) and !empty($r)) {
            $retour = $r;
        }

        return $retour;
    }

    /** get description of a step
     * this method returns the description of the step.
     *
     * @return string description of a step
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

    /** set description of a step
     * this method sets the description of the step.
     *
     * @param string value description of the step
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

    /** set materials of the step item
     * this method sets a list of materials which are linked to the step item.
     *
     * @param cs_list list of cs_material_item
     *
     * @author CommSy Development Group
     */
    public function setMaterialList($value)
    {
        $this->_setObject('CS_MATERIAL_TYPE', $value, false);
    }

    /** set materials of a step item by id
     * this method sets a list of group item_ids which are linked to the step.
     *
     * @param array of group ids, index of id must be 'iid', index of version must be 'vid'<br />
     * Example:<br />
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     *
     * @author CommSy Development Group
     */
    public function setMaterialListByID($value)
    {
        // $this->_setValue('material_for', $value, FALSE);
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    /** get materials of the step item
     * this method returns a list of materials which are linked to the step.
     *
     * @return object cs_list a list of cs_material_item
     *
     * @author CommSy Development Group
     */
    public function getMaterialList()
    {
        return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
    }

    /** get groups of a step
     * this method returns a list of groups which are linked to the step.
     *
     * @return int
     *
     * @author CommSy Development Group
     */
    public function getMinutes(): int
    {
        return (int) $this->_getValue('minutes');
    }

    public function setMinutes($min)
    {
        return $this->_setValue('minutes', (int) $min);
    }

    public function cloneCopy()
    {
        $clone_item = clone $this; // "clone" needed for php5

        return $clone_item;
    }

    /**
    save
     */
    public function save()
    {
        $step_manager = $this->_environment->getStepManager();
        $this->_save($step_manager);
        $this->_saveFiles();     // this must be before saveFileLinks
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
    }

    public function save_without_date()
    {
        $step_manager = $this->_environment->getStepManager();
        $step_manager->setSaveStepWithoutDate();
        $this->_save($step_manager);
        $this->_saveFileLinks(); // this must be done after saving so we can be sure to have an item id
    }

    public function delete($version = '')
    {
        $step_manager = $this->_environment->getStepManager();
        if (!empty($version) and 'current' == $version) {
            $step_manager->delete($this->getItemID());
        } elseif (isset($version)
                   and CS_ALL != $version
                   and is_int((int) $version)
        ) {
            $step_manager->delete($this->getItemID());
        } else {
            $step_manager->delete($this->getItemID());
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
        $step_manager = $this->_environment->getStepManager();
        $step_manager->delete($this->getItemID());
    }

    /** Checks and sets the data of the step_item.
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
