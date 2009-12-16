<?PHP
// plugin from CommSyService

class class_ads {

   var $_environment = NULL;
   var $_translator = NULL;

   /** constructor
    * the only available constructor
    *
    * @param object environment the environment object
    */
   function class_ads ($environment) {
      $this->_environment = $environment;
      $this->_translator = $environment->getTranslationObject();
   }

   function getLinkItemForConfigurationIndex () {
      $retour = NULL;
      $context_item = $this->_environment->getCurrentContextItem();
      if ( $context_item->withAds() ) {
         include_once('classes/cs_link.php');
         $link_item = new cs_link();
         $link_item->setTitle($this->_translator->getMessage('CONFIGURATION_SPONSOR_LINK'));
         $link_item->setDescription($this->_translator->getMessage('CONFIGURATION_SPONSOR_DESC'));
         $link_item->setIconPath('plugins/ads/ADVERTISING.gif');
         $link_item->setContextID($this->_environment->getCurrentContextID());
         $link_item->setModule('configuration');
         $link_item->setFunction('plugin');
         $link_item->setParameter(array('pluginname' => 'ads'));
         $retour = $link_item;
      }
      return $retour;
   }

   function getLeftMenuAsHTML () {
      // sponsors
      $html = '';
      $array = array();
      $title = '';
      $count = '';
      $context_ad_item = NULL;
      $current_context = $this->_environment->getCurrentContextItem();
      if ( $current_context->isServer() ) {
         if ( $current_context->showAds() ) {
            $array = $current_context->getNormalSponsorArray();
            $count = $current_context->getCountNormalSponsors();
            $title = $current_context->getNormalSponsorTitle();
            $context_ad_item = $current_context;
         }
      } elseif ( $current_context->isPortal() ) {
         if ( $current_context->showAds() ) {
            $array = $current_context->getNormalSponsorArray();
            $count = $current_context->getCountNormalSponsors();
            $title = $current_context->getNormalSponsorTitle();
            $context_ad_item = $current_context;
         } elseif (!$current_context->withAds())  {
            $server = $this->_environment->getServerItem();
            if ( $server->showAds() ) {
               $array = $server->getNormalSponsorArray();
               $count = $server->getCountNormalSponsors();
               $title = $server->getNormalSponsorTitle();
               $context_ad_item = $server;
            }
         }
      } else {
         if ( $current_context->showAds() ) {
            $array = $current_context->getNormalSponsorArray();
            $count = $current_context->getCountNormalSponsors();
            $title = $current_context->getNormalSponsorTitle();
            $context_ad_item = $current_context;
         } elseif (!$current_context->withAds())  {
            $portal = $this->_environment->getCurrentPortalItem();
            if ( $portal->showAds() ) {
               $array = $portal->getNormalSponsorArray();
               $count = $portal->getCountNormalSponsors();
               $title = $portal->getNormalSponsorTitle();
               $context_ad_item = $portal;
            } elseif (!$portal->withAds())  {
               $server = $this->_environment->getServerItem();
               if ( $server->showAds() ) {
                  $array = $server->getNormalSponsorArray();
                  $count = $server->getCountNormalSponsors();
                  $title = $server->getNormalSponsorTitle();
                  $context_ad_item = $server;
               }
            }
         }
      }

      if ( !empty($array) ) {
         // normal sponsors
         $html .= LF.'<!-- BEGIN PARTNER -->'.LF;
         $html .= '<div style="padding-top: 1em; padding-left: 0px; padding-right: 0px; padding-bottom: 0px; margin-top: 7px; margin-left: 5px;">'.LF;
         if ( !empty($title) ) {
              $html .= '<div style="font-weight: bold;">'.$title.'</div>'.LF;
         }
         unset($title);
         $first = true;
         foreach ($array as $sponsor) {
            $html .= '      <div style="vertical-align: center; padding:5px;">'.LF;
            if ( !empty($sponsor['IMAGE']) ) {
               $html .= '      <span style="padding-right:10px;">'.LF;
               $params = array();
               $params['picture'] = $sponsor['IMAGE'];
               $curl = curl( $context_ad_item->getItemID(),
                             'picture', 'getfile', $params,'');
               unset($params);
               if ( !empty($sponsor['URL']) ) {
                  $params = array();
                  $params['aim'] = $sponsor['URL'];
                  $ahref = curl($this->_environment->getCurrentContextID(),'log','ads',$params);
                  $html .= '         <a href="'.$ahref.'" target="_blank"><img src="'.$curl.'" alt="Partnerlogo" class="ads"/></a>'.LF;
                  unset($params);
               } else {
                  $html .= '         <img src="'.$curl.'" class="ads" alt="Partnerlogo"/>'.LF;
               }
               $html .= '      </span>'.LF;
               unset($curl);
            }
            $html .= '      </div>'.LF;
         }

         unset($sponsor);
         unset($array);
         unset($count);

         $html .= '</div>'.LF;
         $html .= '<!-- END PARTNER -->'.LF.LF;
      } // end if normal sponsors
      return $html;
   }

