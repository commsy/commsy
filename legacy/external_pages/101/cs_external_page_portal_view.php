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
        $this->cs_page_view($params);
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
        $corporationURL = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_URL');
        $corporationShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_ABBREVIATION');
        $corporationTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_CORPORATION_TITLE');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');

// TODO: based on the selected language, toggle the language in title/URL of alternative page
//      $lang = $this->_getDisplayLanguage();

        $altPageTitle = "English";
        $portalID = $this->_environment->getCurrentPortalID();
        $altPageURL = "?cid=" . $portalID . "&amp;external_language=en";

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
          <a class="nav-link active" href="#">$loginTitle</a>
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
        // TODO: should we honor `$currentPortal->showAuthAtLogin()`?
        // TODO: implement the portalmember form (request new account)
        // TODO: implement the account_forget/password_forget forms
        // TODO: fetch "Secondary Content"
        // TODO: support guest user

        $siteShortTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SITE_SHORT_TITLE');
        $loginTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_LOGIN_TITLE');
        $accountLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ACCOUNT_LABEL');
        $forgotAccountLinkTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_ACCOUNT_LINK_TITLE_FORGOT');
        $passwordLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_PASSWORD_LABEL');
        $forgotPasswordLinkTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_PASSWORD_LINK_TITLE_FORGOT');
        $submitButtonTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SUBMIT_BUTTON_TITLE');
        $indicationsTitle = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_INDICATIONS_TITLE');

        // TODO: use ahref_curl() function instead?
        $portalID = $this->_environment->getCurrentPortalID();
        $formActionURL = "?cid=" . $portalID . "&amp;mod=context&amp;fct=login";
        $forgotAccountURL = "?cid=" . $portalID . "&amp;mod=home&amp;fct=index&amp;cs_modus=account_forget";
        $forgotPasswordURL = "?cid=" . $portalID . "&amp;mod=home&amp;fct=index&amp;cs_modus=password_forget";

        $html = <<<HTML
    <!-- Content -->
    <div class="container container-content">
      <div class="row">
        <!-- Main Content -->
        <div class="col-md-7">
          <h2 class="text-uppercase">$siteShortTitle-$loginTitle</h2>
          <!-- Login -->
          <form id="commsy-login" method="post" action="$formActionURL" name="login">
            <div class="form-group row">
              <label for="inputUsername" class="col-sm-2 col-form-label">$accountLabel</label> 
              <div class="col-sm-10">
                <input type="text" class="form-control" id="inputUsername" name="user_id" placeholder="$accountLabel" required>
                <small id="usernameHelpBlock" class="form-text text-muted"><a href="$forgotAccountURL">$forgotAccountLinkTitle</a></small> 
              </div>
            </div>
            <div class="form-group row">
              <label for="inputPassword" class="col-sm-2 col-form-label">$passwordLabel</label> 
              <div class="col-sm-10">
                <input type="password" class="form-control" id="inputPassword" name="password" placeholder="$passwordLabel" required>
                <small id="passwordHelpBlock" class="form-text text-muted"><a href="$forgotPasswordURL">$forgotPasswordLinkTitle</a></small> 
              </div>
            </div>
{$this->_getAuthSourcesAsHTML()}{$this->_getLoginRedirectAsHTML()}
            <div class="form-group row">
              <div class="col-sm-10">
                <button type="submit" class="btn btn-primary" name="option">$submitButtonTitle</button> 
              </div>
            </div>
          </form>
        </div>
        <!-- Secondary Content -->
        <div class="col-md-4 offset-md-1">
          <h2 class="text-uppercase">$indicationsTitle</h2>
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
        // TODO: support Shibboleth login?

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
        $sourceLabel = $this->_translator->getMessage('EXTERNALMESSAGES_PORTAL_SOURCE_LABEL');

        $html = <<<HTML
            <fieldset class="form-group">
              <div class="row">
                <legend class="col-form-label col-sm-2 pt-0">$sourceLabel</legend> 
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
            <input type="hidden" name="login_redirect" value="$redirectURL"/>
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
            <a class="nav-link active" href="#">$loginTitle</a>
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
}