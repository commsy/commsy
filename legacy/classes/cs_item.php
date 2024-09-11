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

use App\Proxy\PortalProxy;
use App\Repository\MaterialsRepository;
use App\Repository\PortalRepository;
use App\Security\Authorization\Voter\ItemVoter;
use FOS\ElasticaBundle\Persister\ObjectPersisterInterface;
use Symfony\Bundle\SecurityBundle\Security;

class cs_item
{
    /**
     * string - containing the type of the item.
     */
    public $_type = 'item';

    /**
     * array - containing the data of this item, including lists of linked items.
     */
    public $_data = [];
    /**
     * array - array of boolean values. TRUE if key is changed.
     */
    public $_changed = [];

    public $_context_item;

    /** error array for detecting multiple errors.
     *
     */
    public $_error_array = [];

    /**
     * boolean - file list is changed, save new list.
     */
    public $_filelist_changed = false;
    public $_filelist_changed_empty = false;
    public $_cache_on = true;

    /**
     * boolean - if true the modification_date will be updated - else not.
     */
    public $_change_modification_on_save = true;

    public $_link_modifier = true;
    public $_db_load_extras = true;

    private array $externalViewerUsers = [];

    /** constructor
     * the only available constructor, initial values for internal variables.
     *
     * @param cs_environment $_environment
     */
    public function __construct(protected cs_environment $_environment)
    {
        $this->_changed['general'] = true;
        $this->_type = 'item';
    }

     public function getContextItem()
     {
         if (null == $this->_context_item) {
             $contextId = $this->getContextID();
             if (!empty($contextId)) {
                 $item_manager = $this->_environment->getItemManager();
                 $item = $item_manager->getItem($contextId);

                 if (isset($item) && is_object($item)) {
                     $manager = $this->_environment->getManager($item->getItemType());
                     $this->_context_item = $manager->getItem($this->getContextId());

                     return $this->_context_item;
                 }

                 $item_manager = $this->_environment->getItemManager(true);
                 $item = $item_manager->getItem($contextId);

                 if (isset($item) && is_object($item)) {
                     $manager = $this->_environment->getManager($item->getItemType());
                     $this->_context_item = $manager->getItem($this->getContextId());

                     return $this->_context_item;
                 }

                 global $symfonyContainer;

                 /** @var PortalRepository $portalRepository*/
                 $portalRepository = $symfonyContainer->get(PortalRepository::class);

                 $portal = $portalRepository->findPortalByRoomContext($contextId);

                 if ($portal) {
                     $this->_context_item = new PortalProxy($portal, $this->_environment);

                     return $this->_context_item;
                 }
             }
         }

         return $this->_context_item;
     }

    public function setContextItem($context_item)
    {
        if (is_object($context_item)) {
            $this->_context_item = $context_item;
        }
    }

    /**
     * Returns the item's portal.
     *
     * This also works for a user room whose context is its parent
     * project room (whose context, in turn, is the portal).
     */
    public function getPortal(): PortalProxy
    {
        global $symfonyContainer;

        /** @var PortalRepository $portalRepository*/
        $portalRepository = $symfonyContainer->get(PortalRepository::class);

        $portal = $portalRepository->findPortalByRoomContext($this->getContextID());

        return new PortalProxy($portal, $this->_environment);
    }

    public function setCacheOff()
    {
        $this->_cache_on = false;
    }

    public function setSaveWithoutLinkModifier()
    {
        $this->_link_modifier = false;
    }

    /** Sets the data of the item.
     *
     * @param $data_array Is the prepared array from "_buildItem($db_array)"
     */
    public function _setItemData($data_array): void
    {
        $this->_data = $data_array;
    }

    /** Gets the data of the item.
     *
     * @param $data_array Is the prepared array from "_saveItem($db_array)"
     *
     * @return bool TRUE if data is valid FALSE otherwise
     */
    public function _getItemData()
    {
        if ($this->isValid()) {
            return $this->_data;
        } else {
            // TBD
            echo 'Error in cs_item_new._getItemData(). Item not valid.';
        }
    }

    // ##############
    // PUBLIC METHODS
    // ###########

    /** asks if item is editable by everybody ('1') or just creator ('0').
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function isPrivateEditing()
    {
        if (1 == $this->_getValue('public')) {
            return false;
        }

        return true;
    }

    /** sets if item is editable by everybody ('1') or just creator ('0').
     *
     * @param value
     */
    public function setPrivateEditing($value)
    {
        $this->_setValue('public', $value);
    }

    /** get buzzwords of a material
     * this method returns a list of buzzwords which are linked to the material.
     *
     * @return object cs_list a list of buzzwords (cs_label_item)
     *
     * @author CommSy Development Group
     */
    public function getBuzzwordArray()
    {
        $buzzword_array = $this->_getValue('buzzword_array');
        if (empty($buzzword_array)) {
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->setTypeLimit('buzzword');
            $buzzword_list = $this->_getLinkedItemsForCurrentVersion($label_manager, 'buzzword_for');
            $buzzword = $buzzword_list->getFirst();
            while ($buzzword) {
                $name = $buzzword->getName();
                if (!empty($name)) {
                    if (!is_array($this->_data['buzzword_array'])) {
                        $this->_data['buzzword_array'] = [];
                    }
                    $this->_data['buzzword_array'][] = $name;
                }
                $buzzword = $buzzword_list->getNext();
            }
        }

        return $this->_getValue('buzzword_array');
    }

    /** get buzzwords of a material
     * this method returns a list of buzzwords which are linked to the material.
     *
     * @return object cs_list a list of buzzwords (cs_label_item)
     */
    public function getBuzzwordList()
    {
        $label_manager = $this->_environment->getLabelManager();
        $label_manager->setTypeLimit('buzzword');

        return $this->_getLinkedItemsForCurrentVersion($label_manager, 'buzzword_for');
    }

    /** set buzzwords of a material
     * this method sets a list of buzzwords which are linked to the material.
     *
     * @param string value title of the material
     *
     * @author CommSy Development Group
     */
    public function setBuzzwordArray($value)
    {
        $this->_data['buzzword_array'] = $value;
    }

    public function _saveBuzzwords()
    {
        if (!isset($this->_setBuzzwordsByIDs)) {
            $buzzword_array = $this->getBuzzwordArray();
            if (!empty($buzzword_array)) {
                array_walk($buzzword_array, fn ($buzzword) => trim((string) $buzzword));
                $label_manager = $this->_environment->getLabelManager();
                $label_manager->resetLimits();
                $label_manager->setTypeLimit('buzzword');
                $label_manager->setContextLimit($this->getContextID());
                $buzzword_exists_id_array = [];
                $buzzword_not_exists_name_array = [];
                foreach ($buzzword_array as $buzzword) {
                    $buzzword_item = $label_manager->getItemByName($buzzword);
                    if (!empty($buzzword_item)) {
                        $buzzword_exists_id_array[] = ['iid' => $buzzword_item->getItemID()];
                    } else {
                        $buzzword_not_exists_name_array[] = $buzzword;
                    }
                }
                // make buzzword items to get ids
                if (count($buzzword_not_exists_name_array) > 0) {
                    foreach ($buzzword_not_exists_name_array as $new_buzzword) {
                        $item = $label_manager->getNewItem();
                        $item->setContextID($this->getContextID());
                        $item->setName($new_buzzword);
                        $item->setLabelType('buzzword');
                        $item->save();
                        $buzzword_exists_id_array[] = ['iid' => $item->getItemID()];
                    }
                }
                // set id array so the links to the items get saved
                $this->_setValue('buzzword_for', $buzzword_exists_id_array, false);
            } else {
                $this->_setValue('buzzword_for', [], false); // to unset buzzword links
            }
        }
    }

    public function setBuzzwordListByID($array)
    {
        $this->_setValue('buzzword_for', $array, false);
        $this->_setBuzzwordsByIDs = true;
    }

