<?PHP
// class for banner rotation from affili.net

class class_banner_rotation {

   var $_banner_array = array();
   var $_banner_run_array = array();
   var $_code_array = array();
   var $_sub_id = '';
   var $_kunden_nr = '281193';
   var $_advertiser_array = array();
   var $_advertiser_id_array = array();
   var $_data_array = array();

   function class_banner_rotation () {
      $this->_sub_id = $_SERVER['HTTP_HOST'];
      $this->_fillCodeArray();
      $this->_fillBannerArray();
      #$this->showBanner();
      #$this->showAdvertiser();
   }

   function _fillCodeArray () {
      $this->_code_array['468x60']['normal'] = '<a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b%_BANNER&amp;bnb=%_BANNER&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=%_BANNER" border="0" alt="%_ALT" width="468" height="60"/></a>';
      $this->_code_array['234x60']['normal'] = '<a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b%_BANNER&amp;bnb=%_BANNER&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=%_BANNER" border="0" alt="%_ALT" width="234" height="60"/></a>';
      $this->_code_array['468x60']['javascript'] = '<script language="javascript" type="text/javascript" src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=html&amp;hnb=%_BANNER&amp;js=1&amp;subid='.$this->_sub_id.'"></script><noscript><a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b1&amp;bnb=1&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=1" border="0" alt="%_ALT"/></a></noscript>';
      $this->_code_array['234x60']['javascript'] = '<script language="javascript" type="text/javascript" src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=html&amp;hnb=%_BANNER&amp;js=1&amp;subid='.$this->_sub_id.'"></script><noscript><a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b1&amp;bnb=1&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=1" border="0" alt="%_ALT"/></a></noscript>';
      $this->_code_array['468x60']['rotation'] = '<script language="javascript" type="text/javascript" src="http://www.banner-rotation.com/rotate.aspx?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;pool=%_BANNER&amp;subid='.$this->_sub_id.'"></script><noscript><a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b1&amp;bnb=1&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=1" border="0" alt="%_ALT"/></a></noscript>';
      $this->_code_array['logo']['normal'] = '<a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;type=b%_BANNER&amp;bnb=%_BANNER&amp;subid='.$this->_sub_id.'" target="_blank"><img src="http://banners.webmasterplan.com/view.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;b=%_BANNER" border="0" alt="%_ALT"/></a>';
      $this->_code_array['url']['normal'] = '<a href="http://partners.webmasterplan.com/click.asp?ref='.$this->_kunden_nr.'&amp;site=%_SITE&amp;subid='.$this->_sub_id.'" target="_blank">%_URL</a>';
   }

   function _fillBannerArray () {
      $data_array = array();
      include_once('include_banner_data.php');
      $this->_data_array = $data_array;
      foreach ($data_array as $data_array2) {
         foreach ($data_array2 as $size => $value) {
            if ($size == 'advertiser') {
               $this->_advertiser_array[$value['SITE']] = $value;
               $this->_advertiser_id_array[] = $value['SITE'];
            }
         }
      }
   }

   function _getBannerArrayForAdvertiser ($mode, $size, $advertiser_array, $banner_array) {
      $retour = array();
      if ($mode != 'spezial') {
         $banner_code_orig = $this->_code_array[$size][$mode];
         foreach ($banner_array as $banner) {
            $banner_code = str_replace('%_BANNER',$banner,$banner_code_orig);
            foreach ($advertiser_array as $key => $data) {
               $banner_code = str_replace('%_'.$key,$data,$banner_code);
            }
            $retour[] = $banner_code;
            unset($banner_code);
         }
      } elseif ($mode == 'spezial') {
         $retour[] = $banner_array;
      }
      return $retour;
   }

   function _createBannerArrayFor ($id, $size) {
      $data_array2 = $this->_data_array[$id];
      foreach ($data_array2 as $size2 => $value) {
         if ($size2 == $size) {
            $temp_array = array();
            foreach ($value as $mode => $banner_data) {
               $temp_array = array_merge($temp_array,$this->_getBannerArrayForAdvertiser($mode,$size,$data_array2['advertiser'],$banner_data));
            }
            $this->_banner_array[$size][$data_array2['advertiser']['SITE']] = $temp_array;
            unset($temp_array);
         }
      }
   }

   function _createBannerArray ($size2='') {
      foreach ($this->_data_array as $data_array2) {
         foreach ($data_array2 as $size => $value) {
            if ( (!empty($size2) and $size2 == $size)
                 or ( empty($size2) and $size != 'advertiser')) {
               $temp_array = array();
               foreach ($value as $mode => $banner_data) {
                  $temp_array = array_merge($temp_array,$this->_getBannerArrayForAdvertiser($mode,$size,$data_array2['advertiser'],$banner_data));
               }
               $this->_banner_array[$size][$data_array2['advertiser']['SITE']] = $temp_array;
               unset($temp_array);
            }
         }
      }
   }

