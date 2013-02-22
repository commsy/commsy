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

// Function used for redirecting to connected rubrics
function attach_redirect ($rubric_type, $current_iid) {
   global $session, $environment;

   if ( $session->issetValue('cookie')
        and $session->getValue('cookie') == 1 ) {
      $session_id = $session->getSessionID();
      $session_manager = $environment->getSessionManager();
      $session2 = $session_manager->get($session->getValue('commsy_session_id'));
      if ( isset($session2) ) {
         $session = $session2;
      }
      $session->setValue('homepage_session_id',$session_id);
   }

   $infix = '_'.$rubric_type;
   $session->setValue($current_iid.'_post_vars', $_POST);
   if ( isset($_POST[$rubric_type]) ) {
      $session->setValue($current_iid.$infix.'_attach_ids', $_POST[$rubric_type]);
   } else {
      $session->setValue($current_iid.$infix.'_attach_ids', array());
   }
   $session->setValue($current_iid.$infix.'_back_module', 'homepage');
   $session->setValue($current_iid.$infix.'_back_tool', 'homepage');

   $params = array();
   $params['ref_iid'] = $current_iid;
   $params['mode'] = 'formattach';
   global $c_single_entry_point;
   redirect($environment->getCurrentContextID(), type2Module($rubric_type), 'index', $params,'','',$c_single_entry_point);
}

function attach_return ($rubric_type, $current_iid) {
   global $session, $environment;

   $infix = '_'.$rubric_type;

   if ( $session->issetValue('cookie')
        and $session->getValue('cookie') == 1
      ) {
      $session_id = $session->getSessionID();
      $session_manager = $environment->getSessionManager();
      $commsy_session_item = $session_manager->get($session->getValue('commsy_session_id'));
      $attach_ids = $commsy_session_item->getValue($current_iid.$infix.'_attach_ids');
      $commsy_session_item->unsetValue($current_iid.'_post_vars');
      $commsy_session_item->unsetValue($current_iid.$infix.'_attach_ids');
      $commsy_session_item->unsetValue($current_iid.$infix.'_back_module');
      $commsy_session_item->unsetValue($current_iid.$infix.'_back_tool');
      $session_manager->save($commsy_session_item);
   } else {
      $attach_ids = $session->getValue($current_iid.$infix.'_attach_ids');
      $session->unsetValue($current_iid.'_post_vars');
      $session->unsetValue($current_iid.$infix.'_attach_ids');
      $session->unsetValue($current_iid.$infix.'_back_module');
      $session->unsetValue($current_iid.$infix.'_back_tool');
   }
   return $attach_ids;
}

// Function used for cleaning up the session. This function
// deletes ALL session variables this page writes.
function cleanup_session ($current_iid) {
   global $session;
   $session->unsetValue($current_iid.'_post_vars');
   $session->unsetValue($current_iid.'_material_attach_ids');
   $session->unsetValue($current_iid.'_institution_attach_ids');
   $session->unsetValue($current_iid.'_group_attach_ids');
   $session->unsetValue($current_iid.'_topic_attach_ids');
   $session->unsetValue($current_iid.'_material_back_module');
   $session->unsetValue($current_iid.'_institution_back_module');
   $session->unsetValue($current_iid.'_group_back_module');
   $session->unsetValue($current_iid.'_topic_back_module');
   $session->unsetValue($current_iid.'_material_back_tool');
}

// Get the current user and room
$current_user = $environment->getCurrentUserItem();
$context_item = $environment->getCurrentContextItem();

// Get the translator object
$translator = $environment->getTranslationObject();

// Get item to be edited
if ( !empty($_GET['iid']) ) {
   $current_iid = $_GET['iid'];
} elseif ( !empty($_POST['iid']) ) {
   $current_iid = $_POST['iid'];
} else {
   $current_iid = 'NEW';
}

if ( !empty($_GET['rid']) ) {
   $father_iid = $_GET['rid'];
} elseif ( !empty($_POST['rid']) ) {
   $father_iid = $_POST['rid'];
} else {
   $father_iid = '';
}

