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

/* upper class of the material item
 */

use App\Entity\Materials;

/** class for a material
 * this class implements a material item.
 */
class cs_material_item extends cs_item
{
    /**
     * boolean - containing boolean for make new version or not.
     */
    public $_version_id_changed = false;

    /**
     * integer - which of the sections will be saved with new date.
     */
    public $_section_save_id = 0;

    /**
     * integer - list of items attached to material.
     */
    public $_attached_item_list = null;

    public function __construct($environment)
    {
        parent::__construct($environment);
        $this->_type = CS_MATERIAL_TYPE;
    }

// ##############  SET-METHODS

    public function setCopyItem($item)
    {
        $this->_setValue('copy_of', $item->getItemID());
    }

    public function getCopyItem()
    {
        $copy_id = $this->_getValue('copy_of');
        if (!empty($copy_id)) {
            $material_manager = $this->_environment->getMaterialManager();
            $copy_item = $material_manager->getItem($copy_id);
        } else {
            $copy_item = null;
        }

        return $copy_item;
    }

    /** set title of a material
     * this method sets the title of the material and marks the title as 'changed'.
     *
     * @param string value title of the material
     */
    public function setTitle(string $value)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value);
    }

    /** set Author of a material.
     *
     * This is for loading initial values into the item
     *
     * @param string value author of the material
     */
    public function setAuthor($value)
    {
        $this->_setValue('author', $value);
    }

    /** set publishing_date of a material.
     *
     * @param string value publishing_date of the material
     *
     * @author CommSy Development Group
     */
    public function setPublishingDate($value)
    {
        $this->_setValue('publishing_date', $value);
    }

    /** set bibliographic values of a material
     * this method sets the bibliographic values of the material an marks the them as 'changed'.
     *
     * @param string value bibliographic values of the material
     *
     * @author CommSy Development Group
     */
    public function setBibliographicValues($value)
    {
        $this->_addExtra('BIBLIOGRAPHIC', (string) $value);
    }

    public function setBibAvailibility($value)
    {
        $this->_addExtra('BIBLAVAILABILITY', (string) $value);
    }

    public function getBibAvailibility()
    {
        return (string) $this->_getExtra('BIBLAVAILABILITY');
    }

    public function issetBibAvailibility()
    {
        return $this->_issetExtra('BIBLAVAILABILITY');
    }

    public function setBibTOC($value)
    {
        $this->_addExtra('BIBTOC', (string) $value);
    }

    public function getBibTOC()
    {
        return (string) $this->_getExtra('BIBTOC');
    }

    public function issetBibTOC()
    {
        return $this->_issetExtra('BIBTOC');
    }

    public function setBibURL($value)
    {
        $this->_addExtra('BIBURL', (string) $value);
    }

    public function getBibURL()
    {
        return (string) $this->_getExtra('BIBURL');
    }

    public function issetBibURL()
    {
        return $this->_issetExtra('BIBURL');
    }

    /** The following methods are for detailed bib values **/
    public function setBibKind($value)
    {
        $this->_addExtra('BIB_KIND', (string) $value);
    }

    public function getBibKind()
    {
        return (string) $this->_getExtra('BIB_KIND');
    }

    public function setPublisher($value)
    {
        $this->_addExtra('BIB_PUBLISHER', (string) $value);
    }

    public function getPublisher()
    {
        return (string) $this->_getExtra('BIB_PUBLISHER');
    }

    public function setAddress($value)
    {
        $this->_addExtra('BIB_ADDRESS', (string) $value);
    }

    public function getAddress()
    {
        return (string) $this->_getExtra('BIB_ADDRESS');
    }

    public function setEdition($value)
    {
        $this->_addExtra('BIB_EDITION', (string) $value);
    }

    public function getEdition()
    {
        return (string) $this->_getExtra('BIB_EDITION');
    }

    public function setSeries($value)
    {
        $this->_addExtra('BIB_SERIES', (string) $value);
    }

    public function getSeries()
    {
        return (string) $this->_getExtra('BIB_SERIES');
    }

    public function setVolume($value)
    {
        $this->_addExtra('BIB_VOLUME', (string) $value);
    }

    public function getVolume()
    {
        return (string) $this->_getExtra('BIB_VOLUME');
    }

    public function setISBN($value)
    {
        $this->_addExtra('BIB_ISBN', (string) $value);
    }

    public function getISBN()
    {
        return (string) $this->_getExtra('BIB_ISBN');
    }

    public function setISSN($value)
    {
        $this->_addExtra('BIB_ISSN', (string) $value);
    }

    public function getISSN()
    {
        return (string) $this->_getExtra('BIB_ISSN');
    }

    public function setEditor($value)
    {
        $this->_addExtra('BIB_EDITOR', (string) $value);
    }

    public function getEditor()
    {
        return (string) $this->_getExtra('BIB_EDITOR');
    }

    public function setBooktitle($value)
    {
        $this->_addExtra('BIB_BOOKTITLE', (string) $value);
    }

    public function getBooktitle()
    {
        return (string) $this->_getExtra('BIB_BOOKTITLE');
    }

    public function setPages($value)
    {
        $this->_addExtra('BIB_PAGES', (string) $value);
    }

    public function getPages()
    {
        return (string) $this->_getExtra('BIB_PAGES');
    }

    public function setJournal($value)
    {
        $this->_addExtra('BIB_JOURNAL', (string) $value);
    }

    public function getJournal()
    {
        return (string) $this->_getExtra('BIB_JOURNAL');
    }

    public function setIssue($value)
    {
        $this->_addExtra('BIB_ISSUE', (string) $value);
    }

    public function getIssue()
    {
        return (string) $this->_getExtra('BIB_ISSUE');
    }

    public function setThesisKind($value)
    {
        $this->_addExtra('BIB_THESIS_KIND', (string) $value);
    }

    public function getThesisKind()
    {
        return (string) $this->_getExtra('BIB_THESIS_KIND');
    }

    public function setUniversity($value)
    {
        $this->_addExtra('BIB_UNIVERSITY', (string) $value);
    }

    public function getUniversity()
    {
        return (string) $this->_getExtra('BIB_UNIVERSITY');
    }

    public function setFaculty($value)
    {
        $this->_addExtra('BIB_FACULTY', (string) $value);
    }

    public function getFaculty()
    {
        return (string) $this->_getExtra('BIB_FACULTY');
    }

    public function setURL($value)
    {
        $this->_addExtra('BIB_URL', (string) $value);
    }

    public function getURL()
    {
        return (string) $this->_getExtra('BIB_URL');
    }

    public function setURLDate($value)
    {
        $this->_addExtra('BIB_URL_DATE', (string) $value);
    }

    public function getURLDate()
    {
        return (string) $this->_getExtra('BIB_URL_DATE');
    }

    /** Start Dokumentenverwaltung **/
    public function setDocumentEditor($value)
    {
        $this->_addExtra('BIB_DOCUMENT_EDITOR', (string) $value);
    }

    public function getDocumentEditor()
    {
        return (string) $this->_getExtra('BIB_DOCUMENT_EDITOR');
    }

    public function setDocumentMaintainer($value)
    {
        $this->_addExtra('BIB_DOCUMENT_MAINTAINER', (string) $value);
    }

    public function getDocumentMaintainer()
    {
        return (string) $this->_getExtra('BIB_DOCUMENT_MAINTAINER');
    }

    public function setDocumentReleaseNumber($value)
    {
        $this->_addExtra('BIB_DOCUMENT_RELEASE_NUMBER', (string) $value);
    }

    public function getDocumentReleaseNumber()
    {
        return (string) $this->_getExtra('BIB_DOCUMENT_RELEASE_NUMBER');
    }

    public function setDocumentReleaseDate($value)
    {
        $this->_addExtra('BIB_DOCUMENT_RELEASE_DATE', (string) $value);
    }

    public function getDocumentReleaseDate()
    {
        return (string) $this->_getExtra('BIB_DOCUMENT_RELEASE_DATE');
    }

    /** Ende Dokumentenverwaltung **/
    public function setFotoCopyright($value)
    {
        $this->_addExtra('BIB_FOTO_COPYRIGHT', (string) $value);
    }

    public function getFotoCopyright()
    {
        return (string) $this->_getExtra('BIB_FOTO_COPYRIGHT');
    }

    public function setFotoReason($value)
    {
        $this->_addExtra('BIB_FOTO_REASON', (string) $value);
    }

    public function getFotoReason()
    {
        return (string) $this->_getExtra('BIB_FOTO_REASON');
    }

    public function setFotoDate($value)
    {
        $this->_addExtra('BIB_FOTO_DATE', (string) $value);
    }

    public function getFotoDate()
    {
        return (string) $this->_getExtra('BIB_FOTO_DATE');
    }

     /** End: detailed bib values **/
     public function setEtherpadEditor($value)
     {
         // use etherpad editor for material
         $this->_addExtra('etherpad', $value);
     }

     public function getEtherpadEditor()
     {
         return $this->_getExtra('etherpad');
     }

     public function setEtherpadEditorID($id)
     {
         $this->_addExtra('etherpad_id', $id);
     }

     public function getEtherpadEditorID()
     {
         return $this->_getExtra('etherpad_id');
     }

     public function unsetEtherpadEditorID(): void
     {
         $this->_unsetExtra('etherpad_id');
     }

    /** set description of a material
     * this method sets the description of the material an marks it as 'changed'.
     *
     * @param string value description of the material
     *
     * @author CommSy Development Group
     */
    public function setDescription($value)
    {
        // sanitize description
        $converter = $this->_environment->getTextConverter();
        $value = $converter->sanitizeFullHTML($value);
        $this->_setValue('description', $value);
    }

    /** set version id of a material
     * this method sets the version id of the material WITH marking the version id as 'changed'.
     * This is for loading initial values into the item.
     *
     * @param int version ID
     *
     * @author CommSy Development Group
     */
    public function setVersionID($value)
    {
        $this->_setValue('version_id', $value);
        $this->_version_id_changed = true; // needed in material_manager to determine wether to save as new item
    }

    /** set label item-id of a material
     * this method sets the item id of the label for this material.
     *
     * @param string value title of the material
     *
     * @author CommSy Development Group
     */
    public function setLabelID($value)
    {
        $this->_setValueAsID('label_for', $value);
        $this->_data['label'] = '';
    }

    /** set label of a material
     * this method sets the label of the material.
     *
     * @param string value title of the material
     *
     * @author CommSy Development Group
     */
    public function setLabel($value)
    {
        $this->_data['label'] = $value;
        $this->_data['label_for'] = '';
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

    public function setFileIDArray($value)
    {
        $this->_data['file_id_array'] = $value;
        $this->_data['file_list'] = null;
        $this->_filelist_changed = true;
    }

    public function setWorldPublic($value)
    {
        $this->_setValue('world_public', (int) $value);
    }

    public function setSectionList($value)
    {
        $this->_setObject('section_for', $value, false);
    }

    public function isWorldPublic()  // TBD
    {
        $value = $this->getWorldPublic();
        if (2 == $value) {
            return true;
        }

        return false;
    }

// ############### GET-METHODS

     /** get version id of a material
      * this method returns the version id of the material.
      *
      * @return int version of the material
      *
      * @author CommSy Development Group
      */
     public function getVersionID(): int
     {
         return (int) $this->_getValue('version_id');
     }

    public function isCurrentVersion()
    {
        $material_manager = $this->_environment->getMaterialManager();
        $version_list = $material_manager->getVersionList($this->getItemId())->to_array();
        $max_version = 0;
        foreach ($version_list as $version_list_entry) {
            if ($version_list_entry->getVersionId() > $max_version) {
                $max_version = $version_list_entry->getVersionId();
            }
        }
        if ($this->getVersionId() == $max_version) {
            return true;
        }

        return false;
    }

    /** get title of a material
     * this method returns the title of the material.
     *
     * @return string title of a material
     *
     * @author CommSy Development Group
     */
    public function getTitle()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_TITLE');
        } else {
            return (string) $this->_getValue('title');
        }
    }

    /** get author of a material
     * this method returns the author of the material.
     *
     * @return string author of a material
     *
     * @author CommSy Development Group
     */
    public function getAuthor()
    {
        if ('-1' == $this->getPublic()) {
            return '';
        } else {
            return (string) $this->_getValue('author');
        }
    }

    /** get publishing_date of a material
     * this method returns the publishing_date of the material.
     *
     * @return string publishing_date of a material
     */
    public function getPublishingDate()
    {
        if ('-1' == $this->getPublic()) {
            return '';
        } else {
            return (int) $this->_getValue('publishing_date');
        }
    }

    /** get bibliographic values of a material
     * this method gets the bibliographic values of the material.
     *
     * @return string bibliographic values of the material
     *
     * @author CommSy Development Group
     */
    public function getBibliographicValues()
    {
        if ('-1' == $this->getPublic()) {
            return '';
        } else {
            return (string) $this->_getExtra('BIBLIOGRAPHIC');
        }
    }

    /** get description of a material
     * this method returns the description of the material.
     *
     * @return string description of a material
     *
     * @author CommSy Development Group
     */
    public function getDescription()
    {
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $translator->getMessage('COMMON_AUTOMATIC_DELETE_DESCRIPTION');
        } else {
            return (string) $this->_getValue('description');
        }
    }

    /** get projects of a material
     * this method returns a list of projects which are linked to the material.
     *
     * @return object cs_list a list of projects (cs_label_item)
     */
    public function getProjectList()
    {
        return $this->getLinkedItemList(CS_PROJECT_TYPE);
    }

    /** set projects of a material item by id
     * this method sets a list of project item_ids which are linked to the material.
     *
     * @param array of project ids
     *
     * @author CommSy Development Group
     */
    public function setProjectListByID($value)
    {
        $project_array = [];
        foreach ($value as $iid) {
            $tmp_data = [];
            $tmp_data['iid'] = $iid;
            $project_array[] = $tmp_data;
        }
        $this->_setValue(CS_PROJECT_TYPE, $project_array, false);
    }

    /** set projects of a material
     * this method sets a list of projects which are linked to the material.
     *
     * @param object cs_list value list of projects (cs_label_item)
     *
     * @author CommSy Development Group
     */
    public function setProjectList($value)
    {
        $this->_setObject(CS_PROJECT_TYPE, $value, false);
    }

    /** get label of a material
     * this method returns the label of the material.
     *
     * @return string label
     *
     * @author CommSy Development Group
     */
    public function getLabel()
    {
        $label = $this->_getValue('label');
        if (empty($label)) {
            $label_item = $this->getLabelItem();
            if (!empty($label_item) and is_object($label_item)) {
                $this->_data['label'] = $label_item->getName();
            }
        }

        return (string) $this->_getValue('label');
    }

    /** get label item of a material
     * this method returns the label of the material.
     *
     * @return cs_label_item
     *
     * @author CommSy Development Group
     */
    public function getLabelItem()
    {
        $label_manager = $this->_environment->getLabelManager();
        $label_manager->setContextLimit($this->getContextID());
        $label_manager->setTypeLimit('label');
        $label_list = $this->_getLinkedItemsForCurrentVersion($label_manager, 'label_for');
        $retour = null;
        if ($label_list->getCount() > 0) {
            $retour = $label_list->getFirst();
        }

        return $retour;
    }

    /** get tasks associated with a material
     * this method returns a list of tasks which are linked to the material.
     *
     * @return object cs_list a list of tasks
     *
     * @author CommSy Development Group
     */
    public function _getTaskList()
    {
        $task_manager = $this->_environment->getTaskManager();

        return $task_manager->getTaskListForItem($this);
    }

    public function getWorldPublic()
    {
        return $this->_getValue('world_public');
    }

    /**
     * @return cs_list
     */
    public function getSectionList()
    {
        $section_list = $this->_getValue('section_for');
        if (empty($section_list)) {
            $this->_data['section_for'] = $this->_getSectionListForCurrentVersion();
            $section_list = $this->_data['section_for'];
        }

        return $section_list;
    }

    public function _getSectionListForCurrentVersion()
    {
        $section_manager = $this->_environment->getSectionManager();
        $this->_data['section_for'] = $section_manager->getSectionForCurrentVersion($this);

        return $this->_data['section_for'];
    }

  public function selectAttachedItems()
  {
      $link_manager = $this->_environment->getLinkManager();
      $link_array = $link_manager->getLinksFromWithItemType('material_for', $this); // , $this->getVersionID());
      $id_array = [];
      foreach ($link_array as $link) {
          $id_array[$link['type']][] = $link['to_item_id'];
      }
      foreach ($id_array as $type => $id_list) {
          $manager = $this->_environment->getManager($type);
          $this->_attached_item_list[$type] = $manager->getItemList($id_list);
      }
  }

    public function getAttachedNewsList()
    {
        return $this->_getAttachedItemList('news');
    }

    public function getAttachedDateList()
    {
        return $this->_getAttachedItemList('date');
    }

    public function getAttachedDiscussionArticleList()
    {
        return $this->_getAttachedItemList('discarticles');
    }

    public function getAttachedSectionList()
    {
        return $this->_getAttachedItemList('section');
    }

    public function getAttachedAnnouncementList()
    {
        return $this->_getAttachedItemList(CS_ANNOUNCEMENT_TYPE);
    }

    public function _getAttachedItemList($type)
    {
        return $this->_attached_item_list[$type] ?? null;
    }

