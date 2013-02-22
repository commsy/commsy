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

// get room item and current user
$room_item = $environment->getCurrentContextItem();
$current_user = $environment->getCurrentUserItem();
$is_saved = false;

// Get the translator object
$translator = $environment->getTranslationObject();

// Check access rights
if ( !$room_item->isOpen() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $room_item->getTitle()));
   $page->add($errorbox);
} elseif ( ($room_item->isProjectRoom()) or
           ($room_item->isCommunityRoom()) or
           ($room_item->isPortal() and !$current_user->isModerator()) or
           ($room_item->isServer())
       ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('LOGIN_NOT_ALLOWED'));
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
      $form = $class_factory->getClass(CONFIGURATION_TIME_FORM,array('environment' => $environment));
      // Display form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form_view = $class_factory->getClass(CONFIGURATION_FORM_VIEW,$params);
      unset($params);
      $form_view->setAction(curl($environment->getCurrentContextID(),$environment->getCurrentModule(),$environment->getCurrentFunction(),''));

     // ad clock pulse
      if ( isOption($command, $translator->getMessage('CONFIGURATION_TIME_FORM_ELEMENT_AD_TITLE')) ) {
         $counter = 1;
       if (isset($_POST['clock_pulse'])) {
         $counter = count($_POST['clock_pulse']);
       }
         $counter++;
         $form->setCounter($counter);
         unset($counter);
      }

     // remove clock pulse
     if (isset($_POST['clock_pulse'])) {
       $counter = count($_POST['clock_pulse']);
       for ($i=1; $i<=$counter; $i++) {
         if (isset($_POST['delete_'.$i])) {
            $new_counter = $counter-1;
            $delete_i = $i;
         }
       }
       if (isset($new_counter)) {
            $form->setCounter($new_counter);
       }
     }

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $values = $_POST;

       // remove clock pulse values
       if (isset($delete_i)) {
          unset($values['clock_pulse'][$delete_i]);
          for ($i=$delete_i; $i<=$counter; $i++) {
             if (isset($values['clock_pulse'][$i+1])) {
                $values['clock_pulse'][$i] = $values['clock_pulse'][$i+1];
             }
          }
          unset($values['clock_pulse'][$counter]);
       }

         $form->setFormPost($values);
         unset($values);
      } elseif ( isset($room_item) ) {
         $form->setItem($room_item);
      }
      $form->prepareForm();
      $form->loadValues();

      // Save item
      if ( !empty($command) and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {
         $correct = $form->check();
         if ( $correct and isOption($command, $translator->getMessage('PREFERENCES_SAVE_BUTTON')) ) {

            // show time
            if ( isset($_POST['show_time']) and !empty($_POST['show_time']) ) {
               if ( $_POST['show_time'] == 1 ) {
                  $room_item->setShowTime();
               } elseif ( $_POST['show_time'] == -1 ) {
                  $room_item->setNotShowTime();
               }
            }

         if (isset($_POST['name']) and !empty($_POST['name'])) {
            $room_item->setTimeNameArray($_POST['name']);
         }

         if (isset($_POST['clock_pulse']) and !empty($_POST['clock_pulse'])) {
            $room_item->setTimeTextArray($_POST['clock_pulse']);
         }

         if (isset($_POST['future']) and !empty($_POST['future'])) {
            $room_item->setTimeInFuture($_POST['future']);
         }

         // save room_item
         $room_item->save();

         // change (insert) time labels
         $clock_pulse_array = array();
         if (isset($_POST['clock_pulse']) and !empty($_POST['clock_pulse']) and !empty($_POST['clock_pulse'][1])) {
            $current_year = date('Y');
            $current_date = getCurrentDate();
            $ad_year = 0;
            $first = true;
            foreach ($_POST['clock_pulse'] as $key => $clock_pulse) {
              $date_string = $clock_pulse['BEGIN'];
              $month = $date_string[3].$date_string[4];
                  $day = $date_string[0].$date_string[1];
              $begin = $month.$day;

              $date_string = $clock_pulse['END'];
              $month = $date_string[3].$date_string[4];
                  $day = $date_string[0].$date_string[1];
              $end = $month.$day;

              $begin2 = ($current_year+$ad_year).$begin;
              if ($end < $begin) {
                $ad_year++;
                $ad_year_pos = $key;
              }
              $end2 = ($current_year+$ad_year).$end;

              if ($first) {
                 $first = false;
                 $begin_first = $begin2;
              }

              if ( $begin2 <= $current_date
                  and $current_date <= $end2) {
                $current_pos = $key;
              }
            }

            $year = $current_year;

            if ($current_date < $begin_first) {
              $year--;
               $current_pos = count($_POST['clock_pulse']);
            }

            $count = count($_POST['clock_pulse']);
            $position = 1;
            for ($i=0; $i<$_POST['future']+$current_pos; $i++) {
               $clock_pulse_array[] = $year.'_'.$position;
                  $position++;
               if ($position > $count) {
                  $position = 1;
                  $year++;
               }
            }
         }

         if (!empty($clock_pulse_array)) {
            $done_array = array();
            $time_manager = $environment->getTimeManager();
            $time_manager->reset();
            $time_manager->setContextLimit($environment->getCurrentContextID());
            $time_manager->setDeleteLimit(false);
            $time_manager->select();
            $time_list = $time_manager->get();
            if ($time_list->isNotEmpty()) {
               $time_label = $time_list->getFirst();
              while ($time_label) {
                if (!in_array($time_label->getTitle(),$clock_pulse_array)) {
                  $first_new_clock_pulse = $clock_pulse_array[0];
                  $last_new_clock_pulse = array_pop($clock_pulse_array);
                  $clock_pulse_array[] = $last_new_clock_pulse;
                  if ($time_label->getTitle() < $first_new_clock_pulse) {
                     $temp_clock_pulse_array = explode('_',$time_label->getTitle());
                     $clock_pulse_pos = $temp_clock_pulse_array[1];
                     if ($clock_pulse_pos > $count) {
                        if (!$time_label->isDeleted()) {
                            $time_label->setDeleterItem($environment->getCurrentUserItem());
                           $time_label->delete();
                        }
                     } else {
                        if ($time_label->isDeleted()) {
                            $time_label->setModificatorItem($environment->getCurrentUserItem());
                           $time_label->unDelete();
                        }
                     }
                  } elseif ($time_label->getTitle() > $last_new_clock_pulse) {
                     if (!$time_label->isDeleted()) {
                         $time_label->setDeleterItem($environment->getCurrentUserItem());
                        $time_label->delete();
                     }
                  } else {
                     if (!$time_label->isDeleted()) {
                         $time_label->setDeleterItem($environment->getCurrentUserItem());
                        $time_label->delete();
                     }
                  }
                } else {
                  if ($time_label->isDeleted()) {
                      $time_label->setModificatorItem($environment->getCurrentUserItem());
                     $time_label->unDelete();
                  }
                  $done_array[] = $time_label->getTitle();
                }
                  $time_label = $time_list->getNext();
              }
            }

            foreach ($clock_pulse_array as $clock_pulse) {
              if (!in_array($clock_pulse,$done_array)) {
                $time_label = $time_manager->getNewItem();
                $time_label->setContextID($environment->getCurrentContextID());
                $user = $environment->getCurrentUserItem();
                $time_label->setCreatorItem($user);
                $time_label->setModificatorItem($user);
                $time_label->setTitle($clock_pulse);
                $time_label->save();
              }
            }
         } else {
            $time_manager = $environment->getTimeManager();
            $time_manager->reset();
            $time_manager->setContextLimit($environment->getCurrentContextID());
            $time_manager->select();
            $time_list = $time_manager->get();
            if ($time_list->isNotEmpty()) {
               $time_label = $time_list->getFirst();
              while ($time_label) {
                $time_label->setDeleterItem($environment->getCurrentUserItem());
                $time_label->delete();
                  $time_label = $time_list->getNext();
              }
            }
         }

         // renew links to continuous rooms
         $current_context = $environment->getCurrentContextItem();
         $room_list = $current_context->getContinuousRoomList();
         if ($room_list->isNotEmpty()) {
            $room_item2 = $room_list->getFirst();
            while ($room_item2) {
              if ($room_item2->isOpen()) {
                 $room_item2->setContinuous();
                 $room_item2->saveWithoutChangingModificationInformation();
              }
                $room_item2 = $room_list->getNext();
            }
         }

            $form_view->setItemIsSaved();
            $is_saved = true;
         }
      }

#      $form_view->setWithoutDescription();
      $form_view->setForm($form);
      if ( $environment->inPortal() or $environment->inServer() ) {
         $page->addForm($form_view);
      } else {
         $page->add($form_view);
      }
}
?>