// Coming back from attaching something
if ( !empty($_GET['backfrom']) ) {
   $backfrom = $_GET['backfrom'];
} else {
   $backfrom = false;
}

// Load item from database
if ( $current_iid == 'NEW' ) {
   $homepage_manager = $environment->getHomepageManager();
   $homepage_item = $homepage_manager->getNewItem();
   $homepage_item->setFatherID($father_iid);
} else {
   $homepage_manager = $environment->getHomepageManager();
   $homepage_item = $homepage_manager->getItem($current_iid);
}

$current_context = $environment->getCurrentContextItem();

// Check access rights
if (!$current_context->showHomepageLink()) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('HOMEPAGE_ERROR_NOT_ACTIVATED'));
   $page->add($errorbox);
   $error = true;
} elseif ( $context_item->isClosed() ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('PROJECT_ROOM_IS_CLOSED', $context_item->getTitle()));
   $page->add($errorbox);
} elseif ( $current_iid != 'NEW' and !isset($homepage_item) ) {
   $params = array();
   $params['environment'] = $environment;
   $params['with_modifying_actions'] = true;
   $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
   unset($params);
   $errorbox->setText($translator->getMessage('ITEM_DOES_NOT_EXIST', $current_iid));
   $page->add($errorbox);
} elseif ( !(($current_iid == 'NEW' and $current_user->isUser()) or
             ($current_iid != 'NEW' and isset($homepage_item) and
              $homepage_item->mayEdit($current_user))) ) {
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

   // Cancel editing
   if ( isOption($command, $translator->getMessage('COMMON_CANCEL_BUTTON')) ) {
      cleanup_session($current_iid);
      if ( $current_iid == 'NEW' ) {
         if ( !empty($father_iid) ) {
            $params = array();
            $params['iid'] = $father_iid;
            redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'detail', $params);
         } else {
            redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'detail', '');
         }
      } else {
         $params = array();
         $params['iid'] = $current_iid;
         redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'detail', $params);
      }
   }

   // Delete item
   elseif ( isOption($command, $translator->getMessage('HOMEPAGE_DELETE_BUTTON')) ) {
      cleanup_session($current_iid);
      $homepage_item->delete();
      redirect($environment->getCurrentContextID(),$environment->getCurrentModule(), 'detail', '');
   }

   // Show form and/or save item
   else {

      // Redirect to attach material
      if ( isOption($command, $translator->getMessage('HOMEPAGE_RUBRIK_BUTTON')) ) {
         attach_redirect(CS_MATERIAL_TYPE, $current_iid);
      }

      // Initialize the form
      $params = array();
      $params['environment'] = $environment;
      $params['with_modifying_actions'] = true;
      $form = $class_factory->getClass(HOMEPAGE_DETAIL_VIEW,$params);
      unset($params);
      $form->switch2Form();

      // Load form data from postvars
      if ( !empty($_POST) ) {
         $form->setFormPost($_POST);
      }

      // back from attaching rubrik
      elseif ( $backfrom == CS_MATERIAL_TYPE ) {
         $material_manager = $environment->getMaterialManager();
         $title = '';
         $first_title = true;
         $description = '';
         $file_id_array = array();
         if ( $session->issetValue('cookie')
              and $session->getValue('cookie') == 1
            ) {
            $session_id = $session->getSessionID();
            $session_manager = $environment->getSessionManager();
            $commsy_session_item = $session_manager->get($session->getValue('commsy_session_id'));
            $session_post_vars = $commsy_session_item->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         } else {
            $session_post_vars = $session->getValue($current_iid.'_post_vars'); // Must be called before attach_return(...)
         }
         $attach_ids = attach_return(CS_MATERIAL_TYPE, $current_iid);
         if ( !empty($attach_ids) ) {
            foreach ($attach_ids as $item_id) {
               $material_item = $material_manager->getItem($item_id);
               if (isset($material_item)) {
                  if ($first_title) {
                     $first_title = false;
                  } else {
                     $title .= ' - ';
                  }
                  $title .= $material_item->getTitle();
                  $item = $material_item;

                  // FROM MATERIAL DETAIL VIEW
                  // Description
                  $formal_data1 = array();

                  $description2 = $item->getDescription();
                  if ( !empty($description2) ) {
                     $temp_array = array();
                     $temp_array[] = '<h3>'.$translator->getMessage('MATERIAL_ABSTRACT').'</h3>';
                     $temp_array[] = $description2;
                     $formal_data1[]  = $temp_array;
                  }

                  $formal_data2 = array();

                  $bib_kind = $item->getBibKind() ? $item->getBibKind() : 'none';

                  // Author, Year
                  $temp_array = array();
                  if ( $bib_kind == 'collection' ) {
                     $temp_array[0]  = $translator->getMessage('MATERIAL_EDITOR');
                  } else {
                     $temp_array[0]  = $translator->getMessage('MATERIAL_AUTHORS');
                  }
                  $temp_array[1]  = $item->getAuthor();
                  $formal_data2[] = $temp_array;
                  $temp_array = array();
                  $temp_array[]  = $translator->getMessage('MATERIAL_YEAR');
                  $temp_array[]  = $item->getPublishingDate();
                  $formal_data2[] = $temp_array;

                  // Bibliographic
                  switch ( $bib_kind ) {
                     case 'book':
                     case 'collection':
                        $biblio = $item->getAddress().': '.$item->getPublisher();
                        if ( $item->getEdition() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_EDITION', $item->getEdition());
                        }
                        if ( $item->getSeries() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_SERIES', $item->getSeries());
                        }
                        if ( $item->getVolume() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_VOLUME', $item->getVolume());
                        }
                        if ( $item->getISBN() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_ISBN', $item->getISBN());
                        }
                        $biblio .= '.';
                        break;
                     case 'incollection':
                        $biblio = $translator->getMessage('MATERIAL_BIB_IN').': '.
                                  $translator->getMessage('MATERIAL_BIB_EDITOR', $item->getEditor()).': '.
                                  $item->getBooktitle().'. '.
                                  $item->getAddress().': '.$item->getPublisher();
                        if ( $item->getEdition() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_EDITION', $item->getEdition());
                        }
                        if ( $item->getSeries() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_SERIES', $item->getSeries());
                        }
                        if ( $item->getVolume() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_VOLUME', $item->getVolume());
                        }
                        if ( $item->getISBN() ) {
                           $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_ISBN', $item->getISBN());
                        }
                        $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'.';
                        break;
                     case 'article':
                        $biblio = $translator->getMessage('MATERIAL_BIB_IN').': '.
                                  $item->getJournal();
                        if ( $item->getVolume() ) {
                           $biblio .= ', '.$item->getVolume();
                           if ( $item->getIssue() ) {
                              $biblio .= ' ('.$item->getIssue().')';
                           }
                        } elseif ( $item->getIssue() ) {
                           $biblio .= ', '.$item->getIssue();
                        }
                        $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'. ';

                        $bib2 = '';
                        if ( $item->getAddress() ) {
                           $bib2 .= $item->getAddress();
                        }
                        if ( $item->getPublisher() ) {
                           $bib2 .= $bib2 ? ', ' : '';
                           $bib2 .= $item->getPublisher();
                        }
                        if ( $item->getISSN() ) {
                           $bib2 .= $bib2 ? ', ' : '';
                           $bib2 .= $item->getISSN();
                        }
                        $bib2 .= $bib2 ? '. ' : '';

                        $biblio .= $bib2 ? $bib2 : '';
                        break;
                     case 'inpaper':
                        $biblio = $translator->getMessage('MATERIAL_BIB_IN').': '.
                                  $item->getJournal();
                        if ( $item->getIssue() ) {
                           $biblio .= ', '.$item->getIssue();
                        }
                        $biblio .= ', '.$translator->getMessage('MATERIAL_BIB_PAGES', $item->getPages()).'. ';

                        $bib2 = '';
                        if ( $item->getAddress() ) {
                           $bib2 .= $item->getAddress();
                        }
                        if ( $item->getPublisher() ) {
                           $bib2 .= $bib2 ? ', ' : '';
                           $bib2 .= $item->getPublisher();
                        }
                        $bib2 .= $bib2 ? '. ' : '';

                        $biblio .= $bib2 ? $bib2 : '';
                        break;
                     case 'thesis':
                        $temp_Thesis_Kind = mb_strtoupper($item->getThesisKind(), 'UTF-8');
                        switch ( $temp_Thesis_Kind )
                        {
                           case 'BACHELOR':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_BACHELOR').'. ';
                              break;
                           case 'DIPLOMA':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_DIPLOMA').'. ';
                              break;
                           case 'DISSERTATION':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_DISSERTATION').'. ';
                              break;
                           case 'EXAM':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_EXAM').'. ';
                              break;
                           case 'KIND':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_KIND').'. ';
                              break;
                           case 'KIND_DESC':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_KIND_DESC').'. ';
                              break;
                           case 'MASTER':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_MASTER').'. ';
                              break;
                           case 'OTHER':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_OTHER').'. ';
                              break;
                           case 'POSTDOC':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_POSTDOC').'. ';
                              break;
                           case 'TERM':
                              $biblio  .= $this->_translator->getMessage('MATERIAL_THESIS_TERM').'. ';
                              break;
                           default:
                              $biblio  .= $this->_translator->getMessage('COMMON_MESSAGETAG_ERROR').' '.__FILE__.' '.__LINE__;
                              break;
                        }
                        $biblio .= $item->getAddress().': '.$item->getUniversity();
                        if ( $item->getFaculty() ) {
                           $biblio .= ', '.$item->getFaculty();
                        }
                        $biblio .= '.';
                        break;
                     case 'none':
                     default:
                        $biblio = $item->getBibliographicValues();
                  }
                  if ( !empty($biblio) ) {
                     $temp_array = array();
                     $temp_array[]  = $translator->getMessage('MATERIAL_BIBLIOGRAPHIC');
                     $temp_array[]  = $biblio;
                     $formal_data2[] = $temp_array;
                  }

                  // Buzzwords
                  $buzzwords = $item->getBuzzwordArray();
                  if(!empty($buzzwords)) {
                     $temp_array = array();
                     $temp_array[] = $translator->getMessage('MATERIAL_BUZZWORDS');
                     $temp_array[] = implode(', ',$buzzwords);
                     $formal_data2[]  = $temp_array;
                  }

                 // Labels
                  $label_name = $item->getLabel();
                  if ( !empty($label_name) ) {
                     $temp_array = array();
                     $temp_array[]   = $translator->getMessage('MATERIAL_LABEL');
                     $temp_array[]   = $label_name;
                     $formal_data2[]  = $temp_array;
                  }

                  $formal_data3 = array();

                  // Sections
                  $section_list = $item->getSectionList();
                  if ( !$section_list->isEmpty() ){
                     $sections = array();
                     $i = 1;
                     $section = $section_list->getFirst();
                     while ( $section ) {
                        $section_title = '<h3>'.$section->getTitle().'</h3>';
                        $sections[] = $section_title.LF.$section->getDescription();
                        $section = $section_list->getNext();
                        $i++;
                     }
                     $temp_array = array();
                     $temp_array[] = $translator->getMessage('MATERIAL_SECTIONS');
                     $temp_array[] = implode(BRLF.BRLF, $sections);
                     $formal_data3[] = $temp_array;
                  }

                  // END FROM MATERIAL DETAIL VIEW

                  if ( count($attach_ids) > 1 ) {
                     $description .= '<h2>'.$material_item->getTitle().'</h2>'.LF;
                  }

                  foreach ($formal_data1 as $data_array) {
                     if ( !empty($data_array[1]) ) {
                        $description .= $data_array[0].LF.$data_array[1].BRLF;
                     }
                  }
                  if ( !empty($formal_data2) ) {
                     $description .= BRLF;
                     $description .= '<h3>'.$translator->getMessage('MATERIAL_META_DATA').'</h3>'.LF;
                     foreach ($formal_data2 as $data_array) {
                        if ( !empty($data_array[1]) ) {
                           $description .= $data_array[0].': '.$data_array[1].BRLF;
                        }
                     }
                  }
                  if ( !empty($formal_data3) ) {
                     $description .= BRLF;
                     foreach ($formal_data3 as $data_array) {
                        if ( !empty($data_array[1]) ) {
                           $description .= $data_array[1].BRLF;
                        }
                     }
                  }

                  if ( count($attach_ids) > 1 ) {
                     $description .= BRLF.'<hr/>'.LF;
                  }

                  // file ids
                  $file_id_array = array_merge($file_id_array,$material_item->getFileIdArray());
               }
            }
         }
         if ( !empty($title) ) {
            $session_post_vars['title'] = $title;
            $session_post_vars['description'] = $description;
            $session_post_vars['file_id_array'] = $file_id_array;
         }
         $form->setFormPost($session_post_vars);
      }

      // Load form data from database
      elseif ( isset($homepage_item) ) {
         $form->setItem($homepage_item);
      }

      // Create data for a new item
      elseif ( $current_iid == 'NEW' ) {
         cleanup_session($current_iid);
      }

      else {
         include_once('functions/error_functions.php');
         trigger_error('homepage_edit was called in an unknown manner', E_USER_ERROR);
      }

      $form->loadValues();

      // Save item
      if ( !empty($command)
           and isOption($command, $translator->getMessage('HOMEPAGE_SAVE_BUTTON'))
         ) {

         $correct = $form->check();
         if ( $correct ) {

            // Create new item
            if ( !isset($homepage_item) ) {
               $homepage_manager = $environment->getHomepageManager();
               $homepage_item = $homepage_manager->getNewItem();
               $homepage_item->setContextID($context_item->getItemID());
               $user = $environment->getCurrentUserItem();
               $homepage_item->setCreatorItem($user);
               $homepage_item->setCreationDate(getCurrentDateTimeInMySQL());
            }

            // Set modificator and modification date
            $user = $environment->getCurrentUserItem();
            $homepage_item->setModificatorItem($user);
            $homepage_item->setModificationDate(getCurrentDateTimeInMySQL());

            // Set attributes
            if (isset($_POST['title'])) {
               $homepage_item->setTitle($_POST['title']);
            }
            if (isset($_POST['description'])) {
               $homepage_item->setDescription($_POST['description']);
            }
            if (isset($_POST['public'])) {
               $homepage_item->setPublic($_POST['public']);
            }
            if (isset($_POST['rid'])) {
               $homepage_item->setFatherID($_POST['rid']);
            }
            if (isset($_POST['file_id_array'])) {
               $homepage_item->setFileIDArray($_POST['file_id_array']);
            }

            // Save item
            $homepage_item->save();

            // Redirect
            cleanup_session($current_iid);
            $params = array();
            $params['iid'] = $homepage_item->getItemID();
            redirect($environment->getCurrentContextID(),
                     $environment->getCurrentModule(), 'detail', $params);
         } else {
            $params = array();
            $params['environment'] = $environment;
            $params['with_modifying_actions'] = true;
            $errorbox = $class_factory->getClass(ERRORBOX_VIEW,$params);
            unset($params);
            $errorbox->setText($translator->getMessage('HOMEPAGE_ERROR_CHECK_FORM'));
            $page->add($errorbox);
         }
      }

      $page->add($form);
      if ( isset($current_iid) and $current_iid != 'NEW' ) {
         $page->setShownHomepageItemID($current_iid);
      }
   }
}
?>