   function _getBannerCodeForAdvertiser ($mode, $size, $id, $banner='') {
      $retour = '';
      if ( !empty($this->_data_array[$id]['advertiser']) ) {
         $retour = $this->_code_array[$size][$mode];
         foreach ($this->_data_array[$id]['advertiser'] as $key => $data) {
            $retour = str_replace('%_'.$key,$data,$retour);
         }
         if ( !empty($banner) ) {
            $retour = str_replace('%_BANNER',$banner,$retour);
         }
      }
      return $retour;
   }

   function _getNormalLogoForAdvertiser ($id, $banner_id) {
      return $this->_getBannerCodeForAdvertiser('normal', 'logo', $id, $banner_id);
   }

   function _getAdURLForAdvertiser ($mode, $id) {
      return $this->_getBannerCodeForAdvertiser($mode, 'url', $id);
   }

   function _getNormalAdURLForAdvertiser ($id) {
      return $this->_getAdURLForAdvertiser('normal',$id);
   }

   function _getRandomBanner ($size, $id) {
      $retour = '';
      if ( !empty($this->_banner_array[$size][$id]) ) {
         $start = 0;
         $end = count($this->_banner_array[$size][$id])-1;
         $pos = rand($start,$end);
         while (empty($this->_banner_array[$size][$id][$pos])) {
            $pos = rand($start,$end);
         }
         $retour = $this->_banner_array[$size][$id][$pos];
      }
      return $retour;
   }

   function _getBannerAsHTML ($size) {
      $retour = '';
      $start = 0;
      $end = count($this->_advertiser_id_array)-1;
      $pos = rand($start,$end);
      $this->_createBannerArrayFor($this->_advertiser_id_array[$pos],$size);
      while (in_array($this->_advertiser_id_array[$pos],$this->_banner_run_array) or empty($this->_banner_array[$size][$this->_advertiser_id_array[$pos]])) {
         $pos = rand($start,$end);
         $this->_createBannerArrayFor($this->_advertiser_id_array[$pos],$size);
      }
      $this->_banner_run_array[] = $this->_advertiser_id_array[$pos];
      $retour = '<!-- BEGIN PARTNER PROGRAM -->'.$this->_getRandomBanner($size,$this->_advertiser_id_array[$pos]).'<!-- END PARTNER PROGRAM -->';
      return $retour;
   }

   function getBanner468x60AsHTML () {
      return $this->_getBannerAsHTML('468x60');
   }

   function getBanner234x60AsHTML () {
      return $this->_getBannerAsHTML('234x60');
   }

   function showBanner () {
      $this->_createBannerArray();
      $this->_pr($this->_banner_array);
   }

   function showAdvertiser () {
      $this->_pr($this->_advertiser_array);
   }

   function _getLogoForAdvertiser ($advertiser_id) {
      $retour = '';
      if ( !empty($this->_banner_array['logo'][$advertiser_id]) ) {
         $start = 0;
         $end = count($this->_banner_array['logo'][$advertiser_id])-1;
         $pos = rand($start,$end);
         while (empty($this->_banner_array['logo'][$advertiser_id][$pos])) {
            $pos = rand($start,$end);
         }
         $retour = $this->_getRandomBanner('logo',$advertiser_id);
      } else {
         $retour = $this->_getNormalAdURLForAdvertiser($advertiser_id);
      }
      return $retour;
   }

   function getAdvertiserOverviewAsHTML () {
      $retour  = '';
      $toggle = true;
      $first = true;
      $advertiser_array = $this->_advertiser_array;
      if ( !empty($advertiser_array) ) {
         $this->_createBannerArray('logo');
         $retour .= '<table class="advertiser_table">'.LF;
         $sort_by = 'NAME';
           usort($advertiser_array,create_function('$a,$b','return strnatcasecmp($a[\''.$sort_by.'\'],$b[\''.$sort_by.'\']);'));
         foreach ($advertiser_array as $advertiser) {
            if ($toggle) {
               $toggle = false;
               $css_advertiser_td = 'advertiser_td_1';
               if ($first) {
                  $first = false;
                    $retour .= '   <tr class="advertiser_tr">'.LF;
               } else {
                   $retour .= '   </tr>'.LF;
                   $retour .= '   <tr class="advertiser_tr">'.LF;
               }
            } else {
               $toggle = true;
               $css_advertiser_td = 'advertiser_td_2';
            }
            $retour .= '      <td class="'.$css_advertiser_td.'"><span class="bold">'.$advertiser['NAME'].'</span>'.BRLF.$this->_getLogoForAdvertiser($advertiser['SITE']).'</td>'.LF;
         }
         if (!$toggle) {
            $retour .= '   </tr>'.LF;
         }
         $retour .= '</table>'.LF;
      }
      return $retour;
   }

   function _pr ($value) {
      echo('<pre>');
      print_r($value);
      echo('</pre>'."\n\n");
   }
}
?>