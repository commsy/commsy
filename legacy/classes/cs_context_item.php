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

use App\Repository\LogRepository;

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

    private array $cachePageImpressions = [];

    /** constructor: cs_context_item
     * the only available constructor, initial values for internal variables.
     *
     * @param object environment the environment of the commsy
     */
    public function __construct($environment)
    {
        parent::__construct($environment);
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

    public function isOpenForGuests(): bool
    {
        if (1 == $this->_getValue('is_open_for_guests')) {
            return true;
        } else {
            return false;
        }
    }

    public function setOpenForGuests(): void
    {
        $this->_setValue('is_open_for_guests', 1);
    }

    public function setClosedForGuests(): void
    {
        $this->_setValue('is_open_for_guests', 0);
    }

    public function isMaterialOpenForGuests(): bool
    {
        if ($this->_issetExtra('MATERIAL_GUESTS') and 1 == $this->_getExtra('MATERIAL_GUESTS')) {
            return true;
        } else {
            return false;
        }
    }

    public function setMaterialOpenForGuests(): void
    {
        $this->_addExtra('MATERIAL_GUESTS', 1);
    }

    public function setMaterialClosedForGuests(): void
    {
        $this->_addExtra('MATERIAL_GUESTS', 0);
    }

    public function isAssignmentOnlyOpenForRoomMembers(): bool
    {
        return $this->_issetExtra('ROOMASSOCIATION') && $this->_getExtra('ROOMASSOCIATION') === 'onlymembers';
    }

    public function setAssignmentOpenForAnybody(): void
    {
        $this->_addExtra('ROOMASSOCIATION', 'forall');
    }

    public function setAssignmentOnlyOpenForRoomMembers(): void
    {
        $this->_addExtra('ROOMASSOCIATION', 'onlymembers');
    }

    public function isCommunityRoom(): bool
    {
        return false;
    }

    public function isPrivateRoom(): bool
    {
        return false;
    }

    public function isGroupRoom(): bool
    {
        return false;
    }

    public function isUserroom(): bool
    {
        return false;
    }

    public function isProjectRoom(): bool
    {
        return false;
    }

    public function isPortal(): bool
    {
        return false;
    }

    public function isServer(): bool
    {
        return false;
    }

    /** Checks and sets the data of the item.
     *
     * @param $data_array
     */
    public function _setItemData($data_array): void
    {
        // not yet implemented
        $this->_data = $data_array;
    }

    /** get title of a context
     * this method returns the title of the context.
     */
    public function getTitle(): string
    {
        return $this->_getValue('title');
    }

    /** set title of a context
     * this method sets the title of the context.
     */
    public function setTitle(string $value): void
    {
        // sanitize title
        $converter = $this->_environment->getTextConverter();
        $value = htmlentities($value);
        $value = $converter->sanitizeHTML($value);
        $this->_setValue('title', $value, true);
    }

    /** get room type of a context
     * this method returns the room type of the context.
     */
    public function getRoomType(): string
    {
        return $this->_getValue('type');
    }

    /** set room type of a context
     * this method sets the room type of the context.
     */
    public function setRoomType(string $value): void
    {
        $this->_setValue('type', $value, true);
    }

    /** det description array.
     *
     * @return array description text in different languages
     */
    public function getDescriptionArray(): array
    {
        $retour = [];
        if ($this->_issetExtra('DESCRIPTION')) {
            $retour = $this->_getExtra('DESCRIPTION');
        }

        return $retour;
    }

    public function getMaxUploadSizeInBytes(): int
    {
        $val = ini_get('upload_max_filesize');
        $val = trim($val);

        $last = $val[mb_strlen($val) - 1];
        $numericVal = (int) substr($val, 0, -1);
        return match ($last) {
            'k', 'K' => $numericVal * 1024,
            'm', 'M' => $numericVal * 1_048_576,
            default => $numericVal,
        };
    }

    public function setNotShownInPrivateRoomHome($user_id): void
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        $this->_addExtra('IS_SHOW_ON_HOME_'.$tag, 'NO');
    }

    public function setShownInPrivateRoomHome($user_id): void
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        $this->_unsetExtra('IS_SHOW_ON_HOME_'.$tag);
    }

    /** get shown option.
     *
     * @return bool if room is shown on home
     */
    public function isShownInPrivateRoomHome($user_id): bool
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $tag = $current_user->getItemID();
        if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$tag)) {
            if ('NO' == $this->_getExtra('IS_SHOW_ON_HOME_'.$tag)) {
                return false;
            }
        }

        return true;
    }

    /** get shown option.
     *
     * @return bool if room is shown on home
     */
    public function isShownInPrivateRoomHomeByItemID($item_id): bool
    {
        if ($this->_issetExtra('IS_SHOW_ON_HOME_'.$item_id)) {
            if ('NO' == $this->_getExtra('IS_SHOW_ON_HOME_'.$item_id)) {
                return false;
            }
        }

        return true;
    }

    /** set description array.
     *
     * @param array $value description text in different languages
     */
    public function setDescriptionArray(array $value): void
    {
        $this->_addExtra('DESCRIPTION', $value);
    }

    /** get contact moderators of a room
     * this method returns a list of contact moderators which are linked to the room.
     *
     * @return cs_list a list of contact moderators (cs_label_item)
     */
    public function getContactModeratorList(): cs_list
    {
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setContextLimit($this->getItemID());
        $user_manager->setContactModeratorLimit();
        $user_manager->select();
        $contactModeratorList = $user_manager->get();

        return $contactModeratorList ?? new cs_list();
    }

    public function getContactModeratorListString(): string
    {
        $list = $this->getContactModeratorList();

        return implode(', ', array_map(fn ($contact): string => $contact->getFullname(), $list->to_array()));
    }

    /** get description of a context
     * this method returns the description of the context.
     *
     * @return string description of a context
     */
    public function getDescriptionByLanguage($language): string
    {
        $desc_array = $this->getDescriptionArray();
        if (!empty($desc_array[cs_strtoupper($language)])) {
            return $desc_array[cs_strtoupper($language)];
        }

        return '';
    }

    public function getDescription()
    {
        $retour = $this->getDescriptionByLanguage($this->_environment->getSelectedLanguage());
        if (empty($retour)) {
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
     */
    public function getLanguage(): string
    {
        if ($this->_issetExtra('LANGUAGE')) {
            return $this->_getExtra('LANGUAGE');
        }

        return 'user';
    }

    /** set language
     * this method sets the language.
     *
     * @param string value language
     */
    public function setLanguage($value): void
    {
        $this->_addExtra('LANGUAGE', (string) $value);
    }

    /** set description of a context
     * this method sets the description of the context.
     *
     * @param string value description of the context
     * @param string value lanugage of the description
     */
    public function setDescriptionByLanguage($value, $language): void
    {
        $desc_array = $this->getDescriptionArray();
        $desc_array[mb_strtoupper((string) $language, 'UTF-8')] = $value;
        $this->setDescriptionArray($desc_array);
    }

    /** get agb text.
     *
     * @return array agb text in different languages
     */
    public function getAGBTextArray(): array
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
    public function setAGBTextArray($value): void
    {
        $this->_addExtra('AGBTEXTARRAY', (array) $value);
    }

    /** get agb status.
     *
     * @return int agb status 1 = yes, 2 = no
     */
    public function getAGBStatus(): int
    {
        $retour = '2';
        if ($this->_issetExtra('AGBSTATUS')) {
            $retour = $this->_getExtra('AGBSTATUS');
        }

        return intval($retour);
    }

    /** set agb status.
     *
     * @param int $value agb status
     */
    public function setAGBStatus($value): void
    {
        $this->_addExtra('AGBSTATUS', (int) $value);
    }

    // @return boolean true = with AGB, false = without AGB
    public function withAGB(): bool
    {
        return $this->getAGBStatus() == 1;
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

    public function withAssociations(): bool
    {
        if ($this->_issetExtra('WITHASSOCIATIONS')) {
            $re = $this->_getExtra('WITHASSOCIATIONS');
            if (2 == $re) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    public function setWithBuzzwords(): void
    {
        $this->_addExtra('WITHBUZZWORDS', 2);
    }

    public function setWithoutBuzzwords(): void
    {
        $this->_addExtra('WITHBUZZWORDS', 1);
    }

    public function withBuzzwords(): bool
    {
        if ($this->_issetExtra('WITHBUZZWORDS')) {
            $re = $this->_getExtra('WITHBUZZWORDS');
            if (2 == $re) {
                return true;
            }
        } else {
            return true;
        }

        return false;
    }

    public function isBuzzwordMandatory(): bool
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

    public function setBuzzwordMandatory(): void
    {
        $this->_addExtra('BUZZWORDMANDATORY', 1);
    }

    public function unsetBuzzwordMandatory(): void
    {
        $this->_addExtra('BUZZWORDMANDATORY', 0);
    }

    public function isAssociationShowExpanded(): bool
    {
        if ($this->_issetExtra('ASSOCIATIONSHOWEXPANDED')) {
            $value = $this->_getExtra('ASSOCIATIONSHOWEXPANDED');
            if (1 == $value) {
                return true;
            }
        }

        return false;
    }

    public function setAssociationShowExpanded(): void
    {
        $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 1);
    }

    public function unsetAssociationShowExpanded(): void
    {
        $this->_addExtra('ASSOCIATIONSHOWEXPANDED', 0);
    }

    public function isBuzzwordShowExpanded(): bool
    {
        if ($this->_issetExtra('BUZZWORDSHOWEXPANDED')) {
            $value = $this->_getExtra('BUZZWORDSHOWEXPANDED');
            if (0 == $value) {
                return false;
            }
        }

        return true;
    }

    public function setBuzzwordShowExpanded(): void
    {
        $this->_addExtra('BUZZWORDSHOWEXPANDED', 1);
    }

    public function unsetBuzzwordShowExpanded(): void
    {
        $this->_addExtra('BUZZWORDSHOWEXPANDED', 0);
    }

    public function setWithWorkflow(): void
    {
        $this->_addExtra('WITHWORKFLOW', 2);
    }

    public function setWithoutWorkflow(): void
    {
        $this->_addExtra('WITHWORKFLOW', 1);
    }

    public function withWorkflow(): bool
    {
        if ($this->_issetExtra('WITHWORKFLOW')) {
            $re = $this->_getExtra('WITHWORKFLOW');
            if (2 == $re) {
                return true;
            }
        }

        return false;
    }

    public function setWithWorkflowTrafficLight(): void
    {
        $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT', 2);
    }

    public function setWithoutWorkflowTrafficLight(): void
    {
        $this->_addExtra('WITHWORKFLOWTRAFFICLIGHT', 1);
    }

    public function withWorkflowTrafficLight(): bool
    {
        if ($this->_issetExtra('WITHWORKFLOWTRAFFICLIGHT')) {
            $re = $this->_getExtra('WITHWORKFLOWTRAFFICLIGHT');
            if (2 == $re) {
                return true;
            }
        }

        return false;
    }

    public function setWorkflowTrafficLightDefault($value): void
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

    public function setWorkflowTrafficLightTextGreen($value): void
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

    public function setWorkflowTrafficLightTextYellow($value): void
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

    public function setWorkflowTrafficLightTextRed($value): void
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

    public function setWithWorkflowResubmission(): void
    {
        $this->_addExtra('WITHWORKFLOWRESUBMISSION', 2);
    }

    public function setWithoutWorkflowResubmission(): void
    {
        $this->_addExtra('WITHWORKFLOWRESUBMISSION', 1);
    }

    public function withWorkflowResubmission(): bool
    {
        if ($this->_issetExtra('WITHWORKFLOWRESUBMISSION')) {
            $re = $this->_getExtra('WITHWORKFLOWRESUBMISSION');
            if (2 == $re) {
                return true;
            }
        }

        return false;
    }

    public function setWithWorkflowReader(): void
    {
        $this->_addExtra('WITHWORKFLOWREADER', 2);
    }

    public function setWithoutWorkflowReader(): void
    {
        $this->_addExtra('WITHWORKFLOWREADER', 1);
    }

    public function withWorkflowReader(): bool
    {
        if ($this->_issetExtra('WITHWORKFLOWREADER')) {
            $re = $this->_getExtra('WITHWORKFLOWREADER');
            if (2 == $re) {
                return true;
            }
        }

        return false;
    }

    public function setWithWorkflowReaderGroup(): void
    {
        $this->_addExtra('WORKFLOWREADERGROUP', '1');
    }

    public function setWithoutWorkflowReaderGroup(): void
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

    public function setWithWorkflowReaderPerson(): void
    {
        $this->_addExtra('WORKFLOWREADERPERSON', '1');
    }

    public function setWithoutWorkflowReaderPerson(): void
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

    public function setWorkflowReaderShowTo($value): void
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

    public function setWithWorkflowValidity(): void
    {
        $this->_addExtra('WITHWORKFLOWVALIDITY', 2);
    }

    public function setWithoutWorkflowValidity(): void
    {
        $this->_addExtra('WITHWORKFLOWVALIDITY', 1);
    }

    public function withWorkflowValidity(): bool
    {
        if ($this->_issetExtra('WITHWORKFLOWVALIDITY')) {
            $re = $this->_getExtra('WITHWORKFLOWVALIDITY');
            if (2 == $re) {
                return true;
            }
        }

        return false;
    }

    /** get htmltextarea status.
     *
     * @return int discussion status 1 = simple, 2 = threaded,  3 = both
     */
    public function getDiscussionStatus(): int
    {
        $retour = 1;
        if ($this->_issetExtra('DISCUSSIONSTATUS')) {
            $retour = $this->_getExtra('DISCUSSIONSTATUS');
        }

        return intval($retour);
    }

    /** set agb status.
     *
     * @param array value discussion status
     */
    public function setDiscussionStatus($value): void
    {
        $this->_addExtra('DISCUSSIONSTATUS', (int) $value);
    }

    /** get htmltextarea status.
     *
     * @return int htmltextarea status 1 = yes, 2 = yes, but minimum, 3 = no
     */
    public function getHtmlTextAreaStatus(): int
    {
        return 3;
    }

    /** set agb status.
     *
     * @param array value HTMLTextArea status
     */
    public function setHtmlTextAreaStatus($value): void
    {
        $this->_addExtra('HTMLTEXTAREASTATUS', (int) $value);
    }

    // @return boolean true = with HTMLTextArea, false = without HTMLTextArea
    public function withHtmlTextArea(): bool
    {
        return $this->getHtmlTextAreaStatus() != 3;
    }

    /** get dates status.
     *
     * @return string one mode of either normal, calendar or calendar_month
     */
    public function getDatesPresentationStatus(): string
    {
        $retour = 'normal';
        if ($this->_issetExtra('DATEPRESENTATIONSTATUS')) {
            $retour = $this->_getExtra('DATEPRESENTATIONSTATUS');
        }

        // new private room
        if ($this->isPrivateRoom() && 'normal' == $retour) {
            $retour = 'calendar_month';
        }

        return in_array($retour, ['normal', 'calendar', 'calendar_month']) ? $retour : 'normal';
    }

    public function setDatesPresentationStatus(string $value): void
    {
        $value = in_array($value, ['normal', 'calendar', 'calendar_month']) ? $value : 'normal';

        $this->_addExtra('DATEPRESENTATIONSTATUS', $value);
    }

    /** returns a boolean, if the the user can enter the context
     * true: user can enter project
     * false: user can not enter project.
     *
     * @param object user item this user wants to enter the project
     */
    public function mayEnter($user_item): bool
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

    public function isSystemLabel(): bool
    {
        if ($this->_issetExtra('SYSTEM_LABEL')) {
            $value = $this->_getExtra('SYSTEM_LABEL');
            if (1 == $value) {
                return true;
            }
        }

        return false;
    }

    public function mayEnterByUserItemID($user_item_id): bool
    {
        if ($this->isLocked()) {
            return false;
        } elseif (isset($this->_cache_may_enter[$user_item_id])) {
            return $this->_cache_may_enter[$user_item_id];
        } elseif ($this->isOpenForGuests()) {
            return true;
        } else {
            $user_manager = $this->_environment->getUserManager();
            $user_in_room = $user_manager->getItem($user_item_id);
            if ($user_in_room->isUser() && $user_in_room->getContextID() == $this->getItemID()
            ) {
                $this->_cache_may_enter[$user_item_id] = true;
                return true;
            } else {
                $this->_cache_may_enter[$user_item_id] = false;
            }
        }

        return false;
    }

    public function getColorArray(): array
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

    public function setColorArray($array): void
    {
        if (is_array($array)) {
            $this->_addExtra('COLOR', $array);
        }
    }

    /**
     * Returns true if new members will always be checked,
     * otherwise returns false.
     *
     * @return bool
     */
    public function checkNewMembersAlways(): bool
    {
        if ($this->checkNewMembersSometimes()
                or $this->checkNewMembersNever()
                or $this->checkNewMembersWithCode()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Returns true if new members will sometimes be checked,
     * otherwise returns false.
     *
     * @return bool
     */
    public function checkNewMembersSometimes(): bool
    {
        return $this->_getCheckNewMembers() == 2;
    }

    /**
     * Returns true if new members providing the correct access code
     * are allowed to enter instantly, otherwise returns false.
     *
     * @return bool
     */
    public function checkNewMembersWithCode(): bool
    {
        return $this->_getCheckNewMembers() == 3;
    }

    public function setCheckNewMemberCode($value): void
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

    /**
     * Returns true if new members will never be checked and
     * are allowed to enter instantly, otherwise returns false.
     *
     * @return bool
     */
    public function checkNewMembersNever(): bool
    {
        return $this->_getCheckNewMembers() == -1;
    }

    /** get flag for checking new members, INTERNAL -> use checkNewMember()
     * this method returns a flag for checking new members.
     *
     * @return int the flag: -1, new members can enter instantly
     *             1, moderator must activate new members
     *             2, moderator must activate new members,
     *             - room: if account is new
     *             - portal: if account with room membership
     *             3, new members can enter instantly if they provide the correct access code
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
    public function _setRoomAssociation($value): void
    {
        $this->_addExtra('ROOMASSOCIATION', $value);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function _setCheckNewMember($value): void
    {
        $this->_addExtra('CHECKNEWMEMBERS', $value);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberAlways(): void
    {
        $this->_setCheckNewMember(1);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberSometimes(): void
    {
        $this->_setCheckNewMember(2);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberWithCode(): void
    {
        $this->_setCheckNewMember(3);
    }

    /** set flag for check new members
     * this method sets the flag for checking new members.
     *
     * @param bool value flag for checking new members
     */
    public function setCheckNewMemberNever(): void
    {
        $this->_setCheckNewMember(-1);
    }

    /** get filename of logo.
     *
     * @return string filename of logo
     */
    public function getLogoFilename(): string
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
    public function setLogoFilename($value): void
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

    public function setEmailText($message_tag, $array): void
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

    public function setEmailTextArray($array): void
    {
        if (!empty($array)) {
            $this->_addExtra('MAIL_TEXT_ARRAY', $array);
        }
    }

    // ##################################################
    // rubric translation methods
    // ##################################################

    public function getRubricTranslationArray(): array
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
    public function setRubricArray($rubric, $array): void
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
    public function _getRubricArray($rubric): array
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
    public function _setShowTitle($value): void
    {
        $this->_addExtra('SHOWTITLE', (int) $value);
    }

    /** set show title.
     */
    public function setShowTitle(): void
    {
        $this->_setShowTitle(1);
    }

    /** set not show title.
     */
    public function setNotShowTitle(): void
    {
        $this->_setShowTitle(-1);
    }

    /** show title ?
     * true = show title, default
     * false = show title not.
     *
     * @return bool
     */
    public function showTitle(): bool
    {
        $show_int = $this->_getShowTitle();
        if (isset($show_int) and !empty($show_int)) {
            if (-1 == $show_int) {
                return false;
            }
        }

        return true;
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

    public function getHomeConf(): string
    {
        if (!$this->_issetExtra('HOMECONF')) {
            $this->setHomeConf($this->getDefaultHomeConf());
        }

        return $this->_getExtra('HOMECONF');
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

        $rubrics = array_map(fn ($rubric) => "{$rubric}_show", $rubrics);

        return implode(',', $rubrics);
    }

    public function setHomeConf(string $config): void
    {
        // validate
        $rubrics = empty($config) ? [] : explode(',', $config);
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
     * @return int 1 = true / 0 = false
     */
    public function _getExtraConfig(string $type)
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
    public function _setExtraConfig($type, $value): void
    {
        if ('whole' == $type) {
            $this->_addExtra('EXTRA_CONFIG', $value);
        } else {
            $extra_config_array = $this->_getExtraConfig('whole');
            $extra_config_array[mb_strtoupper((string) $type, 'UTF-8')] = $value;
            $this->_setExtraConfig('whole', $extra_config_array);
        }
    }

    public function getExtraConfig()
    {
        return $this->_getExtraConfig('whole');
    }

    public function setExtraConfig($value): void
    {
        $this->_setExtraConfig('whole', $value);
    }

    public function withLogArchive(): bool
    {
        $value = $this->_getExtraConfig('LOGARCHIVE');
        if (1 == $value) {
            return true;
        }

        return false;
    }

    public function setAssessmentActive(): void
    {
        $this->_addExtra('ASSESSMENT', 1);
    }

    public function setAssessmentInactive(): void
    {
        $this->_addExtra('ASSESSMENT', -1);
    }

    public function isAssessmentActive(): bool
    {
        if ($this->_issetExtra('ASSESSMENT')) {
            $active = $this->_getExtra('ASSESSMENT');
            if (1 == $active) {
                return true;
            }
        }

        return false;
    }

    public function withGrouproomFunctions(): bool
    {
        return true;
    }

    public function showGrouproomConfig(): bool
    {
        if ($this->withGrouproomFunctions()) {
            return true;
        } elseif ($this->isProjectRoom() || $this->isCommunityRoom()) {
            $portal = $this->getContextItem();
            return $portal->withGrouproomFunctions();
        }

        return false;
    }

    public function showGrouproomFunctions(): bool
    {
        return $this->showGrouproomConfig() && $this->isGrouproomActive();
    }

    /** is group room active ?
     * can be switched at room configuration.
     *
     * true = group room is active
     * false = group room is not active, default
     *
     * @return bool
     */
    public function isGrouproomActive(): bool
    {
        return true;
    }

    /** set activity of the group room, INTERNAL.
     *
     * @param int value: -1 = not
     *                        1 = yes
     */
    public function _setGrouproomActivity($value): void
    {
        $this->_addExtra('GROUPROOM', (int) $value);
    }

    /** set group room active.
     */
    public function setGrouproomActive(): void
    {
        $this->_setGrouproomActivity(1);
    }

    /** set group room inactive.
     */
    public function setGrouproomInactive(): void
    {
        $this->_setGrouproomActivity(-1);
    }

    public function showServiceLink(): bool
    {
        return $this->isServiceLinkActive();
    }

    /**
     *  set service email adress.
     */
    public function setServiceEmail($email): void
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
    public function setServiceLinkExternal($email): void
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
    public function isServiceLinkActive(): bool
    {
        if ($this->_issetExtra('SERVICELINK')) {
            $active = $this->_getExtra('SERVICELINK');
            if (1 == $active) {
                return true;
            }
        }

        return false;
    }

    /** set activity of the service link, INTERNAL.
     *
     * @param int value: -1 = not
     *                        1 = yes
     */
    public function _setServiceLinkActivity($value): void
    {
        if ($this->_issetExtra('SERVICELINK')) {
            $this->_setExtra('SERVICELINK', (int) $value);
        } else {
            $this->_addExtra('SERVICELINK', (int) $value);
        }
    }

    /** set service link active.
     */
    public function setServiceLinkActive(): void
    {
        $this->_setServiceLinkActivity(1);
    }

    /** set service link inactive.
     */
    public function setServiceLinkInactive(): void
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

    public function setExtraToDoStatusArray($array): void
    {
        if (!$this->_issetExtra('TODOEXTRASTATUSARRAY')) {
            $this->_addExtra('TODOEXTRASTATUSARRAY', $array);
        } else {
            $this->_setExtra('TODOEXTRASTATUSARRAY', $array);
        }
    }

    public function setTemplateAvailability($value): void
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

    public function setCommunityTemplateAvailability($value): void
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

    public function withPath(): bool
    {
        return true;
    }

    public function InformationBoxWithExistingObject(): bool
    {
        $id = $this->getInformationBoxEntryID();
        $manager = $this->_environment->getItemManager();
        $item = $manager->getItem($id);
        if (is_object($item) and !$item->isDeleted()) {
            $entry_manager = $this->_environment->getManager($item->getItemType());
            $entry = $entry_manager->getItem($id);
            if (is_object($entry) and !$entry->isDeleted()) {
                return true;
            }
        }

        return false;
    }

    public function withInformationBox(): bool
    {
        if ($this->_issetExtra('WITHINFORMATIONBOX')) {
            if ('yes' == $this->_getExtra('WITHINFORMATIONBOX') and $this->InformationBoxWithExistingObject()) {
                return true;
            }
        }

        return false;
    }

    public function setwithInformationBox($value): void
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

    public function setDefaultProjectTemplateID($value): void
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

    public function setDefaultCommunityTemplateID($value): void
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

    public function setTemplateDescription($value): void
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

    public function setInformationBoxEntryID($value): void
    {
        $this->_addExtra('INFORMATIONBOXENTRYID', (string) $value);
    }

    // #########################################
    // Tags
    // #########################################

    public function isTagMandatory(): bool
    {
        if ($this->_issetExtra('TAGMANDATORY')) {
            $value = $this->_getExtra('TAGMANDATORY');
            if (1 == $value) {
                return true;
            }
        }

        return false;
    }

    public function setTagMandatory(): void
    {
        $this->_addExtra('TAGMANDATORY', 1);
    }

    public function unsetTagMandatory(): void
    {
        $this->_addExtra('TAGMANDATORY', 0);
    }

    public function isTagEditedByAll(): bool
    {
        if ($this->_issetExtra('TAGEDITEDBY')) {
            $value = $this->_getExtra('TAGEDITEDBY');
            if (2 == $value) {
                return false;
            }
        }

        return true;
    }

    public function setBGImageFilename($name): void
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

    public function setTagEditedByModerator(): void
    {
        $this->_addExtra('TAGEDITEDBY', 2);
    }

    public function setTagEditedByAll(): void
    {
        $this->_addExtra('TAGEDITEDBY', 1);
    }

    public function setWithTags(): void
    {
        $this->_addExtra('WITHTAGS', 2);
    }

    public function setWithoutTags(): void
    {
        $this->_addExtra('WITHTAGS', 1);
    }

    public function withTags(): bool
    {
        if ($this->_issetExtra('WITHTAGS')) {
            $re = $this->_getExtra('WITHTAGS');
            if (2 == $re) {
                return true;
            }
        } else {
            if ($this->_environment->inPrivateRoom()) {
                return true;
            }

            if ($this instanceof \cs_privateroom_item) {
                return true;
            }
        }

        return false;
    }

    public function setTagsShowExpanded(): void
    {
        $this->_addExtra('TAGSSHOWEXPANDED', 1);
    }

    public function unsetTagsShowExpanded(): void
    {
        $this->_addExtra('TAGSSHOWEXPANDED', 0);
    }

    public function isTagsShowExpanded(): bool
    {
        if ($this->_issetExtra('TAGSSHOWEXPANDED')) {
            $value = $this->_getExtra('TAGSSHOWEXPANDED');
            if (0 == $value) {
                return false;
            }
        }

        return true;
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
            $this->_rubric_support[$rubric_type] = !empty($rubric_type) && mb_stristr($current_room_modules, (string) $rubric_type);
        }

        return $this->_rubric_support[$rubric_type];
    }

    public function getAvailableRubrics(): array
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

     public function _setRSSStatus($value): void
     {
         $this->_addExtra('RSS_STATUS', $value);
     }

     public function turnRSSOn(): void
     {
         $this->_setRSSStatus(1);
     }

     public function turnRSSOff(): void
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
    public function withAds(): bool
    {
        if ($this->isServer()) {
            return true;
        } else {
            $value = $this->_getExtraConfig('ADS');
            if (1 == $value) {
                return true;
            }
        }

        return false;
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
    public function setActivityPoints($value): void
    {
        $this->_setValue('activity', $value);
    }

    public function saveActivityPoints($points): void
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

    /** get last status
     * this method returns the last status before blocking the room.
     *
     * @return int the status of the room before it was blocked
     */
    public function getLastStatus(): false|int
    {
        if ($this->_issetExtra('LASTSTATUS')) {
            return intval($this->_getExtra('LASTSTATUS'));
        }

        return false;
    }

    /** set last status
     * this method sets the last status.
     *
     * @param int value status of the room
     */
    public function setLastStatus($value): void
    {
        $this->_addExtra('LASTSTATUS', (int) $value);
    }

    /** set status of a room
     * this method returns the status of the room.
     *
     * @param int $value status of a room
     */
    public function setStatus($value): void
    {
        $this->_setValue('status', (int) $value);
    }

    /** get status of a room
     * this method returns the status of the room.
     *
     * @return int|string status of a room
     */
    public function getStatus(): int|string
    {
        return $this->_getValue('status');
    }

    /** open the room for usage
     * this method sets the status of the room to open.
     */
    public function open(): void
    {
        $this->_data['status'] = CS_ROOM_OPEN;
    }

    /** close a room
     * this method sets the status of the room to closed.
     */
    public function close(): void
    {
        $this->_data['status'] = CS_ROOM_CLOSED;
    }

    /** lock a room
     * this method sets the status of the room to locked.
     */
    public function lock(): void
    {
        $this->setLastStatus($this->getStatus());
        $this->_data['status'] = CS_ROOM_LOCK;
    }

    /** lock a room
     * this method sets the status of the room to locked.
     */
    public function unlock(): void
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
    public function isOpen(): bool
    {
        if (!empty($this->_data['status'])
                and CS_ROOM_OPEN == $this->_data['status']
        ) {
            return true;
        }

        return false;
    }

    /** is a room closed ?
     * this method returns a boolean explaining if a room is closed or not.
     *
     * @return bool true, if a room is closed
     *              false, if a room is not closed
     */
    public function isClosed(): bool
    {
        if (!empty($this->_data['status'])
                and CS_ROOM_CLOSED == $this->_data['status']
        ) {
            return true;
        }

        return false;
    }

    /** is a room locked?
     * this method returns a boolean explaining if a room is locked.
     *
     * @return bool true, if a room is locked
     *              false, if a room is not locked
     */
    public function isLocked(): bool
    {
        if (!empty($this->_data['status'])
                and CS_ROOM_LOCK == $this->_data['status']
        ) {
            return true;
        }

        return false;
    }

    public function lockForMoveWithLinkedRooms(): void
    {
        $this->_addExtra('MOVE', '2');
    }

    public function lockForMove(): void
    {
        $this->_addExtra('MOVE', '1');
    }

    public function moveWithLinkedRooms(): bool
    {
        if ($this->_issetExtra('MOVE')) {
            if (2 == $this->_getExtra('MOVE')) {
                return true;
            }
        }

        return false;
    }

    public function unlockForMove(): void
    {
        $this->_unsetExtra('MOVE');
    }

    /** is a room locked for movement between portals?
     * this method returns a boolean explaining if a room is locked for movement between portals.
     *
     * @return bool true, if a room is locked
     *              false, if a room is not locked
     */
    public function isLockedForMove(): bool
    {
        if ($this->_issetExtra('MOVE')) {
            if (1 == $this->_getExtra('MOVE') or 2 == $this->_getExtra('MOVE')) {
                return true;
            }
        }

        return false;
    }

    // #########################################
    // status of the room
    // ################ END ####################

    /** save context
     * this method save the context.
     */
    public function save(): void
    {
        $manager = $this->_environment->getManager($this->_type);
        $this->_save($manager);
    }

    public function saveWithoutChangingModificationInformation(): void
    {
        $manager = $this->_environment->getManager($this->_type);
        $manager->saveWithoutChangingModificationInformation();
        $this->_save($manager);
    }

    public function mayEdit(cs_user_item $user): bool
    {
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
                    return true;
                }
            }
        }

        return false;
    }

    public function mayEditRegular($user): bool
    {
        if (!empty($user)) {
            if (!$user->isOnlyReadUser()) {
                if ($user->isUser()
                        and ($user->getItemID() == $this->getCreatorID()
                                or $this->isPublic()
                                or $this->isModeratorByUserID($user->getUserID(), $user->getAuthSource())
                        )
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    public function isModeratorByUserID($user_id, $auth_source): bool
    {
        $mod_list = $this->getModeratorList();
        if ($mod_list->isNotEmpty()) {
            $mod = $mod_list->getFirst();
            while ($mod) {
                if ($mod->getUserID() == $user_id and $mod->getAuthSource() == $auth_source) {
                    return true;
                }
                $mod = $mod_list->getNext();
            }
        }

        return false;
    }

    public function isLastModeratorByUserID($user_id, $auth_source): bool
    {
        $mod_list = $this->getModeratorList();
        if (1 == $mod_list->getCount()) {
            $mod = $mod_list->getFirst();
            if ($mod->getUserID() == $user_id && $mod->getAuthSource() == $auth_source) {
                return true;
            }
        }

        return false;
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

    public function resetUserList(): void
    {
        $userManager = $this->_environment->getUserManager();
        $userManager->setCacheOff();
        $this->userList->reset();
    }

    public function isUser($user): bool
    {
        $user_manager = $this->_environment->getUserManager();
        return $user_manager->isUserInContext($user->getUserID(), $this->getItemID(), $user->getAuthSource());
    }

    public function getUserByUserID($user_id, $auth_source): cs_user_item|null
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
     * @author CommSy Development Group
     */
    public function setPublic($value): void
    {
        $this->_setValue('public', $value);
    }

    /** sets if announcement is editable by everybody or just creator.
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

    public function getCountItems($start, $end): int
    {
        $manager = $this->_environment->getItemManager();
        $manager->resetLimits();
        $manager->setContextLimit($this->getItemID());
        return $manager->getCountItems($start, $end);
    }

    public function getCountProjects($start, $end): int
    {
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

        return $manager->getCountProjects($start, $end);
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
    public function setPageImpressionArray($value): void
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
    public function setUserActivityArray($value): void
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

    public function getPageImpressions($external_timespread = 0, $db_page_impressions = 0): int
    {
        if (!isset($this->cachePageImpressions[$external_timespread])) {
            $timespread = ($external_timespread != 0) ? $external_timespread : $this->getTimeSpread();

            $count = 0;
            $pi_array = $this->getPageImpressionArray();
            for ($i = 0; $i < $timespread; ++$i) {
                if (!empty($pi_array[$i])) {
                    $count = $count + $pi_array[$i];
                }
            }

            if ($db_page_impressions == 0) {
                global $symfonyContainer;
                /** @var LogRepository $logRepository */
                $logRepository = $symfonyContainer->get(LogRepository::class);
                $pageImpressions = $logRepository->getCountForContext($this->getItemID());
            } else {
                $pageImpressions = $db_page_impressions;
            }

            $this->cachePageImpressions[$external_timespread] = $count + $pageImpressions;
        }

        return $this->cachePageImpressions[$external_timespread];
    }

    public function isActiveDuringLast99Days(): bool
    {
        return $this->getPageImpressions() > 0;
    }

    public function getNewEntries($external_timespread = 0): int
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
        return $new_entries ? count($new_entries) : 0;
    }

    public function getActiveMembers($external_timespread = 0): int
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
        return !empty($ids) ? count($ids) : 0;
    }

    public function getActiveMembersForNewsletter($external_timespread = 0)
    {
        // take it from UserActivity extras field
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

    public function getPageImpressionsForNewsletter($external_timespread = 0): int
    {
        if (isset($this->cachePageImpressions[$external_timespread])) {
            return $this->cachePageImpressions[$external_timespread];
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
            return $count;
        }
    }

    public function getAllUsers(): int
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

    public function setPageImpressionAndUserActivityLast($value): void
    {
        $this->_addExtra('PIUA_LAST', $value);
    }

    // #################################
    // Workflow
    // #################################

    public function withWorkflowFunctions(): bool
    {
        $value = $this->_getExtraConfig('WORKFLOW');
        if (1 == $value) {
            return true;
        } elseif ($this->isProjectRoom()
            or $this->isCommunityRoom()
            or $this->isGroupRoom()
            or $this->isPrivateRoom()
        ) {
            $portal_room = $this->getContextItem();
            if ($portal_room->withWorkflowFunctions()) {
                return true;
            }
        }

        return false;
    }

    public function setWithWorkflowFunctions(): void
    {
        $this->_setExtraConfig('WORKFLOW', 1);
    }

    public function setWithoutWorkflowFunctions(): void
    {
        $this->_setExtraConfig('WORKFLOW', 0);
    }

    public function setHideAccountname(): void
    {
        $this->_setExtraConfig('HIDE_ACCOUNTNAME', '1');
    }

    public function unsetHideAccountname(): void
    {
        $this->_setExtraConfig('HIDE_ACCOUNTNAME', '2');
    }

    public function getHideAccountname(): bool
    {
        $value = $this->_getExtraConfig('HIDE_ACCOUNTNAME');
        if (2 == $value) {
            return false;
        } elseif (1 == $value) {
            return true;
        }

        return false;
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

      public function setUsersCanEditCalendars(): void
      {
          $this->_addExtra('USERSCANEDITCALENDARS', 1);
      }

      public function unsetUsersCanEditCalendars(): void
      {
          $this->_addExtra('USERSCANEDITCALENDARS', 0);
      }

      public function usersCanEditCalendars(): bool
      {
          if ($this->_issetExtra('USERSCANEDITCALENDARS')) {
              $re = $this->_getExtra('USERSCANEDITCALENDARS');
              if (1 == $re) {
                  return true;
              }
          }

          return false;
      }

      public function setUsersCanSetExternalCalendarsUrl(): void
      {
          $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL', 1);
      }

      public function unsetUsersCanSetExternalCalendarsUrl(): void
      {
          $this->_addExtra('USERSCANSETEXTERNALCALENDARSURL', 0);
      }

      public function usersCanSetExternalCalendarsUrl(): bool
      {
          if ($this->_issetExtra('USERSCANSETEXTERNALCALENDARSURL')) {
              $re = $this->_getExtra('USERSCANSETEXTERNALCALENDARSURL');
              if (1 == $re) {
                  return true;
              }
          }

          return false;
      }
}
