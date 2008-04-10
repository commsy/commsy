<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
// Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
// Edouard Simon, Monique Strauss, Jos� Manuel Gonz�lez V�zquez
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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
if ( !$room_item->withAds() ) {
      redirect( $environment->getCurrentContextID(),
                $environment->getCurrentModule(),
                'index',
                '' );
}

$current_user = $environment->getCurrentUserItem();

// Check access rights
if ( !$room_item->isOpen() ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment, true );
   $errorbox->setText(getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif ( ($room_item->isProjectRoom() and !$current_user->isModerator()) or
           ($room_item->isCommunityRoom() and !$current_user->isModerator()) or
           ($room_item->isPortal() and !$current_user->isModerator()) or
           ($room_item->isServer() and !$current_user->isRoot()) ) {
   include_once('classes/cs_errorbox_view.php');
   $errorbox = new cs_errorbox_view( $environment, true );
   $errorbox->setText(getMessage('LOGIN_NOT_ALLOWED'));
   $page->add($errorbox);
}

// Access granted
else {

   // Find out what to do
   if ( isset($_POST['option']) ) {
      $command = $_POST['option'];
   } else {
      $command = '';
   }

      // Initialize the form
      include_once('plugins/ads/class_configuration_plugin_form.php');
      $form = new class_configuration_plugin_form($environment);
      // display form
      include_once('classes/cs_configuration_form_view.php');
      $form_view = new cs_configuration_form_view($environment);

      if ( isOption($command, getMessage('ADS_AD_NORMAL_SPONSOR_BUTTON'))  or
           isOption($command, getMessage('ADS_ADD_NEXT_NORMAL_SPONSOR_BUTTON')) ) {
         $counter_normal = 0;
         if ( isset($_FILES['normal_name']['name']) ) {
            $counter_normal = count($_FILES['normal_name']['name']);
         } elseif ( isset($room_item) ) {
            $counter_normal = $room_item->getCountNormalSponsors();
         }
         $counter_normal++;
         $form->setCounterNormalSponsors($counter_normal);
         unset($counter_normal);
      }

      // normal sponsor handling
      $counter = 0;
      if (isset($_POST['normal_url'])) {
         $counter = count($_POST['normal_url']);
      }

      if ( isset($_FILES['normal_name']['name']) ) {
         foreach ($_FILES['normal_name']['name'] as $key => $value) {
            if ( !empty($_FILES['normal_name']['tmp_name'][$key]) ) {
               if (isset($c_virus_scan) and $c_virus_scan) {
                  include_once('classes/cs_virus_scan.php');
                  $virus_scanner = new cs_virus_scan($environment);
                  if ($virus_scanner->isClean($_FILES['normal_name']['tmp_name'],$_FILES['normal_name']['name'])) {
                     move_uploaded_file($_FILES['normal_name']['tmp_name'][$key],$_FILES['normal_name']['tmp_name'][$key].'_TEMP_'.$value);
                     $_FILES['normal_name']['tmp_name'][$key] = $_FILES['normal_name']['tmp_name'][$key].'_TEMP_'.$value;
                     if ( isset($_POST['hidden_normal_name'][$key]) and !empty($_POST['hidden_normal_name'][$key]) ) {
                        $_POST['hidden_delete_normal_name'][] = $_POST['hidden_normal_name'][$key];
                     }
                  } else {
                     $errorbox = new cs_errorbox_view($environment, true, 500);
                     $errorbox->setText($virus_scanner->getOutput());
                     $page->add($errorbox);
                     $focus_element_onload = '';
                     $error_on_upload = true;
                  }
               }
            }
         }
      }

      if ( $counter > 0 ) {
         $i = 0;
         $end = false;
         while ( !$end ) {
            // remove normal sponsor from form
            if ( isset($_POST['normal_delete_'.$i]) and isOption($_POST['normal_delete_'.$i], getMessage('ADS_DELETE_BUTTON')) ) {
               if ( isset($_POST['hidden_normal_name'][$i]) ) {
            $disc_manager = $environment->getDiscManager();
                  if ( $disc_manager->existsFile($_POST['hidden_normal_name'][$i]) ) {
                     $_POST['hidden_delete_normal_name'][] = $_POST['hidden_normal_name'][$i];
                  }
               }
               $temp_array_name = array();
               $temp_array_file_name = array();
               $temp_array_url = array();
               $temp_array_file = array();
               $counter2 = 0;
               for ($j=0; $j<$counter; $j++) {
                  if ($j != $i) {
                     if ( isset($_POST['hidden_file_normal_name'][$j]) ) {
                        $temp_array_file_name[$counter2] = $_POST['hidden_file_normal_name'][$j];
                     }
                     if ( isset($_POST['hidden_normal_name'][$j]) ) {
                        $temp_array_name[$counter2] = $_POST['hidden_normal_name'][$j];
                     }
                     if ( isset($_POST['normal_url'][$j]) ) {
                        $temp_array_url[$counter2] = $_POST['normal_url'][$j];
                     }
                     if (isset($_FILES['normal_name']['name'][$j])) {
                        foreach ($_FILES['normal_name'] as $key => $value) {
                           $temp_array_file[$key][$counter2] = $value[$j];
                        }
                     }
                     $counter2++;
                  }
               }
               $_POST['hidden_normal_name'] = $temp_array_name;
               $_POST['hidden_file_normal_name'] = $temp_array_file_name;
               $_FILES['normal_name'] = $temp_array_file;
               $_POST['normal_url'] = $temp_array_url;
               unset($temp_array_file_name);
               unset($temp_array_name);
               unset($temp_array_file);
               unset($temp_array_url);
               unset($counter2);
               $end = true;
            }

            // move normal sponsor down in form
            if ( isset($_POST['normal_down_'.$i]) and isOption($_POST['normal_down_'.$i], getMessage('ADS_DOWN_BUTTON')) ) {
               $temp_url = $_POST['normal_url'][$i];
               $_POST['normal_url'][$i] = $_POST['normal_url'][$i+1];
               $_POST['normal_url'][$i+1] = $temp_url;
               unset($temp_url);

               if ( isset($_POST['hidden_normal_name'][$i]) ) {
                  if ( isset($_POST['hidden_normal_name'][$i+1]) ) {
                     $temp_name = $_POST['hidden_normal_name'][$i];
                     $_POST['hidden_normal_name'][$i] = $_POST['hidden_normal_name'][$i+1];
                     $_POST['hidden_normal_name'][$i+1] = $temp_name;
                     unset($temp_name);
                  } else {
                     $_POST['hidden_normal_name'][$i+1] = $_POST['hidden_normal_name'][$i];
                     unset($_POST['hidden_normal_name'][$i]);
                  }
               } else {
                  if ( isset($_POST['hidden_normal_name'][$i+1]) ) {
                     $_POST['hidden_normal_name'][$i] = $_POST['hidden_normal_name'][$i+1];
                     unset($_POST['hidden_normal_name'][$i+1]);
                  }
               }

               if ( isset($_POST['hidden_file_normal_name'][$i]) ) {
                  if ( isset($_POST['hidden_file_normal_name'][$i+1]) ) {
                     $temp_name = $_POST['hidden_file_normal_name'][$i];
                     $_POST['hidden_file_normal_name'][$i] = $_POST['hidden_file_normal_name'][$i+1];
                     $_POST['hidden_file_normal_name'][$i+1] = $temp_name;
                     unset($temp_name);
                  } else {
                     $_POST['hidden_file_normal_name'][$i+1] = $_POST['hidden_file_normal_name'][$i];
                     unset($_POST['hidden_file_normal_name'][$i]);
                  }
               } else {
                  if ( isset($_POST['hidden_file_normal_name'][$i+1]) ) {
                     $_POST['hidden_file_normal_name'][$i] = $_POST['hidden_file_normal_name'][$i+1];
                     unset($_POST['hidden_file_normal_name'][$i+1]);
                  }
               }

               if ( isset($_FILES['normal_name']['name'][$i]) ) {
                  if ( isset($_FILES['normal_name']['name'][$i+1]) ) {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $temp = $array[$i];
                        $array[$i] = $array[$i+1];
                        $array[$i+1] = $temp;
                        unset($temp);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  } else {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $array[$i+1] = $array[$i];
                        unset($array[$i]);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  }
               } else {
                  if ( isset($_FILES['normal_name']['name'][$i+1]) ) {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $array[$i] = $array[$i+1];
                        unset($array[$i+1]);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  }
               }
               $end = true; // for while loop
            }
            // move normal sponsor up in form
            if ( isset($_POST['normal_up_'.$i]) and isOption($_POST['normal_up_'.$i], getMessage('ADS_UP_BUTTON')) ) {
               $temp_url = $_POST['normal_url'][$i];
               $_POST['normal_url'][$i] = $_POST['normal_url'][$i-1];
               $_POST['normal_url'][$i-1] = $temp_url;
               unset($temp_url);

               if ( isset($_POST['hidden_normal_name'][$i]) ) {
                  if ( isset($_POST['hidden_normal_name'][$i-1]) ) {
                     $temp_name = $_POST['hidden_normal_name'][$i];
                     $_POST['hidden_normal_name'][$i] = $_POST['hidden_normal_name'][$i-1];
                     $_POST['hidden_normal_name'][$i-1] = $temp_name;
                     unset($temp_name);
                  } else {
                     $_POST['hidden_normal_name'][$i-1] = $_POST['hidden_normal_name'][$i];
                     unset($_POST['hidden_normal_name'][$i]);
                  }
               } else {
                  if ( isset($_POST['hidden_normal_name'][$i-1]) ) {
                     $_POST['hidden_normal_name'][$i] = $_POST['hidden_normal_name'][$i-1];
                     unset($_POST['hidden_normal_name'][$i-1]);
                  }
               }

               if ( isset($_POST['hidden_file_normal_name'][$i]) ) {
                  if ( isset($_POST['hidden_file_normal_name'][$i-1]) ) {
                     $temp_name = $_POST['hidden_file_normal_name'][$i];
                     $_POST['hidden_file_normal_name'][$i] = $_POST['hidden_file_normal_name'][$i-1];
                     $_POST['hidden_file_normal_name'][$i-1] = $temp_name;
                     unset($temp_name);
                  } else {
                     $_POST['hidden_file_normal_name'][$i-1] = $_POST['hidden_file_normal_name'][$i];
                     unset($_POST['hidden_file_normal_name'][$i]);
                  }
               } else {
                  if ( isset($_POST['hidden_file_normal_name'][$i-1]) ) {
                     $_POST['hidden_file_normal_name'][$i] = $_POST['hidden_file_normal_name'][$i-1];
                     unset($_POST['hidden_file_normal_name'][$i-1]);
                  }
               }

               if ( isset($_FILES['normal_name']['name'][$i]) ) {
                  if ( isset($_FILES['normal_name']['name'][$i-1]) ) {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $temp = $array[$i];
                        $array[$i] = $array[$i-1];
                        $array[$i-1] = $temp;
                        unset($temp);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  } else {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $array[$i-1] = $array[$i];
                        unset($array[$i]);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  }
               } else {
                  if ( isset($_FILES['normal_name']['name'][$i-1]) ) {
                     foreach ($_FILES['normal_name'] as $key => $array) {
                        $array[$i] = $array[$i-1];
                        unset($array[$i-1]);
                        $_FILES['normal_name'][$key] = $array;
                     }
                  }
               }
               $end = true; // for while loop
            }
            $i++;
            if ($i == $counter) {
               $end = true;
            }
         }
         unset($i);
      }
      unset($counter);

      // Load form data from postvars
      if ( !empty($_POST) ) {
         if ( !empty($_FILES) ) {
            $values = array_merge($_POST,$_FILES);
         } else {
            $values = $_POST;
         }
         $form->setFormPost($values);
         unset($values);
      } elseif ( isset($room_item) ) {
         $form->setItem($room_item);
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command)
           and isOption($command, getMessage('COMMON_SAVE_BUTTON'))
           and ( !isset($error_on_upload)
                 or !$error_on_upload
               )
         ) {
         $correct = $form->check();
         if ( $correct
              and isOption($command, getMessage('COMMON_SAVE_BUTTON'))
            ) {
            // show ads
            if ( isset($_POST['show_ads']) and !empty($_POST['show_ads']) ) {
               if ( $_POST['show_ads'] == 1 ) {
                  $room_item->setShowAds();
               } elseif ( $_POST['show_ads'] == -1 ) {
                  $room_item->setNotShowAds();
               }
            }

            if ( isset($_POST['show_google_ads']) and !empty($_POST['show_google_ads']) ) {
               if ( $_POST['show_google_ads'] == 1 ) {
                  $room_item->setShowGoogleAds();
               } elseif ( $_POST['show_google_ads'] == -1 ) {
                  $room_item->setNotShowGoogleAds();
               }
            }

            if ( isset($_POST['show_amazon_ads']) and !empty($_POST['show_amazon_ads']) ) {
               if ( $_POST['show_amazon_ads'] == 1 ) {
                  $room_item->setShowAmazonAds();
               } elseif ( $_POST['show_amazon_ads'] == -1 ) {
                  $room_item->setNotShowAmazonAds();
               }
            }

            // normal sponsors
            $array = array();
            if ( isset($_POST['hidden_normal_name']) ) {
               foreach ($_POST['hidden_normal_name'] as $key => $value) {
                  if ( strstr($value,'_TEMP_') ) {
                     $count = 0;
                     $end = false;
                     while (!$end) {
                        $filename = 'cid'.$environment->getCurrentContextID().'_SPONSORING_'.$count.'_'.$_POST['hidden_file_normal_name'][$key];
             $disc_manager = $environment->getDiscManager();
             if ( !$disc_manager->existsFile($filename) ) {
                           $end = true;
                        } else {
                           $count++;
                        }
                     }
          $disc_manager = $environment->getDiscManager();
                     $disc_manager->copyFile($value,$filename,true);
                     unset($count);
                  } else {
                     $filename = $value;
                  }
                  $array[$key]['IMAGE'] = $filename;
                  if ( isset($_POST['normal_url'][$key]) and !empty($_POST['normal_url'][$key]) ) {
                     $array[$key]['URL'] = $_POST['normal_url'][$key];
                  }
               }
            }
            if ( isset($_FILES['normal_name']['name']) ) {
               foreach ($_FILES['normal_name']['name'] as $key => $value) {
                  if ( !empty($value) ) {
                     $count = 0;
                     $end = false;
                     while (!$end) {
                        $filename = 'cid'.$environment->getCurrentContextID().'_SPONSORING_'.$count.'_'.$value;
             $disc_manager = $environment->getDiscManager();
             if ( !$disc_manager->existsFile($filename) ) {
                           $end = true;
                        } else {
                           $count++;
                        }
                     }
          $disc_manager = $environment->getDiscManager();
                     $disc_manager->copyFile($_FILES['normal_name']['tmp_name'][$key],$filename,true);
                     $array[$key]['IMAGE'] = $filename;
                     if ( isset($_POST['normal_url'][$key]) and !empty($_POST['normal_url'][$key]) ) {
                        $array[$key]['URL'] = $_POST['normal_url'][$key];
                     }
                  }
               }
            }
            ksort($array);
            $room_item->setNormalSponsorArray($array);
            unset($array);
            $title = '';
            if ( isset($_POST['normal_title']) and !empty($_POST['normal_title']) ) {
               $title = $_POST['normal_title'];
            }
            $room_item->setNormalSponsorTitle($title);
            unset($title);

            // save room_item
            $room_item->save();
            $form_view->setItemIsSaved();

            // delete image files
            if ( isset($_POST['hidden_delete_normal_name']) and !empty($_POST['hidden_delete_normal_name']) ) {
               foreach ($_POST['hidden_delete_normal_name'] as $file) {
                  if ( !empty($file) ) {
          $disc_manager = $environment->getDiscManager();
          if ( $disc_manager->existsFile($file) ) {
                        $disc_manager->unlinkFile($file);
                     } elseif ( file_exists($file) ) {
                        unlink($file);
                     }
                  }
               }
            }
            if ( isset($_POST['hidden_delete_main_name']) and !empty($_POST['hidden_delete_main_name']) ) {
               foreach ($_POST['hidden_delete_main_name'] as $file) {
                  if ( !empty($file) ) {
          $disc_manager = $environment->getDiscManager();
          if ( $disc_manager->existsFile($file) ) {
                        $disc_manager->unlinkFile($file);
                     } elseif ( file_exists($file) ) {
                        unlink($file);
                     }
                  }
               }
            }
         }
      }

      // Display form
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),$environment->getCurrentParameterArray()));
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ) {
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
}
?>