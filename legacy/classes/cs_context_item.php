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

/** class for a context
 * this class implements a context item.
 */
class cs_context_item extends cs_item
{
    public array $_default_colors = [];

    private cs_list $moderator_list;

    private cs_list $userList;

    public array $_default_rubrics_array = [];

    protected array $defaultHomeConf = [];

    public array $_current_rubrics_array = [];

    public array $_current_home_conf_array = [];

    public array $_rubric_support = [];

    public array $_cache_may_enter = [];

    private ?int $countItems = null;

    /** constructor: cs_context_item
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        cs_item::__construct($environment);
        $this->_type = 'context';

        $colors = [];
        $colors['schema'] = 'DEFAULT';
        $colors['tabs_background'] = '#3B658E';
        $colors['tabs_focus'] = '#EC930D';
        $colors['table_background'] = '#EFEFEF';
        $colors['tabs_title'] = 'white';
        $colors['tabs_separators'] = 'white';
        $colors['tabs_dash'] = 'white';
        $colors['headline_text'] = 'white';
        $colors['hyperlink'] = '#01458A';
        $colors['help_background'] = '#2079D3';
        $colors['boxes_background'] = 'white';
        $colors['content_background'] = '#EFECE2';
        $colors['list_entry_odd'] = '#EFECE2';
        $colors['list_entry_even'] = '#F7F7F7';
        $colors['myarea_headline_background'] = '#CDCBC2';
        $colors['myarea_headline_title'] = 'white';
        $colors['myarea_title_background'] = '#F7F7F7';
        $colors['myarea_content_background'] = '#EFECE2';
        $colors['myarea_section_title'] = '#666666';
        $colors['portal_tabs_background'] = '#666666';
        $colors['portal_tabs_title'] = 'white';
        $colors['portal_tabs_focus'] = '#EC930D';
        $colors['portal_td_head_background'] = '#F7F7F7';
        $colors['index_td_head_title'] = 'white';
        $colors['date_title'] = '#EC930D';
        $colors['info_color'] = '#827F76';
        $colors['disabled'] = '#B0B0B0';
        $colors['warning'] = '#FC1D12';
        $colors['welcome_text'] = '#3B658E';
        $colors['head_background'] = '#2A4E72';
        $colors['page_title'] = '#000000';

        $this->_default_colors = $colors;
        $this->userList = new cs_list();
    }

    public function isOpenForGuests()
    {
        if (1 == $this->_getValue('is_open_for_guests')) {
            return true;
        } else {
            return false;
        }
    }

    public function setOpenForGuests()
    {
        $this->_setValue('is_open_for_guests', 1, true);
    }

    public function setClosedForGuests()
    {
        $this->_setValue('is_open_for_guests', 0, true);
    }

    public function isMaterialOpenForGuests()
    {
        if ($this->_issetExtra('MATERIAL_GUESTS') and 1 == $this->_getExtra('MATERIAL_GUESTS')) {
            return true;
        } else {
            return false;
        }
    }

    public function setMaterialOpenForGuests()
    {
        $this->_addExtra('MATERIAL_GUESTS', 1, true);
    }

    public function setMaterialClosedForGuests()
    {
        $this->_addExtra('MATERIAL_GUESTS', 0, true);
    }

    public function isAssignmentOnlyOpenForRoomMembers()
    {
        $retour = false;
        if ($this->_issetExtra('ROOMASSOCIATION') and 'onlymembers' == $this->_getExtra('ROOMASSOCIATION')) {
            $retour = true;
        }

        return $retour;
    }

    public function setAssignmentOpenForAnybody()
    {
        $this->_addExtra('ROOMASSOCIATION', 'forall');
    }

    public function setAssignmentOnlyOpenForRoomMembers()
    {
        $this->_addExtra('ROOMASSOCIATION', 'onlymembers');
    }

    public function isCommunityRoom()
    {
        return false;
    }

    public function isPrivateRoom()
    {
        return false;
    }

    public function isGroupRoom()
    {
        return false;
    }

    public function isUserroom()
    {
        return false;
    }

    public function isProjectRoom()
    {
        return false;
    }

    public function isPortal()
    {
        return false;
    }

    public function isServer()
    {
        return false;
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array Is the prepared array from "_buildItemArray($db_array)"
     */
    public function _setItemData($data_array)
    {
        // not yet implemented
        $this->_data = $data_array;
    }

    /** get title of a context
     * this method returns the title of the context.
     *
     * @return string title of a context
     */
    public function getTitle()
    {
        return $this->_getValue('title');
    }