   function getFooterAsHTML () {
      $html  = '';
      $html .= LF.'<!-- BEGIN ADS FOOTER -->'.LF;
      $html .= '<div style="width:100%;margin-top:10px;margin-left:0px;padding-bottom:10px;">'.LF;

      $context_item = $this->_environment->getCurrentContextItem();
      if ($this->_showGoogleAds($context_item)) {
         $pad_left = 5;
         if ( $this->_environment->inPortal() or $this->_environment->inServer() ) {
            $pad_left = 20;
         }
         $html .= LF.'<!-- BEGIN ADS -->'.LF;
         $html .= '<script type="text/javascript">'.LF;
         $html .= '   <!--'.LF;
         $html .= '   google_ad_client = "pub-5682496729852184";'.LF;
         $html .= '   /* CommSy */'.LF;
         $html .= '   google_ad_slot = "3460990441";'.LF;
         $html .= '   google_ad_width = 728;'.LF;
         $html .= '   google_ad_height = 90;'.LF;
         $html .= '   //-->'.LF;
         $html .= '</script>'.LF;
         $html .= '<script type="text/javascript"'.LF;
         $html .= '        src="http://pagead2.googlesyndication.com/pagead/show_ads.js">'.LF;
         $html .= '</script>'.LF;
         $html .= '<!-- END ADS -->'.LF.LF;
      }

      $html .= '</div>'.LF;
      $html .= '<!-- END ADS FOOTER -->'.LF.LF;

      return $html;
   }

   function _showGoogleAds ($context_item) {
      $show_google_ads = false;
      if ($context_item->withAds() and $context_item->showGoogleAds()) {
         $show_google_ads = true;
      } else {
         if ($context_item->isPortal() and !$context_item->withAds()) {
            $server_item = $this->_environment->getServerItem();
            $show_google_ads = $server_item->showGoogleAds();
         } elseif ( ($context_item->isProjectRoom() or $context_item->isCommunityRoom()) and !$context_item->withAds()) {
            $portal_item = $context_item->getContextItem();
            if ($portal_item->withAds()) {
               if ($portal_item->showGoogleAds()) {
                  $show_google_ads = true;
               }
            } else {
               $server_item = $this->_environment->getServerItem();
               $show_google_ads = $server_item->showGoogleAds();
            }
         }
      }
      return $show_google_ads;
   }

   function getUnderNetNavigationAsHTML () {
      $retour = '';
      return $retour;
   }

   function getMaterialDetailAsHTML () {
      $retour = '';
      $context_item = $this->_environment->getCurrentContextItem();
      $parameter_array = $this->_environment->getCurrentParameterArray();
      if ( $this->_showAmazonAds($context_item)
           and $this->_environment->getCurrentModule() == 'material'
           and $this->_environment->getCurrentFunction() == 'detail'
           and !( isset($parameter_array['mode'])
                  and !empty($parameter_array['mode'])
                  and $parameter_array['mode'] == 'print'
                )
         ) {
         $current_item_id = $this->_environment->getValueOfParameter('iid');
         $material_manager = $this->_environment->getMaterialManager();
         $material_item = $material_manager->getItem($current_item_id);
         if ( isset($material_item) ) {
            $isbn = $material_item->getISBN();
            $color = $context_item->getColorArray();
            if (!empty($isbn)) {
               $retour .= LF.'<div style="float:left;margin-right:8px;"><iframe src="http://rcm-de.amazon.de/e/cm?t=hitev-21&amp;o=3&amp;nou=1&amp;p=8&amp;l=as1&amp;asins='.$isbn.'&amp;fc1=000000&amp;IS2=1&amp;lt1=_blank&amp;lc1='.mb_substr($color['hyperlink'],1).'&amp;bc1=000000&amp;bg1='.mb_substr($color['content_background'],1).'&amp;f=ifr" style="width:120px;height:240px;" scrolling="no" marginwidth="0" marginheight="0" frameborder="0"></iframe></div>'.LF.LF;
            }
         }
      }
      return $retour;
   }

   function _showAmazonAds ($context_item) {
      $show_ads = false;
      if ($context_item->withAds() and $context_item->showAmazonAds()) {
         $show_ads = true;
      } else {
         if ($context_item->isPortal() and !$context_item->withAds()) {
            $server_item = $this->_environment->getServerItem();
            $show_ads = $server_item->showAmazonAds();
         } elseif ( ($context_item->isProjectRoom() or $context_item->isCommunityRoom()) and !$context_item->withAds()) {
            $portal_item = $context_item->getContextItem();
            if ($portal_item->withAds()) {
               if ($portal_item->showAmazonAds()) {
                  $show_ads = true;
               }
            } else {
               $server_item = $this->_environment->getServerItem();
               $show_ads = $server_item->showAmazonAds();
            }
         }
      }
      return $show_ads;
   }

   function getArrayForExtraConfiguration () {
      $retour = array();
      $retour['text']  = $this->_translator->getMessage('CONFIGURATION_EXTRA_SPONSORING');
      $retour['value'] = 'CONFIGURATION_EXTRA_SPONSORING';
      return $retour;
   }

   function getBeforeContentAsHTML () {
      $retour  = '<!-- ADS: BEGIN BEFORE CONTENT -->'.LF;
      $retour .= '<!-- google_ad_section_start -->'.LF;
      $retour .= '<!-- ADS: END   BEFORE CONTENT -->'.LF.LF;
      return $retour;
   }

   function getAfterContentAsHTML () {
      $retour  = '<!-- ADS: BEGIN AFTER CONTENT -->'.LF;
      $retour .= '<!-- google_ad_section_end -->'.LF;
      $retour .= '<!-- ADS: END   AFTER CONTENT -->'.LF.LF;
      return $retour;
   }

   public function isRubricPlugin () {
      return false;
   }
}
?>