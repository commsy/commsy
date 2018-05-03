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

/** upper class of the detail view
 */
$environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
$class_factory = $environment->getClassFactory();
$class_factory->includeClass(PAGE_VIEW);

/** language_functions are needed for language specific display
 */
include_once('functions/language_functions.php');

/** curl_functions are needed for actions
 */
include_once('functions/curl_functions.php');

/** date_functions are needed for language specific display
 */
include_once('functions/date_functions.php');

/** misc_functions are needed for display the commsy version
 */
include_once('functions/misc_functions.php');
include_once('functions/text_functions.php');

/** class for a page view of commsy
 * this class implements a page view of commsy
 */
class cs_external_page_portal_view extends cs_page_view
{
    /**
     * string - two-letter identifier specifying the default display language of the page
     */
    var $_defaultDisplayLanguage = 'de';

    /**
     * array - containing the two-letter identifiers for all supported display languages of the page
     */
    var $_availableDisplayLanguages = ["de", "en"];


    /**
     * string - containing the parameter of the page
     */
    var $_current_parameter = '';

    var $_form_tags = false;

    var $_form_action = '';


    var $_with_room_list = true;

    /**
     * array - containing the hyperlinks for the page
     */
    var $_links = array();

    var $_space_between_views = true;

    var $_blank_page = false;

    var $_blank_page_content = '';

    var $_room_list_view = NULL;

    var $_room_detail_view = NULL;

    var $_configuration_list_view = NULL;

    var $_configuration_preferences_view = NULL;

    var $_mail_to_moderator_view = NULL;

    var $_form_view = NULL;

    var $_show_agbs = false;

    var $_warning = NULL;

    var $_agb_view = NULL;

    var $_with_delete_box = false;

    var $_delete_box_action_url = '';

    var $_delete_box_mode = 'detail';

    var $_delete_box_ids = NULL;
    /**
     * boolean - containing the flag for displaying a personal area for root (e.g. page commsy overview)
     * standard = false
     */
    var $_with_root_personal_area = false;

    /**
     * boolean - containing the flag for displaying a navigation bar for root (e.g. page commsy overview)
     * standard = false
     */
    var $_with_root_navigation_links = false;


    var $_bold_rubric = '';

    var $_shown_as_printable = false;

    var $_with_agb_link = true;

    var $_with_announcements = false;

    var $_style_image_path = 'images/layout/';

    var $_delete_box_hidden_values = array();

    private $_navigation_bar = NULL;

    public $_login_redirect = NULL;

    protected $_has_to_change_email = false;

    /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param array params parameters in an array of this class
     */
    public function __construct($params)
    {
        $this->cs_page_view($params);
        if (file_exists('htdocs/' . $this->_environment->getCurrentPortalID() . '/commsy.css')) {
            $this->_style_image_path = $this->_environment->getCurrentPortalID() . '/images/';
        }
    }

    public function addDeleteBoxHiddenValues($array)
    {
        $this->_delete_box_hidden_values = $array;
    }

    public function setHasToChangeEmail()
    {
        $this->_has_to_change_email = true;
    }

    public function setLoginRedirect()
    {
        $this->_login_redirect = true;
    }

    public function setNavigationBar($value)
    {
        $this->_navigation_bar = $value;
    }

    public function setBlankPage()
    {
        $this->_blank_page = true;
    }

    public function setBlankPageContent($content)
    {
        $this->_blank_page_content = $content;
    }

    public function unsetBlankPage()
    {
        $this->_blank_page = false;
    }

    public function setShowAGBs()
    {
        $this->_show_agbs = true;
    }

    public function withAnnouncements()
    {
        $boolean = true;
        if ($this->_with_announcements == false) {
            $boolean = false;
        }
        return $boolean;
    }

    /** adds a view on the left
     * this method adds a view to the page on the left hand side
     *
     * @param object cs_view a commsy view
     */
    public function addRoomList($view)
    {
        $this->_room_list_view = $view;
    }

    public function addForm($view)
    {
        $this->_form_view = $view;
    }

    public function addAGBView($view)
    {
        $this->_agb_view = $view;
    }

    public function addWarning($view)
    {
        $this->_warning = $view;
    }

    public function addRoomDetail($view)
    {
        $this->_room_detail_view = $view;
    }

    public function addConfigurationListView($view)
    {
        $this->_configuration_list_view = $view;
    }

    public function addConfigurationPreferencesView($view)
    {
        $this->_configuration_preferences_view = $view;
    }

    public function addMailToModeratorFormView($view)
    {
        $this->_mail_to_moderator_view = $view;
    }


    public function setSpace()
    {
        $this->_space_between_views = true;
    }

    public function unsetSpace()
    {
        $this->_space_between_views = false;
    }

    public function setContextID($value)
    {
        $this->_context_id = (int)($value);
    }

    public function setBoldRubric($value)
    {
        $this->_bold_rubric = $value;
    }


    /** so page will be displayed without the personal area
     */
    public function setWithoutPersonalArea()
    {
        $this->_with_personal_area = false;
    }

    /** so page will be displayed with the personal area for root user
     */
    public function setWithRootPersonalArea()
    {
        $this->_with_root_personal_area = true;
    }

    /** so page will be displayed without the navigation links
     * this method skip a flag, so that the navigation links will not be shown
     */
    public function setWithoutNavigationLinks()
    {
        $this->_with_navigation_links = false;
    }

    /** so page will be displayed with the navigation bar for root user
     */
    public function setWithRootNavigationLinks()
    {
        $this->_with_root_navigation_links = true;
    }

    public function addFormTags($action)
    {
        $this->_form_tags = true;
        $this->_form_action = $action;
    }

    /** add an action to the page
     * this method adds an action (hyperlink) to the page view
     *
     * @param string  title        title of the action
     * @param string  explanantion explanation of the action
     * @param string  module       module of the action
     * @param string  function     public function in module of the action
     * @param string  parameter    get parameter of the action
     */
    public function addAction($title, $explanation = '', $module = '', $function = '', $parameter = '')
    {
        $action['title'] = $title;
        $action['module'] = $module;
        $action['function'] = $function;
        $action['parameter'] = $parameter;
        $action['explanation'] = $explanation;
        $this->_links[] = $action;
    }

    /** get the linkbar as HTML
     * this method returns the linkbar as HTML - internal, do not use
     *
     * @return string linkbar as HTML
     *
     * @author CommSy Development Group
     */
    public function _getLinkRowAsHTML()
    {

        $html = LF . '<!-- FADE LEFT MENUE -->' . LF;
        $session = $this->_environment->getSession();
        $left_menue_status = $session->getValue('left_menue_status');
        if ($this->_without_left_menue or (isset($_GET['mode']) and $_GET['mode'] == 'print')) {
            // do nothing
        } elseif ($left_menue_status == 'disapear') {
            $html .= '<div style="vertical-align:bottom;">';
            $params = $this->_environment->getCurrentParameterArray();
            $params['left_menue'] = 'apear';
            $html .= '<div style=" margin:0px; padding-left:5px;">' . LF;
            $html .= ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params, '<span class="required">' . '> ' . '</span>' . '<span style="font-size:8pt; color:black;">' . $this->_translator->getMessage('COMMON_FADE_IN') . '</span>', '', '', '', '');
            $html .= '</div>' . LF;
            unset($params);
            $html .= '</div>' . LF;
        }

        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="portal_tabs_frame">' . LF;
        $html .= '<div class="portal-tabs">' . LF;
        $html .= '<div style="float:right; margin:0px; padding:0px;">' . LF;

        // language options
        $language_array = $this->_environment->getAvailableLanguageArray();
        foreach ($language_array as $lang) {
            $params = array();
            $params['language'] = $lang;
            if ($lang == 'en') {
                $flag_lang = 'gb';
            } elseif ($lang == 'ru') {
                $flag_lang = 'ro';
            } else {
                $flag_lang = $lang;
            }
            $html .= ahref_curl($this->_environment->getCurrentContextID(), 'language', 'change', $params, '<img src="images/flags/' . $flag_lang . '.gif" style="float: left; padding-top: 3px; padding-right: 2px;" alt="' . $this->_translator->getMessageInLang($lang, 'COMMON_CHANGE_LANGUAGE_WITH_FLAG') . '"/>', $this->_translator->getMessageInLang($lang, 'COMMON_CHANGE_LANGUAGE_WITH_FLAG')) . LF;
            unset($params);
        }

