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

/* upper class of the label item
 */

use App\Entity\Labels;

/** class for a label
 * this class implements a commsy label. A label can be a group, a topic, a label, ...
 */
class cs_label_item extends cs_item
{
    /**
     * string - containing the name of the label.
     */
    public $_name;

    /**
     * string - containing the description of the label.
     */
    public $_description;

    /**
     * string - containing the extra information of the label.
     */
    public $_extras;

    /**
     * string - containing the type of the label.
     */
    public $_label_type;

    /**
     * boolean - containing true or false, if label is sort criteria.
     */
    public $_is_sort_criteria = false;

    /**
     * boolean - containing true or false, if label is a system label or not.
     */
    public $_is_system_label = false;

    public $_count_links = 0;

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param string label_type type of the label
     *
     * @author CommSy Development Group
     */
    public function __construct($environment, $label_type = '')
    {
        parent::__construct($environment);
        $this->_type = CS_LABEL_TYPE;
        $this->_data['type'] = $label_type;
    }

    /** sets the data of the item.
     *
     * @author CommSy Development Group
     */
    public function _setItemData($data_array): void
    {
        $translator = $this->_environment->getTranslationObject();
        $this->_data = $data_array;
        if (!empty($this->_data['name']) and $this->_data['name'] == $translator->getMessage('ALL_MEMBERS')) {
            $this->_is_system_label = true;
        }
    }

    /** Checks and returns the data of the item.
     *
     * @author CommSy Development Group
     */
    public function _getItemData(): ?array
    {
        $item_array = [];
        // not yet implemented
        if ($this->isValid()) {
            $item_array['name'] = $this->getName();
            $item_array['type'] = $this->getLabelType();
            $item_array['description'] = $this->getDescription();
            $item_array['extras'] = $this->getExtraInformation();

            return $item_array;
        } else {
            trigger_error('cs_label_item: getItemData(): Invalid Data');
        }

        return null;
    }

    public function getCountLinks()
    {
        return $this->_count_links;
    }

    public function setCountLinks($value)
    {
        return $this->_count_links = (int) $value;
    }

    /** get topics of a label_item
     * this method returns a list of topics which are linked to the label_item.
     *
     * @return object cs_list a list of topics (cs_label_item)
     */
    public function getTopicList()
    {
        $topic_list = $this->_getLinkedItems($this->_environment->getLabelManager(), CS_TOPIC_TYPE);
        $topic_list->sortBy('name');

        return $topic_list;
    }