    /** get list of linked items
     * this method returns a list of items which are linked to this item.
     *
     * @return object cs_list a list of cs_items
     *
     * @author CommSy Development Group
     */
    public function _getLinkedItemsForCurrentVersion($item_manager, $link_type)
    {
        if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {
            $link_manager = $this->_environment->getLinkManager();
            // preliminary version: there should be something like 'getIDArray() in the link_manager'
            $id_array = [];
            $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
            $id_array = [];
            foreach ($link_array as $link) {
                if ($link['to_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['from_item_id'];
                } elseif ($link['from_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['to_item_id'];
                }
            }
            $this->_data[$link_type] = $item_manager->getItemList($id_array, $this->getVersionID());
        }

        return $this->_data[$link_type];
    }

    /** get tags of a material
     * this method returns a list of tags which are linked to the material.
     *
     * @return object cs_list a list of tags (cs_label_item)
     */
    public function getTagArray()
    {
        $tag_array = $this->_getValue('tag_array');
        if (empty($tag_array)) {
            $tag_list = $this->getTagList();
            $tag = $tag_list->getFirst();
            while ($tag) {
                $linked_item = $tag->getLinkedItem($this);  // Get the linked item
                if (isset($linked_item)) {
                    $title = $linked_item->getTitle();
                    if (!empty($title)) {
                        $this->_data['tag_array'][] = $title;
                    }
                    unset($linked_item);
                }
                $tag = $tag_list->getNext();
            }
            unset($tag_list);
            unset($tag);
        }

        return $this->_getValue('tag_array');
    }

    public function getTagsArray()
    {
        $return = [];
        $tag_list = $this->getTagList();
        $tag = $tag_list->getFirst();
        while ($tag) {
            $title = $tag->getTitle();
            if (!empty($title)) {
                $tmp_array = [];
                $tmp_array['id'] = $tag->getItemID();
                $tmp_array['title'] = $tag->getTitle();

                $return[] = $tmp_array;
            }
            $tag = $tag_list->getNext();
        }
        unset($tag_list);
        unset($tag);

        return $return;
    }

    /** get tags of a material
     * this method returns a list of tags which are linked to the material.
     *
     * @return object cs_list a list of tags (cs_label_item)
     */
    public function getTagList()
    {
        $list = new cs_list();
        $tag_list = $this->getLinkItemList(CS_TAG_TYPE);
        $tag = $tag_list->getFirst();
        while ($tag) {
            $linked_item = $tag->getLinkedItem($this);  // Get the linked item
            if (isset($linked_item)) {
                $list->add($linked_item);
                unset($linked_item);
            }
            $tag = $tag_list->getNext();
        }
        unset($tag_list);
        unset($tag);

        return $list;
    }

    /** set materials of a announcement item by item id and version id
     * this method sets a list of material item_ids and version_ids which are linked to the announcement.
     *
     * @param array of material ids, index of id must be 'iid', index of version must be 'vid'
     * Example:
     * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
     */
    public function setTagListByID($value)
    {
        $this->setLinkedItemsByID(CS_TAG_TYPE, $value);
    }

    /** set materials of a announcement
     * this method sets a list of materials which are linked to the news.
     *
     * @param string value title of the news
     */
    public function setTagList($value)
    {
        $this->_setObject(CS_TAG_TYPE, $value, false);
    }

    /** Checks the data of the item.
     *
     * @return bool TRUE if data is valid FALSE otherwise
     *
     * @author CommSy Development Group
     */
    public function isValid()
    {
        $creator = $this->getCreatorID();
        return !empty($creator); // and !empty($creation_date);
    }

    /** is the type of the item = $type ?
     * this method returns a boolean expressing if type of the item is $type or not.
     *
     * @param string type string to compare with type of the item (_type)
     *
     * @return bool true - type of this item is $type
     *              false - type of this item is not $type
     *
     * @author CommSy Development Group
     */
    public function isA($type)
    {
        return $this->_type == $type;
    }

    /** get item id
     * this method returns the id of the item.
     *
     * @return int id of the item
     *
     * @author CommSy Development Group
     */
    public function getItemID(): int
    {
        return (int) $this->_getValue('item_id');
    }

    /** set item id
     * this method sets the id of the item.
     *
     * @param int id of the item
     *
     * @author CommSy Development Group
     */
    public function setItemID($value)
    {
        $this->_setValue('item_id', (int) $value);
    }

    /** get version id
     * this method returns 0
     * it must be overwritten in case version ids are needed.
     *
     * @return int version id of the item
     *
     * @author CommSy Development Group
     */
    public function getVersionID()
    {
        return 0;
    }

    /** set version id
     * this method sets the version id of the item.
     *
     * @param int version id of the item
     *
     * @author CommSy Development Group
     */
    public function setVersionID($value)
    {
        $this->_setValue('version_id', (int) $value);
    }

     /** get context id
      * this function returns the id of the current context:.
      */
     public function getContextID(): int
     {
         $context_id = $this->_getValue('context_id');
         if ('' === $context_id) {
             $context_id = $this->_environment->getCurrentContextID();
         }

         return (int) $context_id;
     }

    /** set context id
     * this method sets the context id of the item.
     *
     * @param int value context id of the item
     */
    public function setContextID($value)
    {
        return $this->_setValue('context_id', $value);
    }

    /** get creator
     * this method returns the modificator of the item
     * By default the creator is returned.
     */
     public function getModificatorItem(): ?cs_user_item
     {
         $retour = $this->_getUserItem('modifier');
         if (!isset($retour)) {
             $retour = $this->getCreatorItem();
         } else {
             $iid = $retour->getItemID();
             if (empty($iid)) {
                 $retour = $this->getCreatorItem();
             }
         }

         return $retour;
     }

    /** get creator-id
     * this method returns the modificator of the item
     * By default the creator is returned.
     *
     * @return cs_user_item creator of the item
     *
     * @author CommSy Development Group
     */
    public function getModificatorID()
    {
        $modifier = $this->_getValue('modifier_id');
        if (!empty($modifier)) {
            return $this->_getValue('modifier_id');
        } else {
            return $this->_getValue('creator_id');
        }
    }

    /** get creation date
     * this method returns the creation date of the item.
     *
     * @return string creation date of the item in datetime-FORMAT
     *
     * @author CommSy Development Group
     */
    public function getCreationDate()
    {
        return $this->_getValue('creation_date');
    }

    /** set creation date
     * this method sets the creation date of the item.
     *
     * @param string creation date in datetime-FORMAT of the item
     *
     * @author CommSy Development Group
     */
    public function setCreationDate($value)
    {
        $this->_setValue('creation_date', (string) $value);
    }

    /** get modification date
     * this method returns the modification date of the item.
     *
     * @return string modification date of the item in datetime-FORMAT
     *
     * @author CommSy Development Group
     */
    public function getModificationDate()
    {
        $date = $this->_getValue('modification_date');
        if (is_null($date) or '0000-00-00 00:00:00' == $date) {
            $date = $this->_getValue('creation_date');
        }

        return $date;
    }

     /** get modification date
      * this method returns the modification date of the item.
      *
      * @return string modification date of the item in datetime-FORMAT
      *
      * @author CommSy Development Group
      */
     public function getActivationDate()
     {
         $date = $this->_getValue('activation_date');
         if (is_null($date) or '0000-00-00 00:00:00' == $date) {
             $date = $this->_getValue('creation_date');
         }

         return $date;
     }

    /** set modification date
     * this method sets the modification date of the item.
     *
     * @param string modification date in datetime-FORMAT of the item
     *
     * @author CommSy Development Group
     */
    public function setModificationDate($value)
    {
        $this->_setValue('modification_date', (string) $value);
    }

     /** set modification date
      * this method sets the modification date of the item.
      *
      * @param string modification date in datetime-FORMAT of the item
      *
      * @author CommSy Development Group
      */
     public function setActivationDate($value)
     {
         $this->_setValue('activation_date', (string) $value);
     }

    /** get deletion date
     * this method returns the deletion date of the item.
     *
     * @return string deletion date of the item in datetime-FORMAT
     *
     * @author CommSy Development Group
     */
    public function getDeletionDate()
    {
        return $this->_getValue('deletion_date');
    }

    public function isNotActivated()
    {
        $date = $this->getActivationDate();
        if ($date > getCurrentDateTimeInMySQL()) {
            return true;
        } else {
            return false;
        }
    }

    public function getActivatingDate()
    {
        $retour = '';
        if ($this->isNotActivated()) {
            $retour = $this->getActivationDate();
        }

        return $retour;
    }

    /** set deletion date
     * this method sets the deletion date of the item.
     *
     * @param string deletion date in datetime-FORMAT of the item
     *
     * @author CommSy Development Group
     */
    public function setDeletionDate($value)
    {
        $this->_setValue('deletion_date', (string) $value);
    }

    /** get type, should be like getItemType (TBD)
     * this method returns the type of the item.
     *
     * @return string type of the item
     */
    public function getType()
    {
        return $this->_type;
    }

    public function getTitle(): string
    {
        $title = $this->_getValue('title');
        if (!empty($title)) {
            return $title;
        } else {
            return $this->_getValue('name');
        }
    }

    /** set type
     * this method sets the type of the item.
     *
     * @param string type of the item
     *
     * @author CommSy Development Group
     */
    public function setType($value)
    {
        $this->_type = (string) $value;
    }

    /** get item type form database tabel item
     * this method returns the type of the item form the database table item.
     *
     * @return string type of the item out of the database table item
     */
    public function getItemType()
    {
        $type = $this->_getValue('type');
        if (empty($type)) {
            $type = $this->getType();
        }

        return $type;
    }

    /** add an extra to the item -- OLD, use setExtra
     * this method adds a value (string, integer or array) to the extra information.
     *
     * @param string key   the key (name) of the value
     * @param *      value the value: string, integer, array
     */
    public function _addExtra($key, $value)
    {
        $this->_setExtra($key, $value);
    }

    /** set an extra in the item
     * this method sets a value (string, integer or array) to the extra information.
     *
     * @param string key   the key (name) of the value
     * @param *      value the value: string, integer, array
     */
    public function _setExtra($key, $value)
    {
        $extras = $this->_getValue('extras');
        $extras[$key] = $value;
        $this->_setValue('extras', $extras);
    }

    /** unset a value
     * this method unsets a value of the extra information.
     *
     * @param string key   the key (name) of the value
     */
    public function _unsetExtra($key)
    {
        if ($this->_issetExtra($key)) {
            $extras = $this->_getValue('extras');
            unset($extras[$key]);
            $this->_setValue('extras', $extras);
        }
    }

    /** exists the extra information with the name $key ?
     * this method returns a boolean, if the value exists or not.
     *
     * @param string key   the key (name) of the value
     *
     * @return bool true, if value exists
     *              false, if not
     */
    public function _issetExtra($key)
    {
        $result = false;
        $extras = $this->_getValue('extras');
        if (isset($extras) and is_array($extras) and array_key_exists($key, $extras) and isset($extras[$key])) {
            $result = true;
        }

        return $result;
    }

    /** get an extra value
     * this method returns a value of the extra information.
     *
     * @param string key the key (name) of the value
     *
     * @return * value of the extra information
     */
    public function _getExtra($key)
    {
        $extras = $this->_getValue('extras');
        if ($this->_issetExtra($key)) {
            return $extras[$key];
        }
    }

    /** get all extra keys
     * this method returns an array with all keys in.
     *
     * @return array returns an array with all keys in
     */
    public function getExtraKeys()
    {
        $extras = $this->_getValue('extras');

        return array_keys($extras);
    }

    /** get extra information of an item
     * this method returns the extra information of an item.
     *
     * @return string extra information of an item
     *
     * @author CommSy Development Group
     */
    public function getExtraInformation()
    {
        return $this->_getValue('extras');
    }

    /** set extra information of an item
     * this method sets the extra information of an item.
     *
     * @param string value extra information of an item
     *
     * @author CommSy Development Group
     */
    public function setExtraInformation($value)
    {
        $this->_setValue('extras', (array) $value);
    }

    public function resetExtraInformation()
    {
        $this->_setValue('extras', []);
    }

    public function isDeleted()
    {
        $is_deleted = false;
        $deletion_date = $this->getDeletionDate();
        if (!empty($deletion_date) and '0000-00-00 00:00:00' != $deletion_date) {
            $is_deleted = true;
        }

        return $is_deleted;
    }

    public function getDeleterID()
    {
        return $this->_getValue('deleter_id');
    }

    public function setDeleterID($value)
    {
        return $this->_setValue('deleter_id', $value);
    }

    public function getCreatorID(): int
    {
        return (int) $this->_getValue('creator_id');
    }

    public function setCreatorID($value)
    {
        return $this->_setValue('creator_id', $value);
    }

    public function setModifierID($value)
    {
        return $this->_setValue('modifier_id', $value);
    }

     /** set creator of a material
      * this method sets the creator of the material.
      */
     public function setCreatorItem(?cs_user_item $user)
     {
         $this->_setUserItem($user, 'creator');
     }

     /** get creator of a material
      * this method returns the creator of the material.
      *
      * @return cs_user_item creator of a material
      *
      * @author CommSy Development Group
      */
     public function getCreatorItem(): ?cs_user_item
     {
         return $this->_getUserItem('creator');
     }

     public function getCreator(): ?cs_user_item
     {
         return $this->getCreatorItem();
     }

    /** set deleter of a material
     * this method sets the deleter of the material.
     *
     * @param user_object deleter of a material
     *
     * @author CommSy Development Group
     */
    public function setDeleterItem($user)
    {
        $this->_setUserItem($user, 'deleter');
    }

    public function setDeleter($user)
    {
        $this->setDeleterItem($user);
    }

    /** set modificator
     * this method set the modificator of the item.
     *
     * @param cs_user_item modificator of the item
     *
     * @author CommSy Development Group
     */
    public function setModificatorItem($item)
    {
        $this->_setUserItem($item, 'modifier');
    }

    /** get deleter of a material
     * this method returns the deleter of the material.
     *
     * @return user_object deleter of a material
     *
     * @author CommSy Development Group
     */
    public function getDeleterItem()
    {
        return $this->_getUserItem('deleter');
    }

    public function getDeleter()
    {
        return $this->getDeleterItem();
    }

     /**
      * returns a list of annotations linked to this item.
      */
     public function getAnnotationList(): ?cs_list
     {
         $annotation_manager = $this->_environment->getAnnotationManager();
         $annotation_manager->resetLimits();
         $annotation_manager->setContextLimit(null);
         $annotation_manager->setLinkedItemID($this->getItemID());
         $annotation_manager->select();

         return $annotation_manager->get();
     }

// ********************************************************
// TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
// ********************************************************
    /** get list of linked items
     * this method returns a list of items which are linked to the news item.
     *
     * @return object cs_list a list of cs_items
     *
     * @author CommSy Development Group
     */
    public function _getLinkedItems($item_manager, $link_type, $order = '')
    {
        if (!isset($this->_data[$link_type]) or !is_object($this->_data[$link_type])) {
            global $environment;
            $link_manager = $environment->getLinkManager();
            // preliminary version: there should be something like 'getIDArray() in the link_manager'

            $link_array = $link_manager->getLinks($link_type, $this, $this->getVersionID(), 'eq');
            $id_array = [];
            foreach ($link_array as $link) {
                if ($link['to_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['from_item_id'];
                } elseif ($link['from_item_id'] == $this->getItemID()) {
                    $id_array[] = $link['to_item_id'];
                }
            }
            $this->_data[$link_type] = $item_manager->getItemList($id_array);
        }

        return $this->_data[$link_type];
    }

    /** get data value
     * this method returns the value for the specified key or an empty string if it is not set.
     *
     * @param string key
     */
    public function _getValue($key)
    {
        if (!isset($this->_data[$key])) {
            if ('extras' == $key) {
                if ($this->_db_load_extras) {
                    $this->_data[$key] = [];
                } else {
                    $this->_loadExtras();
                }
            } else {
                $this->_data[$key] = '';
            }
        }

        return $this->_data[$key];
    }

    public function unsetLoadExtras()
    {
        $this->_db_load_extras = false;
    }

    public function setLoadExtras()
    {
        $this->_db_load_extras = true;
    }

    public function _loadExtras()
    {
        $this->setLoadExtras();
        if (is_object($this)
             and method_exists($this, 'getItemType')
        ) {
            $manager = $this->_environment->getManager($this->getItemType());
            if (is_object($manager)
                 and method_exists($manager, 'getExtras')
            ) {
                $this->_data['extras'] = $manager->getExtras($this->getItemID());
            }
        }
    }

     /** get data object
      * this method returns the object for the specified key or NULL if it is not set.
      *
      * @param string key
      */
     protected function _getObject($key)
     {
         if (!isset($this->_data[$key])) {
             $this->_data[$key] = null;
         }

         return $this->_data[$key];
     }

     private function _getUserItem($role): ?cs_user_item
     {
         $user = $this->_getObject($role);
         if (null === $user) {
             $user_manager = $this->_environment->getUserManager();

             $user_id = $this->_getValue($role.'_id');
             if (null !== $user_id) {
                 $user = $user_manager->getItem((int) $user_id);
                 $this->_data[$role] = $user;
             }
         }

         return $user;
     }

     private function _setUserItem($user, $role)
     {
         if (isset($user) and is_object($user)) {
             $this->_data[$role] = $user;
             $item_id = $user->getItemID();
             $this->_setValue($role.'_id', $item_id);
         }
     }

    public function _setValue($key, $value, $internal = true)
    {
        $this->_data[$key] = $value;
        if ($internal) {
            $this->_changed['general'] = true;
        } else {
            $this->_changed[$key] = true;
        }
    }

    public function _unsetValue($key)
    {
        unset($this->_data[$key]);
    }

    /** set object
     * this method sets an object for the specified key and marks it as changed.
     *
     * @param mixed object to be changed
     *
     * @author CommSy Development Group
     */
    public function _setObject($key, $value, $internal = true)
    {
        $this->_data[$key] = $value;
        if ($internal) {
            $this->_changed['general'] = true;
        } else {
            $this->_changed[$key] = true;
        }
    }

     /** save item
      * this method saves the item to the database; if links to other items (e.g. relevant groups) are changed, they will be updated too.
      *
      * @param cs_manager the manager that should be used to save the item (e.g. cs_news_manager for cs_news_item)
      */
     public function _save($manager)
     {
         $saved = false;
         if (isset($this->_changed['general']) and true == $this->_changed['general']) {
             $manager->setCurrentContextID($this->getContextID());
             if (!$this->_link_modifier) {
                 $manager->setSaveWithoutLinkModifier();
             }
             $saved = $manager->saveItem($this);
         }

         $this->persistExternalViewer();

         foreach ($this->_changed as $changed_key => $is_changed) {
             if ($is_changed) {
                 if ('general' != $changed_key and 'section_for' != $changed_key and 'task_item' != $changed_key and 'copy_of' != $changed_key) {
                     // Abfrage nötig wegen langsamer Migration auf die neuen LinkTypen.
                     if (in_array($changed_key, [CS_TOPIC_TYPE, CS_GROUP_TYPE, CS_PROJECT_TYPE, CS_PRIVATEROOM_TYPE, CS_MYROOM_TYPE, CS_COMMUNITY_TYPE, CS_ANNOUNCEMENT_TYPE, CS_MATERIAL_TYPE, CS_TAG_TYPE, CS_TODO_TYPE, CS_DATE_TYPE, CS_DISCUSSION_TYPE, CS_USER_TYPE])) {
                         $link_manager = $this->_environment->getLinkItemManager();
                         if (is_object($this->_data[$changed_key])) { // a list of objects or one object
                             $this->_setObjectLinkItems($changed_key);
                         } elseif (is_array($this->_data[$changed_key])) { // an array
                             $this->_setIDLinkItems($changed_key);
                         }
                     } else {   // sollte irgendwann überflüssig werden!!!!
                         $link_manager = $this->_environment->getLinkManager();
                         $version_id = $this->getVersionID();
                         $link_manager->deleteLinks($this->getItemID(), $version_id, $changed_key);
                         if (is_object($this->_data[$changed_key])) { // a list of objects or one object
                             $this->_setObjectLinks($changed_key);
                         } elseif (is_array($this->_data[$changed_key])) { // an array
                             $this->_setIDLinks($changed_key);
                         }
                     }
                 }
             }
         }

         return $saved;
     }

     private function persistExternalViewer(): void
     {
         if (!empty($this->externalViewerUsers)) {
             $item_manager = $this->_environment->getItemManager();

             $user_id_array = $item_manager->getExternalViewerUserArrayForItem($this->getItemID());

             // persist new external viewers
             $newExternalViewers = array_diff($this->externalViewerUsers, $user_id_array);
             foreach ($newExternalViewers as $newExternalViewer) {
                 $item_manager->setExternalViewerEntry($this->getItemID(), $newExternalViewer);
             }

             // delete removed external viewers
             $removedExternalViewers = array_diff($user_id_array, $this->externalViewerUsers);
             foreach ($removedExternalViewers as $removedExternalViewer) {
                 $item_manager->deleteExternalViewerEntry($this->getItemID(), $removedExternalViewer);
             }
         } else {
             $item_manager = $this->_environment->getItemManager();

             $user_id_array = $item_manager->getExternalViewerUserArrayForItem($this->getItemID());
             foreach ($user_id_array as $user_id) {
                 $item_manager->deleteExternalViewerEntry($this->getItemID(), $user_id);
             }
         }
     }

    public function _setObjectLinkItems($changed_key)
    {
        // $changed_key_item_list enthält die link_items EINES TYPS, die das Item aktuell bei sich trägt
        // $old_link_item_list die Link items EINES TYPS, die das Link Item vor der Bearbeitung besa
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->resetLimits();
        if ((CS_COMMUNITY_TYPE == $changed_key and $this->isA(CS_PROJECT_TYPE))
             or
            (CS_PROJECT_TYPE == $changed_key and $this->isA(CS_COMMUNITY_TYPE))
        ) {
            $link_manager->setContextLimit($this->getContextID());
        } else {
            $link_manager->setContextLimit($this->_environment->getCurrentContextID());
        }
        $link_manager->setLinkedItemLimit($this);
        $link_manager->setTypeLimit($changed_key);
        $link_manager->select();
        $old_link_item_list = $link_manager->get();
        $delete_link_item_list = $link_manager->get();
        $changed_key_item_list = $this->_data[$changed_key];
        $create_key_item_list = $this->_data[$changed_key];
        $old_link_item = $old_link_item_list->getFirst();
        // Beide Listen durchgehen und vergleichen
        while ($old_link_item) {
            $old_linked_item = $old_link_item->getLinkedItem($this);
            $changed_key_item = $changed_key_item_list->getFirst();
            while ($changed_key_item) {
                $changed_key_item_id = $changed_key_item->getItemID();
                // $changed_key_version_id = $changed_key_item->getVersionID();
                $old_linked_item_id = $old_linked_item->getItemID();
                // $old_linked_version_id = $old_linked_item->getVersionID();
                // gibt es keine Übereinstimmung
                // if ($changed_key_item_id == $old_linked_item_id AND $changed_key_version_id == $old_linked_version_id){
                if ($changed_key_item_id == $old_linked_item_id) {
                    $create_key_item_list->removeElement($changed_key_item);
                    $delete_link_item_list->removeElement($old_linked_item);
                }
                $changed_key_item = $changed_key_item_list->getNext();
            }
            $old_link_item = $old_link_item_list->getNext();
        }
        $changed_key_item = $create_key_item_list->getFirst();
        while ($changed_key_item) {
            // Das neue Link_item erzeugen und abspeichern
            $link_item = $link_manager->getNewItem();
            $link_item->setFirstLinkedItem($this);
            $link_item->setSecondLinkedItem($changed_key_item);
            $link_item->save();
            $changed_key_item = $create_key_item_list->getNext();
        }
        $delete_link_item = $delete_link_item_list->getFirst();
        while ($delete_link_item) {
            $delete_link_item->delete();
            $delete_link_item = $delete_link_item_list->getNext();
        }
    }

    public function _setIDLinkItems($changed_key)
    {
        $type_array = [];
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->resetLimits();
        if (
            (CS_COMMUNITY_TYPE == $changed_key
              and $this->isA(CS_PROJECT_TYPE)
            )
            or (CS_PROJECT_TYPE == $changed_key
                  and $this->isA(CS_COMMUNITY_TYPE)
            )
        ) {
            $link_manager->setContextLimit($this->getContextID());
        } else {
            $link_manager->setContextLimit($this->_environment->getCurrentContextID());
        }
        if (CS_COMMUNITY_TYPE == $changed_key) {
            $change_all_items_in_community_room = true;
        } else {
            $change_all_items_in_community_room = false;
        }
        $link_manager->setLinkedItemLimit($this);
        if (CS_MYROOM_TYPE == $changed_key) {
            $type_array[0] = 'project';
            $type_array[1] = 'community';
            $link_manager->setTypeArrayLimit($type_array);
        } else {
            $link_manager->setTypeLimit($changed_key);
        }
        $link_manager->select();
        $old_link_item_list = $link_manager->get();
        $delete_link_item_list = clone $old_link_item_list;
        $changed_key_array = $this->_data[$changed_key];
        $create_key_array = $changed_key_array;
        $old_link_item = $old_link_item_list->getFirst();
        // Beide Listen durchgehen und vergleichen
        while ($old_link_item) {
            $old_linked_item = $old_link_item->getLinkedItem($this);
            if (isset($old_linked_item)) {
                foreach ($changed_key_array as $item_data) {
                    $old_linked_item_id = $old_linked_item->getItemID();
                    $changed_key_item_id = $item_data['iid'];
                    if ($changed_key_item_id == $old_linked_item_id) {
                        foreach ($create_key_array as $count => $create_data) {
                            if ($create_data['iid'] == $old_linked_item_id) {
                                array_splice($create_key_array, $count, 1);
                            }
                        }
                        $delete_link_item_list->removeElement($old_link_item);
                    }
                }
            }
            $old_link_item = $old_link_item_list->getNext();
        }

        foreach ($create_key_array as $item_data) {
            // Das neue Link_item erzeugen und abspeichern
            $link_item = $link_manager->getNewItem();
            $link_item->setFirstLinkedItem($this);
            $item_manager = $this->_environment->getManager($changed_key);
            $item = $item_manager->getItem($item_data['iid']);
            $link_item->setSecondLinkedItem($item);
            $link_item->save();
        }
        $delete_link_item = $delete_link_item_list->getFirst();
        while ($delete_link_item) {
            if ($change_all_items_in_community_room) {
                $item_id = $delete_link_item->getFirstLinkedItemID();
                $context_id = $delete_link_item->getSecondLinkedItemID();
                $link_manager = $this->_environment->getLinkItemManager();
                $link_manager->deleteAllLinkItemsInCommunityRoom($item_id, $context_id);
            }
            $delete_link_item->delete();
            $delete_link_item = $delete_link_item_list->getNext();
        }
    }

// ********************************************************
// TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
// ********************************************************

    public function _setObjectLinks($changed_key)
    {
        $link_manager = $this->_environment->getLinkManager();
        $item = $this->_data[$changed_key]->getFirst();
        // iterating through the list should be done by the link manager
        while ($item) {
            if ('material_for' == $changed_key ||
                 'member_of' == $changed_key) {// ||
//              $changed_key == 'task_item'){
                $link_array = [];
                $link_array['room_id'] = $this->getContextID();
                $link_array['to_item_id'] = $this->getItemID();
                $link_array['to_version_id'] = $this->getVersionID();
                $link_array['from_item_id'] = $item->getItemID();
                $link_array['from_version_id'] = $this->getVersionID();
            } else {
                $link_array = [];
                $link_array['room_id'] = $this->getContextID();
                $link_array['from_item_id'] = $this->getItemID();
                $link_array['from_version_id'] = $this->getVersionID();
                $link_array['to_item_id'] = $item->getItemID();
                $link_array['to_version_id'] = $item->getVersionID();
            }
            // needed for import material !!!
            if ($item->getContextID() != $this->_environment->getCurrentContextID()) {
                $link_array['room_id'] = $item->getContextID();
            }
            $link_array['link_type'] = $changed_key;
            $link_manager->save($link_array);
            $item = $this->_data[$changed_key]->getNext();
        }
    }

// ********************************************************
// TBD: Nach der vollständigen Migration der Links kann diese Methode entfernt werden
// ********************************************************
    public function _setIDLinks($changed_key)
    {
        $link_manager = $this->_environment->getLinkManager();
        foreach ($this->_data[$changed_key] as $item_data) {
            if ('material_for' == $changed_key ||
                 'member_of' == $changed_key) {// ||
//              $changed_key == 'task_item') {
                $link_array = [];
                $link_array['room_id'] = $this->getContextID();
                $link_array['to_item_id'] = $this->getItemID();
                $link_array['to_version_id'] = $this->getVersionID();
                $link_array['from_item_id'] = $item_data['iid'];
                if (isset($item_data['vid'])) {
                    $link_array['from_version_id'] = $item_data['vid'];
                } else {
                    $link_array['from_version_id'] = 0;
                }
            } else {
                $link_array = [];
                $link_array['room_id'] = $this->getContextID();
                $link_array['from_item_id'] = $this->getItemID();
                $link_array['from_version_id'] = $this->getVersionID();
                if ('buzzword_for' == $changed_key and (!is_array($item_data))) {
                    $link_array['to_item_id'] = $item_data;
                } else {
                    $link_array['to_item_id'] = $item_data['iid'];
                }
                $link_array['to_version_id'] = 0;
            }
            $link_array['link_type'] = $changed_key;
            $link_manager->save($link_array);
        }
        // MERDE
    }

    public function _setValueAsID($key, $value)
    {
        $data = [];
        $data[] = ['iid' => (int) $value, 'vid' => '0'];
        $this->_setValue($key, $data, false);
    }

    public function _setValueAsIDArray($key, $value)
    {
        $data = [];
        foreach ($value as $id) {
            $data[] = ['iid' => $id, 'vid' => '0'];
        }
        $this->_setValue($key, $data, false);
    }

    public function _setObjectAsItem($key, $value)
    {
        $list = new cs_list();
        $list->add((object) $value);
        $this->_setObject($key, $list, false);
    }

    /** delete item
     * this method deletes the item to the database; if links to other items (e.g. relevant groups) are changed, they will be updated too.
     *
     * @param cs_manager the manager that should be used to delete the item (e.g. cs_news_manager for cs_news_item)
     *
     * @author CommSy Development Group
     */
    public function _delete($manager)
    {
        $manager->delete($this->getItemID());
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

        $this->setDeletionDate(getCurrentDateTimeInMySQL());
        $this->setDeleterID($this->_environment->getCurrentUserItem()->getItemID());
    }

    public function _undelete($manager)
    {
        $manager->undelete($this->getItemID());
        $link_manager = $this->_environment->getLinkItemManager();
        $link_manager->undeleteLinks($this);
    }

     /**
      * Returns whether this item's content should get overwritten with some placeholder text.
      *
      * @return bool Whether this item's content should get overwritten (true), or not (false)
      */
     public function getHasOverwrittenContent(): bool
     {
         return false;
     }

     public function isPublic(): bool
     {
         return false;
     }

    public function getPublic()
    {
        return $this->_getValue('public');
    }

    public function mayEdit(cs_user_item $user_item)
    {
        $user = ($user_item->getContextID() !== $this->getContextID())
            ? ($user_item->getRelatedUserItemInContext($this->getContextID()) ?? $user_item)
            : $user_item;

        $access = false;
        if (!$user->isOnlyReadUser()) {
            if ($user->isRoot() or
                 ($user->getContextID() == $this->getContextID()
                  and ($user->isModerator()
                       or ($user->isUser()
                           and ($user->getItemID() == $this->getCreatorID()
                                or !$this->isPrivateEditing()))))
            ) {
                $access = true;
            }
        }

        if (true === $access) {
            // don't check locking for etherpads
            if ($this->_issetExtra('etherpad_id')) {
                $access = true;
            } else {
                global $symfonyContainer;

                /** @var Security $security */
                $security = $symfonyContainer->get('app.security');
                $access = $security->isGranted(ItemVoter::EDIT_LOCK, $this->getItemID());
            }
        } else {
            // NOTE: for guest users, $privateRoomUserItem will be null
            $privateRoomUserItem = $user_item->getRelatedPrivateRoomUserItem();

            // check for sub-types
            switch ($this->getType()) {
                case CS_SECTION_TYPE:
                case CS_STEP_TYPE:
                    $linkedItem = $this->getLinkedItem();
                    $mayEdit = $linkedItem->mayEdit($user_item);
                    if (!$mayEdit && $privateRoomUserItem) {
                        $mayEdit = $linkedItem->mayEdit($privateRoomUserItem);
                    }

                    return $mayEdit;
            }
        }

        return $access;
    }

    public function mayEditByUserID($user_id, $auth_source)
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->resetLimits();
        $user_manager->setUserIDLimit($user_id);
        $user_manager->setAuthSourceLimit($auth_source);
        $user_manager->setContextLimit($this->getContextID());
        $user_manager->select();
        $user_list = $user_manager->get();
        if (1 == $user_list->getCount()) {
            $user_in_room = $user_list->getFirst();

            return $this->mayEdit($user_in_room);
        } elseif ($user_list->getCount() > 1) {
            trigger_error('ambiguous user data in database table "user" for user-id "'.$user_id.'"', E_USER_WARNING);
        } else {
            trigger_error('can not find user data in database table "user" for user-id "'.$user_id.'", auth_source "'.$auth_source.'", context_id "'.$this->getContextID().'"', E_USER_WARNING);
        }
    }

    /** \brief	check via portfolio permission.
     *
     * This Method checks for item <=> activated portfolio - relationships
     */
    public function mayPortfolioSee(string $username)
    {
        $portfolioManager = $this->_environment->getPortfolioManager();

        // get all ids from portfolios we are allow to see
        $portfolioIds = $portfolioManager->getPortfolioForExternalViewer($username);

        // now we get all item tags and their ids
        $tagList = $this->getTagList();
        $tagIdArray = [];

        $tagEntry = $tagList->getFirst();
        while ($tagEntry) {
            $tagIdArray[] = $tagEntry->getItemID();

            $tagEntry = $tagList->getNext();
        }

        if (empty($portfolioIds) || empty($tagIdArray)) {
            return false;
        }

        // get row and column information for all portfolios with given tags
        $portfolioInformation = $portfolioManager->getPortfolioData($portfolioIds, $tagIdArray);

        // if user is allowed to see, there must be two tags for one portfolioId in this array, one for column, one for row
        foreach ($portfolioIds as $portfolioId) {
            if (isset($portfolioInformation[$portfolioId])) {
                $entryArray = $portfolioInformation[$portfolioId];

                if (sizeof($entryArray) > 1) {
                    $hasRow = $hasColumn = false;
                    foreach ($entryArray as $entry) {
                        if (0 == $entry['row']) {
                            $hasColumn = true;
                        }
                        if (0 == $entry['column']) {
                            $hasRow = true;
                        }
                    }

                    if (true === $hasRow && true === $hasColumn) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

     /**
      * @throws \Doctrine\DBAL\Exception
      */
     public function mayExternalSee(int $itemId, string $username): bool
     {
         $item_manager = $this->_environment->getItemManager();
         $retour = $item_manager->getExternalViewerForItem($itemId, $username);
         if ($retour) {
             return true;
         } else {
             return $this->mayPortfolioSee($username);
         }
     }

     /** is the given user allowed to see this item?
      */
     public function maySee(cs_user_item $userItem)
     {
         // Deny access, if the item's context is deleted
         $contextItem = $this->getContextItem();
         if (null === $contextItem || $contextItem->isDeleted()) {
             return false;
         }

         // Root
         if ($userItem->isRoot()) {
             return true;
         }

         // Room user
         $userInContext = ($userItem->getContextID() === $this->getContextID()) ? $userItem :
             $userItem->getRelatedUserItemInContext($this->getContextID());
         if (null !== $userInContext && $userInContext->isUser()) {
             // deactivated entries can be only viewed by a moderator or by their creator
             if ($this->isNotActivated()) {
                 if ($userInContext->isModerator()) {
                     return true;
                 }

                 if ($this->getCreatorID() == $userInContext->getItemId()) {
                     return true;
                 }
             } else {
                 return true;
             }
         }

         // External viewer
         if ($this->mayExternalSee($this->getItemID(), $userItem->getUserID())) {
             return true;
         }

         // Guest
         $currentContextItem = $this->_environment->getCurrentContextItem();
         if ($currentContextItem->isOpenForGuests()) {
             if ($userItem->isGuest() || $userItem->isRequested()) {
                 if (!$this->isNotActivated()) {
                     return true;
                 }
             }
         }

         return false;
     }

    public function getLatestLinkItemList($count)
    {
        $link_list = new cs_list();
        $link_item_manager = $this->_environment->getLinkItemManager();
        $link_item_manager->setLinkedItemLimit($this);
        $link_item_manager->setEntryLimit($count);

        $context_item = $this->_environment->getCurrentContextItem();
        $conf = $context_item->getHomeConf();
        if (!empty($conf)) {
            $rubrics = explode(',', (string) $conf);
        } else {
            $rubrics = [];
        }
        $type_array = [];
        foreach ($rubrics as $rubric) {
            $rubric_array = explode('_', $rubric);
            if ('none' != $rubric_array[1] and CS_USER_TYPE != $rubric_array[0]) {
                $type_array[] = $rubric_array[0];
            }
        }
        $link_item_manager->setTypeArrayLimit($type_array);
        $link_item_manager->setRoomLimit($this->getContextID());
        $link_item_manager->select();
        $link_list = $link_item_manager->get();
        $link_item_manager->resetLimits();

        return $link_list;
    }

     public function getAllLinkItemList(): cs_list
     {
         $link_item_manager = $this->_environment->getLinkItemManager();
         $link_item_manager->setLinkedItemLimit($this);

         $context_item = $this->_environment->getCurrentContextItem();
         $conf = $context_item->getHomeConf();

         // translation of entry to rubrics for new private room
         if ($this->_environment->inPrivateRoom() && mb_stristr((string) $conf, CS_ENTRY_TYPE)) {
             $temp_array = [];
             $temp_array3 = [];
             $rubric_array2 = [];
             $temp_array[] = CS_ANNOUNCEMENT_TYPE;
             $temp_array[] = CS_TODO_TYPE;
             $temp_array[] = CS_DISCUSSION_TYPE;
             $temp_array[] = CS_MATERIAL_TYPE;
             $temp_array[] = CS_DATE_TYPE;
             foreach ($temp_array as $temp_rubric) {
                 if (!mb_stristr((string) $conf, $temp_rubric)) {
                     $temp_array3[] = $temp_rubric.'_nodisplay';
                 }
             }
             $rubric_array = explode(',', (string) $conf);
             foreach ($rubric_array as $temp_rubric) {
                 if (!mb_stristr($temp_rubric, CS_ENTRY_TYPE)) {
                     $rubric_array2[] = $temp_rubric;
                 } else {
                     $rubric_array2 = [...$rubric_array2, ...$temp_array3];
                 }
             }
             $conf = implode(',', $rubric_array2);
         }

         $rubrics = !empty($conf) ? explode(',', (string) $conf) : [];

         $type_array = [];
         foreach ($rubrics as $rubric) {
             $rubric_array = explode('_', $rubric);
             if (('none' != $rubric_array[1] && CS_USER_TYPE != $rubric_array[0]) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_DATE_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TODO_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_GROUP_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_MATERIAL_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_ANNOUNCEMENT_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TASK_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_DISCUSSION_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TOPIC_TYPE == $this->_environment->getCurrentModule()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_DATE_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TODO_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_GROUP_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_MATERIAL_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_ANNOUNCEMENT_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TASK_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_DISCUSSION_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_TOPIC_TYPE == $this->getItemType()) ||
                 (CS_USER_TYPE == $rubric_array[0] && CS_LABEL_TYPE == $this->getItemType())
             ) {
                 $type_array[] = $rubric_array[0];
             }
         }

         $link_item_manager->setTypeArrayLimit($type_array);
         $link_item_manager->setRoomLimit($this->getContextID());
         $link_item_manager->select();
         $link_list = $link_item_manager->get();
         $link_item_manager->resetLimits();

         return $link_list;
     }

     public function getLinkItemList(string $type): ?cs_list
     {
         $type_array = [];
         $link_item_manager = $this->_environment->getLinkItemManager();
         $link_item_manager->setLinkedItemLimit($this);
         if (CS_MYROOM_TYPE == $type) {
             $type_array[0] = 'project';
             $type_array[1] = 'community';
             $link_item_manager->setTypeArrayLimit($type_array);
         } else {
             $link_item_manager->setTypeLimit($type);
         }

         if (
             (CS_COMMUNITY_TYPE == $type && $this->isA(CS_PROJECT_TYPE)) ||
             (CS_PROJECT_TYPE == $type && $this->isA(CS_COMMUNITY_TYPE) ||
                 (CS_COMMUNITY_TYPE == $type && $this->isA(CS_PROJECT_TYPE) && $this->_environment->inServer()) ||
                 (CS_COMMUNITY_TYPE == $type && $this->isA(CS_PROJECT_TYPE) && $this->_environment->inGroupRoom() &&
                     $this->_environment->getCurrentContextItem()->getLinkedProjectItem()->getItemId() == $this->getItemId())
             )
         ) {
             $link_item_manager->setRoomLimit($this->getContextID());
         } elseif ($this->isA(CS_LABEL_TYPE) && CS_GROUP_TYPE == $this->getLabelType()) {
             // müsste dies nicht für alle Fälle gelten ???
             $link_item_manager->setRoomLimit($this->getContextID());
         } elseif ($this->isA(CS_USER_TYPE) || $this->isA(CS_DATE_TYPE) || $this->isA(CS_TODO_TYPE)) {
             $link_item_manager->setRoomLimit($this->getContextID());
         } else {
             $link_item_manager->setRoomLimit($this->_environment->getCurrentContextID());
         }

         $link_item_manager->select();

         return $link_item_manager->get();
     }

    public function getLinkedItemList($type)
    {
        $link_list = $this->getLinkItemList($type);

        $result_list = new cs_list();
        $link_item = $link_list->getFirst();
        while ($link_item) {
            $result_list->add($link_item->getLinkedItem($this));
            $link_item = $link_list->getNext();
        }

        return $result_list;
    }

    public function getAllLinkedItemIDArray()
    {
        $id_array = [];
        $link_list = $this->getAllLinkItemList();
        $link_item = $link_list->getFirst();
        while ($link_item) {
            $link_item_id = $link_item->getFirstLinkedItemID();
            if ($link_item_id == $this->getItemID()) {
                $id_array[] = $link_item->getSecondLinkedItemID();
            } else {
                $id_array[] = $link_item->getFirstLinkedItemID();
            }
            $link_item = $link_list->getNext();
        }

        return $id_array;
    }

    public function isSystemLabel(): bool
    {
        return false;
    }

    public function getLinkedItemIDArray($type)
    {
        $id_array = [];
        $link_list = $this->getLinkItemList($type);
        $link_item = $link_list->getFirst();
        while ($link_item) {
            $link_item_id = $link_item->getFirstLinkedItemID();
            if ($link_item_id == $this->getItemID()) {
                $id_array[] = $link_item->getSecondLinkedItemID();
            } else {
                $id_array[] = $link_item->getFirstLinkedItemID();
            }
            $link_item = $link_list->getNext();
        }

        return $id_array;
    }

    public function setLinkedItemsByID($rubric, $value)
    {
        $data = [];
        foreach ($value as $iid) {
            $tmp['iid'] = $iid;
            $data[] = $tmp;
        }
        $this->_setValue($rubric, $data, false);
    }

     public function setLinkedItemsByIDArray(array $id_array): void
     {
         $item_manager = $this->_environment->getItemManager();

         // Get the typed item for all id's and group them by rubric
         $itemsByRubric = [];
         foreach ($id_array as $iid) {
             $item = $item_manager->getItem($iid);
             $rubric = $item->getItemType();
             if (CS_LABEL_TYPE == $rubric) {
                 $label_manager = $this->_environment->getLabelManager();
                 $label_item = $label_manager->getItem($iid);
                 $rubric = $label_item->getLabelType();
             }

             $itemsByRubric[$rubric][] = [
                 'iid' => $iid,
             ];
         }

         $context_item = $this->_environment->getCurrentContextItem();
         $current_room_modules = $context_item->getHomeConf();
         $roomModules = !empty($current_room_modules) ? explode(',', (string) $current_room_modules) : [];

         $rubric_array = [];
         foreach ($roomModules as $module) {
             $link_name = explode('_', $module);
             if ('none' != $link_name[1]) {
                 if (!($this->_environment->inPrivateRoom() and 'user' == $link_name)) {
                     $rubric_array[] = $link_name[0];
                 }
             }
         }

         // translation of entry to rubrics for new private room
         if ($this->_environment->inPrivateRoom() && in_array(CS_ENTRY_TYPE, $rubric_array)) {
             $temp_array = [];
             $temp_array[] = CS_ANNOUNCEMENT_TYPE;
             $temp_array[] = CS_TODO_TYPE;
             $temp_array[] = CS_DISCUSSION_TYPE;
             $temp_array[] = CS_MATERIAL_TYPE;
             $temp_array[] = CS_DATE_TYPE;

             $temp_array2 = array_filter($temp_array, fn ($rubric) => !in_array($rubric, $rubric_array));

             $rubric_array2 = [];
             foreach ($rubric_array as $temp_rubric) {
                 if (CS_ENTRY_TYPE != $temp_rubric) {
                     $rubric_array2[] = $temp_rubric;
                 } else {
                     $rubric_array2 = [...$rubric_array2, ...$temp_array2];
                 }
             }
             $rubric_array = $rubric_array2;
         }

         foreach ($rubric_array as $rubric) {
             if (
                 CS_DATE_TYPE == $this->_environment->getCurrentModule() ||
                 CS_TODO_TYPE == $this->_environment->getCurrentModule() ||
                 CS_GROUP_TYPE == $this->_environment->getCurrentModule() ||
                 CS_ANNOUNCEMENT_TYPE == $this->_environment->getCurrentModule() ||
                 CS_TASK_TYPE == $this->_environment->getCurrentModule() ||
                 CS_DISCUSSION_TYPE == $this->_environment->getCurrentModule() ||
                 CS_TOPIC_TYPE == $this->_environment->getCurrentModule() || CS_DATE_TYPE == $this->getItemType() ||
                 CS_MATERIAL_TYPE == $this->getItemType() ||
                 CS_GROUP_TYPE == $this->getItemType() ||
                 CS_ANNOUNCEMENT_TYPE == $this->getItemType() ||
                 CS_TASK_TYPE == $this->getItemType() ||
                 CS_DISCUSSION_TYPE == $this->getItemType() ||
                 CS_TOPIC_TYPE == $this->getItemType() ||
                 CS_TODO_TYPE == $this->getItemType()
             ) {
                 if (isset($itemsByRubric[$rubric])) {
                     $this->_setValue($rubric, $itemsByRubric[$rubric], false);
                 } else {
                     $this->_setValue($rubric, [], false);
                 }
             }
         }
     }

    /** change creator and modificator - INTERNAL should be called from methods in subclasses
     * change creator and modificator after item was saved for the first time.
     */
    public function _changeCreatorItemAndModificatorItemTo($user, $manager)
    {
        $this->setCreatorItem($user);
        $this->setModificatorItem($user);
        $manager->setCurrentContextID($this->getContextID());
        $manager->saveItemNew($this);
    }

    public function hasBeenClicked($user)
    {
        $user_array = $this->getArrayNew4User();
        $id = $user->getItemID();
        if (!empty($user_array) and in_array($id, $user_array)) {
            return true;
        } else {
            return false;
        }
    }

    public function HasBeenClickedSinceChanged($user)
    {
        $user_array = $this->getArrayChanged4User();
        $id = $user->getItemID();
        if (!empty($user_array) and in_array($id, $user_array)) {
            return true;
        } else {
            return false;
        }
    }

    public function undelete()
    {
        $manager = $this->_environment->getManager($this->getItemType());
        $manager->undeleteItemByItemID($this->getItemID());
    }

     /** delete item
      * this method deletes an item.
      */
     public function delete()
     {
         $manager = $this->_environment->getManager($this->getItemType());
         $this->_delete($manager);
     }

    public function deleteAssociatedAnnotations()
    {
        $item_manager = $this->_environment->getItemManager();
        $item = $item_manager->getItem($this->getItemID());

        $annotation_list = $item->getAnnotationList();
        foreach ($annotation_list as $annotation) {
            /** @var cs_annotation_item $annotation */
            $annotation->delete();
        }
    }

    // ################# file handling ############################

    /** get list of files attached o this item.
       @return cs_list list of file items
     */
    public function getFileList()
    {
        $file_list = new cs_list();
        if ('-1' == $this->getPublic() || $this->getHasOverwrittenContent()) {
            $translator = $this->_environment->getTranslationObject();

            return $file_list;
        } else {
            if (!empty($this->_data['file_list'])) {
                $file_list = $this->_data['file_list'];
            } else {
                if (isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array'])) {
                    $file_id_array = $this->_data['file_id_array'];
                } else {
                    $file_id_array = [];
                    $link_manager = $this->_environment->getLinkManager();
                    $file_links = $link_manager->getFileLinks($this);
                    if (!empty($file_links)) {
                        foreach ($file_links as $link) {
                            $file_id_array[] = $link['file_id'];
                        }
                    }
                    if (isset($file_id_array)) {
                        $this->_data['file_id_array'] = $file_id_array;
                    }
                }
                if (!empty($file_id_array)) {
                    $file_id_array = array_unique($file_id_array);
                    $file_manager = $this->_environment->getFileManager();
                    $file_manager->setIDArrayLimit($file_id_array);
                    $file_manager->setContextLimit('');
                    $file_manager->select();
                    $file_list = $file_manager->get();
                    if (isset($file_list)
                         and !empty($file_list)
                    ) {
                        $this->_data['file_list'] = $file_list;
                    }
                }
            }
            $file_list->sortby('filename');

            return $file_list;
        }
    }

    /**get array of file ids
       if an array of file-ids has been set (@see setFileIDArray()), get it
       if a list of files has been set (@see setFileList()), get corresponding file-ids,
       otherwise get file-ids according to links in material_link_file
       @return array file_id_array
    */
    public function getFileIDArray(): array
    {
        $file_id_array = [];
        if (isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array'])) { // check if file_id_array has been set by user or this method has been called before
            $file_id_array = $this->_data['file_id_array'];
        } elseif (isset($this->_data['file_id_array'])
                   and empty($this->_data['file_id_array'])
                   and $this->_filelist_changed
        ) { // alle dateien bewusst abhängen
            $file_id_array = $this->_data['file_id_array'];
        } elseif (isset($this->_data['file_list']) and is_object($this->_data['file_list'])) {
            $file = $this->_data['file_list']->getFirst();
            while ($file) {
                $file_id_array[] = $file->getFileID();
                $file = $this->_data['file_list']->getNext();
            }
        } else {
            $link_manager = $this->_environment->getLinkManager();
            $file_links = $link_manager->getFileLinks($this);
            if (!empty($file_links)) {
                foreach ($file_links as $link) {
                    $file_id_array[] = $link['file_id'];
                }
            }
        }

        return $file_id_array;
    }

    public function setFileIDArray($value)
    {
        $this->_data['file_id_array'] = $value;
        $this->_data['file_list'] = null;
        $this->_filelist_changed = true;
        if (empty($value)) {
            $this->_filelist_changed_empty = true;
        }
    }

    public function setFileList($value)
    {
        $this->_data['file_list'] = $value;
        $this->_data['file_id_array'] = [];
        $this->_filelist_changed = true;
    }

    public function _saveFileLinks()   // das ist so komplex, weil wir die filelinks nicht aus der db löschen können
    {// wenn jemandem was eleganteres einfällt: nur zu
        if ($this->_filelist_changed) {
            if (!$this->isNotActivated()) {
                $this->setModificationDate(null);
            }
            $link_manager = $this->_environment->getLinkManager();
            $file_id_array = $this->getFileIDArray();
            if (empty($file_id_array) || $this->_filelist_changed_empty) {
                $link_manager->deleteFileLinks($this);
            } else {
                $current_file_links = $link_manager->getFileLinks($this);
                $keep_links = [];
                if (!empty($current_file_links)) {
                    foreach ($current_file_links as $cur_link) {
                        if (in_array($cur_link['file_id'], $file_id_array)) {
                            $keep_links[] = $cur_link['file_id'];
                        } else {
                            $link_manager->deleteFileLinkByID($this, $cur_link['file_id']);
                        }
                    }
                }
                $add_links = array_diff($file_id_array, $keep_links);
                if (!empty($add_links)) {
                    foreach ($add_links as $file_id) {
                        $link_manager->linkFileByID($this, $file_id);
                    }
                }
            }
        }
    }

    public function _saveFiles()
    {
        $file_id_array = [];
        $result = false;
        if ($this->_filelist_changed
             and isset($this->_data['file_list'])
             and $this->_data['file_list']->getCount() > 0
        ) {
            $file_id_array = [];
            $file_item = $this->_data['file_list']->getFirst();
            while ($file_item) {
                if ($file_item->getContextID() != $this->getContextID()) {
                    $file_item->setContextID($this->getContextID());
                }
                $file_item->setCreatorItem($this->getCreatorItem());
                $result = $file_item->save();
                if ($result) {
                    $file_item_id = $file_item->getFileID();
                    if (!empty($file_item_id)) {
                        $file_id_array[] = $file_item_id;
                    } else {
                        $this->_error_array[] = $file_item->getDisplayName();
                    }
                } else {
                    $this->_error_array[] = $file_item->getDisplayName();
                }
                $file_item = $this->_data['file_list']->getNext();
            }
            $this->setFileIDArray($file_id_array);
        }

        global $c_indexing,$c_indexing_cron;
        if (isset($c_indexing)
             and !empty($c_indexing)
             and $c_indexing
             and isset($c_indexing_cron)
             and !$c_indexing_cron
        ) {
            $ftsearch_manager = $this->_environment->getFTSearchManager();
            $ftsearch_manager->buildFTIndex();
        }
    }

    public function _copyFileList(): cs_list
    {
        $files = $this->getFileList();
        $copy = new cs_list();

        $user = $this->getCreatorItem();

        foreach ($files as $file) {
            /** @var cs_file_item $file */
            $file->setItemID('');
            $file->setTempName($file->getDiskFilename());
            $file->setContextID($this->getContextID());
            $file->setCreatorItem($user);

            $copy->add($file);
        }

        return $copy;
    }

    public function isPublished()
    {
        return true;
    }

    public function getErrorArray()
    {
        return $this->_error_array;
    }

    public function setErrorArray($error_array)
    {
        $this->_error_array = $error_array;
    }

    public function getDescriptionWithoutHTML()
    {
        $retour = $this->getDescription();
        $retour = str_replace('<!-- KFC TEXT -->', '', (string) $retour);
        $retour = preg_replace('~<[A-Za-z][^>.]+>~u', '', $retour);

        return $retour;
    }

    /** save item
     * this methode save the item into the database.
     */
    public function save(): void
    {
        $manager = $this->_environment->getManager($this->getItemType());
        $this->_save($manager);
    }

    /** save item
     * this methode only saves the cs_item itself.
     */
    public function saveAsItem()
    {
        $manager = $this->_environment->getItemManager();
        $this->_save($manager);
    }

    /**
     * returns true if the modification_date should be saved.
     *
     * @param bool
     */
    public function isChangeModificationOnSave()
    {
        return $this->_change_modification_on_save;
    }

    public function setChangeModificationOnSave($save)
    {
        $this->_change_modification_on_save = $save;
    }

    public function getTopicList()
    {
        $topic_list = $this->getLinkedItemList(CS_TOPIC_TYPE);
        $topic_list->sortBy('name');

        return $topic_list;
    }

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

    public function setTopicList($value)
    {
        $this->_setObject(CS_TOPIC_TYPE, $value, false);
    }

     public function setExternalViewerAccounts(array $user_id_array): void
     {
         $this->externalViewerUsers = $user_id_array;
     }

     public function unsetExternalViewerAccounts(): void
     {
         $this->externalViewerUsers = [];
     }

     public function getExternalViewerString(): string
     {
         $item_manager = $this->_environment->getItemManager();
         return $item_manager->getExternalViewerUserStringForItem($this->getItemID());
     }

    public function getGroupList()
    {
        $group_list = $this->getLinkedItemList(CS_GROUP_TYPE);
        $group_list->sortBy('name');

        return $group_list;
    }

    public function setGroupListByID($value)
    {
        $this->setLinkedItemsByID(CS_GROUP_TYPE, $value);
    }

    public function setGroupList($value)
    {
        $this->_setObject(CS_GROUP_TYPE, $value, false);
    }

    public function getMaterialList()
    {
        return $this->getLinkedItemList(CS_MATERIAL_TYPE);
    }

    public function setMaterialListByID($value)
    {
        $this->setLinkedItemsByID(CS_MATERIAL_TYPE, $value);
    }

    public function setMaterialList($value)
    {
        $this->_setObject(CS_MATERIAL_TYPE, $value, false);
    }

    // ------------------------------------------
    // ------------- Wordpressexport -------------
    public function setExportToWordpress($value)
    {
        $this->_addExtra('EXPORT_TO_WORDPRESS', (string) $value);
    }

    // ------------- Wordpressexport -------------
    // ------------------------------------------

    public function getModifierList()
    {
        $retour = null;
        $link_modifier_item_manager = $this->_environment->getLinkModifierItemManager();
        $modifiers = $link_modifier_item_manager->getModifiersOfItem($this->getItemID());
        if (!empty($modifiers)) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->resetLimits();
            $user_manager->setContextLimit($this->_environment->getCurrentContextID());
            $user_manager->setIDArrayLimit($modifiers);
            $user_manager->select();
            $retour = $user_manager->get();
            unset($user_manager);
        }
        unset($link_modifier_item_manager);

        return $retour;
    }

    public function setWorkflowTrafficLight($value)
    {
        $this->_setValue('workflow_status', (string) $value);
    }

    public function getWorkflowTrafficLight()
    {
        return $this->_getValue('workflow_status');
    }

    public function isReadByUser($user)
    {
        $item_manager = $this->_environment->getItemManager();

        return $item_manager->isItemMarkedAsWorkflowRead($this->getItemId(), $user->getItemID());
    }

    public function setWorkflowResubmission($value)
    {
        $this->_setExtra('WORKFLOWRESUBMISSION', (string) $value);
    }

    public function getWorkflowResubmission()
    {
        $result = false;
        if ($this->_issetExtra('WORKFLOWRESUBMISSION')) {
            $result = $this->_getExtra('WORKFLOWRESUBMISSION');
        }

        return $result;
    }

    public function setWorkflowResubmissionDate($value)
    {
        $this->_setValue('workflow_resubmission_date', (string) $value);
    }

    public function getWorkflowResubmissionDate()
    {
        return $this->_getValue('workflow_resubmission_date');
    }

    public function setWorkflowResubmissionWho($value)
    {
        $this->_setExtra('WORKFLOWRESUBMISSIONWHO', (string) $value);
    }

    public function getWorkflowResubmissionWho()
    {
        $result = 'creator';
        if ($this->_issetExtra('WORKFLOWRESUBMISSIONWHO')) {
            $result = $this->_getExtra('WORKFLOWRESUBMISSIONWHO');
        }

        return $result;
    }

    public function setWorkflowResubmissionWhoAdditional($value)
    {
        $value = str_replace(["\t", ' '], '', (string) $value);
        $value_array = explode(',', $value);
        $this->_setExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL', $value_array);
    }

    public function getWorkflowResubmissionWhoAdditional()
    {
        $result = false;
        if ($this->_issetExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL')) {
            $result = implode(', ', $this->_getExtra('WORKFLOWRESUBMISSIONWHOADDITIONAL'));
        }

        return $result;
    }

    public function setWorkflowResubmissionTrafficLight($value)
    {
        $this->_setExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT', (string) $value);
    }

    public function getWorkflowResubmissionTrafficLight()
    {
        $result = '3_none';
        if ($this->_issetExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT')) {
            $result = $this->_getExtra('WORKFLOWRESUBMISSIONTRAFFICLIGHT');
        }

        return $result;
    }

    public function setWorkflowValidity($value)
    {
        $this->_setExtra('WORKFLOWVALIDITY', (string) $value);
    }

    public function getWorkflowValidity()
    {
        $result = false;
        if ($this->_issetExtra('WORKFLOWVALIDITY')) {
            $result = $this->_getExtra('WORKFLOWVALIDITY');
        }

        return $result;
    }

    public function setWorkflowValidityDate($value)
    {
        $this->_setValue('workflow_validity_date', (string) $value);
    }

    public function getWorkflowValidityDate()
    {
        return $this->_getValue('workflow_validity_date');
    }

    public function setWorkflowValidityWho($value)
    {
        $this->_setExtra('WORKFLOWVALIDITYWHO', (string) $value);
    }

    public function getWorkflowValidityWho()
    {
        $result = 'creator';
        if ($this->_issetExtra('WORKFLOWVALIDITYWHO')) {
            $result = $this->_getExtra('WORKFLOWVALIDITYWHO');
        }

        return $result;
    }

    public function setWorkflowValidityWhoAdditional($value)
    {
        $value = str_replace(["\t", ' '], '', (string) $value);
        $value_array = explode(',', $value);
        $this->_setExtra('WORKFLOWVALIDITYWHOADDITIONAL', $value_array);
    }

    public function getWorkflowValidityWhoAdditional()
    {
        $result = false;
        if ($this->_issetExtra('WORKFLOWVALIDITYWHOADDITIONAL')) {
            $result = implode(', ', $this->_getExtra('WORKFLOWVALIDITYWHOADDITIONAL'));
        }

        return $result;
    }

    public function setWorkflowValidityTrafficLight($value)
    {
        $this->_setExtra('WORKFLOWVALIDITYTRAFFICLIGHT', (string) $value);
    }

    public function getWorkflowValidityTrafficLight()
    {
        $result = '3_none';
        if ($this->_issetExtra('WORKFLOWVALIDITYTRAFFICLIGHT')) {
            $result = $this->_getExtra('WORKFLOWVALIDITYTRAFFICLIGHT');
        }

        return $result;
    }

    /** get draft status.
     */
    public function isDraft()
    {
        $isDraft = $this->_getValue('draft');

        if (empty($isDraft)) {
            return 0;
        }

        return $isDraft;
    }

    /** set set draft.
     */
    public function setDraftStatus($value)
    {
        $this->_setValue('draft', (string) $value);
    }

    public function isPinned(): bool
    {
        $isPinned = $this->_getValue('pinned');

        return empty($isPinned) ? false : true;
    }

    public function setPinned(bool $pinned)
    {
        $this->_setValue('pinned', $pinned ? 1 : 0);
    }

    /**
     * @return bool
     *
     * @deprecated
     */
    public function isArchived()
    {
        // An item is "archived" if it exists in an archived room
        $contextItem = $this->getContextItem();
        if ($contextItem) {
            if (method_exists($contextItem, 'getArchived')) {
                return $contextItem->getArchived();
            }
        }

        return false;
    }

     protected function replaceElasticItem(ObjectPersisterInterface $objectPersister, $repository)
     {
         $elasticHost = $_ENV['ELASTICSEARCH_URL'];

         if ($elasticHost) {
             if ($repository instanceof MaterialsRepository) {
                 $object = $repository->findLatestVersionByItemId($this->getItemID());
             } else {
                 $object = $repository->findOneByItemId($this->getItemID());
             }

             if ($object && $object->isIndexable() && !$this->isDraft()) {
                 // Replacing delete + insert with replace will not call the ingest pipeline and
                 // will not process any file attachments
                 $objectPersister->deleteOne($object);
                 $objectPersister->insertOne($object);
             }
         }
     }

     protected function deleteElasticItem($objectPersister, $repository)
     {
         $elasticHost = $_ENV['ELASTICSEARCH_URL'];

         if ($elasticHost) {
             $object = $repository->findOneByItemId($this->getItemID());

             if ($object) {
                 $objectPersister->deleteOne($object);
             }
         }
     }

     public function getPath()
     {
         return null;
     }
}