        $html .= '&nbsp;' . LF;
        $html .= '</div>' . "\n";
        $html .= '<div style="margin:0px; padding:0px;">' . "\n";
        $html .= '<span class="navlist">&nbsp;</span>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '</div>' . "\n";
        $html .= '</div>' . "\n";
        return $html;
    }

    public function _getBlankLinkRowAsHTML()
    {
        $html = LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="tabs_frame">' . LF;
        $html .= '<div class="tabs">' . LF;
        $html .= '<div style="float:right; margin:0px; padding:0px;">' . LF;

        $html .= '&nbsp;' . LF;
        $html .= '</div>' . LF;
        $html .= '<div style="margin:0px; padding:0px;">' . LF;
        $html .= '<span class="navlist">&nbsp;</span>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '<!-- END TABS -->' . LF;
    }

    public function _getWelcomeTextAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . "\n";
        $width = 'width:100%;';
        $html .= '<div class="welcome_frame" style="width: 100%; height:268px; margin-bottom:5px;">' . LF;
        $html .= '<div class="content_without_fader" style="height:268px;">';
        $html .= '<div style="margin:0px; padding:0px">' . "\n";

        $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<td style="width:35%; vertical-align:top; margin:0px; padding-top:0px; padding-left:0px; padding-bottom:5px;">' . "\n";
        $current_portal = $this->_environment->getCurrentPortalItem();
        $logo_filename = $current_portal->getPictureFilename();
        $disc_manager = $this->_environment->getDiscManager();
        $disc_manager->setContextID($current_portal->getItemID());
        if (!empty($logo_filename) and $disc_manager->existsFile($logo_filename)) {
            $params = array();
            $params['picture'] = $current_portal->getPictureFilename();
            $curl = curl($current_portal->getItemID(), 'picture', 'getfile', $params, '');
            unset($params);
            if ($current_portal->isShowAnnouncementsOnHome()) {
                $html .= '<img class="logo" style="width:200px;" src="' . $curl . '" alt="' . $this->_translator->getMessage('LOGO') . '" border="0"/>';
            } else {
                $html .= '<img class="logo" style="width:300px; height:268px;" src="' . $curl . '" alt="' . $this->_translator->getMessage('LOGO') . '" border="0"/>';
            }
        }
        $disc_manager->setContextID($this->_environment->getCurrentContextID());

        $html .= '</td>' . "\n";
        if ($current_portal->isShowAnnouncementsOnHome()) {
            $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">' . "\n";
        } else {
            $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 15px; font-weight: normal;">' . "\n";
        }
        $text = $current_portal->getDescriptionWellcome1();
        if (!empty($text)) {
            $html .= '<div style="width:99%; text-align:left; padding-top:10px; padding-bottom:5px;"><h1 class="portal_title">' . $this->_text_as_html_short($current_portal->getDescriptionWellcome1()) . '</h1></div>' . LF;
        }
        $text = $current_portal->getDescriptionWellcome2();
        if (!empty($text)) {
            $html .= '<div style="width:99%; text-align:right; padding-bottom:10px;"><h1 class="portal_main_title">' . $this->_text_as_html_short($current_portal->getDescriptionWellcome2()) . '</h1></div>' . LF;
        }
        if ($current_portal->isShowAnnouncementsOnHome()) {
            $html .= '</td>' . "\n";
            $html .= '</tr>' . "\n";
            $html .= '<tr>' . "\n";
            $html .= '<td colspan="2" style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">' . "\n";
        }
        $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($current_portal->getDescription()));
        if ($current_portal->isShowAnnouncementsOnHome()) {
            $html .= '</td>' . "\n";
        }
        $html .= '</tr>' . "\n";
        $html .= '</table>' . "\n";

        $html .= '</div>' . "\n";

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getModeratorMailTextAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . "\n";
        $html .= '<div class="welcome_frame" style="width: 100%;">' . LF;
        $html .= '<div class="content_without_fader">';
        $html .= '<div style="margin:0px; padding:0px">' . "\n";

        $html .= '<div style="font-weight:normal; padding:5px;">' . "\n";
        if (isset($this->_mail_to_moderator_view)) {
            $html .= $this->_mail_to_moderator_view->asHTML();
        }
        $html .= '</div>' . "\n";

        $html .= '</div>' . "\n";

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getServerWelcomeTextAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . "\n";
        $html .= '<div class="welcome_frame" style="width: 100%;">' . LF;
        $html .= '<div class="content_without_fader">';
        $html .= '<div style="margin:0px; padding:0px 0px;">' . "\n";

        $html .= '<table style="width:100%; margin:0px; padding:0px; border-collapse:collapse;" summary="Layout">' . "\n";
        $html .= '<tr>' . "\n";
        $current_portal = $this->_environment->getServerItem();
        $html .= '<td style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">' . "\n";
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '<tr>' . "\n";
        $html .= '<td  style="text-align:left; vertical-align:top; padding-top:5px; padding-bottom:5px; padding-left: 5px; font-weight: normal;">' . "\n";
        $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($current_portal->getDescription()));
        $current_user = $this->_environment->getCurrentUser();
        if ($current_user->isRoot()) {
            $html .= '<div class="search_link" style="padding-left:0px; padding-top: 5px;">' . LF;
            $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'index', '', $this->_translator->getMessage('SERVER_CONFIGURATION_ACTION')) . BRLF;
            $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'preferences', array('iid' => 'NEW'), $this->_translator->getMessage('PORTAL_ENTER_NEW')) . BRLF;
            $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'context', 'logout', '', $this->_translator->getMessage('LOGOUT')) . BRLF;
            $html .= '</div>' . LF;
        }
        $html .= '</td>' . "\n";
        $html .= '</tr>' . "\n";
        $html .= '</table>' . "\n";


        $html .= '</div>' . "\n";

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getPortalAnnouncements()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width: 100%; height:268px; margin-bottom:5px;">' . LF;
        $html .= '<div class="content_fader" style="height:268px;">';
        $html .= '<div style="margin:0px; padding:0px 0px;">' . "\n";

        $params = array();
        $params['environment'] = $this->_environment;
        $announcement_view = $this->_class_factory->getClass(ANNOUNCEMENT_SHORT_COMMUNITY_GUIDE_VIEW, $params);
        unset($params);
        $community_manager = $this->_environment->getCommunityManager();
        $community_manager->setOpenedLimit();
        $community_manager->setOrder('activity_rev');
        $community_manager->select();
        $community_list = $community_manager->get();
        if (!$community_list->isEmpty()) {
            $announcement_view->setList($community_list);
            $html .= $announcement_view->asHTML();
        } else {
            $html .= $announcement_view->asHTML();
        }
        $html .= '</div>' . LF;

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getSearchBoxAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width:100%;">' . LF;
        $html .= '<div class="content_fader">' . LF;
        $html .= '<div style="margin:0px; padding-bottom:10px; width:100%;">' . LF;

        if (isset($this->_room_list_view)) {
            $html .= $this->_room_list_view->getSearchBoxasHTML();
        }

        $html .= '</div>' . LF;

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getSystemInfoAsHTML()
    {
        $html = '';
        $html .= '<div style="font-size:8pt; padding-left:10px; padding-top:0px; margin-top:3px;">' . LF;
        $html .= '<div class="footer" style="text-align:left; padding-left:0px; padding-right:0px; padding-top:0px; padding-bottom:10px;">' . LF;
        $html .= '&nbsp;&nbsp;<a href="http://www.commsy.net" target="_top" title="' . $this->_translator->getMessage('COMMON_COMMSY_LINK_TITLE') . '">CommSy ' . getCommSyVersion() . '</a>';
        $version_addon = $this->_environment->getConfiguration('c_version_addon');
        if (!empty($version_addon)) {
            $html .= ' - ' . $version_addon;
        }
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getContentListAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . "\n";
        $html .= '<div class="welcome_frame" style="width:100%;">' . LF;
        $html .= '<div class="content_fader">';
        $html .= '<div style="margin:0px; padding-top:0px; padding-bottom: 10px;">' . "\n";
        if (isset($this->_room_list_view)) {
            $html .= $this->_room_list_view->asHTML();
        }

        $html .= '</div>' . "\n";

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getAGBTextAsHTML()
    {
        $html = '';
        $html .= '<div style="width: 43em; padding-left:10px; font-weight:normal;">' . LF;
        $html .= LF . '<table style="border-collapse:collapse; padding:0px;  margin-top:5px; width:100%;" summary="Layout">' . LF;
        $html .= '<tr>' . LF;
        $html .= '<td style="width:100%;">' . LF;
        $html .= $this->_getLogoAsHTML() . LF;
        $html .= '</td>' . LF;
        $html .= '</tr>' . LF;
        $html .= '<tr>' . LF;
        $html .= '<td>' . LF;
        $html .= $this->_getAGBViewAsHTML() . LF;
        $html .= '</td>' . LF;
        $html .= '</tr>' . LF;
        $html .= '</table>' . LF;
        $html .= '</div>' . LF;
        $html .= '<div style="padding-left:0px;">' . LF;
        $html .= $this->_getSystemInfoAsHTML();
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getAGBViewAsHTML()
    {
        $html = LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="tabs_frame" >' . LF;
        $html .= '<div class="tabs">' . LF;

        if (isset($this->_agb_view) and $this->_agb_view instanceof cs_form_view_plain) {
            $title = $this->_agb_view->getTitle();
        }
        if (empty($title)) {
            $title = $this->_translator->getMessage('AGB_CONFIRMATION');
        }
        if (!empty($this->_navigation_bar)) {
            $title = $this->_navigation_bar;
        }

        $html .= '<div style="margin:0px; padding:0px;">' . LF;
        $html .= '<span class="navlist">&nbsp;' . $title . '</span>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '<!-- END TABS -->' . LF;
        $html .= '<div style="border-left: 2px solid #C3C3C3; border-right: 2px solid #C3C3C3; padding:0px 0px; margin:0px;">' . LF;
        $html .= '<div class="content">' . LF;
        $html .= '<div class="content_fader">';
        $html .= '<a name="top"></a>' . LF;
        if (isset($this->_agb_view)) {
            $html .= $this->_agb_view->asHTML();
        }
        $html .= '</div>';
        $html .= '<div class="top_of_page">' . LF;
        $html .= '<div>' . LF;
        $html .= '<a href="#top">' . '<img src="images/browse_left2.gif" alt="&lt;" border="0"/></a>&nbsp;<a href="#top">' . $this->_translator->getMessage('COMMON_TOP_OF_PAGE') . '</a>';
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    /** get room window as html
     *
     * param cs_project_item project room item
     */
    public function _getRoomAccessAsHTML($item, $mode = 'none')
    {
        $current_user = $this->_environment->getCurrentUserItem();
        $may_enter = $item->mayEnter($current_user);
        $html = '';
        //Projektraum User
        $user_manager = $this->_environment->getUserManager();
        $user_manager->setUserIDLimit($current_user->getUserID());
        $user_manager->setAuthSourceLimit($current_user->getAuthSource());
        $user_manager->setContextLimit($item->getItemID());
        $user_manager->select();
        $user_list = $user_manager->get();
        if (!empty($user_list)) {
            $room_user = $user_list->getFirst();
        } else {
            $room_user = '';
        }

        // archive
        if ($may_enter
            and empty($room_user)
            and $item->isClosed()
            and !$this->_environment->isArchiveMode()
        ) {
            $user_manager = $this->_environment->getZzzUserManager();
            $user_manager->setUserIDLimit($current_user->getUserID());
            $user_manager->setAuthSourceLimit($current_user->getAuthSource());
            $user_manager->setContextLimit($item->getItemID());
            $user_manager->select();
            $user_list = $user_manager->get();
            if (!empty($user_list)) {
                $room_user = $user_list->getFirst();
            } else {
                $room_user = '';
            }
            unset($user_list);
        }

        $current_user = $this->_environment->getCurrentUserItem();

        //Anzeige außerhalb des Anmeldeprozesses
        if ($mode != 'member' and $mode != 'info' and $mode != 'email') {
            $current_user = $this->_environment->getCurrentUserItem();
            $may_enter = $item->mayEnter($current_user);
            // Eintritt erlaubt
            if ($may_enter and ((!empty($room_user) and $room_user->isUser()) or $current_user->isRoot())) {
                global $symfonyContainer;
                $router = $symfonyContainer->get('router');

                $actionCurl = $router->generate(
                    'commsy_room_home',
                    array('roomId' => $item->getItemID())
                );

                $html .= '<a class="room_window" href="' . $actionCurl . '"><img src="images/door_open_large.gif" alt="door open" /></a>' . BRLF;
                $html .= '<div style="padding-top:8px;">&nbsp;</div>' . BRLF;

                //als Gast Zutritt erlaubt, aber kein Mitglied
            } elseif ($item->isLocked()) {
                $html .= '<img src="images/door_closed_large.gif" alt="door closed" />' . LF;

            } elseif ($item->isOpenForGuests()
                and empty($room_user)
            ) {
                global $symfonyContainer;
                $router = $symfonyContainer->get('router');

                $actionCurl = $router->generate(
                    'commsy_room_home',
                    array('roomId' => $item->getItemID())
                );

                $html .= '<a class="room_window" href="' . $actionCurl . '"><img src="images/door_open_large.gif" alt="door open" /></a>' . BRLF;
                $html .= '<div style="padding-top:5px;">' . '> <a href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST') . '</a></div>' . LF;
                if ($item->isOpen()
                    and !$this->_current_user->isOnlyReadUser()
                ) {
                    $params = array();
                    $params = $this->_environment->getCurrentParameterArray();
                    $params['account'] = 'member';
                    $params['room_id'] = $item->getItemID();
                    $actionCurl = curl($this->_environment->getCurrentContextID(),
                        'home',
                        'index',
                        $params,
                        '');
                    $html .= '<div style="padding-top:3px;">' . '> <a href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_JOIN') . '</a></div>' . LF;
                    unset($params);
                } else {
                    $html .= '<div style="padding-top:3px;">> <span class="disabled">' . $this->_translator->getMessage('CONTEXT_JOIN') . '</span></div>' . LF;
                }

                //Um Erlaubnis gefragt
            } elseif (!empty($room_user) and $room_user->isRequested()) {
                if ($item->isOpenForGuests()) {
                    $actionCurl = curl($item->getItemID(),
                        'home',
                        'index',
                        '');
                    $html .= '<a class="room_window" href="' . $actionCurl . '"><img src="images/door_open_large.gif" alt="door open" /></a>' . BRLF;
                    $actionCurl = curl($item->getItemID(),
                        'home',
                        'index',
                        '');
                    $html .= '<div style="padding-top:7px; text-align: center;">' . '> <a class="room_window" href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST') . '</a></div>' . LF;
                } else {
                    $html .= '<img src="images/door_closed_large.gif" alt="door closed"/>' . LF;
                }
                $html .= '<div style="padding-top:7px;"><p style="margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">' . $this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED_YET') . '</p></div>' . LF;
                //Erlaubnis verweigert
            } elseif (!empty($room_user) and $room_user->isRejected()) {
                if ($item->isOpenForGuests()) {
                    $actionCurl = curl($item->getItemID(),
                        'home',
                        'index',
                        '');
                    $html .= '<a class="room_window" href="' . $actionCurl . '"><img src="images/door_open_large.gif" alt="door open"/></a>' . BRLF;
                    $actionCurl = curl($item->getItemID(),
                        'home',
                        'index',
                        '');
                    $html .= '<div style="padding-top:7px;">' . '> <a class="room_window" href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_ENTER_AS_GUEST') . '</a></div>' . LF;
                } else {
                    $html .= '<img src="images/door_closed_large.gif" alt="door closed"/>' . LF;
                }
                $html .= '<div style="padding-top:7px;"><p style=" margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">' . $this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED') . '</p></div>' . LF;

                // noch nicht angemeldet als Mitglied im Raum
            } else {
                $html .= '<img src="images/door_closed_large.gif" alt="door closed" style="vertical-align: middle; "/>' . BRLF;
                if ($item->isOpen()
                    and !$this->_current_user->isOnlyReadUser()
                ) {
                    global $symfonyContainer;
                    $router = $symfonyContainer->get('router');

                    $privateRoom = $current_user->getOwnRoom();
                    if ($privateRoom) {
                        $actionCurl = $router->generate('commsy_context_request', [
                            'roomId' => $privateRoom->getItemID(),
                            'itemId' => $item->getItemID(),
                        ]);
                    } else {
                        $params = $this->_environment->getCurrentParameterArray();
                        $params['account'] = 'member';
                        $params['room_id'] = $item->getItemID();
                        $actionCurl = curl($this->_environment->getCurrentContextID(),
                            'home',
                            'index',
                            $params,
                            '');
                    }

                    $session_item = $this->_environment->getSessionItem();
                    if ($session_item->issetValue('login_redirect')) {
                        $html .= '<div style="padding-top:7px;"><p style="margin-top:0px; margin-bottom:0px;text-align:left;" class="disabled">';
                        if (!$item->isPrivateRoom() and !$item->isGroupRoom()) {
                            $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN', '<a class="room_window" href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_JOIN') . '</a>');
                        } else {
                            $current_user_item = $this->_environment->getCurrentUserItem();
                            $current_user_item = $current_user_item->getRelatedCommSyUserItem();
                            if (isset($current_user_item) and $current_user_item->isUser()) {
                                $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN_NOT_ALLOWED') . LF;
                                if ($item->isGroupRoom()) {
                                    $linked_project_item = $item->getLinkedProjectItem();
                                    $user_related_project_list = $current_user_item->getRelatedProjectList();
                                    $user_is_room_member = false;
                                    if ($user_related_project_list->isNotEmpty()) {
                                        $room_item = $user_related_project_list->getFirst();
                                        while ($room_item) {
                                            if ($room_item->getItemID() == $linked_project_item->getItemID()) {
                                                $user_is_room_member = true;
                                                break;
                                            }
                                            $room_item = $user_related_project_list->getNext();
                                        }
                                    }
                                    $html .= '<br/><br/>';
                                    if ($user_is_room_member) {
                                        $html .= $this->_translator->getMessage('CONTEXT_ENTER_NEED_TO_BECOME_GROUP_MEMBER', $item->getTitle(), $linked_project_item->getTitle());
                                        $html .= '<br/><br/>';
                                        $actionCurl = curl($linked_project_item->getItemID(),
                                            'group',
                                            'detail',
                                            array('account' => 'member', 'iid' => $item->getLinkedGroupItemID()),
                                            '');
                                        $html .= '<a href="' . $actionCurl . '">' . $this->_translator->getMessage('COMMON_REGISTER_HERE') . '</a>' . LF;
                                    } else {
                                        $user_manager->setUserIDLimit($current_user->getUserID());
                                        $user_manager->setAuthSourceLimit($current_user->getAuthSource());
                                        $user_manager->setContextLimit($linked_project_item->getItemID());
                                        $user_manager->select();
                                        $user_list = $user_manager->get();
                                        if (!empty($user_list)) {
                                            $room_user = $user_list->getFirst();
                                        } else {
                                            $room_user = '';
                                        }
                                        if (!empty($room_user) and !$room_user->isRejected()) {
                                            $html .= $this->_translator->getMessage('CONTEXT_ENTER_NEED_TO_BECOME_ROOM_MEMBER', $linked_project_item->getTitle(), $item->getTitle());
                                            $html .= '<br/><br/>';
                                            $actionCurl = curl($this->_environment->getCurrentContextID(),
                                                'home',
                                                'index',
                                                array('room_id' => $linked_project_item->getItemID(), 'account' => 'member'),
                                                '');
                                            $html .= '<a href="' . $actionCurl . '">' . $this->_translator->getMessage('COMMON_REGISTER_HERE') . '</a>' . LF;
                                        } else {
                                            $html .= $this->_translator->getMessage('ACCOUNT_NOT_ACCEPTED');
                                        }
                                    }
                                }
                            } else {
                                $html .= $this->_translator->getMessage('CONTEXT_ENTER_LOGIN2');
                            }
                            unset($current_user_item);
                        }
                        $html .= '</p></div>' . LF;
                        unset($session_item);
                    } elseif (!$item->isPrivateRoom() and !$item->isGroupRoom()) {
                        $html .= '<div style="padding-top:5px;">' . '> <a class="room_window" href="' . $actionCurl . '">' . $this->_translator->getMessage('CONTEXT_JOIN') . '</a></div>' . LF;
                    }
                    unset($params);
                } elseif (!$item->isPrivateRoom() and !$item->isGroupRoom()) {
                    $html .= '<div style="padding-top:5px;">> <span class="disabled">' . $this->_translator->getMessage('CONTEXT_JOIN') . '</span></div>' . LF;
                }
                $html .= '<div style="padding-top:6px;">&nbsp;</div>' . LF;
            }
        }
        return $html;
    }

    public function _getRoomFacts($item)
    {
        $html = '';
        // prepare moderator
        $html_temp = '';
        $moda = array();
        $moda_list = $item->getContactModeratorList();
        $current_user = $this->_environment->getCurrentUser();
        $moda_item = $moda_list->getFirst();
        while ($moda_item) {
            $html_temp .= '<li style="font-weight:normal; font-size:8pt;">' . $this->_text_as_html_short($moda_item->getFullName()) . '</li>';
            $moda_item = $moda_list->getNext();
        }
        $html .= '<span style="font-weight:bold;">' . $this->_translator->getMessage('ROOM_CONTACT') . ':</span>' . LF;
        $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
        if (!empty($html_temp)) {
            $temp_array = array();
            $html .= $html_temp;
            $params = $this->_environment->getCurrentParameterArray();
            $params['account'] = 'email';
            $params['room_id'] = $item->getItemID();
            $actionCurl = curl($this->_environment->getCurrentContextID(),
                'home',
                'index',
                $params,
                '');
            unset($params);
            if ($current_user->isUser()) {
                $html .= '<li style="font-weight:bold; font-size:8pt;">' . '<a href="' . $actionCurl . '">' . $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR') . '</a></li>';
            } else {
                //$html .= '<li style="font-weight:bold; font-size:8pt;">'.'<span class="disabled">'.$this->_translator->getMessage('EMAIL_CONTACT_MODERATOR').'</span></li>';
                $html .= '<li style="font-weight:bold; font-size:8pt;">' . '<a href="' . $actionCurl . '">' . $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR') . '</a></li>';
            }
        } else {
            $html .= '<li style="font-weight:bold; font-size:8pt;">' . '<span class="disabled">' . $this->_translator->getMessage('COMMON_NO_CONTACTS') . '</span></li>';
        }
        $html .= '</ul>' . LF;
        // prepare time (clock pulses)
        $current_context = $this->_environment->getCurrentContextItem();
        if ($current_context->showTime() and ($item->isProjectRoom() or $item->isCommunityRoom())) {
            $time_list = $item->getTimeList();
            #$time_list = new cs_list();
            if ($time_list->isNotEmpty()) {
                $this->translatorChangeToPortal();
                $html .= '<span style="font-weight:bold;">' . $this->_translator->getMessage('COMMON_TIME_NAME') . ':</span>' . LF;
                $this->translatorChangeToCurrentContext();
                if ($item->isContinuous()) {
                    $time_item = $time_list->getFirst();
                    if ($item->isClosed()) {
                        $time_item_last = $time_list->getLast();
                        if ($time_item_last->getItemID() == $time_item->getItemID()) {
                            $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
                            $html .= '   <li style="font-weight:normal; font-size:8pt;">' . LF;
                            $html .= $this->_translator->getTimeMessage($time_item->getTitle()) . LF;
                            $html .= '   </li>' . LF;
                            $html .= '</ul>' . LF;
                        } else {
                            $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
                            $html .= '   <li style="font-weight:normal; font-size:8pt;">' . LF;
                            $html .= $this->_translator->getMessage('COMMON_FROM2') . ' ' . $this->_translator->getTimeMessage($time_item->getTitle()) . LF;
                            $html .= '   </li>' . LF;
                            $html .= '   <li style="font-weight:normal; font-size:8pt;">' . LF;
                            $html .= $this->_translator->getMessage('COMMON_TO') . ' ' . $this->_translator->getTimeMessage($time_item_last->getTitle()) . LF;
                            $html .= '   </li>' . LF;
                            $html .= '</ul>' . LF;
                        }
                    } else {
                        $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
                        $html .= '   <li style="font-weight:normal; font-size:8pt;">' . LF;
                        $html .= $this->_translator->getMessage('ROOM_CONTINUOUS_SINCE') . ' ' . BRLF . $this->_translator->getTimeMessage($time_item->getTitle()) . LF;
                        $html .= '   </li>' . LF;
                        $html .= '</ul>' . LF;
                    }
                } else {
                    $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
                    $time_item = $time_list->getFirst();
                    while ($time_item) {
                        $html .= '<li style="font-weight:normal; font-size:8pt;">' . $this->_translator->getTimeMessage($time_item->getTitle()) . '</li>' . LF;
                        $time_item = $time_list->getNext();
                    }
                    $html .= '</ul>' . LF;
                }
            } else {
                $this->translatorChangeToPortal();
                $html .= '<span style="font-weight:bold;">' . $this->_translator->getMessage('COMMON_TIME_NAME') . ':</span>' . LF;
                $this->translatorChangeToCurrentContext();
                $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
                $html .= '   <li style="font-weight:normal; font-size:8pt;"><span class="disabled">' . LF;
                $html .= $this->_translator->getMessage('ROOM_NOT_LINKED') . LF;
                $html .= '   </span></li>' . LF;
                $html .= '</ul>' . LF;
            }
        }

        // community list
        if ($item->isProjectRoom()) {
            $community_list = $item->getCommunityList();
            $html .= '<span style="font-weight:bold;">' . $this->_translator->getMessage('COMMUNITYS') . ':</span>' . LF;
            $html .= '<ul style="margin-left:0px;margin-top:0em; margin-bottom:0.5em; padding-top:0px;padding-left:1em;">' . LF;
            if ($community_list->isNotEmpty()) {
                $community_item = $community_list->getFirst();
                while ($community_item) {
                    $html .= '<li style="font-weight:normal; font-size:8pt;">' . LF;
                    $params = $this->_environment->getCurrentParameterArray();
                    $params['room_id'] = $community_item->getItemID();
                    $link = ahref_curl($this->_environment->getCurrentContextID(), 'home', 'index', $params, $community_item->getTitle());
                    $html .= $link . LF;
                    $html .= '</li>' . LF;
                    $community_item = $community_list->getNext();
                }
                $html .= '</ul>' . LF;
            } else {
                $html .= '<li style="font-weight:normal; font-size:8pt;" ><span class="disabled">' . LF;
                $html .= $this->_translator->getMessage('ROOM_NOT_LINKED') . LF;
                $html .= '</span></li>' . LF;
                $html .= '</ul>' . LF;
            }
        }

        return $html;
    }

    public function _getRoomForm($item, $mode)
    {
        $html = '';
        $current_user = $this->_environment->getCurrentUser();
        $current_context = $this->_environment->getCurrentContextItem();
        // Person ist User und will Mitglied werden
        if ($mode == 'member' and $current_user->isUser()) {
            $translator = $this->_environment->getTranslationObject();
            $html .= '<div>' . LF;
            $formal_data = array();
            $get_params = $this->_environment->getCurrentParameterArray();
            if (isset($get_params['sort'])) {
                $params['sort'] = $get_params['sort'];
            } elseif (isset($_POST['sort'])) {
                $params['sort'] = $get_params['sort'];
            }
            if (isset($get_params['search'])) {
                $params['search'] = $get_params['search'];
            } elseif (isset($_POST['search'])) {
                $params['search'] = $get_params['search'];
            }
            if (isset($get_params['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            } elseif (isset($_POST['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            }
            if (isset($get_params['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            } elseif (isset($_POST['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            }
            if (isset($get_params['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            } elseif (isset($_POST['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            }
            $params['room_id'] = $item->getItemID();
            $html .= '<form method="post" action="' . curl($this->_environment->getCurrentContextID(), 'home', 'index', $params) . '" name="member">' . LF;
            if (isset($get_params['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $get_params['sort'] . '"/>' . LF;
            } elseif (isset($_POST['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['sort'] . '"/>' . LF;
            }
            if (isset($get_params['search'])) {
                $html .= '   <input type="hidden" name="search" value="' . $get_params['search'] . '"/>' . LF;
            } elseif (isset($_POST['search'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['search'] . '"/>' . LF;
            }
            if (isset($get_params['seltime'])) {
                $html .= '   <input type="hidden" name="seltime" value="' . $get_params['seltime'] . '"/>' . LF;
            } elseif (isset($_POST['seltime'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['seltime'] . '"/>' . LF;
            }
            if (isset($get_params['selroom'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['selroom'] . '"/>' . LF;
            } elseif (isset($_POST['selroom'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['selroom'] . '"/>' . LF;
            }
            if (isset($get_params['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            } elseif (isset($_POST['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            }

            $portal_item = $this->_environment->getCurrentPortalItem();

            if ($item->checkNewMembersWithCode()) {
                $toggle_id = rand(0, 1000000);
                $html .= $this->_translator->getMessage('ACCOUNT_GET_CODE_TEXT');
                if (isset($get_params['error']) and !empty($get_params['error'])) {
                    $temp_array[0] = $this->_translator->getMessage('COMMON_ATTENTION') . ': ';
                    $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
                    if ($_GET['error'] == 'agb') {
                        $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_AGB_ERROR');
                    }
                    $formal_data[] = $temp_array;
                }
                $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE') . ': ';
                $temp_array[1] = '<input type="text" name="code" tabindex="14" size="30"/>' . LF;

                if ($item->getAGBStatus() != 2 and $portal_item->withAGBDatasecurity()) {
                    $text_array = $item->getAGBTextArray();

                    $lang = strtoupper($this->_translator->_selected_language);
                    $usage_info = $text_array[$lang];

                    $temp_array[1] .= BRLF;
                    $checkbox = '<input type="checkbox" name="agb_acceptance" value="1">';

                    $link = ahref_curl($item->getItemID(), 'agb', 'index', '', $this->_translator->getMessage('AGB_CONFIRMATION'));

                    $link_agb = '<a onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');" target="agb" href="commsy.php?cid=' . $item->getItemID() . '&mod=agb&fct=index&agb=1">' . $this->_translator->getMessage('AGB_CONFIRMATION') . '</a>';

                    $temp_array[1] .= $this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT', $checkbox, $link_agb) . LF;
                    $temp_array[1] .= BRLF;

                    #$temp_array[1] .= $usage_info;
                }
                $formal_data[] = $temp_array;

                $temp_array = array();
                $temp_array[0] = '&nbsp;';
                $temp_array[1] = '<input type="submit" name="option" tabindex="15" value="' . $this->_translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON') . '"/>' .
                    '&nbsp;&nbsp;' . '<input type="submit" name="option" tabindex="16" value="' . $this->_translator->getMessage('COMMON_BACK_BUTTON') . '"/>' . LF;
                $formal_data[] = $temp_array;

                if (!empty($formal_data)) {
                    $html .= $this->_getFormalDataAsHTML2($formal_data);
                    $html .= BRLF;
                }
                unset($formal_data);

                // Normale Raumanmeldung trotz Passwort
                $title = $this->_translator->getMessage('ACCOUNT_GET_CODE_TEXT_2');
                $html .= '<div>';
                $html .= '<img id="toggle' . $toggle_id . '" src="images/more.gif"/>';
                $html .= '&nbsp;' . $title . '';
                $html .= '</div>';
                $html .= '<div id="creator_information' . $toggle_id . '">' . LF;
                $html .= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
                $html .= '<script type="text/javascript">initTextFormatingInformation("' . $toggle_id . '",false);</script>';
                $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON') . ': ';
                $value = '';
                if (!empty($get_params['description_user'])) {
                    $value = $get_params['description_user'];
                    $value = str_replace('%20', ' ', $value);
                }
                $temp_array[1] = '<textarea name="description_user" cols="31" rows="10" tabindex="14">' . $value . '</textarea>' . LF;

                // if code is set for room
                if ($item->getAGBStatus() != 2 and $portal_item->withAGBDatasecurity()) {
                    $text_array = $item->getAGBTextArray();
                    $lang = strtoupper($this->_translator->_selected_language);

                    $usage_info = $text_array[$lang];

                    $temp_array[1] .= BRLF;
                    #$temp_array[1] .= '<input type="checkbox" name="agb_acceptance" value="1">';

                    $checkbox = '<input type="checkbox" name="agb_acceptance" value="1">';

                    $link = ahref_curl($item->getItemID(), 'agb', 'index', '', $this->_translator->getMessage('AGB_CONFIRMATION'));

                    $link_agb = '<a onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');" target="agb" href="commsy.php?cid=' . $item->getItemID() . '&mod=agb&fct=index&agb=1">' . $this->_translator->getMessage('AGB_CONFIRMATION') . '</a>';

                    $temp_array[1] .= $this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT', $checkbox, $link_agb) . LF;

                    #$temp_array[1] .= $this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT').LF;
                    $temp_array[1] .= BRLF;

                    #$temp_array[1] .= $usage_info;
                }

                $formal_data[] = $temp_array;

            } else {
                $html .= $this->_translator->getMessage('ACCOUNT_GET_4_TEXT');
                if (isset($get_params['error']) and !empty($get_params['error'])) {
                    $temp_array[0] = $this->_translator->getMessage('COMMON_ATTENTION') . ': ';
                    $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_CODE_ERROR');
                    if ($_GET['error'] == 'agb') {
                        $temp_array[1] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_AGB_ERROR');
                    }
                    $formal_data[] = $temp_array;
                }
                $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_ROOM_REASON') . ': ';
                $value = '';
                if (!empty($get_params['description_user'])) {
                    $value = $get_params['description_user'];
                    $value = str_replace('%20', ' ', $value);
                }

                $temp_array[1] = '<textarea name="description_user" cols="31" rows="10" tabindex="14">' . $value . '</textarea>' . LF;

                if ($item->getAGBStatus() != 2 and $portal_item->withAGBDatasecurity()) {
                    $text_array = $item->getAGBTextArray();
                    $lang = strtoupper($this->_translator->_selected_language);

                    $temp_array[1] .= BRLF;
                    $checkbox = '<input type="checkbox" name="agb_acceptance" value="1">';

                    $link = ahref_curl($item->getItemID(), 'agb', 'index', '', $this->_translator->getMessage('AGB_CONFIRMATION'));

                    $link_agb = '<a onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');" target="agb" href="commsy.php?cid=' . $item->getItemID() . '&mod=agb&fct=index&agb=1">' . $this->_translator->getMessage('AGB_CONFIRMATION') . '</a>';

                    $temp_array[1] .= $this->_translator->getMessage('COMMON_AGB_CONFIRMATION_LINK_INPUT', $checkbox, $link_agb) . LF;
                    $temp_array[1] .= BRLF;
                }

                $formal_data[] = $temp_array;
            }

            $temp_array = array();
            $temp_array[0] = '&nbsp;';
            $temp_array[1] = '<input type="submit" name="option" tabindex="15" value="' . $this->_translator->getMessage('ACCOUNT_GET_MEMBERSHIP_BUTTON') . '"/>' .
                '&nbsp;&nbsp;' . '<input type="submit" name="option" tabindex="16" value="' . $this->_translator->getMessage('COMMON_BACK_BUTTON') . '"/>' . LF;
            $formal_data[] = $temp_array;
            if (!empty($formal_data)) {
                $html .= $this->_getFormalDataAsHTML2($formal_data);
                $html .= BRLF;
            }
            unset($params);
            $html .= '</form>' . LF;
            $html .= '</div>' . LF;
            $html .= '</div>' . LF;
        } // person is guest und will Mitglied werden
        elseif ($mode == 'member' and $current_user->isGuest()) {
            $translator = $this->_environment->getTranslationObject();
            $html .= '<div>' . LF;
            $params = $this->_environment->getCurrentParameterArray();
            $params['cs_modus'] = 'portalmember';
            $link = ahref_curl($this->_environment->getCurrentContextID(), $this->_environment->getCurrentModule(), $this->_environment->getCurrentFunction(), $params, $this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE_LINK'));
            $html .= $this->_translator->getMessage('ACCOUNT_GET_GUEST_CHOICE', $link);
            $html .= '</div>' . LF;
        } elseif ($mode == 'email') {
            $translator = $this->_environment->getTranslationObject();
            $html .= '<div>' . LF;
            $formal_data = array();

            $get_params = $this->_environment->getCurrentParameterArray();
            if (isset($get_params['sort'])) {
                $params['sort'] = $get_params['sort'];
            } elseif (isset($_POST['sort'])) {
                $params['sort'] = $get_params['sort'];
            }
            if (isset($get_params['search'])) {
                $params['search'] = $get_params['search'];
            } elseif (isset($_POST['search'])) {
                $params['search'] = $get_params['search'];
            }
            if (isset($get_params['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            } elseif (isset($_POST['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            }
            if (isset($get_params['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            } elseif (isset($_POST['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            }
            if (isset($get_params['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            } elseif (isset($_POST['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            }
            $params['room_id'] = $item->getItemID();
            $html .= $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT');
            $html .= '<form method="post" action="' . curl($this->_environment->getCurrentContextID(), 'home', 'index', $params) . '" name="member">' . LF;
            if (isset($get_params['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $get_params['sort'] . '"/>' . LF;
            } elseif (isset($_POST['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['sort'] . '"/>' . LF;
            }
            if (isset($get_params['search'])) {
                $html .= '   <input type="hidden" name="search" value="' . $get_params['search'] . '"/>' . LF;
            } elseif (isset($_POST['search'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['search'] . '"/>' . LF;
            }
            if (isset($get_params['seltime'])) {
                $html .= '   <input type="hidden" name="seltime" value="' . $get_params['seltime'] . '"/>' . LF;
            } elseif (isset($_POST['seltime'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['seltime'] . '"/>' . LF;
            }
            if (isset($get_params['selroom'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['selroom'] . '"/>' . LF;
            } elseif (isset($_POST['selroom'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['selroom'] . '"/>' . LF;
            }
            if (isset($get_params['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            } elseif (isset($_POST['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            }
            $temp_array[0] = $this->_translator->getMessage('EMAIL_CONTACT_MODERATOR_TEXT_DESC') . ': ';
            $temp_array[1] = '<textarea name="description_user" cols="31" rows="10" wrap="virtual" tabindex="14" ></textarea>' . LF;
            $formal_data[] = $temp_array;
            $temp_array = array();
            $temp_array[0] = '&nbsp;';
            $temp_array[1] = '<input type="submit" name="option"  value="' . $this->_translator->getMessage('CONTACT_MAIL_SEND_BUTTON') . '"/>' .
                '&nbsp;&nbsp;' . '<input type="submit" name="option" value="' . $this->_translator->getMessage('COMMON_BACK_BUTTON') . '"/>' . LF;
            $formal_data[] = $temp_array;
            if (!empty($formal_data)) {
                $html .= $this->_getFormalDataAsHTML2($formal_data);
                $html .= BRLF;
            }
            unset($params);
            $html .= '</form>' . LF;
            $html .= '</div>' . LF;
        } // Person ist User und hat sich angemeldet; wurde aber nicht automatisch freigschaltet
        elseif ($mode == 'info') {
            // redirect if user is not logged in
            if ($this->_environment->getCurrentUser()->isGuest()) {
                redirect($this->_environment->getCurrentContextID(), 'home', 'index', '');
            }

            $translator = $this->_environment->getTranslationObject();
            $html .= '<div>' . LF;
            $formal_data = array();
            $get_params = $this->_environment->getCurrentParameterArray();
            if (isset($get_params['sort'])) {
                $params['sort'] = $get_params['sort'];
            } elseif (isset($_POST['sort'])) {
                $params['sort'] = $get_params['sort'];
            }
            if (isset($get_params['search'])) {
                $params['search'] = $get_params['search'];
            } elseif (isset($_POST['search'])) {
                $params['search'] = $get_params['search'];
            }
            if (isset($get_params['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            } elseif (isset($_POST['seltime'])) {
                $params['seltime'] = $get_params['seltime'];
            }
            if (isset($get_params['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            } elseif (isset($_POST['selroom'])) {
                $params['selroom'] = $get_params['selroom'];
            }
            if (isset($get_params['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            } elseif (isset($_POST['sel_archive_room'])) {
                $params['sel_archive_room'] = $get_params['sel_archive_room'];
            }
            $params['room_id'] = $item->getItemID();
            $html .= '<form method="post" action="' . curl($this->_environment->getCurrentContextID(), 'home', 'index', $params) . '" name="member">' . LF;
            if (isset($get_params['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $get_params['sort'] . '"/>' . LF;
            } elseif (isset($_POST['sort'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['sort'] . '"/>' . LF;
            }
            if (isset($get_params['search'])) {
                $html .= '   <input type="hidden" name="search" value="' . $get_params['search'] . '"/>' . LF;
            } elseif (isset($_POST['search'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['search'] . '"/>' . LF;
            }
            if (isset($get_params['seltime'])) {
                $html .= '   <input type="hidden" name="seltime" value="' . $get_params['seltime'] . '"/>' . LF;
            } elseif (isset($_POST['seltime'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['seltime'] . '"/>' . LF;
            }
            if (isset($get_params['selroom'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['selroom'] . '"/>' . LF;
            } elseif (isset($_POST['selroom'])) {
                $html .= '   <input type="hidden" name="sort" value="' . $_POST['selroom'] . '"/>' . LF;
            }
            if (isset($get_params['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            } elseif (isset($_POST['sel_archive_room'])) {
                $html .= '   <input type="hidden" name="selroom" value="' . $get_params['sel_archive_room'] . '"/>' . LF;
            }
            $temp_array = array();
            $temp_array[0] = $this->_translator->getMessage('ACCOUNT_PROCESS_CONFIRMATION') . ': ';
            $temp_array[1] = $this->_translator->getMessage('ACCOUNT_GET_6_TEXT_2', $item->getTitle());
            $formal_data[] = $temp_array;
            $temp_array = array();
            $temp_array[0] = '&nbsp;';
            $temp_array[1] = '<input type="submit" name="option"  value="' . $this->_translator->getMessage('COMMON_NEXT') . '"/>' . LF;
            $formal_data[] = $temp_array;
            if (!empty($formal_data)) {
                $html .= $this->_getFormalDataAsHTML2($formal_data);
                $html .= BRLF;
            }
            unset($params);
            $html .= '</form>' . LF;
            $html .= '</div>' . LF;
        }

        return $html;
    }

    public function _getFormalDataAsHTML2($data, $spacecount = 0, $clear = false)
    {
        $prefix = str_repeat(' ', $spacecount);
        $html = $prefix . '<table class="detail" style="width: 100%;" summary="Layout" ';
        if ($clear) {
            $html .= 'style="clear:both"';
        }
        $html .= '>' . "\n";
        foreach ($data as $value) {
            if (!empty($value[0])) {
                $html .= $prefix . '   <tr>' . LF;
                $html .= $prefix . '      <td style="padding: 10px 2px 10px 0px; color: #666; vertical-align: top; width: 1%;">' . LF;
                $html .= $prefix . '         ' . $value[0] . '&nbsp;' . LF;
            } else {
                $html .= $prefix . '         &nbsp;';
            }
            $html .= $prefix . '      </td><td style="margin: 0px; padding: 10px 2px 10px 0px;">' . LF;
            if (!empty($value[1])) {
                $html .= $prefix . '         ' . $value[1] . LF;
            }
            $html .= $prefix . '      </td>' . LF;
            $html .= $prefix . '   </tr>' . LF;
        }
        $html .= $prefix . '</table>' . LF;
        return $html;
    }

    public function _getRoomFormAsHTML($item)
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width:100%;">' . LF;
        $html .= '<div class="content_without_fader" style="padding:0px 0px 5px 0px;">' . LF;
        $html .= '<div style="margin:0px;width:100%; font-weight:normal; font-size:10pt;">' . LF;
        $html .= '<div style="padding-left:5px; padding-right:5px;">' . LF;
        if (isset($this->_warning)) {
            $html .= $this->_warning->asHTML();
        }
        if (isset($this->_form_view) and !empty($this->_form_view)) {
            $html .= $this->_form_view->asHTML();
        }
        $html .= '</div>' . LF;

        $html .= '</div>' . LF;

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getConfigurationAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width:100%;">' . LF;
        $html .= '<div class="content_without_fader">';
        $html .= '<div style="margin:0px;width:100%; font-weight:normal;">' . LF;
        $html .= '<div style="padding-left:5px; padding-right:5px;">' . LF;

        if ($this->_environment->getCurrentFunction() == 'index'
            and isset($this->_configuration_list_view)
            and !empty($this->_configuration_list_view)
        ) {
            $html .= '<div style="padding-top:15px;">' . LF;
            $html .= $this->_configuration_list_view->asHTML();
            $html .= '</div>' . LF;
        } elseif (isset($this->_form_view) and !empty($this->_form_view)) {
            $html .= '<div style="padding-top:15px;">' . LF;
            $html .= $this->_form_view->asHTML();
            $html .= '</div>' . LF;
            if ($this->_with_delete_box) {
                $html .= $this->getDeleteBoxAsHTML('portal');
            }
        }

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function _getLanguageIndexAsHTML()
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width:100%;">' . LF;
        $html .= '<div class="content_without_fader">';
        $html .= '<div style="margin:0px;width:100%; font-weight:normal;">' . LF;
        $html .= '<div style="padding-left:5px; padding-right:5px;">' . LF;

        if ($this->_environment->getCurrentFunction() == 'index'
            and isset($this->_configuration_list_view)
            and !empty($this->_configuration_list_view)
        ) {
            $html .= $this->_configuration_list_view->asHTML();
        } elseif (isset($this->_form_view) and !empty($this->_form_view)) {
            $html .= '<div>' . LF;
            $html .= $this->_form_view->asHTML();
            $html .= '</div>' . LF;
        }

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;

        $html .= '</div>' . LF;
        $html .= '</div>' . LF;
        return $html;
    }

    public function getDeleteBoxAsHTML($type = 'room', $archived_room = false)
    {
        if ($this->_environment->getCurrentModule() != 'home'
            and !($this->_environment->getCurrentModule() == 'configuration'
                and $this->_environment->getCurrentFunction() == 'preferences'
            )
        ) {
            return $this->getDeleteBoxAsHTML2();
        }
        $session = $this->_environment->getSession();
        $left_menue_status = $session->getValue('left_menue_status');
        if ($left_menue_status != 'disapear' and !$this->_without_left_menue) {
            $left = '0em';
            $width = '56em';
        } else {
            $left = '0em';
            $width = '73em';
        }
        $html = '<div style="position: absolute; z-index:100;  top:-3px; left:-3px; width:' . $width . '; height: 300px;">' . LF;
        $html .= '<center>';
        $margin_left = '100px;';
        if ($this->_environment->getCurrentBrowser() == 'MSIE'
            and (mb_substr($this->_environment->getCurrentBrowserVersion(), 0, 1) == '7')
        ) {
            $margin_left = '-200px;';
        }
        $html .= '<div style="position:fixed; z-index:100; margin-top:0px; margin-left:' . $margin_left . '; width:400px; padding:20px; background-color:#FFF; border:2px solid red;">';
        $html .= '<form style="margin-bottom:0px; padding:0px;" method="post" action="' . $this->_delete_box_action_url . '">';
        foreach ($this->_delete_box_hidden_values as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>' . LF;
        }
        if ($type == 'portal') {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_PORTAL');
        } else {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
        }
        $html .= '</h2>';
        if ($type == 'portal') {
            $html .= '<p style="text-align:left; font-weight:normal;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_PORTAL');
        } elseif ($archived_room) {
            $html .= '<p style="text-align:left; font-weight:normal;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM_ARCHIVED');
        } else {
            $html .= '<p style="text-align:left; font-weight:normal;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
        }
        $html .= '</p>';
        $html .= '<div style="height:20px;">';
        $html .= '<input style="float:right;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('COMMON_DELETE_BUTTON') . '" tabindex="2"/>';
        $html .= '<input style="float:left;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('COMMON_CANCEL_BUTTON') . '" tabindex="2"/>';
        if ($type != 'portal'
            and !$archived_room
        ) {
            $html .= '<input style="float:left;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('ROOM_ARCHIV_BUTTON') . '" tabindex="2"/>';
        }
        $html .= '</div>';
        $html .= '</form>';
        $html .= '</div>';
        $html .= '</center>';
        $html .= '</div>';
        $html .= '<div id="delete" style="position: absolute; z-index:90; top:-3px; left:-3px; width:' . $width . '; height: 300px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">' . LF;
        $html .= '</div>';
        return $html;
    }

    // from room_view
    public function getDeleteBoxAsHTML2()
    {
        $session = $this->_environment->getSession();
        $left_menue_status = $session->getValue('left_menue_status');
        $left = '0em';
        $width = '100%';
        $html = '<div style="position: absolute; z-index:1000; top:95px; left:' . $left . '; width:' . $width . '; height: 100%;">' . LF;
        $html .= '<center>';
        $html .= '<div style="position:fixed; left:' . $left . '; z-index:1000; margin-top:10px; margin-left: 30%; width:400px; padding:20px; background-color:#FFF; border:2px solid red;">';
        $html .= '<form style="margin-bottom:50px;" method="post" action="' . $this->_delete_box_action_url . '">' . LF;
        foreach ($this->_delete_box_hidden_values as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '"/>' . LF;
        }

        if ($this->_delete_box_mode == 'index') {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_TITLE');
            $html .= '</h2>' . LF;
            $count = 0;
            if ($this->_delete_box_ids) {
                $count = count($this->_delete_box_ids);
            }
            if ($count == 1) {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION_ONE_ENTRY', $count);
                $html .= '</p>' . LF;
            } else {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_INDEX_DESCRIPTION', $count);
                $html .= '</p>' . LF;
            }
        } elseif ($this->_environment->getCurrentFunction() == 'preferences'
            or
            ($this->_environment->getCurrentModule() == 'project'
                and $this->_environment->getCurrentFunction() == 'detail'
            )
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'material'
            and $this->_environment->getCurrentFunction() == 'detail'
            and (isset($_GET['del_version'])
                and (!empty($_GET['del_version'])
                    or $_GET['del_version'] == 0
                )
            )
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_VERSION_TITLE_MATERIAL_VERSION');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MATERIAL_VERSION');
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'configuration'
            and $this->_environment->getCurrentFunction() == 'wiki'
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_WIKI_TITLE');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_WIKI');
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'configuration'
            and $this->_environment->getCurrentFunction() == 'wordpress'
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_WORDPRESS_TITLE');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_WORDPRESS');
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'configuration'
            and ($this->_environment->getCurrentFunction() == 'room_options'
                or $this->_environment->getCurrentFunction() == 'account_options'
            )
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE_ROOM');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_ROOM');
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'account'
        ) {
            $html .= '<h2>' . $this->_translator->getMessage('USER_CLOSE_FORM');
            $html .= '</h2>' . LF;

            // datenschutz: overwrite or not (03.09.2012 IJ)
            $overwrite = true;
            global $symfonyContainer;
            $disable_overwrite = $symfonyContainer->getParameter('commsy.security.privacy_disable_overwriting');
            if (!empty($disable_overwrite) and $disable_overwrite) {
                $overwrite = false;
            }
            if ($overwrite) {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('USER_DELETE_FORM_DESCRIPTION');
            } else {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('USER_DELETE_FORM_DESCRIPTION_NOT_OVERWRITE');
            }
            $html .= '</p>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'group'
            and $this->_environment->getCurrentFunction() == 'detail'
        ) {
            $iid = $this->_environment->getValueOfParameter('iid');
            $group_manager = $this->_environment->getGroupManager();
            $group_item = $group_manager->getItem($iid);
            if ($group_item->isGroupRoomActivated()) {
                $title = $this->_translator->getMessage('COMMON_DELETE_GROUP_WITH_ROOM_TITLE');
                $desc = $this->_translator->getMessage('COMMON_DELETE_GROUP_WITH_ROOM_DESC');
                $desc .= BRLF . BRLF . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
            } else {
                $title = $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE');
                $desc = $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
            }
            $html .= '<h2>' . $title . '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $desc . '</p>' . LF;
            $user_item = $this->_environment->getCurrentUserItem();
            if ($user_item->isModerator()) {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
                $html .= '</p>' . LF;
            }
        } else {
            $user_item = $this->_environment->getCurrentUserItem();
            $html .= '<h2>' . $this->_translator->getMessage('COMMON_DELETE_BOX_TITLE');
            $html .= '</h2>' . LF;
            $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION');
            $html .= '</p>' . LF;
            if ($user_item->isModerator()) {
                $html .= '<p style="text-align:left;">' . $this->_translator->getMessage('COMMON_DELETE_BOX_DESCRIPTION_MODERATOR');
                $html .= '</p>' . LF;
            }
        }
        $html .= '<div>' . LF;
        $html .= '<input style="float:right;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('COMMON_DELETE_BUTTON') . '" tabindex="2"/>' . LF;
        $html .= '<input style="float:left;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('COMMON_CANCEL_BUTTON') . '" tabindex="2"/>' . LF;
        if (($this->_environment->getCurrentModule() == 'configuration'
                and ($this->_environment->getCurrentFunction() == 'preferences'
                    or $this->_environment->getCurrentFunction() == 'room_options'
                    or $this->_environment->getCurrentFunction() == 'account_options'
                )
            )
            or
            ($this->_environment->getCurrentModule() == 'project'
                and $this->_environment->getCurrentFunction() == 'detail'
            )
        ) {
            $html .= '<input style="float:left;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('ROOM_ARCHIV_BUTTON') . '" tabindex="2"/>' . LF;
        } elseif ($this->_environment->getCurrentModule() == 'account') {
            $html .= '<input style="float:left;" type="submit" name="delete_option" value="' . $this->_translator->getMessage('COMMON_USER_REJECT_BUTTON') . '" tabindex="2"/>' . LF;
        }
        $html .= '</div>' . LF;
        $html .= '</form>' . LF;
        $html .= '</div>' . LF;
        $html .= '</center>' . LF;
        $html .= '</div>' . LF;
        $html .= '<div id="delete" style="position: absolute; z-index:900; top:95px; left:' . $left . '; width:' . $width . '; height: 100%; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">';
        $html .= '</div>' . LF;
        return $html;
    }

    public function addDeleteBox($url, $mode = 'detail', $selected_ids = NULL)
    {
        $this->_with_delete_box = true;
        $this->_delete_box_action_url = $url;
        $this->_delete_box_mode = $mode;
        $this->_delete_box_ids = $selected_ids;
    }

    public function _getRoomItemAsHTML($item)
    {
        $html = '';
        $html .= LF . '<!-- BEGIN TABS -->' . LF;
        $html .= '<div class="welcome_frame" style="width:100%; margin-bottom:5px;">' . LF;
        $html .= '<div class="content_without_fader">';
        $html .= '<div style="margin:0px; padding:0px 0px; width:100%;">' . "\n";

        $html .= '<table style="border-collapse:collapse; border:0px solid black; margin-left:5px; margin-right:5px;" summary="Layout">' . LF;
        $html .= '<tr>' . LF;
        $html .= '<td colspan="6" style="width:100%; border-bottom:1px solid #B0B0B0;">' . LF;
        $html .= '<div style="float:right; padding-top:10px;">' . LF;

        // actions
        $current_context = $this->_environment->getCurrentContextItem();
        $current_user = $this->_environment->getCurrentUser();
        if (!$item->isDeleted() and !$item->isPrivateRoom() and !$item->isGroupRoom()) {
            $params = array();
            $params['iid'] = $item->getItemID();
            if (($current_user->isModerator() or $item->mayEdit($current_user)) and $this->_with_modifying_actions) {
                $params = array();
                $params['iid'] = $item->getItemID();
                $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'common', $params, $this->_translator->getMessage('PORTAL_EDIT_ROOM'), '', '', '', '', '', '', 'class="portal_link"') . BRLF;
                unset($params);
                $params = $this->_environment->getCurrentParameterArray();
                $params['iid'] = $item->getItemID();
                $params['room_id'] = $item->getItemID();
                $params['action'] = 'delete';
                $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(),
                        $this->_environment->getCurrentModule(),
                        'index',
                        $params,
                        $this->_translator->getMessage('COMMON_DELETE_ROOM'),
                        '', '', '', '', '', '', 'class="portal_link"') . LF;
                unset($params);
            } else {
                $html .= '<span class="disabled">> ' . $this->_translator->getMessage('PORTAL_EDIT_ROOM') . '</span>' . BRLF;
                $html .= '<span class="disabled">> ' . $this->_translator->getMessage('COMMON_DELETE_ROOM') . '</span>' . LF;
            }
            $html .= BRLF;

            if ($current_user->isModerator()
                and $this->_with_modifying_actions
                and !$item->isLocked()
            ) {
                $params['iid'] = $item->getItemID();
                $params['automatic'] = 'lock';
                $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_LOCK'), '', '', '', '', '', '', 'class="portal_link"') . BRLF;
                unset($params);
            } elseif ($current_user->isModerator()
                and $this->_with_modifying_actions
                and $item->isLocked()
            ) {
                $params = array();
                $params['automatic'] = 'unlock';
                $params['iid'] = $item->getItemID();
                $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_UNLOCK'), '', '', '', '', '', '', 'class="portal_link"') . BRLF;
                unset($params);
            }
            if ($current_user->isModerator()
                and $this->_with_modifying_actions
                and !$item->isClosed()
                and !$item->isTemplate()
            ) {
                $params = array();
                $params['iid'] = $item->getItemID();
                if ($item->isLocked()
                    and $item->isArchived()
                ) {
                    $params['automatic'] = 'open';
                    $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_OPEN'), '', '', '', '', '', '', 'class="portal_link"') . LF;
                } else {
                    $params['automatic'] = 'archive';
                    $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_ARCHIVE'), '', '', '', '', '', '', 'class="portal_link"') . LF;
                }
                unset($params);
            } elseif ($current_user->isModerator()
                and $this->_with_modifying_actions
                and $item->isClosed()
            ) {
                $params = array();
                $params['iid'] = $item->getItemID();
                $params['automatic'] = 'open';
                $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_OPEN'), '', '', '', '', '', '', 'class="portal_link"') . LF;
                unset($params);
            }
            $server_item = $this->_environment->getServerItem();
            $portal_list = $server_item->getPortalList();
            if ($portal_list->getCount() > 1 and !$item->isGroupRoom()) {
                if ($current_user->isModerator()
                    and $this->_with_modifying_actions
                    and !$item->isLockedForMove()) {
                    $params = array();
                    $params['iid'] = $item->getItemID();
                    $html .= BR . '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'move', $params, $this->_translator->getMessage('PORTAL_MOVE_ROOM'), '', '', '', '', '', '', 'class="portal_link"') . LF;
                    unset($params);
                } elseif ($current_user->isModerator()
                    and $this->_with_modifying_actions
                    and $item->isLockedForMove()) {
                    $html .= BR . '<span class="disabled">> ' . $this->_translator->getMessage('PORTAL_MOVE_ROOM') . '</span>' . LF;
                }
            }

            if ($current_user->isRoot()
                and $this->_with_modifying_actions
            ) {
                $params = array();
                $params['iid'] = $item->getItemID();
                $html .= BR . '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'export', $params, $this->_translator->getMessage('PORTAL_EXPORT_ROOM'), '', '', '', '', '', '', 'class="portal_link"') . LF;
                unset($params);
            }
        } elseif ($current_user->isRoot()) {
            $params = array();
            $params['iid'] = $item->getItemID();
            $params['automatic'] = 'undelete';
            $html .= '> ' . ahref_curl($this->_environment->getCurrentContextID(), 'configuration', 'room', $params, $this->_translator->getMessage('CONTEXT_ROOM_UNDELETE'), '', '', '', '', '', '', 'class="portal_link"') . LF;
            unset($params);
        }
        // end actions

        $html .= '</div>' . LF;
        $html .= '<div style="width:70%;">' . LF;
        $html .= $this->_getRoomHeaderAsHTML($item);
        $html .= '</div>' . LF;
        $html .= '</td>' . LF;
        $html .= '</tr>' . LF;
        $html .= '<tr>' . LF;
        $mode = '';
        if (isset($_GET['account'])) {
            $mode = $_GET['account'];
        }
        if (empty($mode)) {
            $html .= '<td style="width:1%; vertical-align:middle;">' . LF;
            $html .= '<img src="' . $this->_style_image_path . 'portal_key.gif" alt="" border="0"/>';
            $html .= '</td>' . LF;
            $html .= '<td style="width:26%; vertical-align:middle;">' . LF;
            $html .= '<span class="search_title">' . $this->_translator->getMessage('COMMON_ACCESS_POINT') . ':' . '</span>';
            $html .= '</td>' . LF;

            $html .= '<td style="width:1%; vertical-align:middle;">' . LF;
            $html .= '<img src="' . $this->_style_image_path . 'portal_info.gif" alt="" border="0"/>' . LF;
            $html .= '</td>' . LF;
            $html .= '<td style="width:42%; vertical-align:middle;">' . LF;
            $html .= '<span class="search_title">' . $this->_translator->getMessage('COMMON_DESCRIPTION') . ':' . '</span>';
            $html .= '</td>' . LF;

            $html .= '<td style="width:1%; vertical-align:middle;">' . LF;
            $html .= '<img src="' . $this->_style_image_path . 'portal_info2.gif" alt="" border="0"/>' . LF;
            $html .= '</td>' . LF;
            $html .= '<td style="width:29%; vertical-align:middle;">' . LF;
            $html .= '<span class="search_title">' . $this->_translator->getMessage('COMMON_FACTS') . ':' . '</span>';
            $html .= '</td>' . LF;
        } else {
            $html .= '<td colspan="4" rowspan="2" style="width:71%; vertical-align:top; font-weight:normal;">' . LF;
            $html .= $this->_getRoomForm($item, $mode);
            $html .= '</td>' . LF;

            $html .= '<td style="width:1%; vertical-align:top;">' . LF;
            $html .= '<img src="' . $this->_style_image_path . 'portal_info2.gif" alt="" border="0"/>' . LF;
            $html .= '</td>' . LF;
            $html .= '<td style="width:29%; vertical-align:top; padding-top:10px;">' . LF;
            $html .= '<span class="search_title">' . $this->_translator->getMessage('COMMON_FACTS') . ':' . '</span>';
            $html .= '</td>' . LF;
        }


        $html .= '</tr>' . LF;
        $html .= '<tr>' . LF;
        if (empty($mode)) {
            $html .= '<td>' . LF;
            $html .= '</td>' . LF;
            $html .= '<td style="vertical-align:top; font-weight:normal;">' . LF;
            $html .= $this->_getRoomAccessAsHTML($item);
            $html .= '</td>' . LF;

            $html .= '<td>' . LF;
            $html .= '</td>' . LF;
            $html .= '<td style="font-weight:normal; font-size:8pt; vertical-align:top;">' . LF;
            $desc = $item->getDescription();
            if (!empty($desc)) {
                $html .= $this->_text_as_html_long($this->_cleanDataFromTextArea($item->getDescription()));
            } else {
                $html .= '<span class="disabled">' . $this->_translator->getMessage('COMMON_NO_DESCRIPTION') . '</span>' . LF;
            }
            $html .= '</td>' . LF;
        }

        $html .= '<td>' . LF;
        $html .= '</td>' . LF;
        $html .= '<td style="vertical-align:top;">' . LF;
        $html .= $this->_getRoomFacts($item);
        $html .= '</td>' . LF;

        $html .= '</tr>' . LF;
        $html .= '</table>' . LF;

        $html .= '</div>' . "\n";

        $html .= '</div>' . LF;
        if ($this->_with_delete_box) {
            if (isset($item)
                and $item->isArchived()
            ) {
                $html .= $this->getDeleteBoxAsHTML('room', true);
            } else {
                $html .= $this->getDeleteBoxAsHTML();
            }
        }
        $html .= '</div>' . LF;
        return $html;
    }

    /** get the header as HTML
     * this method returns the commsy header as HTML - internal, do not use
     *
     * @return string header as HTML
     */
    public function _getRoomHeaderAsHTML($item)
    {
        $html = LF . '<!-- BEGIN HEADER -->' . LF;
        // title
        $html .= '<table style=" width:100%; padding:0px; margin:0px;" summary="Layout">';
        $html .= '<tr>';
        $html .= '<td style="width: 1%; vertical-align:bottom;">';
        $logo_filename = $item->getLogoFilename();
        $current_user = $this->_environment->getCurrentUserItem();
        if (!empty($logo_filename)) {
            $params = array();
            $params['picture'] = $item->getLogoFilename();
            $curl = curl($item->getItemID(), 'picture', 'getfile', $params, '');
            unset($params);
            $html .= '      <img class="logo" style="height:48px;" src="' . $curl . '" alt="' . $this->_translator->getMessage('LOGO') . '" border="0"/>';
        }
        $html .= '</td>';
        // logo
        $html .= '<td style="width: 99%; vertical-align:middle; padding-left:10px; padding-top:12px; padding-bottom:13px; padding-right:0px; text-align:left;">';
        $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: bold;">';
        if (!$item->isPrivateRoom()) {
            $html .= $this->_text_as_html_short($item->getTitle());
        } else {
            $owner = $item->getOwnerUserItem();
            if (!empty($owner)) {
                $html .= $this->_text_as_html_short($this->_translator->getMessage('PRIVATE_ROOM_TITLE') . ' ' . $owner->getFullname());
            }
            unset($owner);
        }
        $html .= '</span>' . LF;
        if ($item->isDeleted()) {
            $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
            $html .= ' (' . $this->_translator->getMessage('ROOM_STATUS_DELETED') . ')';
            $html .= '</span>' . LF;
        } elseif ($item->isLocked()) {
            $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
            $html .= ' (' . $this->_translator->getMessage('PROJECTROOM_LOCKED') . ')' . LF;
            $html .= '</span>' . LF;
        } elseif ($item->isProjectroom() and $item->isTemplate()) {
            $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
            $html .= ' (' . $this->_translator->getMessage('PROJECTROOM_TEMPLATE') . ')' . LF;
            $html .= '</span>' . LF;
        } elseif ($item->isClosed()) {
            $html .= '      <span style="padding-bottom:0px; font-size: 22px; font-weight: normal;">';
            $html .= ' (' . $this->_translator->getMessage('PROJECTROOM_CLOSED') . ')' . LF;
            $html .= '</span>' . LF;
        }
        $html .= '</td>';
        $html .= '</tr>';
        $html .= '</table>';
        $html .= '<!-- END HEADER -->' . LF;
        return $html;
    }

    private function _getCommSyBarBeforeContentAsHTML()
    {
        $html = "";

        $currentUser = $this->_environment->getCurrentUserItem();
        $translator = $this->_environment->getTranslationObject();

        $shib_logout_url = '';
        $shib_logout_flag = false;
        // shibboleth get logout information
        if (!$currentUser->isGuest()) {
            $auth_source_manager = $this->_environment->getAuthSourceManager();
            $current_auth_source = $auth_source_manager->getItem($currentUser->getAuthSource());
            if ($current_auth_source->getSourceType() == 'Shibboleth') {
                $shib_logout_url = $current_auth_source->getShibbolethSessionLogout();
                $shib_logout_flag = true;
            }
        }

        if ($this->_environment->InPortal() && !$currentUser->isGuest()) {
            $html .= '
   			<div id="top_menu">
   				<div id="tm_wrapper_outer">
   					<div id="tm_wrapper">
						<div id="tm_icons_bar">
   		';

            if (!$currentUser->isReallyGuest()) {
                if ($shib_logout_flag) {
                    $html .= '
   							<a href="' . $shib_logout_url . '" id="tm_logout" title="' . $translator->getMessage("LOGOUT") . '">
   								&nbsp;
   							</a>
   				';
                } else {
                    $html .= '
   							<a href="room/' . $this->_environment->getCurrentContextID() . '/logout" id="tm_logout" title="' . $translator->getMessage("LOGOUT") . '">
   								&nbsp;
   							</a>
   				';
                }

            } else {
                $html .= '
   							<a href="commsy.php?cid=' . $this->_environment->getCurrentPortalID() . '&mod=home&fct=index&room_id=' . $this->_environment->getCurrentContextID() . '&login_redirect=1" class="tm_user" style="width:70px;" title="' . $translator->getMessage("MYAREA_LOGIN_BUTTON") . '">
   								' . $translator->getMessage("MYAREA_LOGIN_BUTTON") . '
   							</a>
   			';
            }

            $html .= '
   							<div class="clear"></div>
   						</div>
   						
   						<div id="tm_pers_bar">
   							<span id="tm_user">
   		';

            if (!$currentUser->isReallyGuest() && !$currentUser->isRoot()) {
                $html .= '<a id="tm_user" href="/room/' . $currentUser->getOwnRoom()->getItemId() . '/user/' . $currentUser->getRelatedPrivateRoomUserItem()->getItemId() . '/personal">' . $translator->getMessage("COMMON_WELCOME") . ", " . mb_substr($currentUser->getFullName(), 0, 20) . '</a>';
            } else {
                $html .= $translator->getMessage("COMMON_WELCOME") . ", " . $translator->getMessage("COMMON_GUEST");
            }

            $html .= '
   							</span>
   						</div>
   		';

            $ownRoomItem = $currentUser->getOwnRoom();
            if (isset($ownRoomItem)) {
                $html .= '	<div id="tm_breadcrumb_old">';
                $retour = '';
                $retour .= '   <form style="margin:0px; padding:0px;" method="post" action="' . curl($this->_environment->getCurrentContextID(), 'room', 'change', '') . '" name="room_change_bar">' . LF;
                $retour .= '         <select onchange="document.room_change_bar.submit()" size="1" style="font-size:8pt; width:220px;" name="room_id" id="submit_form">' . LF;
                $context_array = array();
                $context_array = $this->_getAllOpenContextsForCurrentUser();
                $current_portal = $this->_environment->getCurrentPortalItem();
                $translator = $this->_environment->getTranslationObject();
                $text_converter = $this->_environment->getTextConverter();
                if (!$this->_environment->inServer()) {
                    $title = $this->_environment->getCurrentPortalItem()->getTitle();
                    $title .= ' (' . $translator->getMessage('COMMON_PORTAL') . ')';
                    $additional = '';
                    if ($this->_environment->inPortal()) {
                        $additional = 'selected="selected"';
                    }
                    $retour .= '            <option value="' . $this->_environment->getCurrentPortalID() . '" ' . $additional . '>' . $title . '</option>' . LF;

                    $current_portal_item = $this->_environment->getCurrentPortalItem();
                    if ($current_portal_item->showAllwaysPrivateRoomLink()) {
                        $link_active = true;
                    } else {
                        $current_user_item = $this->_environment->getCurrentUserItem();
                        if ($current_user_item->isRoomMember()) {
                            $link_active = true;
                        } else {
                            $link_active = false;
                        }
                        unset($current_user_item);
                    }
                    unset($current_portal_item);

                }

                $first_time = true;
                foreach ($context_array as $con) {
                    $title = $text_converter->text_as_html_short($con['title']);
                    $additional = '';
                    if (isset($con['selected']) and $con['selected']) {
                        $additional = ' selected="selected"';
                    }
                    if ($con['item_id'] == -1) {
                        $additional = ' class="disabled" disabled="disabled"';
                        if (!empty($con['title'])) {
                            $title = '----' . $text_converter->text_as_html_short($con['title']) . '----';
                        } else {
                            $title = '&nbsp;';
                        }
                    }
                    if ($con['item_id'] == -2) {
                        $additional = ' class="disabled" disabled="disabled" style="font-style:italic;"';
                        if (!empty($con['title'])) {
                            $title = $text_converter->text_as_html_short($con['title']);
                        } else {
                            $title = '&nbsp;';
                        }
                        $con['item_id'] = -1;
                        if ($first_time) {
                            $first_time = false;
                        } else {
                            $retour .= '            <option value="' . $con['item_id'] . '"' . $additional . '>&nbsp;</option>' . LF;
                        }
                    }
                    $retour .= '            <option value="' . $con['item_id'] . '"' . $additional . '>' . $title . '</option>' . LF;
                }

                $current_user_item = $this->_environment->getCurrentUserItem();
                if (!$current_user_item->isUser() and $current_user_item->getUserID() != "guest") {
                    $context = $this->_environment->getCurrentContextItem();
                    if (!empty($context_array)) {
                        $retour .= '            <option value="-1" class="disabled" disabled="disabled">&nbsp;</option>' . LF;
                    }
                    $retour .= '            <option value="-1" class="disabled" disabled="disabled">----' . $translator->getMessage('MYAREA_CONTEXT_GUEST_IN') . '----</option>' . LF;
                    $retour .= '            <option value="' . $context->getItemID() . '" selected="selected">' . $context->getTitle() . '</option>' . "\n";
                }
                $retour .= '         </select>' . LF;
                $retour .= '   </form>' . LF;
                unset($context_array);

                $html .= $retour;
                $html .= '	</div>';
            }

            if ($currentUser->isModerator()) {
                $html .= '
   						<div id="tm_icons_left_bar">
   							<a href="commsy.php?cid=' . $this->_environment->getCurrentContextID() . '&mod=configuration&fct=index" id="tm_settings" title="' . $translator->getMessage("COMMON_CONFIGURATION") . '">&nbsp;</a>
   			';

                $html .= '
   							<div class="clear"></div>
   						</div>
   				';
            }

            $html .= '
   						<div class="clear"></div>
   					</div>
   				</div>
   			</div>
   		';
        }

        return $html;
    }



    /** Get page view as HTML.
     * This method returns the page view in HTML code.
     * @return string page view as HMTL
     * @author CommSy Development Group
     */
    public function asHTML()
    {
        $lang = $this->_getDisplayLanguage();

        $html = <<<HTML
<!doctype html>
<html lang="$lang">
{$this->_getHTMLHeadAsHTML()}

{$this->_getBodyAsHTML()}
</html>
HTML;

        return $html;
    }


    /** Get the display language of the page view as a two-letter language identifier.
     * @return string the page 's display language
     * @author CommSy Development Group
     */
    public function _getDisplayLanguage()
    {
        $lang = $this->_defaultDisplayLanguage;

        $selectedLanguage = $this->_environment->getSelectedLanguage();
        if (in_array($selectedLanguage, $this->_availableDisplayLanguages)) {
            $lang = $selectedLanguage;
        }

        return $lang;
    }


    /** Get the HTML head element as HTML.
     * @return string HTML head element as HTML
     * @author CommSy Development Group
     */
    public function _getHTMLHeadAsHTML()
    {
        $siteShortTitle = "AGORA";

        $html = <<<HTML
  <head>
{$this->_getMetaAsHTML()}
    
{$this->_getCSSAsHTML()}
    
    <title>$siteShortTitle - CommSy Login</title>
  </head>
HTML;

        return $html;
    }


    /** Get the HTML body element as HTML.
     * @return string HTML body element as HTML
     * @author CommSy Development Group
     */
    public function _getBodyAsHTML()
    {
        $html = <<<HTML
  <body>
{$this->_getHeaderAsHTML()}

{$this->_getSiteInfoAsHTML()}

{$this->_getMainNavigationAsHTML()}

{$this->_getContentAsHTML()}

{$this->_getFooterAsHTML()}

{$this->_getJavaScriptAsHTML()}
  </body>
HTML;

        return $html;
    }


    /** Get the meta tags to be included within the HTML head as HTML.
     * @return string meta tags as HTML
     * @author CommSy Development Group
     */
    public function _getMetaAsHTML()
    {
        $html = <<<HTML
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
HTML;

        return $html;
    }


    /** Get the CSS specifications to be included within the HTML head as HTML.
     * @return string CSS specifications as HTML
     * @author CommSy Development Group
     */
    public function _getCSSAsHTML()
    {
        // TODO: use a local bootstrap dist file

        $portalID = $this->_environment->getCurrentPortalID();

        $html = <<<HTML
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/external_portal_styles/$portalID/css/custom.css">
HTML;

        return $html;
    }


    /** Get the visible page header as HTML.
     * @return string visible page header as HTML
     * @author CommSy Development Group
     */
    public function _getHeaderAsHTML()
    {
        // TODO: localize strings, or accept an array of nav-link definitions as input
        $corporationURL = "https://www.uni-hamburg.de/";
        $corporationShortTitle = "UHH";
        $corporationTitle = "Universität Hamburg";
        $loginTitle = "Login";
        $altPageTitle = "English";

        $html = <<<HTML
    <!-- Header -->
    <div class="container-fluid container-topnav">
      <div class="container">
        <!-- Top Navigation -->
        <ul class="nav d-flex">
          <li class="nav-item p-1 first d-block d-md-none">
            <a class="nav-link" href="$corporationURL" title="$corporationTitle">$corporationShortTitle</a>
          </li>
          <li class="nav-item p-1 second">
            <a class="nav-link active" href="#">$loginTitle</a>
          </li>
          <li class="nav-item ml-auto p-1 last">
            <a class="nav-link" href="index-en.html">$altPageTitle</a>
          </li>
        </ul>
      </div>
    </div>
HTML;

        return $html;
    }


    /** Get the site info as HTML.
     * @return string site info as HTML
     * @author CommSy Development Group
     */
    public function _getSiteInfoAsHTML()
    {
        $portalID = $this->_environment->getCurrentPortalID();

        // TODO: localize strings/URLs
        $corporationTitle = "Uni Hamburg";
        $corporationLogoFileName = "logo-uhh.svg";
        $corporationLogoURL = "css/external_portal_styles/" . $portalID . "/img/" . $corporationLogoFileName;

        $sitePage = "Startseite";
        $siteShortTitle = "AGORA";
        $siteTitle = "ePlattform der Fakultät für Geisteswissenschaften";
        $siteURL = "https://www.agora.uni-hamburg.de/";
        $siteLogoFileName = "logo-agora--de.png";
        $siteLogoURL = "css/external_portal_styles/" . $portalID . "/img/" . $siteLogoFileName;

        $html = <<<HTML
    <!-- Site name + Slogan -->
    <div class="container container-sitename d-block d-md-none">
      <h1><a href="$siteURL" title="$siteShortTitle $sitePage">$siteShortTitle</a></h1>
      <p>$siteTitle</p>
    </div>

    <!-- Logos -->
    <div class="container container-logos d-none d-md-block">
      <div class="d-flex justify-content-between">
        <span class="logo-uhh"><img src="$corporationLogoURL" alt="Logo $corporationTitle" /></span>
        <span class="logo-agora"><img src="$siteLogoURL" alt="$siteShortTitle-Logo" /></span>
      </div>
    </div>
HTML;

        return $html;
    }


    /** Get the main site navigation as HTML.
     * @return string main navigation as HTML
     * @author CommSy Development Group
     */
    public function _getMainNavigationAsHTML()
    {
        // TODO: extract & localize strings/URLs
        $siteURL = "https://www.agora.uni-hamburg.de/";
        $loginTitle = "Login";

        $html = <<<HTML
    <!-- Main Navigation -->
    <div class="container container-mainnav">
      <ul class="nav">
        <li class="nav-item first">
          <a class="nav-link" href="$siteURL">Start<span class="d-none d-sm-inline">seite</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="#">$loginTitle</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="https://www.agora.uni-hamburg.de/hilfe-bei-der-nutzung">Hilfe<span class="d-none d-md-inline"> bei der Nutzung</span></a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="https://www.agora.uni-hamburg.de/ueber-agora">Über <span class="d-none d-sm-inline">AGORA</span></a>
        </li>
        <li class="nav-item last">
          <a class="nav-link" href="https://www.agora.uni-hamburg.de/kontakt">Kontakt</a>
        </li>
      </ul>
    </div>
HTML;

        return $html;
    }


    /** Get the main body content as HTML.
     * @return string main body content as HTML
     * @author CommSy Development Group
     */
    public function _getContentAsHTML()
    {
        // TODO: extract & localize strings/URLs
        // TODO: implement CommSy login functionality
        // TODO: should we honor `$currentPortal->showAuthAtLogin()`?
        // TODO: implement account_forget/password_forget functionality?
        // TODO: fetch "Secondary Content"

        $portalID = $this->_environment->getCurrentPortalID();
        $formActionURL = "?cid=" . $portalID . "&amp;mod=context&amp;fct=login";

        $html = <<<HTML
    <!-- Content -->
    <div class="container container-content">
      <div class="row">
        <!-- Main Content -->
        <div class="col-md-7">
          <h2 class="text-uppercase">AGORA-Login</h2>
          <!-- Login -->
          <form id="commsy-login" method="post" action="$formActionURL" name="login">
            <div class="form-group row">
              <label for="inputUsername" class="col-sm-2 col-form-label">Kennung</label> 
              <div class="col-sm-10">
                <input type="text" class="form-control" id="inputUsername" name="user_id" placeholder="Kennung" required>
                <small id="usernameHelpBlock" class="form-text text-muted"><a href="https://www.agoracommsy.uni-hamburg.de/?cid=651782&mod=home&fct=index&cs_modus=account_forget">Kennung vergessen?</a></small> 
              </div>
            </div>
            <div class="form-group row">
              <label for="inputPassword" class="col-sm-2 col-form-label">Passwort</label> 
              <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword" name="password" placeholder="Passwort" required>
                <small id="passwordHelpBlock" class="form-text text-muted"><a href="https://www.agoracommsy.uni-hamburg.de/?cid=651782&mod=home&fct=index&cs_modus=password_forget">Passwort vergessen?</a></small> 
              </div>
            </div>
{$this->_getAuthSourcesAsHTML()}
            <div class="form-group row">
              <div class="col-sm-10">
                <button type="submit" class="btn btn-primary" name="option">Anmelden</button> 
              </div>
            </div>
          </form>
        </div>
        <!-- Secondary Content -->
        <div class="col-md-4 offset-md-1">
          <h2 class="text-uppercase">Hinweise</h2>
          <p>At vero eos et accusam et justo duo dolores et ea rebum. Stet clita kasd gubergren, no sea takimata sanctus est Lorem ipsum dolor sit amet.</p>
        </div>
      </div>
    </div>
HTML;

        return $html;
    }


    /** Get the "auth_sources" form element(s) to be included within the CommSy login form as HTML.
     * @return string "auth_sources" form elements as HTML
     * @author CommSy Development Group
     */
    public function _getAuthSourcesAsHTML()
    {
        // TODO: support Shibboleth login

        $currentPortal = $this->_environment->getCurrentPortalItem();
        $authSourceList = $currentPortal->getAuthSourceListEnabled();

        if (!isset($authSourceList) || $authSourceList->isEmpty()) {
            return '';
        }

        $authSourceItem = $authSourceList->getFirst();

        // single auth source
        if ($authSourceList->getCount() == 1) {
            $authSourceID = $authSourceItem->getItemID();

            $html = <<<HTML
            <input type="hidden" name="auth_source" value="$authSourceID"/>
HTML;
            return $html;
        }

        // multiple auth sources
        $html = <<<HTML
            <fieldset class="form-group">
              <div class="row">
                <legend class="col-form-label col-sm-2 pt-0">Quelle</legend> 
                <div class="col-sm-10">
HTML;

        $defaultAuthSourceID = $this->_getDefaultAuthSourceID();
        $i = 0;

        while ($authSourceItem) {
            ++$i;
            $authSourceID = $authSourceItem->getItemID();
            $authSourceName = $authSourceItem->getTitle();
            $authSourceDefault = ($authSourceID == $defaultAuthSourceID ? ' checked' : '');

            $html .= LF . <<<HTML
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="auth_source" id="radioSource{$i}" value="$authSourceID"$authSourceDefault>
                    <label class="form-check-label" for="radioSource{$i}">$authSourceName</label> 
                  </div>
HTML;
            $authSourceItem = $authSourceList->getNext();
        }

        $html .= LF . <<<HTML
                </div>
              </div>
            </fieldset>
HTML;

        return $html;
    }


    /** Get the ID of the default authentication source.
     * @return integer default auth source ID
     * @author CommSy Development Group
     */
    public function _getDefaultAuthSourceID()
    {
        // TODO: can we assume 0 to mean "no default auth source" given?
        $id = 0; // no default auth source

        $currentPortal = $this->_environment->getCurrentPortalItem();
        $portalDefaultID = $currentPortal->getAuthDefault();

        if (isset($_GET['auth_source']) && !empty($_GET['auth_source'])) {
            $id = $_GET['auth_source'];

        } elseif (isset($portalDefaultID) && !empty($portalDefaultID)) {
            $id = $portalDefaultID;
        }

        return $id;
    }


    /** Get the visible page footer as HTML.
     * @return string visible page footer as HTML
     * @author CommSy Development Group
     */
    public function _getFooterAsHTML()
    {
        // TODO: extract & localize strings/URLs
        $sitePage = "Startseite";
        $siteShortTitle = "AGORA";
        $siteTitle = "ePlattform der Fakultät für Geisteswissenschaften";
        $siteURL = "https://www.agora.uni-hamburg.de/";
        $siteEmail = "agora@uni-hamburg.de";
        $loginTitle = "Login";

        $html = <<<HTML
    <!-- Footer -->
    <footer class="container-fluid">
      <div class="container">
        <!-- Footer Navigation -->
        <ul class="nav justify-content-center">
          <li class="nav-item">
            <a class="nav-link active" href="$siteURL">$sitePage</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="#">$loginTitle</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://www.agora.uni-hamburg.de/hilfe-bei-der-nutzung">Hilfe bei der Nutzung</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://www.agora.uni-hamburg.de/ueber-agora">Über AGORA</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://www.agora.uni-hamburg.de/kontakt">Kontakt</a>
          </li>
        </ul>
        <div class="text-center">
          <p>$siteShortTitle · $siteTitle · $siteEmail</p>
        </div>
      </div>
    </footer>
HTML;

        return $html;
    }


    /** Get the JavaScript specifications to be included within the HTML body as HTML.
     * @return string JavaScript specifications as HTML
     * @author CommSy Development Group
     */
    public function _getJavaScriptAsHTML()
    {
        // TODO: remove JavaScript if unnecessary

        $html = <<<HTML
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <!-- <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script> -->
    <!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script> -->
    <!-- <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script> -->
HTML;

        return $html;
    }



    public function asHTMLOLD()
    {

        $html = '';
        $session = $this->_environment->getSession();
        if (!empty($session)) {
            $session_id = $session->getSessionID();
        } else {
            $session_id = '';
        }
        // Header
        $html .= $this->_getHTMLHeadAsHTML();
        // Body
        if (!$this->_blank_page) {
            $html .= '<body class="tundra"';
            if (($this->_focus_onload) or ($this->_with_delete_box)) {
                $html .= ' onload="';
                if ($this->_focus_onload) {
                    $html .= ' window.focus();setfocus();';
                }
                if ($this->_with_delete_box) {
                    $html .= ' initDeleteLayer();';
                }
                $html .= ' "';
            }
            $views = array_merge($this->_views, $this->_views_left, $this->_views_right, $this->_views_overlay);
            if (isset($this->_form_view)) {
                $views[] = $this->_form_view;
            }
            $view = reset($views);
            while ($view) {
                $html .= $view->getInfoForBodyAsHTML();
                $view = next($views);
            }
            $html .= '>' . LF;

            /* CommSy Bar */
            $html .= $this->_getCommSyBarBeforeContentAsHTML();

            $html .= $this->_getPluginInfosForBeforeContentAsHTML();
            if ($this->_show_agbs) {
                $html .= $this->_getAGBTextAsHTML();
            } else {
                $html .= '<div style="width: 72em; margin: 0 auto;">' . LF;
                $html .= LF . '<table style="border-collapse:collapse; padding:0px;  margin-top:5px; width:100%;" summary="Layout">' . LF;

                // Page Header
                $session = $this->_environment->getSession();
                $left_menue_status = $session->getValue('left_menue_status');
                $html .= '<tr>' . LF;
                if (($left_menue_status != 'disapear' and !$this->_without_left_menue)) {
                    $html .= '<td style=" width:13.7em; vertical-align:bottom;">' . LF;
                    $html .= $this->_getLogoAsHTML() . LF;
                    $html .= '</td>' . LF;
                    $html .= '<td style="width:58.3em; vertical-align:bottom; padding-bottom:0px;">';
                } else {
                    $html .= '<td style="width:72em; vertical-align:bottom; padding-bottom:0px;">';
                }
                $server_item = $this->_environment->getServerItem();
                if ($server_item->showOutOfService()) {
                    $html .= '<h2 style="margin-left: 10px;">' . $this->_translator->getMessage('CONFIGURATION_OUTOFSERVICE_FORM_TITLE') . '</h2>';
                }
                $html .= '</td>' . LF;
                $html .= '</tr>' . LF;

                $html .= '<tr>' . LF;
                $session = $this->_environment->getSession();
                $left_menue_status = $session->getValue('left_menue_status');

                // INSTALLTION: for initializing first portal
                // root can login at server and initialize first portal
                $show_left_menue = false;
                $current_context = $this->_environment->getCurrentContextItem();
                if (!$current_context->isDeleted()) {
                    if (!$this->_environment->inServer()) {
                        $show_left_menue = true;
                    } else {
                        $server_item = $this->_environment->getCurrentContextItem();
                        $portal_list = $server_item->getPortalList();
                        if (!isset($portal_list) or $portal_list->isEmpty()) {
                            $show_left_menue = true;
                        }
                        $user = $this->_environment->getCurrentUser();
                        if ($user->isRoot()) {
                            $show_left_menue = true;
                        }
                    }
                }
                // INSTALLTION: for initializing first portal

                if ($left_menue_status != 'disapear' and $show_left_menue) {
                    $html .= '<td style="margin-bottom:0px; padding:0px; vertical-align:top;">' . LF;
                    $html .= LF . '<!-- COMMSY_MYAREA: START -->' . LF . LF;
                    $html .= $this->getMyAreaAsHTML();
                    $html .= LF . '<!-- COMMSY_MYAEREA: END -->' . LF . LF;
                    $html .= '</td>' . LF;
                    $html .= '<td style="padding-left:5px; padding-top:0px; margin:0px; vertical-align: top; ">' . LF;
                } else {
                    $html .= '<td colspan="2" style="padding-left:5px; padding-top:0px; margin:0px; vertical-align: top; ">' . LF;
                }

                // Link Row
                if ($this->_with_navigation_links and !$this->_shown_as_printable) {
                    $html .= $this->_getLinkRowAsHTML();
                } else {
                    $html .= $this->_getBlankLinkRowAsHTML();
                }
                $html .= '<div class="portal_content">' . LF;

                $html .= '<table style="width:100%" summary="Layout">' . LF;
                $mod = $this->_environment->getCurrentModule();
                $fct = $this->_environment->getCurrentFunction();
                if (!empty($this->_views)) {
                    foreach ($this->_views as $view) {
                        if (isset($view->_title)) {
                            $html .= '<tr>' . LF;
                            $html .= '<td colspan="2">' . LF;
                            if ($this->flushHTML()) {
                                echo($html);
                                flush();
                                $html = '';
                            }
                            $html .= $view->asHTML();
                            $html .= '</td>' . LF;
                            $html .= '</tr>' . LF;
                        }
                    }
                }
                $session = $this->_environment->getSession();
                $left_menue_status = $session->getValue('left_menue_status');
                if ($left_menue_status != 'disapear' and !$this->_environment->inServer()) {
                    $width = 'width:55.5em;';
                    $width_left = 'width:36em;';
                    $width_right = 'width:18.7em;';
                } else {
                    $width = 'width:68.5em;';
                    $width_left = 'width:48em;';
                    $width_right = 'width:18.7em;';
                }

                // first
                if (!$current_context->isDeleted()
                    and (!$this->_environment->inServer()
                        or $this->_environment->getCurrentFunction() == 'statistic'
                    )
                ) {
                    $html .= '<tr>' . LF;
                    if (isset($this->_agb_view)) {
                        $html .= '<td colspan="2" class="portal_leftviews" style="' . $width . '">' . LF;
                        $html .= $this->_getAGBViewAsHTML() . LF;
                        $html .= '</td>' . LF;
                    } elseif (isset($_GET['room_id'])) {
                        $room_manager = $this->_environment->getRoomManager();
                        $room_item = $room_manager->getItem($_GET['room_id']);
                        if (!isset($room_item)
                            and !$this->_environment->isArchiveMode()
                        ) {
                            $zzz_room_manager = $this->_environment->getZzzRoomManager();
                            $room_item = $zzz_room_manager->getItem($_GET['room_id']);
                            unset($zzz_room_manager);
                        }
                        if (isset($room_item)) {
                            $html .= '<td colspan="2" class="portal_leftviews" style="' . $width . '">' . LF;
                            $html .= $this->_getRoomItemAsHTML($room_item);
                            $html .= '</td>' . LF;
                        } else {

                            $with_announcements = $current_context->isShowAnnouncementsOnHome();
                            if ($with_announcements) {
                                $html .= '<td class="portal_leftviews" style="' . $width_left . '">' . LF;
                                $html .= $this->_getWelcomeTextAsHTML();
                                $html .= '</td>' . LF;
                                $html .= '<td class="portal_rightviews" style="' . $width_right . '">' . LF;
                                $html .= $this->_getPortalAnnouncements();
                                $html .= '</td>' . LF;
                            } else {

                                $html .= '<td colspan="2" class="portal_leftviews" style="' . $width . '">' . LF;
                                $html .= $this->_getWelcomeTextAsHTML();
                                $html .= '</td>' . LF;
                            }
                        }
                    } elseif (isset($_GET['iid']) and $mod == 'configuration') {
                        $html .= '<td colspan="2" class="portal_leftviews">' . LF;
                        $room_manager = $this->_environment->getRoomManager();
                        $room_item = $room_manager->getItem($_GET['iid']);
                        $html .= $this->_getRoomFormAsHTML($room_item);
                        $html .= '</td>' . LF;
                    } elseif ($mod == 'mail' and $this->_environment->getCurrentFunction() == 'to_moderator') {
                        $html .= '<td colspan="2" class="portal_leftviews" style="width:' . $width . '">' . LF;
                        $html .= $this->_getModeratorMailTextAsHTML();
                        $html .= '</td>' . LF;
                    } elseif ($mod == 'configuration' or $mod == 'account') {
                        $html .= '<td colspan="2" class="portal_leftviews" style="width:' . $width . '">' . LF;
                        $html .= $this->_getConfigurationAsHTML();
                        $html .= '</td>' . LF;
                    } elseif ($mod == 'language') {
                        $html .= '<td colspan="2" class="portal_leftviews" style="width:' . $width . '">' . LF;
                        $html .= $this->_getLanguageIndexAsHTML();
                        $html .= '</td>' . LF;
                    } elseif ($mod == 'mail' and $fct == 'process') {
                        $html .= '<td colspan="2" class="portal_leftviews" style="width:' . $width . '">' . LF;
                        $html .= $this->_getConfigurationAsHTML();
                        $html .= '</td>' . LF;
                    } elseif (($mod == 'project' and $fct == 'edit')
                        or ($mod == 'community' and $fct == 'edit')
                    ) {
                        $html .= '<td colspan="2" class="portal_leftviews" style="width:' . $width . '">' . LF;
                        $html .= $this->_getConfigurationAsHTML();
                        $html .= '</td>' . LF;
                    } else {
                        $with_announcements = $current_context->isShowAnnouncementsOnHome();
                        if ($with_announcements) {
                            $html .= '<td class="portal_leftviews" style="' . $width_left . '">' . LF;
                            $html .= $this->_getWelcomeTextAsHTML();
                            $html .= '</td>' . LF;
                            $html .= '<td class="portal_rightviews" style="' . $width_right . '">' . LF;
                            $html .= $this->_getPortalAnnouncements();
                            $html .= '</td>' . LF;
                        } else {
                            $html .= '<td colspan="2" class="portal_leftviews" style="' . $width . '">' . LF;
                            $html .= $this->_getWelcomeTextAsHTML();
                            $html .= '</td>' . LF;
                        }
                    }
                    $html .= ' </tr>' . LF;
                } elseif ($this->_environment->inServer()
                    and ($mod == 'configuration' or $mod == 'account')
                ) {
                    $html .= '<tr>' . LF;
                    $html .= '<td colspan="2" class="portal_leftviews">' . LF;
                    $html .= $this->_getConfigurationAsHTML();
                    $html .= '</td>' . LF;
                    $html .= ' </tr>' . LF;
                }

                // second
                if (!$current_context->isDeleted()
                    and !(isset($_GET['iid']) and ($fct == 'common' or $fct == 'preferences' or $fct == 'move' or $fct == 'export'))
                    and (!(!isset($_GET['iid']) and $mod == 'configuration'))
                    and (!($mod == 'configuration' and $fct == 'service')) // configuration_service: don't show second row
                    and (!($mod == 'configuration' and $fct == 'plugins')) // configuration_plugins: don't show second row
                    and (!($mod == 'account'))
                    and (!($mod == 'agb')) // AGB: don't show second row
                    and (!($mod == 'mail' and $fct == 'process'))
                    and (!($mod == 'mail' and $fct == 'to_moderator'))
                    and (!($mod == 'project' and $fct == 'edit'))
                    and (!($mod == 'community' and $fct == 'edit'))
                    and (!($mod == 'language'))
                    and !$this->_environment->inServer()
                    and !isset($this->_agb_view)
                ) {
                    $html .= '<tr>' . LF;
                    $html .= '<td class="portal_leftviews" style="' . $width_left . '">' . LF;
                    $html .= $this->_getContentListAsHTML();
                    $html .= '</td>' . LF;
                    $html .= '<td class="portal_rightviews" style="' . $width_right . '">' . LF;
                    $html .= $this->_getSearchBoxAsHTML();
                    $html .= '</td>' . LF;
                    $html .= '</tr>' . LF;
                } elseif ($this->_environment->inServer()
                    and !($mod == 'configuration' or $mod == 'account')
                ) {
                    $width_left = 'width:39em;';
                    $width_right = 'width:28.5em;';
                    $html .= '<tr>' . LF;
                    $html .= '<td class="portal_leftviews" style="' . $width_left . '">' . LF;
                    $html .= $this->_getContentListAsHTML();
                    $html .= '</td>' . LF;
                    $html .= '<td class="portal_rightviews" style="' . $width_right . '">' . LF;
                    $html .= $this->_getServerWelcomeTextAsHTML();
                    $html .= '</td>' . LF;
                    $html .= '</tr>' . LF;
                }
                $html .= '</table>' . LF;

                $html .= '</div>' . LF;
                $html .= '</td></tr>';

                $html .= '<tr>';
                if (!$this->_environment->inServer()) {
                    $html .= '<td></td>';
                    $html .= '<td>';
                } else {
                    $html .= '<td colspan="2">';
                }
                $html .= '<div class="footer" style="float:right; text-align:right; padding-left:0px; padding-right:10px; padding-top:0px; padding-bottom:10px;">' . LF;
                $current_user = $this->_environment->getCurrentUserItem();
                $current_context = $this->_environment->getCurrentContextItem();
                $email_to_moderators = '';
                if ($current_context->showMail2ModeratorLink()) {

                    $email_to_moderators = ahref_curl($this->_environment->getCurrentContextID(),
                        'mail',
                        'to_moderator',
                        '',
                        $this->_translator->getMessage('COMMON_MAIL_TO_MODERATOR'));
                }

                // service link
                if ($current_context->showServiceLink()) {
                    $service_link_ext = $current_context->getServiceLinkExternal();

                    if ($service_link_ext == '') {
                        $portal_item = $this->_environment->getCurrentPortalItem();
                        if (isset($portal_item) and !empty($portal_item)) {
                            $service_link_ext = $portal_item->getServiceLinkExternal();
                        }
                        unset($portal_item);
                    }

                    if ($service_link_ext == '') {
                        $server_item = $this->_environment->getServerItem();
                        $service_link_ext = $server_item->getServiceLinkExternal();
                    }

                    if (!empty($service_link_ext)) {
                        if (strstr($service_link_ext, '%')) {
                            $text_convert = $this->_environment->getTextConverter();
                            $service_link_ext = $text_convert->convertPercent($service_link_ext, true, true);
                        }
                        $email_to_service = '<a href="' . $service_link_ext . '" title="' . $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE') . '" target="_blank">' . $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2') . '</a>';
                    } else {
                        $serviceEmail = $current_context->getServiceEmail();

                        if (empty($serviceEmail)) {
                            $portalItem = $this->_environment->getCurrentPortalItem();

                            if ($portalItem) {
                                $serviceEmail = $portalItem->getServiceEmail();
                            }
                        }

                        if (empty($serviceEmail)) {
                            $serverItem = $this->_environment->getServerItem();

                            $serviceEmail = $serverItem->getServiceEmail();
                        }

                        $email_to_service = '';

                        if (!empty($serviceEmail)) {
                            $email_to_service = '<a href="mailto:' . $serviceEmail . '" title="' . $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2_LINK_TITLE') . '">' . $this->_translator->getMessage('COMMON_MAIL_TO_SERVICE2') . '</a>' . LF;
                        }
                    }

                    $html .= '<table style="margin:0px; padding:0px; border-collapse: collapse; border:0px solid black;" summary="Layout">' . LF;
                    $html .= '  <tr>' . LF;
                    $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">' . LF;
                    if ($email_to_moderators != '') {
                        $html .= $email_to_moderators;
                    }
                    if ($current_context->withAGB() and !isset($this->_agb_view) and $this->_with_agb_link) {
                        if ($email_to_moderators != '') {
                            $html .= '&nbsp;-&nbsp;';
                        }
                        $html .= ahref_curl($this->_environment->getCurrentContextID(), 'agb', 'index', '', $this->_translator->getMessage('COMMON_AGB_LINK_TEXT'), '', 'agb', '', '', ' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"') . '&nbsp;-&nbsp;';
                    }
                    $html .= '     </td>' . LF;
                    if (!empty($email_to_service)) {
                        $html .= '     <td style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">' . LF;
                        $html .= $email_to_service;
                        $html .= '     </td>' . LF;
                    }
                    $html .= '  </tr>' . LF;
                    $html .= '</table>' . LF;
                } else {
                    $html .= '<div style="margin:0px; padding:0px; font-size:8pt; vertical-align:text-bottom;">' . LF;
                    if ($email_to_moderators != '') {
                        $html .= $email_to_moderators;
                    }
                    if ($current_context->withAGB() and !isset($this->_agb_view) and $this->_with_agb_link) {
                        if ($email_to_moderators != '') {
                            $html .= '&nbsp;-&nbsp;';
                        }
                        $html .= ahref_curl($this->_environment->getCurrentContextID(), 'agb', 'index', '', $this->_translator->getMessage('AGB_CONFIRMATION_LINK_INPUT'), '', 'agb', '', '', ' onclick="window.open(href, target, \'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400\');"');
                    }
                    $html .= '</div>' . LF;
                }

                $html .= $this->_getPluginInfosForAfterContentAsHTML();
                $html .= '</div>' . LF;
                $html .= '<div style="padding-left:10px;">' . LF;
                $html .= $this->_getSystemInfoAsHTML();
                $html .= '</div>' . LF;
                $html .= $this->_getFooterAsHTML();
                $html .= '</td></tr>';
                $html .= ' </table>' . BRLF;
                $html .= '</div>' . LF;
            }
            if (isset($_GET['show_profile']) and $_GET['show_profile'] == 'yes') {
                $html .= $this->getProfileBoxAsHTML();
            }
            if (!empty($this->_views_overlay)) {
                foreach ($this->_views_overlay as $view) {
                    $html .= $this->_getOverlayBoxAsHTML($view);
                }
            }
            $html .= '</body>' . LF;
            $html .= '</html>' . LF;
        }
        return $html;
    }

    public function getProfileBoxAsHTML()
    {
        $html = '<div style="position:absolute; left:0px; top:0px; z-index:1000; width:100%; height: 100%;">' . LF;
        $html .= '<div style="z-index:1000; margin-top:40px; margin-bottom:0px; margin-left: 20%; width:60%; text-align:left; background-color:#FFFFFF;">';
        global $profile_view;
        $html .= $profile_view->asHTML();
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div id="profile" style="position: absolute; left:0px; top:0px; z-index:900; width:100%; height: 850px; background-color:#FFF; opacity:0.7; filter:Alpha(opacity=70);">' . LF;
        $html .= '</div>';
        return $html;
    }
}