    /** set topics of a label_item item by id
     * this method sets a list of topic item_ids which are linked to the label_item.
     *
     * @param array of topic ids
     */
    public function setTopicListByID($value)
    {
        $topic_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $topic_array[] = $tmp_data;
        }
        $this->_setValue(CS_TOPIC_TYPE, $topic_array, false);
    }

    /** set topics of a label_item
     * this method sets a list of topics which are linked to the label_item.
     *
     * @param object cs_list value list of topics (cs_label_item)
     */
    public function setTopicList($value)
    {
        $this->_setObject(CS_TOPIC_TYPE, $value, false);
    }

    /** get materials of a label_item
     * this method returns a list of materials which are linked to the label_item.
     *
     * @return object cs_list a list of materials (cs_material_item)
     */
    public function getMaterialList()
    {
        return $this->_getLinkedItems($this->_environment->getMaterialManager(), CS_MATERIAL_TYPE);
    }

    /** set materials of a label item by item id and version id
     * this method sets a list of material item_ids and version_ids which are linked to the label_item.
     *
     * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     */
    public function setMaterialListByID($value)
    {
        $this->_setValue(CS_MATERIAL_TYPE, $value, false);
    }

    /** set materials of a label_item
     * this method sets a list of materials which are linked to the label_item.
     *
     * @param string value title of the label_item
     */
    public function setMaterialList($value)
    {
        $this->_setObject(CS_MATERIAL_TYPE, $value, false);
    }

     public function getMemberItemList(): cs_list
     {
         $members = new cs_list();
         $member_ids = $this->getLinkedItemIDArray(CS_USER_TYPE);
         if (!empty($member_ids)) {
             $user_manager = $this->_environment->getUserManager();
             $user_manager->setIDArrayLimit($member_ids);
             $user_manager->setUserLimit();
             $user_manager->select();
             $members = $user_manager->get();
         }

         return $members;
     }

    public function getCountMemberItemList()
    {
        $members = $this->getMemberItemList();

        return $members->getCount();
    }

    public function getCountAllLinkItemList($addUsers = true)
    {
        $entries = $this->getAllLinkItemList();
        $counter = 0;
        if (!$addUsers) {
            foreach ($entries->to_array() as $entry) {
                if ('user' != $entry->getFirstLinkedItemType() && 'user' != $entry->getSecondLinkedItemType()) {
                    ++$counter;
                }
            }
        } else {
            $counter = $entries->getCount();
        }

        return $counter;
    }

    /** checks the data of the item.
     */
    public function isValid()
    {
        $name = $this->getName();
        $type = $this->getLabelType();

        return !empty($name) and !empty($type);
    }

    /** get name
     * this method returns the name of the label.
     *
     * @return string name of the label
     */
    public function getName()
    {
        return $this->_getValue('name');
    }

    /** set name
     * this method sets the name of the label.
     *
     * @param string value name of the item
     */
    public function setName($value)
    {
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('name', $value);
    }

    /** set title
     * this method sets the title of the label.
     *
     * @param string value title of the item
     */
    public function setTitle(string $value): void
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('name', $value);
    }

    /** get title
     * this method returns the name of the label.
     *
     * @return string name of the label
     */
    public function getTitle(): string
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return $this->_getValue('name');
        }
    }

    /** get description
     * this method returns the description of the label.
     *
     * @return string description of the label
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

    /** set description
     * this method sets the description of the label.
     *
     * @param string value description of the item
     */
    public function setDescription($value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    /** get type of label
     * this method returns the type of the label.
     *
     * @return string type of the label
     */
    public function getLabelType()
    {
        return $this->_getValue('type');
    }

    /** set label type
     * this method sets the type of the label.
     *
     * @param string value type of the item
     *
     * @author CommSy Development Group
     */
    public function setLabelType($value)
    {
        $this->_setValue('type', (string) $value);
    }

    /** is the label sort criteria ?
     * this method returns a boolean expressing if label is sort criteria or not.
     *
     * @return bool true - label is sort criteria
     *              false - label is not sort criteria
     *
     * @author CommSy Development Group
     */
    public function isSortCriteria()
    {
        return $this->_is_sort_criteria;
    }

    /** make label a sort criteria
     * this method makes the label to a sort criteria.
     *
     * @param bool value true - label is sort criteria
     *                      false - label is not sort criteria
     *
     * @author CommSy Development Group
     */
    public function makeSortCriteria($value = true)
    {
        $this->_is_sort_criteria = $value;
    }

    /** is the label a system generated label ?
     * this method returns a boolean expressing if label is a system generated label or not.
     *
     * @return bool true - label is a system generated label
     *              false - label is not a system generated label
     *
     * @author CommSy Development Group
     */
    public function isSystemLabel(): bool
    {
        $retour = false;
        if ($this->_issetExtra('SYSTEM_LABEL')) {
            $value = $this->_getExtra('SYSTEM_LABEL');
            if (1 == $value) {
                $retour = true;
            }
        }

        return $retour;
    }

    /** make label a system generated label
     * this method makes the label to a system generated label.
     *
     * @param bool value true - label is a system generated label
     *                      false - label is not a system generated label
     */
    public function makeSystemLabel($value = true)
    {
        if ($value) {
            $this->_addExtra('SYSTEM_LABEL', 1);
        } else {
            $this->_addExtra('SYSTEM_LABEL', -1);
        }
    }

    /** save news item
     * this methode save the news item into the database.
     *
     * @author CommSy Development Group
     */
    public function save(): void
    {
        $label_manager = $this->_environment->getLabelManager();
        $this->_save($label_manager);

        // prevent indexing of label types like buzzwords
        if (in_array($this->getLabelType(), [
            'group',
            'topic',
            'institution',
        ])) {
            $this->updateElastic();
        }
    }

     public function updateElastic()
     {
         global $symfonyContainer;
         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_label');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Labels::class);

         $this->replaceElasticItem($objectPersister, $repository);
     }

    /** delete label item
     * this methode delete the label item.
     *
     * @author CommSy Development Group
     */
    public function delete()
    {
        $manager = $this->_environment->getLabelManager();
        $this->_delete($manager);

        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_label');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository(Labels::class);

        $this->deleteElasticItem($objectPersister, $repository);
    }

    /** set picture filename of the label (used for groups)
     * this method sets the picture filename of the label.
     *
     * @param string value picture filename of the label
     *
     * @author CommSy Development Group
     */
    public function setPicture($name)
    {
        $this->_addExtra('LABELPICTURE', $name);
    }

    /** get picture filename of the label (used for groups)
     * this method gets the picture filename of the label.
     *
     * @return string picture filename of the label
     */
    public function getPicture()
    {
        $retour = '';
        if ($this->_issetExtra('LABELPICTURE')) {
            $retour = $this->_getExtra('LABELPICTURE');
        }

        return $retour;
    }

    public function isMember($user)
    {
        $link_member_list = $this->getLinkItemList(CS_USER_TYPE);
        $link_member_item = $link_member_list->getFirst();
        $is_member = false;
        while ($link_member_item) {
            $linked_user_id = $link_member_item->getLinkedItemID($this);
            if ($user->getItemID() == $linked_user_id) {
                $is_member = true;
                break;
            }
            $link_member_item = $link_member_list->getNext();
        }

        return $is_member;
    }

     public function addMember(cs_user_item $user): void
     {
         if (!$this->isMember($user)) {
             $link_manager = $this->_environment->getLinkItemManager();
             $link_item = $link_manager->getNewItem();
             $link_item->setFirstLinkedItem($this);
             $link_item->setSecondLinkedItem($user);
             $link_item->save();
         }
     }

     public function removeMember(cs_user_item $user): void
     {
         $linkedMemberList = $this->getLinkItemList(CS_USER_TYPE);
         foreach ($linkedMemberList as $linkedMemberItem) {
             $linked_user_id = $linkedMemberItem->getLinkedItemID($this);
             if ($user->getItemID() == $linked_user_id) {
                 $linkedMemberItem->delete();
             }
         }
     }

    /** asks if item is editable by everybody or just creator.
     *
     * @param value
     */
    public function isPublic(): bool
    {
        if (1 == $this->_getValue('public')) {
            return true;
        } else {
            return false;
        }
    }

    /** sets if announcement is editable by everybody or just creator.
     *
     * @param value
     */
    public function setPublic($value): void
    {
        $this->_setValue('public', $value);
    }

    /** change creator and modificator of the label
     * needed for saving group all, because at the first saving, creator ist user from
     * community room.
     *
     * @param user cs_user_item
     */
    public function changeCreatorItemAndModificatorItemTo($user)
    {
        $this->_changeCreatorItemAndModificatorItemTo($user, $this->_environment->getLabelManager());
    }

     /** returns whether the given user may edit the label item or not,
      * but will always prevent editing if the label item is a system label.
      */
     public function mayEdit(cs_user_item $user_item)
     {
         if ($this->isSystemLabel()) {
             return false;
         }

         $mayEditItem = parent::mayEdit($user_item);

         return $mayEditItem;
     }
}
