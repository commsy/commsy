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

include_once('functions/curl_functions.php');

/** upper class of the detail view
 */
$environment = $symfonyContainer->get('commsy_legacy.environment')->getEnvironment();
$class_factory = $environment->getClassFactory();
$class_factory->includeClass(PAGE_VIEW);

/** Class for a custom CommSy portal view.
 * This class implements a custom page view of the CommSy portal.
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


    /** constructor
     * the only available constructor, initial values for internal variables
     *
     * @param array params parameters in an array of this class
     */
    public function __construct($params)
    {
        parent::__construct($params);

        if (file_exists('htdocs/' . $this->_environment->getCurrentPortalID() . '/commsy.css')) {
            $this->_style_image_path = $this->_environment->getCurrentPortalID() . '/images/';
        }

        $portalID = $this->_environment->getCurrentPortalID();
        $this->_translator->addMessageDatFolder('external_pages/' . $portalID . '/externalmessages');

        $this->_honorExternalLanguage();
    }


    /** Adds a view on the left.
     * This method adds a view to the page on the left hand side.
     * @param object cs_view a commsy view
     */
    public function addRoomList($view)
    {
        // TODO: check if/why this is still needed

        $this->_room_list_view = $view;
    }


    /** Honors the language specified by the current request in the `external_language` parameter,
     * and updates the selected language of the current user (or session) accordingly.
     * @author CommSy Development Group
     */
    public function _honorExternalLanguage()
    {
        $lang = '';

        if (isset($_GET['external_language']) && !empty($_GET['external_language'])) {
            $lang = $_GET['external_language'];
        } elseif (isset($_POST['external_language']) && !empty($_POST['external_language'])) {
            $lang = $_POST['external_language'];
        }
        if (empty($lang)) {
            return;
        }

        $currentUser = $this->_environment->getCurrentUserItem();
        if ($currentUser->isUser()) {
            $currentUser->setLanguage($lang);
            $currentUser->setChangeModificationOnSave(false);
            $currentUser->save();
        } else {
            $sessionItem = $this->_environment->getSessionItem();
            $sessionItem->setValue('message_language_select', $lang);
        }

        $this->_translator->setSelectedLanguage($lang);
        $this->_environment->setSelectedLanguage($lang);

        $params = $this->_environment->getCurrentParameterArray();
        unset($params['external_language']);
        $parameterArray = $this->_environment->_getCurrentParameterArray();
        $retour = array();
        if (count($parameterArray) > 0) {
            foreach ($parameterArray as $parameter) {
                $tempParameterArray = explode('=', $parameter);
                if ('external_language' != $tempParameterArray[0]) {
                    $retour[] = $tempParameterArray[0] . '=' . $tempParameterArray[1];
                }
            }
        }
        $this->_environment->_current_parameter_array = $retour;
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
     * @return string the page's display language
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


    /** Get the first alternate two-letter language identifier from the list of available display languages.
     * @return string the page's alternate display language
     * @author CommSy Development Group
     */
    public function _getAlternateDisplayLanguage()
    {
        $altLang = $this->_defaultDisplayLanguage;
        $lang = $this->_getDisplayLanguage();
        $availableDisplayLanguages = $this->_availableDisplayLanguages;
        $langIndex = array_search($lang, $availableDisplayLanguages);

        if ($langIndex !== false) {
            unset($availableDisplayLanguages[$langIndex]);
            $availableDisplayLanguages = array_values($availableDisplayLanguages);
            if (isset($availableDisplayLanguages[0]) && !empty($availableDisplayLanguages[0])) {
                $altLang = $availableDisplayLanguages[0];
            }
        }

        return $altLang;
    }


    /** Get the HTML head element as HTML.
     * @return string HTML head element as HTML
     * @author CommSy Development Group
     */
    public function _getHTMLHeadAsHTML()
    {
        $siteShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_SHORT_TITLE');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');

        $html = <<<HTML
  <head>
{$this->_getMetaAsHTML()}

{$this->_getCSSAsHTML()}

{$this->_getJavaScriptAsHTML()}

    <title>$siteShortTitle - CommSy $loginTitle</title>
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
        $corporationURL = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_URL');
        $corporationShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_ABBREVIATION');
        $corporationTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_TITLE');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');
        $altPageTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ALT_PAGE_TITLE');

        $portalID = $this->_environment->getCurrentPortalID();
        $currentModule = $this->_environment->getCurrentModule();
        $currentFunction = $this->_environment->getCurrentFunction();
        $getVars = $this->_environment->getCurrentParameterArray();

        $loginURL = curl($portalID, 'context', 'login', '');

        $getVars['external_language'] = $this->_getAlternateDisplayLanguage();
        $altPageURL = curl($portalID, $currentModule, $currentFunction, $getVars);

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
            <a class="nav-link active" href="$loginURL">$loginTitle</a>
          </li>
          <li class="nav-item ml-auto p-1 last">
            <a class="nav-link" href="$altPageURL">$altPageTitle</a>
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

        $corporationTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_SHORT_TITLE');
        $corporationLogoFileName = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_LOGO_FILE_NAME');
        $corporationLogoURL = "css/external_portal_styles/" . $portalID . "/img/" . $corporationLogoFileName;
        $sitePageTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_PAGE_TITLE');
        $siteShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_SHORT_TITLE');
        $siteTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_TITLE');
        $siteURL = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_URL');
        $siteLogoFileName = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_LOGO_FILE_NAME');
        $siteLogoURL = "css/external_portal_styles/" . $portalID . "/img/" . $siteLogoFileName;

        $html = <<<HTML
    <!-- Site name + Slogan -->
    <div class="container container-sitename d-block d-md-none">
      <h1><a href="$siteURL" title="$siteShortTitle $sitePageTitle">$siteShortTitle</a></h1>
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
        $sitePageShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_PAGE_SHORT_TITLE');
        $sitePageTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_PAGE_TITLE');
        $siteURL = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_URL');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');
        $navLinkShortTitleHelp = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_SHORT_TITLE_HELP');
        $navLinkTitleHelp = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_HELP');
        $navLinkURLHelp = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_HELP');
        $navLinkShortTitleAbout = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_SHORT_TITLE_ABOUT');
        $navLinkTitleAbout = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_ABOUT');
        $navLinkURLAbout = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_ABOUT');
        $navLinkTitleContact = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_CONTACT');
        $navLinkURLContact = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_CONTACT');

        $portalID = $this->_environment->getCurrentPortalID();
        $loginURL = curl($portalID, 'context', 'login', '');

        $html = <<<HTML
    <!-- Main Navigation -->
    <div class="container container-mainnav">
      <ul class="nav">
        <li class="nav-item first">
          <a class="nav-link" href="$siteURL">
            <span class="d-inline d-sm-none">$sitePageShortTitle</span>
            <span class="d-none d-sm-inline">$sitePageTitle</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link active" href="$loginURL">$loginTitle</a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="$navLinkURLHelp">
            <span class="d-inline d-md-none">$navLinkShortTitleHelp</span>
            <span class="d-none d-md-inline">$navLinkTitleHelp</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="$navLinkURLAbout">
            <span class="d-inline d-sm-none">$navLinkShortTitleAbout</span>
            <span class="d-none d-sm-inline">$navLinkTitleAbout</span>
          </a>
        </li>
        <li class="nav-item last">
          <a class="nav-link" href="$navLinkURLContact">$navLinkTitleContact</a>
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
        // TODO: support guest user

        $loggedIn = $this->_isUserLoggedIn(); // `true` if user is logged in on current portal
        $csModus = $this->_getCommSyModus();

        $siteShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_SHORT_TITLE');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');
        $contentTitle = ($loggedIn) ? $siteShortTitle : $siteShortTitle . "-" . $loginTitle;

        $html = <<<HTML
    <!-- Content -->
    <div class="container container-content">
      <div class="row">
        <!-- Main Content -->
        <div class="col-md-7">
          <h2 class="text-uppercase">$contentTitle</h2>