// ##########END############
// #########################
// ########TESTING##########
// #########################
// #########################

// ############### SAVING

    public function save($mode = '')
    {
        $this->_saveLabel();
        $this->_saveSections($mode);
        $this->_saveFiles();
        $material_manager = $this->_environment->getMaterialManager();
        $this->_save($material_manager);
        $this->_saveFileLinks(); // this must be done after saving material so we can be sure to have a material id
        $this->_filelist_changed = false;
        $this->_version_id_changed = false;
        $this->_changed = [];

        $this->updateElastic();
    }

     public function updateElastic()
     {
         global $symfonyContainer;
         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_material');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Materials::class);

         $this->replaceElasticItem($objectPersister, $repository);
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

    public function _saveLabel()
    {
        $id = $this->_getValue('label_for');
        $no_id = empty($id);
        if ($no_id) {
            $label = $this->getLabel();
            if (!empty($label)) {
                // create new label_item and save it
                $label_manager = $this->_environment->getLabelManager();
                $label_manager->setContextLimit($this->getContextID());
                $label_manager->setTypeLimit('label');
                $label_item = $label_manager->getItemByName($label);
                if (empty($label_item)) {
                    $label_item = $label_manager->getNewItem();
                    $label_item->setContextID($this->getContextID());
                    $label_item->setCreatorItem($this->getCreatorItem());
                    $label_item->setName($label);
                    $label_item->setLabelType('label');
                    $label_item->save();
                }
                // set label id so the link to the label gets saved
                $this->setLabelID($label_item->getItemID());
            }
        }
    }

    public function _saveSections($mode = '')
    {
        $error_array_sum = [];
        if (isset($this->_changed['section_for'])) {
            $section_list = $this->getSectionList();
            if ($section_list->getCount() > 0) {
                $new_section_list = new cs_section_list();
                $section = $section_list->getFirst();
                $error_array_sum = $this->GetErrorArray();
                while ($section) {
                    $section_id = $section->getItemID();
                    $file_id_array = $section->getFileIDArray();
                    $file_list = $section->getFileList();
                    if ($section->getContextID() != $this->getContextID()) {
                        $section->setContextID($this->getContextID());
                    }
                    if ($section->getVersionID() != $this->getVersionID()) {
                        $section->setVersionID($this->getVersionID());
                    }

                    // set files new, so they will be saved for the new version
                    // and for copying in new rooms
                    if ('copy' == $mode) {
                        $section->setFileList($file_list);
                        $user = $this->_environment->getCurrentUserItem();
                        $section->setCreatorItem($user);
                        $section->setModificatorItem($user);
                    } elseif (isset($file_id_array)) {
                        $section->setFileIDArray($file_id_array);
                    }
                    unset($file_list);
                    unset($file_id_array);

                    // just set the date new at the modified section... all others keep their old date
                    if ($section->getItemId() == $this->_section_save_id) {
                        $section->save();
                        $error_array = $section->getErrorArray();

                        // Error merging with values from material-copy
                        if (!empty($error_array)) {
                            if (isset($error_array_sum) and !empty($error_array_sum)) {
                                $error_array_sum = array_merge($error_array_sum, $error_array);
                            } else {
                                $error_array_sum = $error_array;
                            }
                            $this->SetErrorArray($error_array_sum);
                        }
                    } else {
                        $section->save_without_date();
                    }
                    unset($error_array);
                    unset($error_array_sum);
                    $new_section_list->append($section);
                    $section = $section_list->getNext();
                }
                $this->setSectionList($new_section_list);
            }
        }
    }

     /** delete material
      * this method deletes the material.
      */
     public function delete($version = 'current')
     {
         global $symfonyContainer;

         /** @var EventDispatcher $eventDispatcer */
         $eventDispatcer = $symfonyContainer->get('event_dispatcher');

         $itemDeletedEvent = new \App\Event\ItemDeletedEvent($this);
         $eventDispatcer->dispatch($itemDeletedEvent, \App\Event\ItemDeletedEvent::NAME);

         // delete associated tasks
         $task_list = $this->_getTaskList();
         if (isset($task_list)) {
             $current_task = $task_list->getFirst();
             while ($current_task) {
                 $current_task->delete();
                 $current_task = $task_list->getNext();
             }
         }

         // delete sections
         $section_list = $this->getSectionList();
         if ($section_list->isNotEmpty()) {
             $section_item = $section_list->getFirst();
             while ($section_item) {
                 if ('current' == $version) {
                     $section_item->delete($this->getVersionID());
                 } elseif (CS_ALL == $version) {
                     $section_item->delete($version); // CS_ALL -> delete all versions of the section
                 } else {
                     $section_item->delete();
                 }
                 $section_item = $section_list->getNext();
             }
         }

         // delete material with versions
         $material_manager = $this->_environment->getMaterialManager();
         if ('current' == $version) {
             $material_manager->delete($this->getItemID(), $this->getVersionID());
         } else { // delete all versions of the material
             $material_manager->delete($this->getItemID());
         }

         // delete links
         $link_manager = $this->_environment->getLinkItemManager();
         $link_manager->deleteLinksBecauseItemIsDeleted($this->getItemID());

         // delete links to files
         $link_manager = $this->_environment->getLinkItemFileManager();
         $link_manager->deleteByItem($this->getItemID(), $this->getVersionID());

         // delete associated annotations
         $this->deleteAssociatedAnnotations();

         $objectPersister = $symfonyContainer->get('app.elastica.object_persister.commsy_material');
         $em = $symfonyContainer->get('doctrine.orm.entity_manager');
         $repository = $em->getRepository(Materials::class);

         $this->deleteElasticItem($objectPersister, $repository);
     }

     /** deletes all versions of a material
      * this method deletes all versions of a material.
      *
      * @author CommSy Development Group
      */
     public function deleteAllVersions()
     {
         $this->delete(CS_ALL);
     }

// ########################## COPYING AND CLONING

public function copy()
{
    $temp_array = [];
    $copy = $this->cloneCopy(true);
    $copy->setItemID('');
    $copy->setFileList($this->_copyFileList());
    $copy->setCopyItem($this);
    $copy->setContextID($this->_environment->getCurrentContextID());
    $user = $this->_environment->getCurrentUserItem();
    $copy->setCreatorItem($user);
    $copy->setModificatorItem($user);
    $list = new cs_list();
    if ($this->_environment->getCurrentContextID() != $this->getContextID()) {
        // Add a new labels if necessary
        $label_manager = $this->_environment->getLabelManager();
        $label_manager->reset();
        $label_manager->setContextLimit($this->_environment->getCurrentContextID());
        $label_manager->setTypeLimit('label');
        $label_manager->select();
        $label_list = $label_manager->get();
        $exist = null;
        if (!empty($label_list)) {
            $label = $label_list->getFirst();

            while ($label) {
                if (0 == strcmp((string) $label->getName(), ltrim($this->getLabel()))) {
                    $exist = $label->getItemID();
                }
                $label = $label_list->getNext();
            }
        }
        if (!isset($exist)) {
            $temp_array = [];
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->reset();
            $label_item = $label_manager->getNewItem();
            $label_item->setLabelType('label');
            $label_item->setTitle(ltrim($this->getLabel()));
            $label_item->setContextID($this->_environment->getCurrentContextID());
            $user = $this->_environment->getCurrentUserItem();
            $label_item->setCreatorItem($user);
            $label_item->setCreationDate(getCurrentDateTimeInMySQL());
            $label_item->save();
            $copy->setLabelId($label_item->getItemId());
        } elseif (isset($exist)) {
            $temp_array = [];
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->reset();
            $label_item = $label_manager->getItem($exist);
            $copy->setLabelId($exist);
        }

        // Add a new buzzwords if necessary
        $original_buzzword_array = $this->getBuzzwordArray();
        // Get all buzzwords in context in array
        $buzzwords_in_room_array = [];
        $buzzword_manager = $this->_environment->getLabelManager();
        $buzzword_manager->reset();
        $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
        $buzzword_manager->setTypeLimit('buzzword');
        $buzzword_manager->select();
        $buzzword_list = $buzzword_manager->get();
        if (!empty($buzzword_list)) {
            $buzzword = $buzzword_list->getFirst();
            while ($buzzword) {
                $temp_array['name'] = $buzzword->getName();
                $temp_array['id'] = $buzzword->getItemId();
                $buzzwords_in_room_array[] = $temp_array;
                $buzzword = $buzzword_list->getNext();
            }
        }

        // if buzzword exists, put id in array, if it doesn't exist, create it, then put id in array
        $buzzword_ids = [];
        if (isset($original_buzzword_array) and
             !empty($original_buzzword_array)) {
            for ($i = 0; $i < (is_countable($original_buzzword_array) ? count($original_buzzword_array) : 0); ++$i) {
                $found = false;
                if (isset($buzzwords_in_room_array) and
                     !empty($buzzwords_in_room_array)) { // There are buzzwords in the context
                    for ($j = 0; $j < count($buzzwords_in_room_array); ++$j) {
                        if (isset($buzzwords_in_room_array[$j]) and
                                   isset($original_buzzword_array[$i]) and
                                   isset($buzzwords_in_room_array[$j]['name']) and
                                   !empty($buzzwords_in_room_array[$j]['name']) and
                                   isset($buzzwords_in_room_array[$j]['id']) and
                                   !empty($buzzwords_in_room_array[$j]['id'])
                        ) {
                            if (0 == strcmp((string) $buzzwords_in_room_array[$j]['name'], ltrim((string) $original_buzzword_array[$i]))) {
                                $buzzword_ids[] = $buzzwords_in_room_array[$j]['id'];
                                $found = true;
                                break;
                            }
                            if (!$found and $j == count($buzzwords_in_room_array) - 1) {
                                $buzzword_manager = $this->_environment->getLabelManager();
                                $buzzword_manager->reset();
                                $buzzword_item = $buzzword_manager->getNewItem();
                                $buzzword_item->setLabelType('buzzword');
                                $buzzword_item->setTitle(ltrim((string) $original_buzzword_array[$i]));
                                $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                                $user = $this->_environment->getCurrentUserItem();
                                $buzzword_item->setCreatorItem($user);
                                $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
                                $buzzword_item->save();
                                $buzzword_ids[] = $buzzword_item->getItemID();
                            }
                        }
                    }
                } else { // There are no buzzwords in the room, so create all
                    if (isset($original_buzzword_array[$i]) and
                         !empty($original_buzzword_array[$i])
                    ) {
                        $buzzword_manager = $this->_environment->getLabelManager();
                        $buzzword_manager->reset();
                        $buzzword_item = $buzzword_manager->getNewItem();
                        $buzzword_item->setLabelType('buzzword');
                        $buzzword_item->setTitle(ltrim((string) $original_buzzword_array[$i]));
                        $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                        $user = $this->_environment->getCurrentUserItem();
                        $buzzword_item->setCreatorItem($user);
                        $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
                        $buzzword_item->save();
                        $buzzword_ids[] = $buzzword_item->getItemID();
                    }
                }
            }
        }
        $copy->setBuzzwordListByID($buzzword_ids);
        $copy->setGroupList($list);
        $copy->setTopicList($list);
    }
    $copy->setSectionList(new cs_list());
    $copy->save();
    $copy_id = $copy->getItemId();

    // files in from sections
    $section_list = $this->_copySectionList($copy_id);
    $copy->setSectionList($section_list);
    unset($section_list);
    $copy->save($mode = 'copy');
    $copy_id = $copy->getItemID();

    $reader_manager = $this->_environment->getReaderManager();
    $reader_manager->markRead($copy_id, $copy->getVersionID());

    // Import all versions off the material
    $material_manager = $this->_environment->getMaterialManager();
    $version_list = $material_manager->getVersionList($this->getItemID());
    $import_version = $version_list->getFirst();
    $version = $this->getVersionID();
    while ($import_version) {
        $version_id = $import_version->getVersionID();
        if ($version_id != $version) {
            $copy_version = $import_version->copyVersion($copy_id);
            $reader_manager->markRead($copy_id, $version_id);
        }
        $import_version = $version_list->getNext();
    }

    $this->_updateInternalLinks($copy);

    return $copy;
}

public function _updateInternalLinks($copy)
{
    $old_section_list = $this->_getSectionListForCurrentVersion();
    $new_section_list = $copy->_getSectionListForCurrentVersion();

    $id_array = [];
    $id_array[$this->getItemID()] = $copy->getItemID();
    $old_section_item = $old_section_list->getFirst();
    while ($old_section_item) {
        $new_section_item = $new_section_list->getFirst();
        while ($new_section_item) {
            if ($old_section_item->getNumber() == $new_section_item->getNumber()) {
                $id_array[$old_section_item->getItemID()] = $new_section_item->getItemID();
            }
            $new_section_item = $new_section_list->getNext();
        }
        $old_section_item = $old_section_list->getNext();
    }

    $this->_updateInternalLinksInText($copy, $id_array);
    $new_section_item = $new_section_list->getFirst();
    while ($new_section_item) {
        $this->_updateInternalLinksInText($new_section_item, $id_array);
        $new_section_item = $new_section_list->getNext();
    }
}

public function _updateInternalLinksInText($item, $id_array)
{
    $temp_description = $item->getDescription();
    foreach ($id_array as $old_id => $new_id) {
        $temp_description = str_replace('['.$old_id.']', '['.$new_id.']', (string) $temp_description);
        $temp_description = str_replace('(:item '.$old_id, '(:item '.$new_id, $temp_description);
    }
    $item->setDescription($temp_description);
    $item->save();
}

public function copyVersion($id)
{
    $temp_array = [];
    $copy = $this->cloneCopy(true);
    $copy->setItemID($id);
    $copy->setVersionID($this->getVersionID());
    $copy->setFileList($this->_copyFileList());
    $copy->setCopyItem($this);
    $copy->setContextID($this->_environment->getCurrentContextID());
    $copy->setCreatorItem($this->_environment->getCurrentUserItem());
    $list = new cs_list();
    if ($this->_environment->getCurrentContextID() != $this->getContextID()) {
        // Add a new labels if necessary
        $label_manager = $this->_environment->getLabelManager();
        $label_manager->reset();
        $label_manager->setContextLimit($this->_environment->getCurrentContextID());
        $label_manager->setTypeLimit('label');
        $label_manager->select();
        $label_list = $label_manager->get();
        $exist = null;
        if (!empty($label_list)) {
            $label = $label_list->getFirst();
            while ($label) {
                if (0 == strcmp((string) $label->getName(), ltrim($this->getLabel()))) {
                    $exist = $label->getItemID();
                }
                $label = $label_list->getNext();
            }
        }
        if (!isset($exist)) {
            $temp_array = [];
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->reset();
            $label_item = $label_manager->getNewItem();
            $label_item->setLabelType('label');
            $label_item->setTitle(ltrim($this->getLabel()));
            $label_item->setContextID($this->_environment->getCurrentContextID());
            $label_item->setCreatorItem($this->_environment->getCurrentUserItem());
            $label_item->setCreationDate(getCurrentDateTimeInMySQL());
            $label_item->save();
            $copy->setLabelId($label_item->getItemId());
        } elseif (isset($exist)) {
            $temp_array = [];
            $label_manager = $this->_environment->getLabelManager();
            $label_manager->reset();
            $label_item = $label_manager->getItem($exist);
            $copy->setLabelId($exist);
        }

        // Add a new buzzwords if necessary
        $original_buzzword_array = $this->getBuzzwordArray();
        // Get all buzzwords in context in array
        $buzzwords_in_room_array = [];
        $buzzword_manager = $this->_environment->getLabelManager();
        $buzzword_manager->reset();
        $buzzword_manager->setContextLimit($this->_environment->getCurrentContextID());
        $buzzword_manager->setTypeLimit('buzzword');
        $buzzword_manager->select();
        $buzzword_list = $buzzword_manager->get();
        if (!empty($buzzword_list)) {
            $buzzword = $buzzword_list->getFirst();
            while ($buzzword) {
                $temp_array['name'] = $buzzword->getName();
                $temp_array['id'] = $buzzword->getItemId();
                $buzzwords_in_room_array[] = $temp_array;
                $buzzword = $buzzword_list->getNext();
            }
        }

        // if buzzword exists, put id in array, if it doesn't exist, create it, then put id in array
        $buzzword_ids = [];
        if (isset($original_buzzword_array) and
             !empty($original_buzzword_array)) {
            for ($i = 0; $i < (is_countable($original_buzzword_array) ? count($original_buzzword_array) : 0); ++$i) {
                $found = false;
                if (isset($buzzwords_in_room_array) and
                     !empty($buzzwords_in_room_array)) { // There are buzzwords in the room
                    for ($j = 0; $j < count($buzzwords_in_room_array); ++$j) {
                        if (isset($buzzwords_in_room_array[$j]) and
                                   isset($original_buzzword_array[$i]) and
                                   isset($buzzwords_in_room_array[$j]['name']) and
                                   !empty($buzzwords_in_room_array[$j]['name']) and
                                   isset($buzzwords_in_room_array[$j]['id']) and
                                   !empty($buzzwords_in_room_array[$j]['id'])
                        ) {
                            if (0 == strcmp((string) $buzzwords_in_room_array[$j]['name'], ltrim((string) $original_buzzword_array[$i]))) {
                                $buzzword_ids[] = $buzzwords_in_room_array[$j]['id'];
                                $found = true;
                                break;
                            }
                            if (!$found and $j == count($buzzwords_in_room_array) - 1) {
                                $buzzword_manager = $this->_environment->getLabelManager();
                                $buzzword_manager->reset();
                                $buzzword_item = $buzzword_manager->getNewItem();
                                $buzzword_item->setLabelType('buzzword');
                                $buzzword_item->setTitle(ltrim((string) $original_buzzword_array[$i]));
                                $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                                $buzzword_item->setCreatorItem($this->_environment->getCurrentUserItem());
                                $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
                                $buzzword_item->save();
                                $buzzword_ids[] = $buzzword_item->getItemID();
                            }
                        }
                    }
                } else { // There are no buzzwords in the context, so create all
                    if (isset($original_buzzword_array[$i]) and
                         !empty($original_buzzword_array[$i])
                    ) {
                        $buzzword_manager = $this->_environment->getLabelManager();
                        $buzzword_manager->reset();
                        $buzzword_item = $buzzword_manager->getNewItem();
                        $buzzword_item->setLabelType('buzzword');
                        $buzzword_item->setTitle(ltrim((string) $original_buzzword_array[$i]));
                        $buzzword_item->setContextID($this->_environment->getCurrentContextID());
                        $buzzword_item->setCreatorItem($this->_environment->getCurrentUserItem());
                        $buzzword_item->setCreationDate(getCurrentDateTimeInMySQL());
                        $buzzword_item->save();
                        $buzzword_ids[] = $buzzword_item->getItemID();
                    }
                }
            }
        }
        $copy->setBuzzwordListByID($buzzword_ids);
        $copy->setGroupList($list);
        $copy->setTopicList($list);
    }

    $copy->setSectionList(new cs_list());
    $copy->save();
    $section_list = $this->_copySectionList($id);
    $copy->setSectionList($section_list);
    unset($section_list);
    $copy->save($mode = 'copy');
}

public function cloneCopy($new_version = false)
{
    $clone_item = clone $this; // "clone" needed for php5
    if (!empty($this->_changed) and !$new_version) {
        trigger_error('attempt to clone unsaved / changed material; clone will match the persistent state of this item', E_USER_WARNING);
    }
    $label_item = $this->getLabelItem();
    if (null != $label_item) {
        $clone_item->setLabel($label_item->getName());
    }
    $clone_item->setBuzzwordArray($this->getBuzzwordArray());
    $clone_item->setFileIDArray($this->getFileIDArray());
    $group_list = $this->getGroupList();
    $clone_item->setGroupList($group_list);
    $section_list = $this->getSectionList();
    $clone_item->setSectionList($section_list);
    $topic_list = $this->getTopicList();
    $clone_item->setTopicList($topic_list);

    return $clone_item;
}

public function _copySectionList($copy_id)
{
    $section_list = $this->getSectionList();
    $section_new_list = new cs_section_list();
    if (!empty($section_list) and $section_list->getCount() > 0) {
        $section_item = $section_list->getFirst();
        while ($section_item) {
            $file_list = $section_item->_copyFileList();
            $section_item->setFileList($file_list);
            $section_item->setItemID('');
            $section_item->setContextID($this->_environment->getCurrentContextID());
            $section_item->setLinkedItemID($copy_id);
            $section_new_list->append($section_item);
            $section_item = $section_list->getNext();
        }
    }

    return $section_new_list;
}

// ########################## DEPRECATED: SHOULD BE REMOVED ASAP

    public function isNotRequestedForPublishing()
    {
        $value = $this->getWorldPublic();
        if (empty($value) or 0 == $value) {
            return true;
        }

        return false;
    }

    public function isRequestedForPublishing()
    {
        $value = $this->getWorldPublic();
        if (1 == $value) {
            return true;
        }

        return false;
    }

    public function isPublished()
    {
        $value = $this->getWorldPublic();
        if (2 == $value) {
            return true;
        }

        return false;
    }

    /** get information in dublin core style
     * this method returns an array with information of the material in dublin core style.
     *
     * @return array array with information in dublin core style
     */
    public function getDublinCoreArray()
    {
        $retour = [];
        $retour['DC.TITLE'] = $this->getTitle();
        $retour['DC.CREATOR.NAME'] = $this->getAuthor();

        // hier sollte eigentlich nur der Verleger / Herausgeber erscheinen
        // das ist aber im grunde genommen okay
        $bibliographic = $this->getBibliographicValues();
        if (!empty($bibliographic) and strstr($bibliographic, '<!-- KFC TEXT -->')) {
            $bibliographic = str_replace('<!-- KFC TEXT -->', '', $bibliographic);
        }
        if (!empty($bibliographic)) {
            $retour['DC.PUBLISHER'] = htmlentities($bibliographic, ENT_NOQUOTES, 'UTF-8');
        }

        // das Datum muss eigentlich so vorliegen jjjjmmtt
        $retour['DC.DATE.CREATION'] = $this->getPublishingDate();

        // hierfür gibt es eigentlich eine definierte Liste im Standard
        $material_type = $this->getLabelItem();
        if (isset($material_type)) {
            $retour['DC.TYPE'] = $material_type->getName();
        }

        $file_list = $this->getFileList();
        if (!$file_list->isEmpty()) {
            $format = '';
            $first = true;
            $file_item = $file_list->getFirst();
            while ($file_item) {
                if ($first) {
                    $first = false;
                } else {
                    $format .= ', ';
                }
                $format .= $file_item->getMime();
                $format .= ' ('.$file_item->getFileSize().'kb)';
                $file_item = $file_list->getNext();
            }
        }
        if (empty($format)) {
            $format = 'Text/HTML';
        }
        $retour['DC.FORMAT'] = '(SCHEME=IMT) '.$format;

        // $retour['DC.Language'] = '';
        // $retour['DC.Coverage.Spatial'] = ''; //Geografische Gültigkeit

        $keyword_array = $this->getBuzzwordArray();
        if (!empty($keyword_array)) {
            $retour['DC.SUBJECT.KEYWORD'] = implode(',', $keyword_array);
        }

        $topic_list = $this->getTopicList();
        if (!$topic_list->isEmpty()) {
            $topic = '';
            $first = true;
            $topic_item = $topic_list->getFirst();
            while ($topic_item) {
                if ($first) {
                    $first = false;
                } else {
                    $topic .= ', ';
                }
                $topic .= $topic_item->getName();
                $topic_item = $topic_list->getNext();
            }
            $retour['DC.SUBJECT.CLASSIFICATION'] = $topic;
        }

        $description = $this->getDescription();
        if (!empty($description)) {
            $retour['DC.DESCRIPTION'] = strip_tags($description);
        }

        // $retour['DC.Relation'] = ''; //Angabe einer URL zu einer Ressource, die mit dem Material assiziierbar ist.

        // Die folgenden Angaben beziehen sich immer auf die Quelle, in der das Material publiziert wurde.
        // Dies könnte z.B. ein Buch sein, in dem das Material (Artikel) erschienen ist.
        // $retour['DC.Source.Creator'] = '';
        // $retour['DC.Source.Title'] = '';
        // $retour['DC.Source.Volume'] = '';
        // $retour['DC.Source.PublishingPlace'] = '';
        // $retour['DC.Source.Date'] = '';
        // $retour['DC.Source.PageNumber'] = '';

        // $retour['DC.RIGHTS'] = ''; // Standardtext zur Nutzerinformation, dass die Urheberrechte bzw. die spezifischen Verwertungsrechte am Dokument zu beachten sind.

        return $retour;
    }

    /** asks if item is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function isPublic()
    {
        if (1 == $this->_getValue('public')) {
            return true;
        }

        return false;
    }

    /** sets if announcement is editable by everybody or just creator.
     *
     * @param value
     */
    public function setPublic($value)
    {
        $this->_setValue('public', $value);
    }

    public function setSectionSaveId($section_id)
    {
        if (!empty($section_id)) {
            $this->_section_save_id = $section_id;
        } else {
            $this->_section_save_id = 'NEW';
        }
    }

    /** get list of files attached o this item.
       @return cs_list list of file items
     */
    public function getFileListWithFilesFromSections()
    {
        $file_list = new cs_list();
        if ('-1' == $this->getPublic()) {
            $translator = $this->_environment->getTranslationObject();

            return $file_list;
        } else {
            // material
            if (!empty($this->_data['file_list'])) {
                $file_list = $this->_data['file_list'];
            } else {
                if (isset($this->_data['file_id_array']) and !empty($this->_data['file_id_array'])) {
                    $file_id_array = $this->_data['file_id_array'];
                } else {
                    $link_manager = $this->_environment->getLinkManager();
                    $file_links = $link_manager->getFileLinks($this);
                    if (!empty($file_links)) {
                        foreach ($file_links as $link) {
                            $file_id_array[] = $link['file_id'];
                        }
                    }
                }
                if (!empty($file_id_array)) {
                    $file_manager = $this->_environment->getFileManager();
                    $file_manager->setIDArrayLimit($file_id_array);
                    $file_manager->setContextLimit('');
                    $file_manager->select();
                    $file_list = $file_manager->get();
                }
            }

            // sections
            $section_item_list = clone $this->getSectionList();
            if ($section_item_list->isNotEmpty()) {
                $section_list_item = $section_item_list->getFirst();
                while ($section_list_item) {
                    $section_file_list = $section_list_item->getFileList();
                    if ($section_file_list->isNotEmpty()) {
                        $file_list->addList($section_file_list);
                    }
                    unset($section_list_item);
                    $section_list_item = $section_item_list->getNext();
                }
            }
            unset($section_list_item);
            unset($section_item_list);
            $file_list->sortby('filename');
        }

        return $file_list;
    }

    // ------------------------------------------
    // ------------- study.log ------------------

    /** get the x-position of the item
     * this method get the x-position of the item for study.log.
     *
     * @param int
     */
    public function getPosX()
    {
        $retour = $this->_getExtra('x');

        return $retour;
    }

    /** get the y-position of the item
     * this method get the y-position of the item for study.log.
     *
     * @param int
     */
    public function getPosY()
    {
        $retour = $this->_getExtra('y');

        return $retour;
    }

    /** set the x-position of the item
     * this method set the x-position of the item for study.log.
     *
     * @param int
     */
    public function setPosX($value)
    {
        $this->_addExtra('x', (int) $value);
    }

    /** set the y-position of the item
     * this method set the y-position of the item for study.log.
     *
     * @param int
     */
    public function setPosY($value)
    {
        $this->_addExtra('y', (int) $value);
    }

    // ------------- study.log ------------------
    // ------------------------------------------

    public function isLocked()
    {
        if ($this->getEtherpadEditor()) {
            return false;
        }

        return parent::isLocked();
    }

     public function setLicenseId($licenseId)
     {
         $this->_setValue('license_id', $licenseId);
     }

     public function getLicenseId(): int
     {
         return (int) $this->_getValue('license_id');
     }

     public function getLicenseTitle()
     {
         if ($this->getLicenseId() && $this->getLicenseId() > 0) {
             global $symfonyContainer;
             $licensesRepository = $symfonyContainer->get('doctrine.orm.entity_manager')->getRepository(\App\Entity\License::class);
             $license = $licensesRepository->findOneById($this->getLicenseId());

             return $license->getTitle();
         }

         return '';
     }
}