    /** set title of a context
     * this method sets the title of the context.
     *
     * @param string value title of the context
     */
    public function setTitle($value)
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value, true);
    }

    /** get room type of a context
     * this method returns the room type of the context.
     *
     * @return string room type of a context
     */
    public function getRoomType()
    {
        return $this->_getValue('type');
    }

    /** set room type of a context
     * this method sets the room type of the context.
     *
     * @param string value room type of the context
     */
    public function setRoomType($value)
    {
        $this->_setValue('type', $value, true);
    }

    /** det description array.
     *
     * @return array description text in different languages
     */
    public function getDescriptionArray()
    {
        $retour = [];
        if ($this->_issetExtra('DESCRIPTION')) {
            $retour = $this->_getExtra('DESCRIPTION');
        }

        return $retour;
    }

    public function getMaxUploadSizeInBytes()
    {
        $val = ini_get('upload_max_filesize');
        $val = trim($val);

        $last = $val[mb_strlen($val) - 1];
        $numericVal = (int) substr($val, 0, -1);
        match ($last) {
            'k', 'K' => $numericVal *= 1024,
            'm', 'M' => $numericVal *= 1_048_576,
            default => $numericVal,
        };
    }

    public function setNotShownInPrivateRoomHome($user_id)
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        $this->_addExtra('IS_SHOW_ON_HOME_'.$tag, 'NO');
    }

    public function setShownInPrivateRoomHome($user_id)
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        $this->_unsetExtra('IS_SHOW_ON_HOME_'.$tag);
    }

    /** get shown option.
     *
     * @return bool if room is shown on home
     */
    public function isShownInPrivateRoomHome($user_id)
    {
        $retour = 'true';
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$tag)) {
            if ('NO' == $this->_getExtra('IS_SHOW_ON_HOME_'.$tag)) {
                $retour = false;
            }
        }
        unset($current_user);

        return $retour;
    }

    /** get shown option.
     *
     * @return bool if room is shown on home
     */
    public function isShownInPrivateRoomHomeByItemID($item_id)
    {
        $retour = 'true';
        if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$item_id)) {
            if ('NO' == $this->_getExtra('IS_SHOW_ON_HOME_'.$item_id)) {
                $retour = false;
            }
        }

        return $retour;
    }

    /** set description array.
     *
     * @param array value description text in different languages
     */
    public function setDescriptionArray($value)
    {
        $this->_addExtra('DESCRIPTION', (array) $value);
    }

    /** get contact moderators of a room
     * this method returns a list of contact moderators which are linked to the room.
     *
     * @return cs_list a list of contact moderators (cs_label_item)
     */
    public function getContactModeratorList(): cs_list
    {
        if (!isset($this->_contact_moderator_list)) {
            $user_manager = $this->_environment->getUserManager();
            $user_manager->setContextLimit($this->getItemID());
            $user_manager->setContactModeratorLimit();
            $user_manager->select();
            $this->_contact_moderator_list = $user_manager->get();

            if ($this->_contact_moderator_list->isEmpty()) {
                $this->_contact_moderator_list = $this->getModeratorList();
            }
        }

        return $this->_contact_moderator_list;
    }

    public function getContactModeratorListString(): string
    {
        $list = $this->getContactModeratorList();

        return implode(', ', array_map(fn ($contact): string => $contact->getFullname(), $list->to_array()));
    }

      public function getModeratorListString(): string
      {
          $list = $this->getModeratorList();

          return implode(', ', array_map(fn ($moderator): string => $moderator->getFullname(), $list->to_array()));
      }

    /** get description of a context
     * this method returns the description of the context.
     *
     * @return string description of a context
     */
    public function getDescriptionByLanguage($language)
    {
        $retour = '';
        $desc_array = $this->getDescriptionArray();
        if (!empty($desc_array[cs_strtoupper($language)])) {
            $retour = $desc_array[cs_strtoupper($language)];
        }

        return $retour;
    }

    public function getDescription()
    {
        $retour = $this->getDescriptionByLanguage($this->_environment->getSelectedLanguage());
        if (empty($retour)) {
            $current_user = $this->_environment->getCurrentUserItem();
            $retour = $this->getDescriptionByLanguage($this->_environment->getUserLanguage());
        }
        if (empty($retour)) {
            $retour = $this->getDescriptionByLanguage($this->getLanguage());
        }
        if (empty($retour) and ($this->isProjectRoom() or $this->isCommunityRoom())) {
            $current_portal = $this->_environment->getCurrentPortalItem();
            $retour = $this->getDescriptionByLanguage($current_portal->getLanguage());
        }
        if (empty($retour)) {
            $server = $this->_environment->getServerItem();
            $retour = $this->getDescriptionByLanguage($server->getLanguage());
        }
        if (empty($retour)) {
            $desc_array = $this->getDescriptionArray();
            if (!empty($desc_array)) {
                foreach ($desc_array as $desc) {
                    if (!empty($desc)) {
                        $retour = $desc;
                        break;
                    }
                }
            }
        }

        return $retour;
    }

    /** get language
     * this method returns the language.
     *
     * @return string language
     */
    public function getLanguage()
    {
        if ($this->isServer()) {
            $retour = 'user';
        } else {
            $server = $this->_environment->getServerItem();
            $retour = $server->getLanguage();
        }
        if ($this->_issetExtra('LANGUAGE')) {
            $retour = $this->_getExtra('LANGUAGE');
        }

        return $retour;
    }

    /** set language
     * this method sets the language.
     *
     * @param string value language
     */
    public function setLanguage($value)
    {
        $this->_addExtra('LANGUAGE', (string) $value);
    }

    /** set description of a context
     * this method sets the description of the context.
     *
     * @param string value description of the context
     * @param string value lanugage of the description
     */
    public function setDescriptionByLanguage($value, $language)
    {
        $desc_array = $this->getDescriptionArray();
        $desc_array[mb_strtoupper($language, 'UTF-8')] = $value;
        $this->setDescriptionArray($desc_array);
    }

    /** get agb text.
     *
     * @return array agb text in different languages
     */
    public function getAGBTextArray()
    {
        $retour = [];
        if ($this->_issetExtra('AGBTEXTARRAY')) {
            $retour = $this->_getExtra('AGBTEXTARRAY');
        }

        return $retour;
    }

    /** set agb text.
     *
     * @param array value agb in different languages
     */
    public function setAGBTextArray($value)
    {
        $this->_addExtra('AGBTEXTARRAY', (array) $value);
    }

    /** get agb status.
     *
     * @return int agb status 1 = yes, 2 = no
     */
    public function getAGBStatus()
    {
        $retour = '2';
        if ($this->_issetExtra('AGBSTATUS')) {
            $retour = $this->_getExtra('AGBSTATUS');
        }

        return $retour;
    }

    /** set agb status.
     *
     * @param array value agb status
     */
    public function setAGBStatus($value)
    {
        $this->_addExtra('AGBSTATUS', (int) $value);
    }

    // @return boolean true = with AGB, false = without AGB
    public function withAGB()
    {
        $agb_status = $this->getAGBStatus();
        if (1 == $agb_status) {
            $retour = true;
        } else {
            $retour = false;
        }

        return $retour;
    }

      public function getAGBChangeDate(): ?DateTimeImmutable
      {
          if ($this->_issetExtra('AGB_CHANGE_DATE')) {
              $agbChangeDate = $this->_getExtra('AGB_CHANGE_DATE') ?? '';

              return !empty($agbChangeDate) ?
                  DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $agbChangeDate) :
                  null;
          }

          return null;
      }

      public function setAGBChangeDate(?DateTimeImmutable $agbChangeDate): self
      {
          $this->_addExtra(
              'AGB_CHANGE_DATE',
              $agbChangeDate ? $agbChangeDate->format('Y-m-d H:i:s') : ''
          );

          return $this;
      }

    public function withAssociations()
    {
        $retour = false;
        if ($this->_issetExtra('WITHASSOCIATIONS')) {
            $re = $this->_getExtra('WITHASSOCIATIONS');
            if (2 == $re) {
                $retour = true;
            }
        } else {
            $retour = true;
        }

        return $retour;
    }

    public function setWithBuzzwords()
    {
        $this->_addExtra('WITHBUZZWORDS', 2);
    }

    public function setWithoutBuzzwords()
    {
        $this->_addExtra('WITHBUZZWORDS', 1);
    }

    public function withBuzzwords()
    {
        $retour = false;
        if ($this->_issetExtra('WITHBUZZWORDS')) {
            $re = $this->_getExtra('WITHBUZZWORDS');
            if (2 == $re) {
                $retour = true;
            }
        } else {
            $retour = true;
        }

        return $retour;
    }

    public function isBuzzwordMandatory()
    {
        $retour = false;
        if ($this->_issetExtra('BUZZWORDMANDATORY')) {
            $value = $this->_getExtra('BUZZWORDMANDATORY');
            if (1 == $value) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setBuzzwordMandatory()
    {
        $this->_addExtra('BUZZWORDMANDATORY', 1);
    }

    public function unsetBuzzwordMandatory()
    {
        $this->_addExtra('BUZZWORDMANDATORY', 0);
    }

      public function isAssociationShowExpanded()
      {
          if ($this->_issetExtra('ASSOCIATIONSHOWEXPANDED')) {
              $value = $this->_getExtra('ASSOCIATIONSHOWEXPANDED');
              if (1 == $value) {
                  return true;
              }
          }

          return false;
      }

      public function setAssociationShowExpanded()
      {
          $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 1);
      }

      public function unsetAssociationShowExpanded()
      {
          $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 0);
      }

    public function isBuzzwordShowExpanded()
    {
        $retour = true;
        if ($this->_issetExtra('BUZZWORDSHOWEXPANDED')) {
            $value = $this->_getExtra('BUZZWORDSHOWEXPANDED');
            if (0 == $value) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setBuzzwordShowExpanded()
    {
        $this->_addExtra('BUZZWORDSHOWEXPANDED', 1);
    }

    public function unsetBuzzwordShowExpanded()
    {
        $this->_addExtra('BUZZWORDSHOWEXPANDED', 0);
    }

    public function setWithWorkflow()
    {
        $this->_addExtra('WITHWORKFLOW', 2);
    }

    public function setWithoutWorkflow()
    {
        $this->_addExtra('WITHWORKFLOW', 1);
    }

    public function withWorkflow()
    {
        $retour = false;
        if ($this->_issetExtra('WITHWORKFLOW')) {
            $re = $this->_getExtra('WITHWORKFLOW');
            if (2 == $re) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setWithWorkflowTrafficLight()
    {
        $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT', 2);
    }

    public function setWithoutWorkflowTrafficLight()
    {
        $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT', 1);
    }

    public function withWorkflowTrafficLight()
    {
        $retour = false;
        if ($this->_issetExtra('WITHWORKFLOWTRAFFICLIGHT')) {
            $re = $this->_getExtra('WITHWORKFLOWTRAFFICLIGHT');
            if (2 == $re) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setWorkflowTrafficLightDefault($value)
    {
        $this->_addExtra('WORKFLOWTRAFFICLIGHTDEFAULT', $value);
    }

    public function getWorkflowTrafficLightDefault()
    {
        $retour = '3_none';
        if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTDEFAULT')) {
            $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTDEFAULT');
        }

        return $retour;
    }

    public function setWorkflowTrafficLightTextGreen($value)
    {
        $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN', $value);
    }

    public function getWorkflowTrafficLightTextGreen()
    {
        $retour = '';
        if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN')) {
            $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTGREEN');
        }

        return $retour;
    }

    public function setWorkflowTrafficLightTextYellow($value)
    {
        $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW', $value);
    }

    public function getWorkflowTrafficLightTextYellow()
    {
        $retour = '';
        if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW')) {
            $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTYELLOW');
        }

        return $retour;
    }

    public function setWorkflowTrafficLightTextRed($value)
    {
        $this->_addExtra('WORKFLOWTRAFFICLIGHTTEXTRED', $value);
    }

    public function getWorkflowTrafficLightTextRed()
    {
        $retour = '';
        if ($this->_issetExtra('WORKFLOWTRAFFICLIGHTTEXTRED')) {
            $retour = $this->_getExtra('WORKFLOWTRAFFICLIGHTTEXTRED');
        }

        return $retour;
    }

    public function setWithWorkflowResubmission()
    {
        $this->_addExtra('WITHWORKFLOWRESUBMISSION', 2);
    }

    public function setWithoutWorkflowResubmission()
    {
        $this->_addExtra('WITHWORKFLOWRESUBMISSION', 1);
    }

    public function withWorkflowResubmission()
    {
        $retour = false;
        if ($this->_issetExtra('WITHWORKFLOWRESUBMISSION')) {
            $re = $this->_getExtra('WITHWORKFLOWRESUBMISSION');
            if (2 == $re) {
                $retour = true;
            }
        }
        // else {
        //  $retour = true;
        // }
        return $retour;
    }

    public function setWithWorkflowReader()
    {
        $this->_addExtra('WITHWORKFLOWREADER', 2);
    }

    public function setWithoutWorkflowReader()
    {
        $this->_addExtra('WITHWORKFLOWREADER', 1);
    }

    public function withWorkflowReader()
    {
        $retour = false;
        if ($this->_issetExtra('WITHWORKFLOWREADER')) {
            $re = $this->_getExtra('WITHWORKFLOWREADER');
            if (2 == $re) {
                $retour = true;
            }
        }
        // else {
        //  $retour = true;
        // }
        return $retour;
    }

    public function setWithWorkflowReaderGroup()
    {
        $this->_addExtra('WORKFLOWREADERGROUP', '1');
    }

    public function setWithoutWorkflowReaderGroup()
    {
        $this->_addExtra('WORKFLOWREADERGROUP', '0');
    }

    public function getWorkflowReaderGroup()
    {
        $retour = '0';
        if ($this->_issetExtra('WORKFLOWREADERGROUP')) {
            $retour = $this->_getExtra('WORKFLOWREADERGROUP');
        }

        return $retour;
    }

    public function setWithWorkflowReaderPerson()
    {
        $this->_addExtra('WORKFLOWREADERPERSON', '1');
    }

    public function setWithoutWorkflowReaderPerson()
    {
        $this->_addExtra('WORKFLOWREADERPERSON', '0');
    }

    public function getWorkflowReaderPerson()
    {
        $retour = '0';
        if ($this->_issetExtra('WORKFLOWREADERPERSON')) {
            $retour = $this->_getExtra('WORKFLOWREADERPERSON');
        }

        return $retour;
    }

    public function setWorkflowReaderShowTo($value)
    {
        $this->_addExtra('WORKFLOWREADERSHOWTO', $value);
    }

    public function getWorkflowReaderShowTo()
    {
        $retour = 'moderator';
        if ($this->_issetExtra('WORKFLOWREADERSHOWTO')) {
            $retour = $this->_getExtra('WORKFLOWREADERSHOWTO');
        }

        return $retour;
    }

    public function setWithWorkflowValidity()
    {
        $this->_addExtra('WITHWORKFLOWVALIDITY', 2);
    }

    public function setWithoutWorkflowValidity()
    {
        $this->_addExtra('WITHWORKFLOWVALIDITY', 1);
    }

    public function withWorkflowValidity()
    {
        $retour = false;
        if ($this->_issetExtra('WITHWORKFLOWVALIDITY')) {
            $re = $this->_getExtra('WITHWORKFLOWVALIDITY');
            if (2 == $re) {
                $retour = true;
            }
        }

        return $retour;
    }

    /** get htmltextarea status.
     *
     * @return int discussion status 1 = simple, 2 = threaded,  3 = both
     */
    public function getDiscussionStatus()
    {
        $retour = 1;
        if ($this->_issetExtra('DISCUSSIONSTATUS')) {
            $retour = $this->_getExtra('DISCUSSIONSTATUS');
        }

        return $retour;
    }

    /** set agb status.
     *
     * @param array value discussion status
     */
    public function setDiscussionStatus($value)
    {
        $this->_addExtra('DISCUSSIONSTATUS', (int) $value);
    }

      /** get htmltextarea status.
       *
       * @return int htmltextarea status 1 = yes, 2 = yes, but minimum, 3 = no
       */
      public function getHtmlTextAreaStatus()
      {
          return 3;
      }

    /** set agb status.
     *
     * @param array value HTMLTextArea status
     */
    public function setHtmlTextAreaStatus($value)
    {
        $this->_addExtra('HTMLTEXTAREASTATUS', (int) $value);
    }

    // @return boolean true = with HTMLTextArea, false = without HTMLTextArea
    public function withHtmlTextArea()
    {
        $htmltextarea = $this->getHtmlTextAreaStatus();
        if (3 != $htmltextarea) {
            $retour = true;
        } else {
            $retour = false;
        }

        return $retour;
    }

    /** get dates status.
     *
     * @return int dates status "normal" or "calendar"
     */
    public function getDatesPresentationStatus()
    {
        $retour = 'normal';
        if ($this->_issetExtra('DATEPRESENTATIONSTATUS')) {
            $retour = $this->_getExtra('DATEPRESENTATIONSTATUS');
        }

        // new private room
        if ($this->isPrivateRoom() && 'normal' == $retour) {
            $retour = 'calendar_month';
        }

        return $retour;
    }

    /** set agb status.
     *
     * @param array value dates status
     */
    public function setDatesPresentationStatus($value)
    {
        $this->_addExtra('DATEPRESENTATIONSTATUS', (string) $value);
    }

    /** returns a boolean, if the the user can enter the context
     * true: user can enter project
     * false: user can not enter project.
     *
     * @param object user item this user wants to enter the project
     */
    public function mayEnter($user_item)
    {
        return $this->mayEnterByUserID($user_item->getUserID(), $user_item->getAuthSource());
    }

    /**
     * returns a boolean, if  the user can enter the context
     * true: user can enter project
     * false: user can not enter project.
     *
     * @param string $user_id id of user wants to enter the project
     */
    public function mayEnterByUserID($user_id, $auth_source): bool
    {
        if (isset($this->_cache_may_enter[$user_id.'_'.$auth_source])) {
            return $this->_cache_may_enter[$user_id.'_'.$auth_source];
        }

        if ('root' == $user_id) {
            return true;
        }

        if ($this->isLocked()) {
            return false;
        }

        if ($this->isOpenForGuests()) {
            return true;
        }

        $user_manager = $this->_environment->getUserManager();
        if ($user_manager->isUserInContext($user_id, $this->getItemID(), $auth_source)) {
            $this->_cache_may_enter[$user_id.'_'.$auth_source] = true;

            return true;
        } else {
            $this->_cache_may_enter[$user_id.'_'.$auth_source] = false;
        }

        return false;
    }

     public function isSystemLabel()
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

    public function mayEnterByUserItemID($user_item_id)
    {
        $retour = false;
        if ($this->isLocked()) {
            $retour = false;
        } elseif (isset($this->_cache_may_enter[$user_item_id])) {
            $retour = $this->_cache_may_enter[$user_item_id];
        } elseif ($this->isOpenForGuests()) {
            $retour = true;
        } else {
            $user_manager = $this->_environment->getUserManager();
            $user_in_room = $user_manager->getItem($user_item_id);
            if ($user_in_room->isUser()
                 and $user_in_room->getContextID() == $this->getItemID()
            ) {
                $retour = true;
                $this->_cache_may_enter[$user_item_id] = true;
            } else {
                $this->_cache_may_enter[$user_item_id] = false;
            }
            unset($user_in_room);
            unset($user_manager);
        }

        return $retour;
    }

    public function getColorArray()
    {
        $retour = $this->_default_colors;
        if ($this->_issetExtra('COLOR')) {
            $retour = $this->_getExtra('COLOR');
            $retour_temp = [];
            if (is_array($retour)) {
                foreach ($retour as $key => $entry) {
                    $retour_temp[mb_strtolower($key, 'UTF-8')] = $entry;
                }
            }
            $retour = $retour_temp;
        }

        return $retour;
    }

    public function setColorArray($array)
    {
        if (is_array($array)) {
            $this->_addExtra('COLOR', $array);
        }
    }

    /** get flag for checking always new members
     * this method returns a boolean for checking always new members.
     *
     * @return int the flag
     */
    public function checkNewMembersAlways()
    {
        $retour = true;
        if ($this->checkNewMembersSometimes()
                or $this->checkNewMembersNever()
                or $this->checkNewMembersWithCode()
        ) {
            $retour = false;
        }

        return $retour;
    }

    /** get flag for checking always new members
     * this method returns a boolean for checking always new members.
     *
     * @return int the flag
     */
    public function checkNewMembersSometimes()
    {
        $retour = false;
        if (2 == $this->_getCheckNewMembers()) {
            $retour = true;
        }

        return $retour;
    }

    /** get flag for checking always new members
     * this method returns a boolean for checking always new members.
     *
     * @return int the flag
     */
    public function checkNewMembersWithCode()
    {
        $retour = false;
        if (3 == $this->_getCheckNewMembers()) {
            $retour = true;
        }

        return $retour;
    }

    public function setCheckNewMemberCode($value)
    {
        $this->_addExtra('CHECKNEWMEMBERS_CODE', $value);
    }

    public function getCheckNewMemberCode()
    {
        $retour = '';
        if ($this->_issetExtra('CHECKNEWMEMBERS_CODE')) {
            $retour = $this->_getExtra('CHECKNEWMEMBERS_CODE');
        }

        return $retour;
    }

    /** get flag for checking never new members
     * this method returns a boolean for checking never new members.
     *
     * @return int the flag
     */
    public function checkNewMembersNever()
    {
        $retour = false;
        if (-1 == $this->_getCheckNewMembers()) {
            $retour = true;
        }

        return $retour;
    }

    /** get flag for checking new members, INTERNAL -> use checkNewMember()
     * this method returns a flag for checking new members.
     *
     * @return int the flag: -1, new members can enter instantly
     *             1, moderator must activate new members
     *             2, moderator must activate new members,
     *             - room: if account is new
     *             - portal: if account with room membership
     */
    public function _getCheckNewMembers()
    {
        $retour = false;
        if ($this->_issetExtra('CHECKNEWMEMBERS')) {
            $retour = $this->_getExtra('CHECKNEWMEMBERS');
        }

        return $retour;
    }

    /**
     * Return value for Room asociation.
     *
     * @return mixed|string|void
     */
    public function _getRoomAssociation()
    {
        $retour = '';
        if ($this->_issetExtra('ROOMASSOCIATION')) {
            $retour = $this->_getExtra('ROOMASSOCIATION');
        }

        return $retour;
    }

    /*
     * set value to room asociation
     */
    public function _setRoomAssociation($value)
    {
        $this->_addExtra('ROOMASSOCIATION', $value);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function _setCheckNewMember($value)
    {
        $this->_addExtra('CHECKNEWMEMBERS', $value);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberAlways()
    {
        $this->_setCheckNewMember(1);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberSometimes()
    {
        $this->_setCheckNewMember(2);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberWithCode()
    {
        $this->_setCheckNewMember(3);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberNever()
    {
        $this->_setCheckNewMember(-1);
    }

    /** get filename of logo.
     *
     * @return string filename of logo
     */
    public function getLogoFilename()
    {
        $retour = '';
        if ($this->_issetExtra('LOGOFILENAME')) {
            $retour = $this->_getExtra('LOGOFILENAME');
        }

        return $retour;
    }

    /** set filename of logo.
     *
     * @param string filename of logo
     */
    public function setLogoFilename($value)
    {
        $this->_addExtra('LOGOFILENAME', (string) $value);
    }

    // ##################################################
    // email text translation methods
    // ##################################################

    public function getEmailTextArray()
    {
        $retour = [];
        if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
            $retour = $this->_getExtra('MAIL_TEXT_ARRAY');
        }

        return $retour;
    }

    public function setEmailText($message_tag, $array)
    {
        $mail_text_array = [];
        if ($this->_issetExtra('MAIL_TEXT_ARRAY')) {
            $mail_text_array = $this->_getExtra('MAIL_TEXT_ARRAY');
        }
        if (!empty($array)) {
            $mail_text_array[$message_tag] = $array;
        } elseif (!empty($mail_text_array[$message_tag])) {
            unset($mail_text_array[$message_tag]);
        }
        $this->_addExtra('MAIL_TEXT_ARRAY', $mail_text_array);
    }

    public function setEmailTextArray($array)
    {
        if (!empty($array)) {
            $this->_addExtra('MAIL_TEXT_ARRAY', $array);
        }
    }

    // ##################################################
    // rubric translation methods
    // ##################################################

    public function getRubricTranslationArray()
    {
        $retour = [];
        $rubric_array = [];
        $rubric_array[] = CS_PROJECT_TYPE;
        $rubric_array[] = CS_COMMUNITY_TYPE;
        $rubric_array[] = CS_TOPIC_TYPE;
        $rubric_array[] = CS_TIME_TYPE;

        foreach ($rubric_array as $rubric) {
            $retour[cs_strtoupper($rubric)] = $this->_getRubricArray($rubric);
        }

        return $retour;
    }

    /** set RubricArray
     * this method sets the Rubric Name.
     *
     * @param array value name cases
     */
    public function setRubricArray($rubric, $array)
    {
        $rubric_translation_array = $this->_getExtra('RUBRIC_TRANSLATION_ARRAY');
        $rubric_translation_array[cs_strtoupper($rubric)] = $array;
        $this->_addExtra('RUBRIC_TRANSLATION_ARRAY', $rubric_translation_array);
    }

    /** get RubricArray
     * this method gets the Rubric Name.
     *
     * @return array value name cases
     */
    public function _getRubricArray($rubric)
    {
        $retour = [];
        if ($this->_issetExtra('RUBRIC_TRANSLATION_ARRAY')) {
            $rubric_translation_array = $this->_getExtra('RUBRIC_TRANSLATION_ARRAY');
            if (!empty($rubric_translation_array[cs_strtoupper($rubric)])) {
                $retour = $rubric_translation_array[cs_strtoupper($rubric)];
            }
        }
        if (empty($retour)) {
            if (CS_PROJECT_TYPE == $rubric) {
                $retour['NAME'] = CS_PROJECT_TYPE;
                $retour['DE']['GENUS'] = 'M';
                $retour['DE']['NOMS'] = 'Projektraum';
                $retour['DE']['GENS'] = 'Projektraums';
                $retour['DE']['AKKS'] = 'Projektraum';
                $retour['DE']['DATS'] = 'Projektraum';
                $retour['DE']['NOMPL'] = 'Projekträume';
                $retour['DE']['GENPL'] = 'Projekträume';
                $retour['DE']['AKKPL'] = 'Projekträume';
                $retour['DE']['DATPL'] = 'Projekträumen';
                $retour['EN']['GENUS'] = 'M';
                $retour['EN']['NOMS'] = 'project workspace';
                $retour['EN']['GENS'] = 'project workspace';
                $retour['EN']['AKKS'] = 'project workspace';
                $retour['EN']['DATS'] = 'project workspace';
                $retour['EN']['NOMPL'] = 'project workspaces';
                $retour['EN']['GENPL'] = 'project workspaces';
                $retour['EN']['AKKPL'] = 'project workspaces';
                $retour['EN']['DATPL'] = 'project workspaces';
                $retour['RU']['GENUS'] = 'F';
                $retour['RU']['NOMS'] = 'sala de proiecte';
                $retour['RU']['GENS'] = 'salii de proiecte';
                $retour['RU']['AKKS'] = 'sala de proiecte';
                $retour['RU']['DATS'] = 'salii de proiecte';
                $retour['RU']['NOMPL'] = 'salile de proiecte';
                $retour['RU']['GENPL'] = 'salilor de proiecte';
                $retour['RU']['AKKPL'] = 'salile de proiecte';
                $retour['RU']['DATPL'] = 'salilor de proiecte';
            } elseif (CS_COMMUNITY_TYPE == $rubric) {
                $retour['NAME'] = CS_COMMUNITY_TYPE;
                $retour['DE']['GENUS'] = 'M';
                $retour['DE']['NOMS'] = 'Gemeinschaftsraum';
                $retour['DE']['GENS'] = 'Gemeinschaftsraums';
                $retour['DE']['AKKS'] = 'Gemeinschaftsraum';
                $retour['DE']['DATS'] = 'Gemeinschaftsraum';
                $retour['DE']['NOMPL'] = 'Gemeinschaftsräume';
                $retour['DE']['GENPL'] = 'Gemeinschaftsräume';
                $retour['DE']['AKKPL'] = 'Gemeinschaftsräume';
                $retour['DE']['DATPL'] = 'Gemeinschaftsräumen';
                $retour['EN']['GENUS'] = 'M';
                $retour['EN']['NOMS'] = 'community workspace';
                $retour['EN']['GENS'] = 'community workspace';
                $retour['EN']['AKKS'] = 'community workspace';
                $retour['EN']['DATS'] = 'community workspace';
                $retour['EN']['NOMPL'] = 'community workspaces';
                $retour['EN']['GENPL'] = 'community workspaces';
                $retour['EN']['AKKPL'] = 'community workspaces';
                $retour['EN']['DATPL'] = 'community workspaces';
                $retour['RU']['GENUS'] = 'F';
                $retour['RU']['NOMS'] = 'sala comunitara';
                $retour['RU']['GENS'] = 'salii comunitare';
                $retour['RU']['AKKS'] = 'sala comunitara';
                $retour['RU']['DATS'] = 'salii comunitare';
                $retour['RU']['NOMPL'] = 'salile comunitare';
                $retour['RU']['GENPL'] = 'salilor comunitare';
                $retour['RU']['AKKPL'] = 'salile comunitare';
                $retour['RU']['DATPL'] = 'salilor comunitare';
            } elseif (CS_TOPIC_TYPE == $rubric) {
                $retour['NAME'] = CS_TOPIC_TYPE;
                $retour['DE']['GENUS'] = 'N';
                $retour['DE']['NOMS'] = 'Thema';
                $retour['DE']['GENS'] = 'Themas';
                $retour['DE']['AKKS'] = 'Thema';
                $retour['DE']['DATS'] = 'Thema';
                $retour['DE']['NOMPL'] = 'Themen';
                $retour['DE']['GENPL'] = 'Themen';
                $retour['DE']['AKKPL'] = 'Themen';
                $retour['DE']['DATPL'] = 'Themen';
                $retour['EN']['GENUS'] = 'N';
                $retour['EN']['NOMS'] = 'topic';
                $retour['EN']['GENS'] = 'topic';
                $retour['EN']['AKKS'] = 'topic';
                $retour['EN']['DATS'] = 'topic';
                $retour['EN']['NOMPL'] = 'topics';
                $retour['EN']['GENPL'] = 'topics';
                $retour['EN']['AKKPL'] = 'topics';
                $retour['EN']['DATPL'] = 'topics';
                $retour['RU']['GENUS'] = 'F';
                $retour['RU']['NOMS'] = 'tema';
                $retour['RU']['GENS'] = 'temei';
                $retour['RU']['AKKS'] = 'tema';
                $retour['RU']['DATS'] = 'temei';
                $retour['RU']['NOMPL'] = 'temele';
                $retour['RU']['GENPL'] = 'temelor';
                $retour['RU']['AKKPL'] = 'temele';
                $retour['RU']['DATPL'] = 'temelor';
            } else {
                $retour['NAME'] = 'rubrics';
                $retour['DE']['GENUS'] = 'F';
                $retour['DE']['NOMS'] = 'Rubrik';
                $retour['DE']['GENS'] = 'Rubrik';
                $retour['DE']['AKKS'] = 'Rubrik';
                $retour['DE']['DATS'] = 'Rubrik';
                $retour['DE']['NOMPL'] = 'Rubriken';
                $retour['DE']['GENPL'] = 'Rubriken';
                $retour['DE']['AKKPL'] = 'Rubriken';
                $retour['DE']['DATPL'] = 'Rubriken';
                $retour['EN']['GENUS'] = 'F';
                $retour['EN']['NOMS'] = 'rubric';
                $retour['EN']['GENS'] = 'rubric';
                $retour['EN']['AKKS'] = 'rubric';
                $retour['EN']['DATS'] = 'rubric';
                $retour['EN']['NOMPL'] = 'rubrics';
                $retour['EN']['GENPL'] = 'rubrics';
                $retour['EN']['AKKPL'] = 'rubrics';
                $retour['EN']['DATPL'] = 'rubrics';
                $retour['RU']['GENUS'] = 'F';
                $retour['RU']['NOMS'] = 'rubrica';
                $retour['RU']['GENS'] = 'rubricii';
                $retour['RU']['AKKS'] = 'rubrica';
                $retour['RU']['DATS'] = 'rubricii';
                $retour['RU']['NOMPL'] = 'rubricile';
                $retour['RU']['GENPL'] = 'rubricilor';
                $retour['RU']['AKKPL'] = 'rubricile';
                $retour['RU']['DATPL'] = 'rubricilor';
            }
        }

        return $retour;
    }

    /** get show title, INTERNAL.
     *
     * @return int show title: -1 = not
     *             1 = yes
     */
    public function _getShowTitle()
    {
        $retour = '';
        if ($this->_issetExtra('SHOWTITLE')) {
            $retour = $this->_getExtra('SHOWTITLE');
        }

        return $retour;
    }

    /** set show title, INTERNAL.
     *
     * @param int show title: -1 = not
     *                             1 = yes
     */
    public function _setShowTitle($value)
    {
        $this->_addExtra('SHOWTITLE', (int) $value);
    }

    /** set show title.
     */
    public function setShowTitle()
    {
        $this->_setShowTitle(1);
    }

    /** set not show title.
     */
    public function setNotShowTitle()
    {
        $this->_setShowTitle(-1);
    }

    /** show title ?
     * true = show title, default
     * false = show title not.
     *
     * @return bool
     */
    public function showTitle()
    {
        $retour = true;
        $show_int = $this->_getShowTitle();
        if (isset($show_int) and !empty($show_int)) {
            if (-1 == $show_int) {
                $retour = false;
            }
        }

        return $retour;
    }

    /** get moderators of the context
     * this method returns a list of moderators of the context.
     *
     * @return cs_list a list of moderator (cs_user_item)
     */
    public function getModeratorList(): cs_list
    {
        if (!isset($this->moderator_list)) {
            $userManager = $this->_environment->getUserManager();
            $userManager->resetLimits();
            $userManager->setContextLimit($this->getItemID());
            $userManager->setModeratorLimit();
            $userManager->select();
            $this->moderator_list = $userManager->get();
        }

        return $this->moderator_list;
    }

    public function getHomeConf()
    {
        $retour = $this->_issetExtra('HOMECONF') ? $this->_getExtra('HOMECONF') : '';

        if (empty($retour)) {
            $retour = $this->getDefaultHomeConf();
            $this->setHomeConf($retour);
        }

        return $retour;
    }

    /**
     * get configuration of the homepage
     * this method configuration of the homepage.
     *
     * @return string configuration of the homepage
     */
    public function getDefaultHomeConf(): string
    {
        $rubrics = $this->_default_rubrics_array;

        // only consider rubrics that are set in the default home conf array
        // this should always be the case
        $rubrics = array_filter($rubrics, fn ($rubric) => isset($this->defaultHomeConf[$rubric]));

        $rubrics = array_map(fn ($rubric) => "${rubric}_show", $rubrics);

        return implode(',', $rubrics);
    }

    /**
     * set home conf
     * this method sets the home conf.
     *
     * @param string $config home conf
     */
    public function setHomeConf(string $config)
    {
        // validate
        $rubrics = explode(',', $config);
        $filtered = array_filter($rubrics, function ($rubric) {
            [$rubricType, $rubricConf] = explode('_', $rubric);
            return isset($this->defaultHomeConf[$rubricType]) && in_array($rubricConf, ['show', 'hide']);
        });

        if (count($rubrics) != count($filtered)) {
            throw new LogicException('Invalid rubric configuration');
        }

        $this->_addExtra('HOMECONF', $config);
    }

    // #########################################
    // extras (add-ons) configuration
    // ############# BEGIN #####################

    /** get part of the extra config array, INTERNAL.
     *
     * @param string type: ads for sponsoring / ads
     *                     whole for the whole array
     *
     * @return int 1 = true / 0 = false
     */
    public function _getExtraConfig($type)
    {
        if ('whole' == $type) {
            $retour = [];
        } else {
            $retour = '';
        }
        if ($this->_issetExtra('EXTRA_CONFIG')) {
            $extra_config_array = $this->_getExtra('EXTRA_CONFIG');
            if ('whole' == $type) {
                $retour = $extra_config_array;
            } elseif (isset($extra_config_array[mb_strtoupper($type, 'UTF-8')])) {
                $retour = $extra_config_array[mb_strtoupper($type, 'UTF-8')];
            }
        }

        return $retour;
    }

    /** set part of the extra config array, INTERNAL.
     *
     * @param string part: ads for sponsoring / ads
     *                     whole for the whole array
     * @param array
     */
    public function _setExtraConfig($type, $value)
    {
        if ('whole' == $type) {
            $this->_addExtra('EXTRA_CONFIG', $value);
        } else {
            $extra_config_array = $this->_getExtraConfig('whole');
            $extra_config_array[mb_strtoupper($type, 'UTF-8')] = $value;
            $this->_setExtraConfig('whole', $extra_config_array);
        }
    }

    public function getExtraConfig()
    {
        return $this->_getExtraConfig('whole');
    }

    public function setExtraConfig($value)
    {
        $this->_setExtraConfig('whole', $value);
    }

    // #########################################
    // log-archive flag
    // #########################################

    public function withLogArchive()
    {
        $retour = false;
        $value = $this->_getExtraConfig('LOGARCHIVE');
        if (1 == $value) {
            $retour = true;
        }

        return $retour;
    }

    // #########################################
    // assessment flag
    // #########################################

    public function setAssessmentActive()
    {
        $this->_addExtra('ASSESSMENT', (int) 1);
    }

    public function setAssessmentInactive()
    {
        $this->_addExtra('ASSESSMENT', (int) -1);
    }

    public function isAssessmentActive()
    {
        $retour = false;
        if ($this->_issetExtra('ASSESSMENT')) {
            $active = $this->_getExtra('ASSESSMENT');
            if (1 == $active) {
                $retour = true;
            }
        }

        return $retour;
    }

    // #########################################
    // grouproom flag
    // #########################################

    public function withGrouproomFunctions()
    {
        return true;
    }

    public function showGrouproomConfig()
    {
        $retour = false;
        if ($this->withGrouproomFunctions()) {
            $retour = true;
        } elseif ($this->isProjectRoom()
                or $this->isCommunityRoom()
        ) {
            $portal = $this->getContextItem();
            $retour = $portal->withGrouproomFunctions();
        }

        return $retour;
    }

    public function showGrouproomFunctions()
    {
        $retour = false;
        if ($this->showGrouproomConfig() and $this->isGrouproomActive()) {
            $retour = true;
        }

        return $retour;
    }

    /** is group room active ?
     * can be switched at room configuration.
     *
     * true = group room is active
     * false = group room is not active, default
     *
     * @return bool
     */
    public function isGrouproomActive()
    {
        return true;
    }

    /** set activity of the group room, INTERNAL.
     *
     * @param int value: -1 = not
     *                        1 = yes
     */
    public function _setGrouproomActivity($value)
    {
        $this->_addExtra('GROUPROOM', (int) $value);
    }

    /** set group room active.
     */
    public function setGrouproomActive()
    {
        $this->_setGrouproomActivity(1);
    }

    /** set group room inactive.
     */
    public function setGrouproomInactive()
    {
        $this->_setGrouproomActivity(-1);
    }

    // #########################################
    // service link
    // #########################################

    public function showServiceLink()
    {
        $retour = false;
        if ($this->isServiceLinkActive()) {
            $retour = true;
        }

        return $retour;
    }

    /**
     *  set service email adress.
     */
    public function setServiceEmail($email)
    {
        $this->_addExtra('SERVICEEMAIL', (string) $email);
    }

    /**
     *  get service email adress.
     */
    public function getServiceEmail()
    {
        return $this->_getExtra('SERVICEEMAIL');
    }

    /**
     *  set external service link.
     */
    public function setServiceLinkExternal($email)
    {
        $this->_addExtra('SERVICELINKEXTERNAL', (string) $email);
    }

    /**
     *  get external service link.
     */
    public function getServiceLinkExternal()
    {
        return $this->_getExtra('SERVICELINKEXTERNAL');
    }

    /** is service link active ?
     * can be switched at room configuration.
     *
     * true = service link is active
     * false = service link is not active, default
     *
     * @return bool
     */
    public function isServiceLinkActive()
    {
        $retour = false;
        if ($this->_issetExtra('SERVICELINK')) {
            $active = $this->_getExtra('SERVICELINK');
            if (1 == $active) {
                $retour = true;
            }
        }

        return $retour;
    }

    /** set activity of the service link, INTERNAL.
     *
     * @param int value: -1 = not
     *                        1 = yes
     */
    public function _setServiceLinkActivity($value)
    {
        if ($this->_issetExtra('SERVICELINK')) {
            $this->_setExtra('SERVICELINK', (int) $value);
        } else {
            $this->_addExtra('SERVICELINK', (int) $value);
        }
    }

    /** set service link active.
     */
    public function setServiceLinkActive()
    {
        $this->_setServiceLinkActivity(1);
    }

    /** set service link inactive.
     */
    public function setServiceLinkInactive()
    {
        $this->_setServiceLinkActivity(0);
    }

    public function getExtraToDoStatusArray()
    {
        $retour = [];
        if ($this->_issetExtra('TODOEXTRASTATUSARRAY')) {
            $retour = $this->_getExtra('TODOEXTRASTATUSARRAY');
        }

        return $retour;
    }

    public function setExtraToDoStatusArray($array)
    {
        if (!$this->_issetExtra('TODOEXTRASTATUSARRAY')) {
            $this->_addExtra('TODOEXTRASTATUSARRAY', $array);
        } else {
            $this->_setExtra('TODOEXTRASTATUSARRAY', $array);
        }

        return;
    }

    public function setTemplateAvailability($value)
    {
        if (!$this->_issetExtra('TEMPLATE_AVAILABILITY')) {
            $this->_addExtra('TEMPLATE_AVAILABILITY', (int) $value);
        } else {
            $this->_setExtra('TEMPLATE_AVAILABILITY', (int) $value);
        }
    }

    public function getTemplateAvailability()
    {
        $retour = '1';
        if ($this->_issetExtra('TEMPLATE_AVAILABILITY')) {
            $retour = $this->_getExtra('TEMPLATE_AVAILABILITY');
        }

        return $retour;
    }

    public function setCommunityTemplateAvailability($value)
    {
        if (!$this->_issetExtra('TEMPLATE_COMMUNITY_AVAILABILITY')) {
            $this->_addExtra('TEMPLATE_COMMUNITY_AVAILABILITY', (int) $value);
        } else {
            $this->_setExtra('TEMPLATE_COMMUNITY_AVAILABILITY', (int) $value);
        }
    }

    public function getCommunityTemplateAvailability()
    {
        $retour = '1';
        if ($this->_issetExtra('TEMPLATE_COMMUNITY_AVAILABILITY')) {
            $retour = $this->_getExtra('TEMPLATE_COMMUNITY_AVAILABILITY');
        }

        return $retour;
    }

    // #########################################
    // Pfad
    // #########################################

    public function withPath()
    {
        return true;
    }

    public function InformationBoxWithExistingObject()
    {
        $retour = false;
        $id = $this->getInformationBoxEntryID();
        $manager = $this->_environment->getItemManager();
        $item = $manager->getItem($id);
        if (is_object($item) and !$item->isDeleted()) {
            $entry_manager = $this->_environment->getManager($item->getItemType());
            $entry = $entry_manager->getItem($id);
            if (is_object($entry) and !$entry->isDeleted()) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function withInformationBox()
    {
        $retour = false;
        if ($this->_issetExtra('WITHINFORMATIONBOX')) {
            if ('yes' == $this->_getExtra('WITHINFORMATIONBOX') and $this->InformationBoxWithExistingObject()) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setwithInformationBox($value)
    {
        $this->_addExtra('WITHINFORMATIONBOX', (string) $value);
    }

    public function getDefaultProjectTemplateID()
    {
        $retour = '-1';
        if ($this->_issetExtra('DEFAULTPROJECTTEMPLATEID')) {
            $retour = $this->_getExtra('DEFAULTPROJECTTEMPLATEID');
        }

        return $retour;
    }

    public function setDefaultProjectTemplateID($value)
    {
        $this->_addExtra('DEFAULTPROJECTTEMPLATEID', (string) $value);
    }

    public function getDefaultCommunityTemplateID()
    {
        $retour = '-1';
        if ($this->_issetExtra('DEFAULTCOMMUNITYTEMPLATEID')) {
            $retour = $this->_getExtra('DEFAULTCOMMUNITYTEMPLATEID');
        }

        return $retour;
    }

    public function setDefaultCommunityTemplateID($value)
    {
        $this->_addExtra('DEFAULTCOMMUNITYTEMPLATEID', (string) $value);
    }

    public function getTemplateDescription()
    {
        $retour = '';
        if ($this->_issetExtra('TEMPLATEDESCRIPTION')) {
            $retour = $this->_getExtra('TEMPLATEDESCRIPTION');
        }

        return $retour;
    }

    public function setTemplateDescription($value)
    {
        $this->_addExtra('TEMPLATEDESCRIPTION', (string) $value);
    }

    public function getInformationBoxEntryID()
    {
        if ($this->_issetExtra('INFORMATIONBOXENTRYID')) {
            return $this->_getExtra('INFORMATIONBOXENTRYID');
        }

        return '';
    }

    public function setInformationBoxEntryID($value)
    {
        $this->_addExtra('INFORMATIONBOXENTRYID', (string) $value);
    }

    // #########################################
    // Tags
    // #########################################

    public function isTagMandatory()
    {
        $retour = false;
        if ($this->_issetExtra('TAGMANDATORY')) {
            $value = $this->_getExtra('TAGMANDATORY');
            if (1 == $value) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setTagMandatory()
    {
        $this->_addExtra('TAGMANDATORY', 1);
    }

    public function unsetTagMandatory()
    {
        $this->_addExtra('TAGMANDATORY', 0);
    }

    public function isTagEditedByAll()
    {
        $retour = true;
        if ($this->_issetExtra('TAGEDITEDBY')) {
            $value = $this->_getExtra('TAGEDITEDBY');
            if (2 == $value) {
                $retour = false;
            }
        }

        return $retour;
    }

    public function setBGImageFilename($name)
    {
        $this->_addExtra('BGIMAGEFILENAME', $name);
    }

    public function getBGImageFilename()
    {
        $retour = '';
        if ($this->_issetExtra('BGIMAGEFILENAME')) {
            $retour = $this->_getExtra('BGIMAGEFILENAME');
        }

        return $retour;
    }

    public function setTagEditedByModerator()
    {
        $this->_addExtra('TAGEDITEDBY', 2);
    }

    public function setTagEditedByAll()
    {
        $this->_addExtra('TAGEDITEDBY', 1);
    }

    public function setWithTags()
    {
        $this->_addExtra('WITHTAGS', 2);
    }

    public function setWithoutTags()
    {
        $this->_addExtra('WITHTAGS', 1);
    }

    public function withTags()
    {
        $retour = false;
        if ($this->_issetExtra('WITHTAGS')) {
            $re = $this->_getExtra('WITHTAGS');
            if (2 == $re) {
                $retour = true;
            }
        } else {
            if ($this->_environment->inPrivateRoom()) {
                $retour = true;
            }

            if ($this instanceof \cs_privateroom_item) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setTagsShowExpanded()
    {
        $this->_addExtra('TAGSSHOWEXPANDED', 1);
    }

    public function unsetTagsShowExpanded()
    {
        $this->_addExtra('TAGSSHOWEXPANDED', 0);
    }

    public function isTagsShowExpanded()
    {
        $retour = true;
        if ($this->_issetExtra('TAGSSHOWEXPANDED')) {
            $value = $this->_getExtra('TAGSSHOWEXPANDED');
            if (0 == $value) {
                $retour = false;
            }
        }

        return $retour;
    }

    // ########################################
    // rubrics
    // ########################################

    /** returns a boolean, if the project HomeConf support rubric = true
     * else false.
     */
    public function withRubric($rubric_type)
    {
        if (!isset($this->_rubric_support[$rubric_type])) {
            $current_room_modules = $this->getHomeConf();
            // rubric is mentioned? if not -> false
            if (!empty($rubric_type) and mb_stristr($current_room_modules, $rubric_type)) {
                $this->_rubric_support[$rubric_type] = true;
            } else {
                $this->_rubric_support[$rubric_type] = false;
            }
        }

        return $this->_rubric_support[$rubric_type];
    }

    public function getAvailableRubrics()
    {
        $current_room_modules = $this->getHomeConf();
        if (!empty($current_room_modules)) {
            $tokens = explode(',', $current_room_modules);
            $pointer = 0;
            foreach ($tokens as $module) {
                [$rubric, $view] = explode('_', $module);
                if ('contact' == $rubric) {
                    $rubric = 'user';
                }
                if ($this->withRubric($rubric)) {
                    $this->_current_rubrics_array[$pointer++] = Module2Type($rubric);
                }
                $this->_current_home_conf_array[Module2Type($rubric)] = $view;
            }
        }

        return $this->_current_rubrics_array;
    }

    public function getAvailableDefaultRubricArray(): array
    {
        return $this->_default_rubrics_array;
    }

     public function isRSSOn(): bool
     {
         $value = $this->getRSSStatus();
         if (!empty($value) && -1 == $value) {
             return false;
         }

         return true;
     }

     public function getRSSStatus()
     {
         if ($this->_issetExtra('RSS_STATUS')) {
             return $this->_getExtra('RSS_STATUS');
         }

         return '';
     }

     public function _setRSSStatus($value)
     {
         $this->_addExtra('RSS_STATUS', $value);
     }

     public function turnRSSOn()
     {
         $this->_setRSSStatus(1);
     }

     public function turnRSSOff()
     {
         $this->_setRSSStatus(-1);
     }

    // #########################################
    // ads
    // #########################################

    /** with ads ?
     * true = ads are possible
     * false = ads are not possible, default.
     *
     * server always true
     *
     * @return bool
     */
    public function withAds()
    {
        $retour = false;
        if ($this->isServer()) {
            $retour = true;
        } else {
            $value = $this->_getExtraConfig('ADS');
            if (1 == $value) {
                $retour = true;
            }
        }

        return $retour;
    }

    // ############## BEGIN ####################
    // activity points
    // #########################################

    /** get title of a context
     * this method returns the title of the context.
     *
     * @return string title of a context
     */
    public function getActivityPoints()
    {
        return $this->_getValue('activity');
    }

    /** set title of a context
     * this method sets the title of the context.
     *
     * @param string value title of the context
     */
    public function setActivityPoints($value)
    {
        $this->_setValue('activity', $value, true);
    }

      public function saveActivityPoints($points)
      {
          $this->setActivityPoints($points + $this->getActivityPoints());
          if ($this->isProjectRoom()) {
              $manager = $this->_environment->getProjectManager();
          } elseif ($this->isGroupRoom()) {
              $manager = $this->_environment->getGrouproomManager();
          } elseif ($this->isUserroom()) {
              $manager = $this->_environment->getUserRoomManager();
          } elseif ($this->isCommunityRoom()) {
              $manager = $this->_environment->getCommunityManager();
          } elseif ($this->isPortal()) {
              $manager = $this->_environment->getPortalManager();
          } elseif ($this->isServer()) {
              $manager = $this->_environment->getServerManager();
          }
          if (isset($manager)) {
              $manager->saveActivityPoints($this);
          }
      }

    // #########################################
    // activity points
    // ################ END ####################

    // ############## BEGIN ####################
    // status of the room
    // #########################################

    /** get last status
     * this method returns the last status before blocking the room.
     *
     * @return int the status of the room before it was blocked
     */
    public function getLastStatus()
    {
        $retour = false;
        if ($this->_issetExtra('LASTSTATUS')) {
            $retour = $this->_getExtra('LASTSTATUS');
        }

        return $retour;
    }

    /** set last status
     * this method sets the last status.
     *
     * @param int value status of the room
     */
    public function setLastStatus($value)
    {
        $this->_addExtra('LASTSTATUS', (int) $value);
    }

    /** set status of a room
     * this method returns the status of the room.
     *
     * @param int value status of a room
     */
    public function setStatus($value)
    {
        $this->_setValue('status', (int) $value, true);
    }

    /** get status of a room
     * this method returns the status of the room.
     *
     * @return int status of a room
     */
    public function getStatus()
    {
        return $this->_getValue('status');
    }

    /** open the room for usage
     * this method sets the status of the room to open.
     */
    public function open()
    {
        $this->_data['status'] = CS_ROOM_OPEN;
    }

    /** close a room
     * this method sets the status of the room to closed.
     */
    public function close()
    {
        $this->_data['status'] = CS_ROOM_CLOSED;
    }

    /** lock a room
     * this method sets the status of the room to locked.
     */
    public function lock()
    {
        $this->setLastStatus($this->getStatus());
        $this->_data['status'] = CS_ROOM_LOCK;
    }

    /** lock a room
     * this method sets the status of the room to locked.
     */
    public function unlock()
    {
        $temp = $this->getLastStatus();
        $this->setLastStatus($this->getStatus());
        $this->_data['status'] = $temp;
    }

    /** is room a normal open ?
     * this method returns a boolean explaining if a room is open.
     *
     * @return bool true, if a room is open
     *              false, if a room is not open
     */
    public function isOpen()
    {
        $retour = false;
        if (!empty($this->_data['status'])
                and CS_ROOM_OPEN == $this->_data['status']
        ) {
            $retour = true;
        }

        return $retour;
    }

    /** is a room closed ?
     * this method returns a boolean explaining if a room is closed or not.
     *
     * @return bool true, if a room is closed
     *              false, if a room is not closed
     */
    public function isClosed()
    {
        $retour = false;
        if (!empty($this->_data['status'])
                and CS_ROOM_CLOSED == $this->_data['status']
        ) {
            $retour = true;
        }

        return $retour;
    }

    /** is a room locked?
     * this method returns a boolean explaining if a room is locked.
     *
     * @return bool true, if a room is locked
     *              false, if a room is not locked
     */
    public function isLocked()
    {
        $retour = false;
        if (!empty($this->_data['status'])
                and CS_ROOM_LOCK == $this->_data['status']
        ) {
            $retour = true;
        }

        return $retour;
    }

    public function lockForMoveWithLinkedRooms()
    {
        $this->_addExtra('MOVE', '2');
    }

    public function lockForMove()
    {
        $this->_addExtra('MOVE', '1');
    }

    public function moveWithLinkedRooms()
    {
        $retour = false;
        if ($this->_issetExtra('MOVE')) {
            if (2 == $this->_getExtra('MOVE')) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function unlockForMove()
    {
        $this->_unsetExtra('MOVE');
    }

    /** is a room locked for movement between portals?
     * this method returns a boolean explaining if a room is locked for movement between portals.
     *
     * @return bool true, if a room is locked
     *              false, if a room is not locked
     */
    public function isLockedForMove()
    {
        $retour = false;
        if ($this->_issetExtra('MOVE')) {
            if (1 == $this->_getExtra('MOVE') or 2 == $this->_getExtra('MOVE')) {
                $retour = true;
            }
        }

        return $retour;
    }

    // #########################################
    // status of the room
    // ################ END ####################

    /** save context
     * this method save the context.
     */
    public function save()
    {
        $manager = $this->_environment->getManager($this->_type);
        $this->_save($manager);
        $this->_changes = [];
    }

    public function saveWithoutChangingModificationInformation()
    {
        $manager = $this->_environment->getManager($this->_type);
        $manager->saveWithoutChangingModificationInformation();
        $this->_save($manager);
        $this->_changes = [];
    }

    public function mayEdit(cs_user_item $user)
    {
        $value = false;
        if (!empty($user)) {
            if (!$user->isOnlyReadUser()) {
                if ($user->isRoot()
                        or ($user->isUser()
                                and ($user->getItemID() == $this->getCreatorID()
                                        or $this->isPublic()
                                        or $this->isModeratorByUserID($user->getUserID(), $user->getAuthSource())
                                        or ($this->_environment->inCommunityRoom()
                                                and $this->isProjectRoom()
                                                and $user->isModerator()
                                        )
                                )
                        )
                ) {
                    $value = true;
                }
            }
        }

        return $value;
    }

    public function mayEditRegular($user)
    {
        $value = false;
        if (!empty($user)) {
            if (!$user->isOnlyReadUser()) {
                if ($user->isUser()
                        and ($user->getItemID() == $this->getCreatorID()
                                or $this->isPublic()
                                or $this->isModeratorByUserID($user->getUserID(), $user->getAuthSource())
                        )
                ) {
                    $value = true;
                }
            }
        }

        return $value;
    }

    public function isModeratorByUserID($user_id, $auth_source)
    {
        $retour = false;
        $mod_list = $this->getModeratorList();
        if ($mod_list->isNotEmpty()) {
            $mod = $mod_list->getFirst();
            while ($mod) {
                if ($mod->getUserID() == $user_id and $mod->getAuthSource() == $auth_source) {
                    $retour = true;
                    break;
                }
                $mod = $mod_list->getNext();
            }
        }

        return $retour;
    }

    public function isLastModeratorByUserID($user_id, $auth_source)
    {
        $retour = false;
        $mod_list = $this->getModeratorList();
        if (1 == $mod_list->getCount()) {
            $mod = $mod_list->getFirst();
            if ($mod->getUserID() == $user_id
                    and $mod->getAuthSource() == $auth_source
            ) {
                $retour = true;
            }
        }

        return $retour;
    }

    /** get users of the context
     * this method returns a list of users of the context.
     *
     * @return cs_list a list of user (cs_user_item)
     */
    public function getUserList(): cs_list
    {
        if ($this->userList->isEmpty()) {
            $userManager = $this->_environment->getUserManager();
            $userManager->resetLimits();
            $userManager->setContextLimit($this->getItemID());
            $userManager->setUserLimit();
            $userManager->select();
            $this->userList = $userManager->get();
        }

        return $this->userList;
    }

    public function resetUserList()
    {
        $userManager = $this->_environment->getUserManager();
        $userManager->setCacheOff();
        $this->userList->reset();
    }

    public function isUser($user)
    {
        $user_manager = $this->_environment->getUserManager();
        return $user_manager->isUserInContext($user->getUserID(), $this->getItemID(), $user->getAuthSource());
    }

    public function getUserByUserID($user_id, $auth_source)
    {
        $retour = null;
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($this->getItemID());
        $user_manager->setUserIDLimit($user_id);
        $user_manager->setAuthSourceLimit($auth_source);
        $user_manager->select();
        $user_list = $user_manager->get();
        if ($user_list->isNotEmpty() and 1 == $user_list->getCount()) {
            $retour = $user_list->getFirst();
        }

        return $retour;
    }

    /** asks if item is editable by everybody or just creator.
     *
     * @param value
     */
    public function isPublic()
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
     *
     * @author CommSy Development Group
     */
    public function setPublic($value)
    {
        $this->_setValue('public', $value);
    }

    /** sets if announcement is editable by everybody or just creator.
     *
     * @param value
     *
     * @author CommSy Development Group
     */
    public function getPublic()
    {
        return $this->_getValue('public');
    }

    // #####################################################################
    // statistic functions
    // #####################################################################

    public function getCountItems($start, $end)
    {
        if (!isset($this->countItems)) {
            $manager = $this->_environment->getItemManager();
            $manager->resetLimits();
            $manager->setContextLimit($this->getItemID());
            $this->countItems = (int) $manager->getCountItems($start, $end);
        }
        return $this->countItems;
    }

    public function getCountProjects($start, $end)
    {
        if (!isset($this->_count_projects)) {
            $manager = $this->_environment->getProjectManager();
            $manager->resetLimits();
            if ($this->isCommunityRoom()) {
                $manager->setContextLimit($this->getContextID());
                global $c_cache_cr_pr;
                if (!isset($c_cache_cr_pr) or !$c_cache_cr_pr) {
                    $manager->setCommunityRoomLimit($this->getItemID());
                } else {
                    /*
                     * use redundant infos in community room
                     */
                    $manager->setIDArrayLimit($this->getInternalProjectIDArray());
                }
            } else {
                $manager->setContextLimit($this->getItemID());
            }
            $this->_count_projects = $manager->getCountProjects($start, $end);
        }
        $retour = $this->_count_projects;

        return $retour;
    }

    /** get time spread for items on home
     * this method returns the time spread for items on the home of the context.
     *
     * @return int the time spread
     *
     * @author CommSy Development Group
     */
    public function getTimeSpread()
    {
        $retour = '30';
        if ($this->_issetExtra('TIMESPREAD')) {
            $retour = $this->_getExtra('TIMESPREAD');
        }

        return $retour;
    }

    /** set page impression array.
     *
     * @param array value page impression
     */
    public function setPageImpressionArray($value)
    {
        // only save for 365 days
        if (is_array($value)) {
            while (count($value) > 365) {
                array_pop($value);
            }
        }
        $this->_addExtra('PAGE_IMPRESSION', (array) $value);
    }

    /** get page impression array.
     */
    public function getPageImpressionArray()
    {
        $retour = $this->_getExtra('PAGE_IMPRESSION');
        if (empty($retour)) {
            $retour = [];
        }

        return $retour;
    }

    /*
      * set user activity array
    */
    public function setUserActivityArray($value)
    {
        if (is_array($value)) {
            while (count($value) > 365) {
                array_pop($value);
            }
        }
        $this->_addExtra('USER_ACTIVITY', (array) $value);
    }

    /*
      * get user activity array
    */
    public function getUserActivityArray()
    {
        $retour = $this->_getExtra('USER_ACTIVITY');
        if (empty($retour)) {
            $retour = [];
        }

        return $retour;
    }

    public function getPageImpressions($external_timespread = 0, $db_page_impressions = 0)
    {
        $retour = 0;
        if (isset($this->_page_impression_array[$external_timespread])) {
            $retour = $this->_page_impression_array[$external_timespread];
        } else {
            if (0 != $external_timespread) {
                $timespread = $external_timespread;
            } else {
                $timespread = $this->getTimeSpread();
            }
            $count = 0;
            $pi_array = $this->getPageImpressionArray();
            for ($i = 0; $i < $timespread; ++$i) {
                if (!empty($pi_array[$i])) {
                    $count = $count + $pi_array[$i];
                }
            }
            if (0 == $db_page_impressions) {
                $log_manager = $this->_environment->getLogManager();
                $log_manager->resetLimits();
                $log_manager->setContextLimit($this->getItemID());
                $page_impressions = $log_manager->getCountAll();
                unset($log_manager);
            } else {
                $page_impressions = $db_page_impressions;
            }
            $this->_page_impression_array[$external_timespread] = $count + $page_impressions;
            $retour = $this->_page_impression_array[$external_timespread];
        }

        return $retour;
    }

    public function isActiveDuringLast99Days()
    {
        return $this->getPageImpressions() > 0;
    }

    public function getNewEntries($external_timespread = 0)
    {
        if (0 != $external_timespread) {
            $timespread = $external_timespread;
        } else {
            $timespread = $this->getTimeSpread();
        }
        $conf = $this->getHomeConf();
        $rubrics = [];
        if (!empty($conf)) {
            $rubrics = explode(',', $conf);
        }
        $check_managers = [];
        foreach ($rubrics as $rubric) {
            [$rubric_name, $rubric_status] = explode('_', $rubric);
            if ('none' != $rubric_status) {
                $check_managers[] = $rubric_name;
                if (CS_DISCUSSION_TYPE == $rubric_name) {
                    $check_managers[] = 'discarticle';
                }
                if (CS_MATERIAL_TYPE == $rubric_name) {
                    $check_managers[] = CS_SECTION_TYPE;
                }
            }
        }
        $check_managers[] = CS_ANNOTATION_TYPE;
        $item_manager = $this->_environment->getItemManager();
        $item_manager->setContextLimit($this->getItemID());
        $item_manager->setExistenceLimit($timespread);
        $item_manager->setInactiveEntriesLimit(\cs_manager::SHOW_ENTRIES_ONLY_ACTIVATED);
        $item_manager->setTypeArrayLimit($check_managers);
        $item_manager->resetData();
        $new_entries = $item_manager->getIDArray();
        $count_total = $new_entries ? count($new_entries) : 0;
        unset($item_manager);

        return $count_total;
    }

    public function getActiveMembers($external_timespread = 0)
    {
        if (0 != $external_timespread) {
            $timespread = $external_timespread;
        } else {
            $timespread = $this->getTimeSpread();
        }
        $user_manager = $this->_environment->getUserManager();
        $user_manager->reset();
        $user_manager->setContextLimit($this->getItemID());
        $user_manager->setUserLimit();
        $user_manager->setLastLoginLimit($timespread);
        $ids = $user_manager->getIDArray();
        $active = !empty($ids) ? count($ids) : 0;
        unset($user_manager);

        return $active;
    }

    public function getActiveMembersForNewsletter($external_timespread = 0)
    {
        // take it from UserActivity extras field
        $retour = 0;
        if (isset($this->_user_activity_array[$external_timespread])) {
            $retour = $this->_user_activity_array[$external_timespread];
        } else {
            if (0 != $external_timespread) {
                $timespread = $external_timespread;
            } else {
                $timespread = $this->getTimeSpread();
            }

            $count = 0;
            $ua_array = $this->getUserActivityArray();

            for ($i = 0; $i < $timespread; ++$i) {
                if (!empty($ua_array[$i])) {
                    $count += $ua_array[$i];
                }
            }
            $retour = $count;
        }

        return $retour;
    }

    public function getPageImpressionsForNewsletter($external_timespread = 0)
    {
        $retour = 0;
        if (isset($this->_page_impression_array[$external_timespread])) {
            $retour = $this->_page_impression_array[$external_timespread];
        } else {
            if (0 != $external_timespread) {
                $timespread = $external_timespread;
            } else {
                $timespread = $this->getTimeSpread();
            }
            $count = 0;
            $pi_array = $this->getPageImpressionArray();

            for ($i = 0; $i < $timespread; ++$i) {
                if (!empty($pi_array[$i])) {
                    $count += $pi_array[$i];
                }
            }
            $retour = $count;
        }

        return $retour;
    }

    public function getAllUsers()
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->reset();
        $user_manager->setContextLimit($this->getItemID());
        $user_manager->setUserLimit();

        return $user_manager->getCountAll();
    }

    public function delete()
    {
    }

    public function getPageImpressionAndUserActivityLast()
    {
        $retour = $this->_getExtra('PIUA_LAST');
        if (empty($retour)) {
            $retour = '';
        }

        return $retour;
    }

    public function setPageImpressionAndUserActivityLast($value)
    {
        $this->_addExtra('PIUA_LAST', $value);
    }

    // #################################
    // Workflow
    // #################################

    public function withWorkflowFunctions()
    {
        $retour = false;
        $value = $this->_getExtraConfig('WORKFLOW');
        if (1 == $value) {
            $retour = true;
        } elseif ($this->isProjectRoom()
        or $this->isCommunityRoom()
        or $this->isGroupRoom()
        or $this->isPrivateRoom()
        ) {
            $portal_room = $this->getContextItem();
            if ($portal_room->withWorkflowFunctions()) {
                $retour = true;
            }
        }

        return $retour;
    }

    public function setWithWorkflowFunctions()
    {
        $this->_setExtraConfig('WORKFLOW', 1);
    }

    public function setWithoutWorkflowFunctions()
    {
        $this->_setExtraConfig('WORKFLOW', 0);
    }

    public function setHideAccountname()
    {
        $this->_setExtraConfig('HIDE_ACCOUNTNAME', '1');
    }

    public function unsetHideAccountname()
    {
        $this->_setExtraConfig('HIDE_ACCOUNTNAME', '2');
    }

    public function getHideAccountname()
    {
        $retour = false;
        $value = $this->_getExtraConfig('HIDE_ACCOUNTNAME');
        if (2 == $value) {
            $retour = false;
        } elseif (1 == $value) {
            $retour = true;
        }

        return $retour;
    }

    public function getDefaultCalendarId()
    {
        global $symfonyContainer;
        $calendarsService = $symfonyContainer->get('commsy.calendars_service');
        if (!isset($calendarsService->getDefaultCalendar($this->getItemId())[0])) {
            $calendarsService->createCalendar($this, null, null, true);
        }

        return $calendarsService->getDefaultCalendar($this->getItemId())[0]->getId();
    }

      public function setUsersCanEditCalendars()
      {
          $this->_addExtra('USERSCANEDITCALENDARS', 1);
      }

      public function unsetUsersCanEditCalendars()
      {
          $this->_addExtra('USERSCANEDITCALENDARS', 0);
      }

      public function usersCanEditCalendars()
      {
          $retour = false;
          if ($this->_issetExtra('USERSCANEDITCALENDARS')) {
              $re = $this->_getExtra('USERSCANEDITCALENDARS');
              if (1 == $re) {
                  $retour = true;
              }
          }

          return $retour;
      }

      public function setUsersCanSetExternalCalendarsUrl()
      {
          $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL', 1);
      }

      public function unsetUsersCanSetExternalCalendarsUrl()
      {
          $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL', 0);
      }

      public function usersCanSetExternalCalendarsUrl()
      {
          $retour = false;
          if ($this->_issetExtra('USERSCANSETEXTERNALCALENDARSURL')) {
              $re = $this->_getExtra('USERSCANSETEXTERNALCALENDARSURL');
              if (1 == $re) {
                  $retour = true;
              }
          }

          return $retour;
      }
}