HTML;

        if (empty($csModus)) {
            if ($loggedIn) {
                $html .= LF . $this->_getLoggedInContentAsHTML();
            } else {
                $html .= LF . $this->_getLoginFormAsHTML();
            }
        }
        else {
            if ($csModus === 'portalmember' || $csModus === 'portalmember2') {
                $html .= LF . $this->_getNewAccountFormAsHTML($csModus);
            }
            elseif ($csModus === 'account_forget') {
                $html .= LF . $this->_getForgottenAccountFormAsHTML();
            }
            elseif ($csModus === 'password_forget') {
                $html .= LF . $this->_getForgottenPasswordFormAsHTML();
            }
            elseif ($csModus === 'password_change') {
                $html .= LF . $this->_getChangePasswordFormAsHTML();
            }
        }

        $secondaryContent = $this->_getSecondaryContentAsHTML();

        $html .= LF . <<<HTML
        </div>$secondaryContent
      </div>
    </div>
HTML;
        return $html;
    }


    /** Returns whether the current user is logged in on the current portal.
     * @return bool true if logged in, otherwise false
     * @author CommSy Development Group
     */
    public function _isUserLoggedIn()
    {
        $currentUser = $this->_environment->getCurrentUserItem();
        $loggedIn = !empty($currentUser) && $currentUser->getUserID() !== 'guest';

        return $loggedIn;
    }


    /** Get the value of the `cs_modus` parameter from the current request.
     * @return string the request's `cs_modus` parameter value
     * @author CommSy Development Group
     */
    public function _getCommSyModus()
    {
        $getVars = $this->_environment->getCurrentParameterArray();
        $postVars = $this->_environment->getCurrentPostParameterArray();
        $csModus = '';

        if (!empty($getVars['cs_modus'])) {
            $csModus = $getVars['cs_modus'];
        } elseif (!empty($postVars['cs_modus'])) {
            $csModus = $postVars['cs_modus'];
        }

        return $csModus;
    }


    /** Get the CommSy login form as HTML.
     * @return string login form as HTML
     * @author CommSy Development Group
     */
    public function _getLoginFormAsHTML()
    {
        $accountLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ACCOUNT_LABEL');
        $forgotAccountLinkTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ACCOUNT_LINK_TITLE_FORGOT');
        $createAccountLinkTitle = $this->_translator->getMessage('MYAREA_LOGIN_ACCOUNT_WANT_LINK');
        $passwordLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_PASSWORD_LABEL');
        $forgotPasswordLinkTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_PASSWORD_LINK_TITLE_FORGOT');
        $submitButtonTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SUBMIT_BUTTON_TITLE');

        $portalID = $this->_environment->getCurrentPortalID();
        $currentModule = $this->_environment->getCurrentModule();
        $currentFunction = $this->_environment->getCurrentFunction();
        $getVars = $this->_environment->getCurrentParameterArray();

        $formActionURL = curl($portalID, 'context', 'login', '');

        $getVars['cs_modus'] = 'account_forget';
        $forgotAccountLink = ahref_curl($portalID, $currentModule, $currentFunction, $getVars, $forgotAccountLinkTitle, '', '', '', '', '', '', '', '', 'forgotAccount');

        $createAccountLink = '';
        if ($this->_allowsSelfRegistration() === true) {
            $getVars['cs_modus'] = 'portalmember';
            $createAccountLink = ahref_curl($portalID, $currentModule, $currentFunction, $getVars, $createAccountLinkTitle, '', '', '', '', '', '', '', '', 'portalmember');
        }

        $getVars['cs_modus'] = 'password_forget';
        $forgotPasswordLink = ahref_curl($portalID, $currentModule, $currentFunction, $getVars, $forgotPasswordLinkTitle, '', '', '', '', '', '', '', '', 'forgotPassword');

        $html = $this->_getErrorBoxAsHTML();

        $html .= LF . <<<HTML
          <!-- Login -->
          <form id="login" method="post" action="$formActionURL" name="login">
            <div class="form-group row">
              <label for="inputUsername" class="col-sm-2 col-md-3 col-lg-2 col-form-label">$accountLabel</label>
              <div class="col-sm-10 col-md-9 col-lg-10">
                <input type="text" class="form-control" id="inputUsername" name="user_id" placeholder="$accountLabel" required />
                <div class="d-flex justify-content-between">
                  <small id="usernameHelpBlock" class="form-text text-muted">$forgotAccountLink</small>
                  <small id="newuserHelpBlock" class="form-text text-muted">$createAccountLink</small>
                </div>
              </div>
            </div>
            <div class="form-group row">
              <label for="inputPassword" class="col-sm-2 col-md-3 col-lg-2 col-form-label">$passwordLabel</label>
              <div class="col-sm-10 col-md-9 col-lg-10">
                <input type="password" class="form-control" id="inputPassword" name="password" placeholder="$passwordLabel" required />
                <small id="passwordHelpBlock" class="form-text text-muted">$forgotPasswordLink</small>
              </div>
            </div>
{$this->_getAuthSourcesAsHTML()}{$this->_getLoginRedirectAsHTML()}
            <div class="form-group row">
              <div class="col-sm-12">
                <button type="submit" class="btn btn-primary" name="option" value="$submitButtonTitle">$submitButtonTitle</button>
              </div>
            </div>
          </form>
HTML;

        return $html;
    }


    /** Returns whether any of the enabled authentication sources allows users to add an account.
     * @return bool true if at least one of the enabled authentication sources allows self registration
     * @author CommSy Development Group
     */
    function _allowsSelfRegistration()
    {
        $currentPortal = $this->_environment->getCurrentPortalItem();
        $authSourceList = $currentPortal->getAuthSourceListEnabled();

        if (!isset($authSourceList) || $authSourceList->isEmpty()) {
            return false;
        }

        $allowAddAccount = false;
        $authSourceItem = $authSourceList->getFirst();
        while ($authSourceItem) {
            if ($authSourceItem->allowAddAccount()) {
                $allowAddAccount = true;
                break;
            }
            $authSourceItem = $authSourceList->getNext();
        }

        return $allowAddAccount;
    }


    /** Get the errorbox as HTML.
     * This method uses an AGORA-specific errorbox view (instead of the one from `$this->getMyAreaErrorBox()`).
     * @return string errorbox view as HTML, or an empty string if there are no errors
     * @author CommSy Development Group
     */
    function _getErrorBoxAsHTML()
    {
        $sessionItem = $this->_environment->getSessionItem();
        if (!$sessionItem->issetValue('error_array')) {
            return '';
        }

        $errorArray = $sessionItem->getValue('error_array');

        $params = array();
        $params['environment'] = $this->_environment;
        $params['with_modifying_actions'] = true;
        $params['width'] = '100%';

        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes/views';

        include_once($externalIncludePath . '/cs_errorbox_view_agora.php');
        $errorbox = new cs_errorbox_view_agora($params);
        unset($params);

        $errorString = implode(BRLF, $errorArray);
        $errorbox->setText($errorString);

        return LF . $errorbox->asHTML() . LF;
    }


    /** Get the content that's shown for logged in CommSy users as HTML.
     * @return string "logged in" content as HTML
     * @author CommSy Development Group
     */
    public function _getLoggedInContentAsHTML()
    {
        global $symfonyContainer;
        $symfonyTranslatorService = $symfonyContainer->get('translator');
        $portalID = $this->_environment->getCurrentPortalID();
        $getVars = $this->_environment->getCurrentParameterArray();

        $currentUser = $this->_environment->getCurrentUserItem();
        $currentUserOwnRoom = $currentUser->getOwnRoom($portalID); // empty for the root user
        $currentUserName = $currentUser->getFullName();

        $headlineLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ACCOUNT_STATUS');
        $dashboardButtonTitle = ($currentUserOwnRoom) ? $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGGEDIN_SUBMIT_BUTTON_TITLE') : $this->_translator->getMessage('SERVER_PORTAL_OVERVIEW');
        $allRoomsTitle = $symfonyTranslatorService->trans('All rooms', [], 'room');
        $logoutButtonTitle = $this->_translator->getMessage('MYAREA_LOGOUT');

        // TODO: use Symfony router to create URLs
        $contextID = ($currentUserOwnRoom) ? $currentUserOwnRoom->getItemID() : $portalID;
        $formActionURL = ($currentUserOwnRoom) ? "/dashboard/" . $contextID : curl(0, 'home', 'index', $getVars);
        $allRoomsURL = "/room/" . $contextID . "/all";
        $logoutURL = ($currentUserOwnRoom) ? "/room/" . $contextID . "/logout" : curl($portalID, 'context', 'logout', $getVars);

        $html = <<<HTML
          <!-- Content For Logged In Users -->
          <form id="commsy" method="post" action="$formActionURL" name="commsy">
            <fieldset class="form-group">
              <div class="form-row">
                <legend class="col-form-label font-weight-bold">$headlineLabel</legend>
              </div>
            </fieldset>
            <div class="form-group row">
              <div class="col">
                <span id="text1" class="personal">$currentUserName</span>
              </div>
            </div>
            <div class="form-group row">
              <div class="col">
                <button type="submit" class="btn btn-primary" name="option">$dashboardButtonTitle</button>
                <a href="$allRoomsURL" class="btn text-dark">$allRoomsTitle</a>
                <a href="$logoutURL" class="btn">$logoutButtonTitle</a>
              </div>
            </div>
          </form>
HTML;

        return $html;
    }


    /** Get the CommSy "new account" (or "complete account") form as HTML.
     * @param array csModus the value of the `cs_modus` parameter from the current request
     * @return string "new account" (or "complete account") form as HTML
     * @author CommSy Development Group
     */
    public function _getNewAccountFormAsHTML($csModus)
    {
        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes';

        if ($csModus === 'portalmember') {
            include_once($externalIncludePath . '/cs_home_member_page_agora.php');
            $leftPage = new cs_home_member_page_agora($this->_environment);
        } else {
            include_once($externalIncludePath . '/cs_home_member2_page_agora.php');
            $leftPage = new cs_home_member2_page_agora($this->_environment);
        }
        $html = $leftPage->execute();
        unset($leftPage);

        return $html;
    }


    /** Get the CommSy "forgotten account" form as HTML.
     * @return string "forgotten account" form as HTML
     * @author CommSy Development Group
     */
    public function _getForgottenAccountFormAsHTML()
    {
        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes';

        include_once($externalIncludePath . '/cs_account_forget_page_agora.php');
        $leftPage = new cs_account_forget_page_agora($this->_environment);
        $html = $leftPage->execute();
        unset($leftPage);

        return $html;
    }


    /** Get the CommSy "forgotten password" form as HTML.
     * @return string "forgotten password" form as HTML
     * @author CommSy Development Group
     */
    public function _getForgottenPasswordFormAsHTML()
    {
        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes';

        include_once($externalIncludePath . '/cs_password_forget_page_agora.php');
        $leftPage = new cs_password_forget_page_agora($this->_environment);
        $html = $leftPage->execute();
        unset($leftPage);

        return $html;
    }


    /** Get the CommSy "change password" form as HTML.
     * @return string "change password" form as HTML
     * @author CommSy Development Group
     */
    public function _getChangePasswordFormAsHTML()
    {
        $portalID = $this->_environment->getCurrentPortalID();
        $externalIncludePath = 'external_pages/' . $portalID . '/classes';

        include_once($externalIncludePath . '/cs_password_change_page_agora.php');
        $leftPage = new cs_password_change_page_agora($this->_environment);
        $html = $leftPage->execute();
        unset($leftPage);

        return $html;
    }


    /** Get any secondary content (such as server and/or portal news) as HTML.
     * @return string secondary content as HTML
     * @author CommSy Development Group
     */
    public function _getSecondaryContentAsHTML()
    {
        if ($this->_shouldDisplayServerNews() === false && $this->_shouldDisplayPortalNews() === false) {
            return '';
        }

        $indicationsTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_INDICATIONS_TITLE');

        $html = LF . <<<HTML

        <!-- Secondary Content -->
        <div class="col-md-4 offset-md-1">
          <h2 class="text-uppercase">$indicationsTitle</h2>
{$this->_getServerAndPortalNewsAsHTML()}
        </div>
HTML;

        return $html;
    }


    /** Returns whether any server news should be displayed.
     * @return bool true if server news should be displayed, otherwise false
     * @author CommSy Development Group
     */
    public function _shouldDisplayServerNews()
    {
        $server = $this->_environment->getServerItem();
        $currentPortal = $this->_environment->getCurrentPortalItem();
        $displayNews = $server->showServerNews() && $currentPortal->showNewsFromServer();

        return $displayNews;
    }


    /** Returns whether any news from the current portal should be displayed.
     * @return bool true if portal news should be displayed, otherwise false
     * @author CommSy Development Group
     */
    public function _shouldDisplayPortalNews()
    {
        $currentPortal = $this->_environment->getCurrentPortalItem();
        $displayNews = $currentPortal->showServerNews();

        return $displayNews;
    }


    /** Get any server & portal news as HTML.
     * @return string server & portal news as HTML
     * @author CommSy Development Group
     */
    public function _getServerAndPortalNewsAsHTML()
    {
        $server = $this->_environment->getServerItem();
        $currentPortal = $this->_environment->getCurrentPortalItem();

        $html = <<<HTML
          <div class="container container-news">
HTML;

        if ($this->_shouldDisplayServerNews()) {
            $html .= LF . $this->_getNewsAsHTML($server, $this->_translator->getMessage('COMMON_SERVER_NEWS'));
        }

        if ($this->_shouldDisplayPortalNews()) {
            $html .= LF . $this->_getNewsAsHTML($currentPortal, $this->_translator->getMessage('COMMON_PORTAL_NEWS'));
        }

        $html .= LF . <<<HTML
          </div>
HTML;

        return $html;
    }


    /** Get the server or portal news as HTML.
     * @param object contextItem the server or current portal item whose news shall be returned
     * @param string newsHeadline the localized headline for the returned news
     * @return string server or portal news as HTML
     * @author CommSy Development Group
     */
    public function _getNewsAsHTML($contextItem, $newsHeadline)
    {
        if (!$contextItem instanceof cs_server_item && !$contextItem instanceof cs_portal_item) {
            return '';
        }

        $newsLinkURL = $contextItem->getServerNewsLink();
        $newsTitle = $contextItem->getServerNewsTitle();
        $newsText = $contextItem->getServerNewsText();

        // NOTE: we currently don't display any $newsHeadline
        $html = '';

        $html .= <<<HTML
            <div class="row bg-light mt-3">
HTML;

        if (!empty($newsTitle)) {
            $newsTitle = $this->_text_as_html_short($newsTitle);
            $html .= LF . <<<HTML
              <div class="col-sm-12 p-2">
                <span class="font-weight-bold">
HTML;

            if (!empty($newsLinkURL)) {
                $newsLinkURL = $this->_text_as_html_short($newsLinkURL);
                $html .= <<<HTML
<a href="$newsLinkURL" class="text-muted">$newsTitle</a>
HTML;
            } else {
                $html .= $newsTitle;
            }

            $html .= <<<HTML
</span>
              </div>
HTML;
        }

        if (!empty($newsText)) {
            $newsText = $this->_cleanDataFromTextArea($newsText);
            $html .= LF . <<<HTML
              <div class="col-sm-12 p-2">
                $newsText
              </div>
HTML;
        }

        $html .= LF . <<<HTML
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
        // NOTE: for now, there's no Shibboleth login support

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
            <input type="hidden" name="auth_source" value="$authSourceID" />
HTML;
            return $html;
        }

        // multiple auth sources
        $sourceLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SOURCE_LABEL');

        $html = <<<HTML
            <fieldset class="form-group">
              <div class="row">
                <legend class="col-form-label col-sm-2 col-md-3 col-lg-2 pt-0">$sourceLabel</legend>
                <div class="col-sm-10 col-md-9 col-lg-10">
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
                    <input class="form-check-input" type="radio" name="auth_source" id="radioSource{$i}" value="$authSourceID"$authSourceDefault />
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


    /** Get the login redirect (if any) as a form element to be included within the CommSy login form as HTML.
     * @return string login redirect as HTML
     * @author CommSy Development Group
     */
    public function _getLoginRedirectAsHTML()
    {
        $sessionItem = $this->_environment->getSessionItem();
        if (!$sessionItem->issetValue('login_redirect')) {
            return '';
        }

        $redirectURL = $sessionItem->getValue('login_redirect');
        $sessionItem->unsetValue('login_redirect');

        $html = LF . <<<HTML
            <input type="hidden" name="login_redirect" value="$redirectURL" />
HTML;

        return $html;
    }


    /** Get the visible page footer as HTML.
     * @return string visible page footer as HTML
     * @author CommSy Development Group
     */
    public function _getFooterAsHTML()
    {
        $sitePageTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_PAGE_TITLE');
        $siteShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_SHORT_TITLE');
        $siteTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_TITLE');
        $siteURL = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_URL');
        $siteEmail = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_EMAIL');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');
        $navLinkTitleHelp = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_HELP');
        $navLinkURLHelp = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_HELP');
        $navLinkTitleAbout = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_ABOUT');
        $navLinkURLAbout = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_ABOUT');
        $navLinkTitleContact = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_TITLE_CONTACT');
        $navLinkURLContact = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_NAV_LINK_URL_CONTACT');

        $portalID = $this->_environment->getCurrentPortalID();
        $loginURL = curl($portalID, 'context', 'login', '');

        $html = <<<HTML
    <!-- Footer -->
    <footer class="container-fluid">
      <div class="container">
        <!-- Footer Navigation -->
        <ul class="nav justify-content-center">
          <li class="nav-item">
            <a class="nav-link active" href="$siteURL">$sitePageTitle</a>
          </li>
          <li class="nav-item">
            <a class="nav-link active" href="$loginURL">$loginTitle</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="$navLinkURLHelp">$navLinkTitleHelp</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="$navLinkURLAbout">$navLinkTitleAbout</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="$navLinkURLContact">$navLinkTitleContact</a>
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
        $portalID = $this->_environment->getCurrentPortalID();

        $html = <<<HTML
    <!-- JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script src="https://code.jquery.com/jquery-3.2.1.slim.min.js" integrity="sha384-KJ3o2DKtIkvYIK3UENzmM7KCkRr/rE9/Qpg6aAZGJwFDMVNA/GpGFF93hXpG5KkN" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>
    <script src="css/external_portal_styles/$portalID/js/strength.js"></script> 
HTML;

        return $html;
    }
}