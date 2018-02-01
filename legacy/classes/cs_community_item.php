<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Blössl, Matthias Finck, Dirk Fust, Franz Grünig,
// Oliver Hankel, Iver Jackewitz, Michael Janneck, Martti Jeenicke,
// Detlev Krause, Irina L. Marinescu, Frithjof Meyer, Timo Nolte, Bernd Pape,
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

/** upper class of the community item
 */
include_once('classes/cs_room_item.php');

/** class for a community
 * this class implements a community item
 */
class cs_community_item extends cs_room_item {

   /**
    * Constructor
    */
   function cs_community_item ($environment) {
      $this->cs_context_item($environment);
      $this->_type = CS_COMMUNITY_TYPE;
      $this->_default_rubrics_array[0] = CS_ANNOUNCEMENT_TYPE;
      $this->_default_rubrics_array[1] = CS_PROJECT_TYPE;
      $this->_default_rubrics_array[2] = CS_TODO_TYPE;
      $this->_default_rubrics_array[3] = CS_DATE_TYPE;
      $this->_default_rubrics_array[4] = CS_MATERIAL_TYPE;
      $this->_default_rubrics_array[5] = CS_DISCUSSION_TYPE;
      $this->_default_rubrics_array[6] = CS_USER_TYPE;
      $this->_default_rubrics_array[7] = CS_TOPIC_TYPE;
      $this->_default_rubrics_array[8] = CS_INSTITUTION_TYPE;
      $this->_default_home_conf_array[CS_ANNOUNCEMENT_TYPE] = 'short';
      $this->_default_home_conf_array[CS_PROJECT_TYPE] = 'short';
      $this->_default_home_conf_array[CS_DATE_TYPE] = 'none';
      $this->_default_home_conf_array[CS_MATERIAL_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_USER_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_TOPIC_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_INSTITUTION_TYPE] = 'tiny';
      $this->_default_home_conf_array[CS_DISCUSSION_TYPE] = 'none';
      $this->_default_home_conf_array[CS_TODO_TYPE] = 'none';
   }

   function isCommunityRoom () {
      return true;
   }

   /** get projects of a project
    * this method returns a list of projects which are linked to the project
    *
    * @return object cs_list a list of projects (cs_project_item)
    */
   function getProjectList () {
      return $this->getLinkedItemList(CS_PROJECT_TYPE);
   }

   /** get project ids of a community
    * this method returns an array of projects ids which are linked to the community
    *
    * @return array an array of projects ids
    */
   function getProjectIDArray () {
      return $this->getLinkedItemIDArray(CS_PROJECT_TYPE);
   }

   public function getInternalProjectIDArray () {
      $retour = array();
      if ( $this->_issetExtra('PROJECT_ID_ARRAY') ) {
         $array = $this->_getExtra('PROJECT_ID_ARRAY');
         if ( is_array($array) ) {
            $retour = $array;
         }
      }
      return $retour;
   }

   public function setInternalProjectIDArray ( $array ) {
      if ( is_array($array) ) {
         $this->_setExtra('PROJECT_ID_ARRAY',$array);
      }
   }

   public function addProjectID2InternalProjectIDArray ( $id ) {
      if ( is_numeric($id) ) {
         $array = $this->getInternalProjectIDArray();
         if ( !in_array($id,$array) ) {
            $array[] = $id;
         }
         $this->setInternalProjectIDArray($array);
      }
   }

   public function removeProjectID2InternalProjectIDArray ( $id ) {
      if ( is_numeric($id) ) {
         $array = $this->getInternalProjectIDArray();
         if ( in_array($id,$array) ) {
            unset($array[array_search($id,$array)]);
         }
         $this->setInternalProjectIDArray($array);
      }
   }

   public function unsetInternalProjectIDArray () {
      $this->setInternalProjectIDArray(array());
   }

   public function initInternalProjectIDArray () {
      $this->setInternalProjectIDArray($this->getProjectIDArray());
   }

   /** get communitys of a project
    * this method returns a list of communitys which are linked to the project
    *
    * @return object cs_list a list of communitys (cs_community_item)
    */
   function getCommunityList () {
      return $this->getLinkedItemList(CS_COMMUNITY_TYPE);
   }


  /** set projects of a project item by item id and version id
   * this method sets a list of project item_ids and version_ids which are linked to the project
   *
   * @param array of project ids, index of id must be 'iid', index of version must be 'vid'
   * Example:
   * array(array('iid' => id1, 'vid' => version1), array('iid' => id2, 'vid' => version2))
   *
   * @author CommSy Development Group
   */
   function setProjectListByID ($value) {
      $project_array = array();
      foreach ( $value as $iid ) {
         $tmp_data = array();
         $tmp_data['iid'] = $iid;
         $project_array[] = $tmp_data;
      }
      $this->_setValue(CS_PROJECT_TYPE, $project_array, FALSE);
   }

   /** set projects of a project
    * this method sets a list of projects which are linked to the project
    *
    * @param string value title of the project
    *
    * @author CommSy Development Group
    */
   function setProjectList ($value) {
      $this->_setObject(CS_PROJECT_TYPE, $value, FALSE);
   }

   /** save community
    * this method save the community
    */
   function save() {
      $item_id = $this->getItemID();
      $manager = $this->_environment->getCommunityManager();
      $current_user = $this->_environment->getCurrentUser();
      if ( empty($item_id) ) {
         $this->setContinuous();
         $this->setServiceLinkActive();
         $this->setContactPerson($current_user->getFullName());
      }

      $this->_save($manager);
      unset($manager);

      if ( empty($item_id) ) {
         // create first moderator
         $new_room_user = $current_user->cloneData();
         $new_room_user->setContextID($this->getItemID());
         $new_room_user->makeModerator();
         $new_room_user->makeContactPerson();
         $new_room_user->setVisibleToLoggedIn();
         $new_room_user->setAccountWantMail('yes');
         $new_room_user->setOpenRoomWantMail('yes');
         $new_room_user->setPublishMaterialWantMail('yes');
         $new_room_user->save();
         $new_room_user->setCreatorID2ItemID();
         $picture = $current_user->getPicture();
         if (!empty($picture)) {
            $value_array = explode('_',$picture);
            $value_array[0] = 'cid'.$new_room_user->getContextID();
            $new_picture_name = implode('_',$value_array);

            $disc_manager = $this->_environment->getDiscManager();
            $disc_manager->copyImageFromRoomToRoom($picture,$new_room_user->getContextID());
            unset($disc_manager);

            $new_room_user->setPicture($new_picture_name);
            $new_room_user->save();
         }
         unset($new_room_user);
         unset($current_user);

         // send mail to moderation
         $this->_sendMailRoomOpen();
         $this->generateLayoutImages();

         // sync count room redundancy
         $current_portal_item = $this->getContextItem();
         if ( $current_portal_item->isCountRoomRedundancy() ) {
            $current_portal_item->syncCountCommunityRoomRedundancy(true);
         }
         unset($current_portal_item);
      }

      else {
         $new_status = $this->getStatus();
         if ( !empty($this->_old_status)
              and !empty($new_status)
              and $new_status != $this->_old_status ) {
            if ( $this->_old_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomUnlock();
            } elseif ( $new_status == CS_ROOM_CLOSED ) {
               $this->_sendMailRoomArchive();
            } elseif ( $new_status == CS_ROOM_OPEN ) {
               $this->_sendMailRoomReOpen();
            } elseif ( $new_status == CS_ROOM_LOCK ) {
               $this->_sendMailRoomLock();
            }
         }
      }
      if ( empty($item_id) ) {
         $this->initTagRootItem();
      }

      $this->updateElastic();
   }

    public function updateElastic()
    {
        global $symfonyContainer;
        $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.room');
        $em = $symfonyContainer->get('doctrine.orm.entity_manager');
        $repository = $em->getRepository('CommsyBundle:Room');

        $this->replaceElasticItem($objectPersister, $repository);
    }

   /** delete community
    * this method deletes the community
    */
   function delete() {
      parent::delete();

      // send mail to moderation
      $this->_sendMailRoomDelete();

      $manager = $this->_environment->getCommunityManager();
      $this->_delete($manager);

      // sync count room redundancy
      $current_portal_item = $this->getContextItem();
      if ( $current_portal_item->isCountRoomRedundancy() ) {
         $current_portal_item->syncCountCommunityRoomRedundancy(true);
      }
      unset($current_portal_item);

      global $symfonyContainer;
      $objectPersister = $symfonyContainer->get('fos_elastica.object_persister.commsy.room');
      $em = $symfonyContainer->get('doctrine.orm.entity_manager');
      $repository = $em->getRepository('CommsyBundle:Room');

       // use zzz repository if room is archived
       if ($this->isArchived()) {
           $repository = $em->getRepository('CommsyBundle:ZzzRoom');
       }

       $this->deleteElasticItem($objectPersister, $repository);
   }

   function undelete () {
      $manager = $this->_environment->getCommunityManager();
      $this->_undelete($manager);

      // send mail to moderation
      $this->_sendMailRoomUnDelete();

      // sync count room redundancy
      $current_portal_item = $this->getContextItem();
      if ( $current_portal_item->isCountRoomRedundancy() ) {
         $current_portal_item->syncCountCommunityRoomRedundancy(true);
      }
      unset($current_portal_item);
   }

   function getTimeSpread () {
      $retour = '90';
      if ($this->_issetExtra('TIMESPREAD')) {
         $retour = $this->_getExtra('TIMESPREAD');
      }
      return $retour;
   }

   /** set time spread for items on home
    * this method sets the time spread for items on the home of the project project
    *
    * @param integer value the time spread
    */
   public function setTimeSpread ($value) {
      $this->_addExtra('TIMESPREAD',(int)$value);
   }

   function setRoomContext ($value) {
      $this->_addExtra('ROOM_CONTEXT',(string)$value);
      if ($value == 'uni'){
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_short,'.CS_DISCUSSION_TYPE.'_none');
         $retour['NAME'] = CS_PROJECT_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Projektraum';
        $retour['DE']['GENS']= 'Projektraums';
        $retour['DE']['AKKS']= 'Projektraum';
        $retour['DE']['DATS']= 'Projektraum';
        $retour['DE']['NOMPL']= 'Projekträume';
        $retour['DE']['GENPL']= 'Projekträume';
        $retour['DE']['AKKPL']= 'Projekträume';
        $retour['DE']['DATPL']= 'Projekträumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'project workspace';
        $retour['EN']['GENS']= 'project workspace';
        $retour['EN']['AKKS']= 'project workspace';
        $retour['EN']['DATS']= 'project workspace';
        $retour['EN']['NOMPL']= 'project workspaces';
        $retour['EN']['GENPL']= 'project workspaces';
        $retour['EN']['AKKPL']= 'project workspaces';
        $retour['EN']['DATPL']= 'project workspaces';
        $retour['RU']['GENUS']= 'F';
        $retour['RU']['NOMS']= 'sala de proiecte';
        $retour['RU']['GENS']= 'salii de proiecte';
        $retour['RU']['AKKS']= 'sala de proiecte';
        $retour['RU']['DATS']= 'salii de proiecte';
        $retour['RU']['NOMPL']= 'salile de proiecte';
        $retour['RU']['GENPL']= 'salilor de proiecte';
        $retour['RU']['AKKPL']= 'salile de proiecte';
        $retour['RU']['DATPL']= 'salilor de proiecte';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);
         $retour = array();
         $retour['NAME'] = CS_TOPIC_TYPE;
        $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Thema';
        $retour['DE']['GENS']= 'Themas';
        $retour['DE']['AKKS']= 'Thema';
        $retour['DE']['DATS']= 'Thema';
        $retour['DE']['NOMPL']= 'Themen';
        $retour['DE']['GENPL']= 'Themen';
        $retour['DE']['AKKPL']= 'Themen';
        $retour['DE']['DATPL']= 'Themen';
        $retour['EN']['GENUS']= 'N';
        $retour['EN']['NOMS']= 'topic';
        $retour['EN']['GENS']= 'topic';
        $retour['EN']['AKKS']= 'topic';
        $retour['EN']['DATS']= 'topic';
        $retour['EN']['NOMPL']= 'topics';
        $retour['EN']['GENPL']= 'topics';
        $retour['EN']['AKKPL']= 'topics';
        $retour['EN']['DATPL']= 'topics';
        $retour['RU']['GENUS']= 'F';
        $retour['RU']['NOMS']= 'tema';
        $retour['RU']['GENS']= 'temei';
        $retour['RU']['AKKS']= 'tema';
        $retour['RU']['DATS']= 'temei';
        $retour['RU']['NOMPL']= 'temele';
        $retour['RU']['GENPL']= 'temelor';
        $retour['RU']['AKKPL']= 'temele';
        $retour['RU']['DATPL']= 'temelor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Gemeinschaftsraum';
        $retour['DE']['GENS']= 'Gemeinschaftsraums';
        $retour['DE']['AKKS']= 'Gemeinschaftsraum';
        $retour['DE']['DATS']= 'Gemeinschaftsraum';
        $retour['DE']['NOMPL']= 'Gemeinschaftsräume';
        $retour['DE']['GENPL']= 'Gemeinschaftsräume';
        $retour['DE']['AKKPL']= 'Gemeinschaftsräume';
        $retour['DE']['DATPL']= 'Gemeinschaftsräumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'community workspace';
        $retour['EN']['GENS']= 'community workspace';
        $retour['EN']['AKKS']= 'community workspace';
        $retour['EN']['DATS']= 'community workspace';
        $retour['EN']['NOMPL']= 'community workspaces';
        $retour['EN']['GENPL']= 'community workspaces';
        $retour['EN']['AKKPL']= 'community workspaces';
        $retour['EN']['DATPL']= 'community workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);
      } elseif ($value == 'school') {
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_none,'.CS_DISCUSSION_TYPE.'_none');
         $retour['NAME'] = CS_TOPIC_TYPE;
        $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Gremium';
        $retour['DE']['GENS']= 'Gremiums';
        $retour['DE']['AKKS']= 'Gremium';
        $retour['DE']['DATS']= 'Gremium';
        $retour['DE']['NOMPL']= 'Gremien';
        $retour['DE']['GENPL']= 'Gremien';
        $retour['DE']['AKKPL']= 'Gremien';
        $retour['DE']['DATPL']= 'Gremien';
        $retour['EN']['GENUS']= 'N';
        $retour['EN']['NOMS']= 'committee';
        $retour['EN']['GENS']= 'committee';
        $retour['EN']['AKKS']= 'committee';
        $retour['EN']['DATS']= 'committee';
        $retour['EN']['NOMPL']= 'committees';
        $retour['EN']['GENPL']= 'committees';
        $retour['EN']['AKKPL']= 'committees';
        $retour['EN']['DATPL']= 'committees';
        $retour['RU']['GENUS']= 'N';
        $retour['RU']['NOMS']= 'comitetul';
        $retour['RU']['GENS']= 'comitetului';
        $retour['RU']['AKKS']= 'comitetul';
        $retour['RU']['DATS']= 'comitetului';
        $retour['RU']['NOMPL']= 'comitetele';
        $retour['RU']['GENPL']= 'comitetelor';
        $retour['RU']['AKKPL']= 'comitetele';
        $retour['RU']['DATPL']= 'comitetelor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);
         $retour = array();
         $retour['NAME'] = CS_PROJECT_TYPE;
         $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Klassenraum';
        $retour['DE']['GENS']= 'Klassenraums';
        $retour['DE']['AKKS']= 'Klassenraum';
        $retour['DE']['DATS']= 'Klassenraum';
        $retour['DE']['NOMPL']= 'Klassenräume';
        $retour['DE']['GENPL']= 'Klassenräume';
        $retour['DE']['AKKPL']= 'Klassenräume';
        $retour['DE']['DATPL']= 'Klassenräumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'class workspace';
        $retour['EN']['GENS']= 'class workspace';
        $retour['EN']['AKKS']= 'class workspace';
        $retour['EN']['DATS']= 'class workspace';
        $retour['EN']['NOMPL']= 'class workspaces';
        $retour['EN']['GENPL']= 'class workspaces';
        $retour['EN']['AKKPL']= 'class workspaces';
        $retour['EN']['DATPL']= 'class workspaces';
        $retour['RU']['GENUS']= 'F';
        $retour['RU']['NOMS']= 'sala de clasa';
        $retour['RU']['GENS']= 'salii de clasa';
        $retour['RU']['AKKS']= 'sala de clasa';
        $retour['RU']['DATS']= 'salii de clasa';
        $retour['RU']['NOMPL']= 'salile de clasa';
        $retour['RU']['GENPL']= 'salilor de clasa';
        $retour['RU']['AKKPL']= 'salile de clasa';
        $retour['RU']['DATPL']= 'salilor de clasa';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Schulraum';
        $retour['DE']['GENS']= 'Schulraums';
        $retour['DE']['AKKS']= 'Schulraum';
        $retour['DE']['DATS']= 'Schulraum';
        $retour['DE']['NOMPL']= 'Schulräume';
        $retour['DE']['GENPL']= 'Schulräume';
        $retour['DE']['AKKPL']= 'Schulräume';
        $retour['DE']['DATPL']= 'Schulräumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'school workspace';
        $retour['EN']['GENS']= 'school workspace';
        $retour['EN']['AKKS']= 'school workspace';
        $retour['EN']['DATS']= 'school workspace';
        $retour['EN']['NOMPL']= 'school workspaces';
        $retour['EN']['GENPL']= 'school workspaces';
        $retour['EN']['AKKPL']= 'school workspaces';
        $retour['EN']['DATPL']= 'school workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);
      }
      elseif ($value == 'project'){
         $this->setHomeConf(CS_ANNOUNCEMENT_TYPE.'_short,'.CS_PROJECT_TYPE.'_short,'.CS_MATERIAL_TYPE.'_tiny,'.CS_USER_TYPE.'_tiny,'.CS_TOPIC_TYPE.'_short,'.CS_INSTITUTION_TYPE.'_none,'.CS_DISCUSSION_TYPE.'_none');
         $retour['NAME'] = CS_PROJECT_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Projektraum';
        $retour['DE']['GENS']= 'Projektraums';
        $retour['DE']['AKKS']= 'Projektraum';
        $retour['DE']['DATS']= 'Projektraum';
        $retour['DE']['NOMPL']= 'Projekträume';
        $retour['DE']['GENPL']= 'Projekträume';
        $retour['DE']['AKKPL']= 'Projekträume';
        $retour['DE']['DATPL']= 'Projekträumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'project workspace';
        $retour['EN']['GENS']= 'project workspace';
        $retour['EN']['AKKS']= 'project workspace';
        $retour['EN']['DATS']= 'project workspace';
        $retour['EN']['NOMPL']= 'project workspaces';
        $retour['EN']['GENPL']= 'project workspaces';
        $retour['EN']['AKKPL']= 'project workspaces';
        $retour['EN']['DATPL']= 'project workspaces';
        $retour['RU']['GENUS']= 'F';
        $retour['RU']['NOMS']= 'sala de proiecte';
        $retour['RU']['GENS']= 'salii de proiecte';
        $retour['RU']['AKKS']= 'sala de proiecte';
        $retour['RU']['DATS']= 'salii de proiecte';
        $retour['RU']['NOMPL']= 'salile de proiecte';
        $retour['RU']['GENPL']= 'salilor de proiecte';
        $retour['RU']['AKKPL']= 'salile de proiecte';
        $retour['RU']['DATPL']= 'salilor de proiecte';
         $this->setRubricArray (CS_PROJECT_TYPE, $retour);

         $retour = array();
         $retour['NAME'] = CS_TOPIC_TYPE;
        $retour['DE']['GENUS']= 'N';
         $retour['DE']['NOMS']= 'Thema';
        $retour['DE']['GENS']= 'Themas';
        $retour['DE']['AKKS']= 'Thema';
        $retour['DE']['DATS']= 'Thema';
        $retour['DE']['NOMPL']= 'Themen';
        $retour['DE']['GENPL']= 'Themen';
        $retour['DE']['AKKPL']= 'Themen';
        $retour['DE']['DATPL']= 'Themen';
        $retour['EN']['GENUS']= 'N';
        $retour['EN']['NOMS']= 'topic';
        $retour['EN']['GENS']= 'topic';
        $retour['EN']['AKKS']= 'topic';
        $retour['EN']['DATS']= 'topic';
        $retour['EN']['NOMPL']= 'topics';
        $retour['EN']['GENPL']= 'topics';
        $retour['EN']['AKKPL']= 'topics';
        $retour['EN']['DATPL']= 'topics';
        $retour['RU']['GENUS']= 'F';
        $retour['RU']['NOMS']= 'tema';
        $retour['RU']['GENS']= 'temei';
        $retour['RU']['AKKS']= 'tema';
        $retour['RU']['DATS']= 'temei';
        $retour['RU']['NOMPL']= 'temele';
        $retour['RU']['GENPL']= 'temelor';
        $retour['RU']['AKKPL']= 'temele';
        $retour['RU']['DATPL']= 'temelor';
         $this->setRubricArray (CS_TOPIC_TYPE, $retour);

         $retour['NAME'] = CS_COMMUNITY_TYPE;
        $retour['DE']['GENUS']= 'M';
        $retour['DE']['NOMS']= 'Gemeinschaftsraum';
        $retour['DE']['GENS']= 'Gemeinschaftsraums';
        $retour['DE']['AKKS']= 'Gemeinschaftsraum';
        $retour['DE']['DATS']= 'Gemeinschaftsraum';
        $retour['DE']['NOMPL']= 'Gemeinschaftsräume';
        $retour['DE']['GENPL']= 'Gemeinschaftsräume';
        $retour['DE']['AKKPL']= 'Gemeinschaftsräume';
        $retour['DE']['DATPL']= 'Gemeinschaftsräumen';
        $retour['EN']['GENUS']= 'M';
        $retour['EN']['NOMS']= 'community workspace';
        $retour['EN']['GENS']= 'community workspace';
        $retour['EN']['AKKS']= 'community workspace';
        $retour['EN']['DATS']= 'community workspace';
        $retour['EN']['NOMPL']= 'community workspaces';
        $retour['EN']['GENPL']= 'community workspaces';
        $retour['EN']['AKKPL']= 'community workspaces';
        $retour['EN']['DATPL']= 'community workspaces';
         $this->setRubricArray (CS_COMMUNITY_TYPE, $retour);
      }
   }

   ###################################################
   # time methods
   ###################################################

   function _getShowTime () {
      $retour = '';
      if ($this->_issetExtra('TIME_SHOW')) {
         $retour = $this->_getExtra('TIME_SHOW');
      }
      return $retour;
   }

   function showTime () {
      $retour = true;
      $value = $this->_getShowTime();
      if ($value == -1) {
        $retour = false;
      }
      return $retour;
   }

   function setShowTime () {
      $this->_addExtra('TIME_SHOW',1);
   }

   function setNotShowTime () {
      $this->_addExtra('TIME_SHOW',-1);
   }

   ###########################################################
   # some function to get lists of items in one community room
   ###########################################################

   function getProjectRoomList () {
      $room_manager = $this->_environment->getProjectManager();
      $room_manager->resetLimits();
      $room_manager->setContextLimit($this->getContextID());
      global $c_cache_cr_pr;
      if ( !isset($c_cache_cr_pr) or !$c_cache_cr_pr ) {
         $room_manager->setCommunityRoomLimit($this->getItemID());
      } else {
         /**
          * use redundant infos in community room
          */
         $room_manager->setIDArrayLimit($this->getInternalProjectIDArray());
      }
      $room_manager->select();
      $room_list = $room_manager->get();

      return $room_list;
   }

   function isActive ($start,$end) {
      $activity_border = 9;
      $activity = 0;

      $activity = $activity + $this->getCountItems($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      /*
      $activity = $activity + $this->getCountAnnouncements($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }

      $activity = $activity + $this->getCountUsers($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }

      $activity = $activity + $this->getCountMaterials($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }

      $activity = $activity + $this->getCountDiscussions($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }

      $activity = $activity + $this->getCountInstitutions($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }

      $activity = $activity + $this->getCountTopics($start,$end);
      if ($activity > $activity_border) {
         #return true;
      }
      */

      // count project items additionaly because item manager can count them
      $activity = $activity + $this->getCountProjects($start,$end);
      if ($activity > $activity_border) {
         return true;
      }

      return false;
   }

   #########################################################
   # COMMSY CRON JOBS
   #
   # this cron jobs only works if a daily cron job is
   # configured to run cron.php in /htdocs
   #########################################################

   /** cron weekly, INTERNAL
    * here you can link weekly cron jobs
    *
    * @return array results of running crons
    */
   function _cronWeekly () {
      // you can link daily cron jobs here like this
      // $cron_array[] = $this->_CRON_METHOD();
      $cron_array = array();

      return $cron_array;
   }

   function getUsageInfoTextForRubric($rubric){
      $translator = $this->_environment->getTranslationObject();

      // index Seiten
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
        if ( empty($retour) ) {
           $retour = array();
        } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
        }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   function setUsageInfoTextForRubric($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_TEXT');
        if ( empty($value_array) ) {
           $value_array = array();
        } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
        }
      } else {
         $value_array = array();
      }
      if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])){
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
      $this->_addExtra('USAGE_INFO_TEXT',$value_array);
   }

   function setUsageInfoTextForRubricForm($rubric,$string){
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $value_array = $this->_getExtra('USAGE_INFO_FORM_TEXT');
        if ( empty($value_array) ) {
           $value_array = array();
        } elseif ( !is_array($value_array) ) {
            $value_array = XML2Array($value_array);
        }
      } else {
         $value_array = array();
      }
      if(!empty($string)){
         $value_array[mb_strtoupper($rubric, 'UTF-8')]=$string;
      }else{
         if (isset($value_array[mb_strtoupper($rubric, 'UTF-8')])){
            unset($value_array[mb_strtoupper($rubric, 'UTF-8')]);
         }
      }
      $this->_addExtra('USAGE_INFO_FORM_TEXT',$value_array);
   }


   function getUsageInfoTextForRubricForm($rubric){
      // formular
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
         if ( empty($retour) ) {
            $retour = array();
         } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
         }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   function getUsageInfoTextForRubricFormInForm($rubric){
      // Konfiguration: Einstellung (Formular)
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_FORM_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_FORM_TEXT');
        if ( empty($retour) ) {
           $retour = array();
        } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
        }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }

   function getUsageInfoTextForRubricInForm($rubric){
      // Konfigurationsoption: Einstellen (Index)
      $funct = $this->_environment->getCurrentFunction();
      if ($this->_issetExtra('USAGE_INFO_TEXT')) {
         $retour = $this->_getExtra('USAGE_INFO_TEXT');
        if ( empty($retour) ) {
           $retour = array();
        } elseif ( !is_array($retour) ) {
            $retour = XML2Array($retour);
        }
      } else {
         $retour = array();
      }
      if (isset($retour[mb_strtoupper($rubric, 'UTF-8')]) and !empty($retour[mb_strtoupper($rubric, 'UTF-8')])){
         $retour = $retour[mb_strtoupper($rubric, 'UTF-8')];
      } else {
         $retour = '';
      }
      return $retour;
   }
   
    function getMDOActive() {
        // Konfigurationsoption: Medieninhalte(Mediendistribution-Online)
        $retour = '';
        if($this->_issetExtra('MEDIA_MDO_ACTIVE')) {
            $retour = $this->_getExtra('MEDIA_MDO_ACTIVE');
        }

        return $retour;
    }

    function setMDOActive($active) {
        if($active) {
            $this->_addExtra('MEDIA_MDO_ACTIVE', 1);
        } else {
            $this->_addExtra('MEDIA_MDO_ACTIVE', -1);
        }
    }

    function getMDOKey() {
        // Konfigurationsoption: Medieninhalte(Mediendistribution-Online)
        $retour = '';
        if($this->_issetExtra('MEDIA_MDO_KEY')) {
            $retour = $this->_getExtra('MEDIA_MDO_KEY');
        }

        return $retour;
    }

    function setMDOKey($key) {
        if(!empty($key)) {
            $this->_addExtra('MEDIA_MDO_KEY', $key);
        } else {
            $this->_addExtra('MEDIA_MDO_KEY', -1);
        }
    }


   ################################################################
   # mail to moderation, if the community room status changed
   # - delete
   # - undelete
   # - open
   # - archive
   # - template (not implemented yet because flagged function)
   # - untemplate (not implemented yet because flagged function)
   # - reopen
   ################################################################

   function _sendMailRoomDelete () {
      $this->_sendMailRoomDeleteToCommunityModeration();
      $this->_sendMailRoomDeleteToPortalModeration();
   }

   function _sendMailRoomUnDelete () {
      $this->_sendMailRoomUnDeleteToCommunityModeration();
      $this->_sendMailRoomUnDeleteToPortalModeration();
   }

   function _sendMailRoomOpen () {
      $this->_sendMailRoomOpenToCommunityModeration();
      $this->_sendMailRoomOpenToPortalModeration();
   }

   function _sendMailRoomArchive () {
      $this->_sendMailRoomArchiveToCommunityModeration();
      $this->_sendMailRoomArchiveToPortalModeration();
   }

   function _sendMailRoomReOpen () {
      $this->_sendMailRoomReOpenToCommunityModeration();
      $this->_sendMailRoomReOpenToPortalModeration();
   }

   function _sendMailRoomLock () {
      $this->_sendMailRoomLockToCommunityModeration();
      $this->_sendMailRoomLockToPortalModeration();
   }

   function _sendMailRoomUnlock () {
      $this->_sendMailRoomUnlockToCommunityModeration();
      $this->_sendMailRoomUnlockToPortalModeration();
   }

   function _sendMailToModeration ($room_moderation, $room_change) {
      if ( $room_moderation == 'portal' ) {
                   $this->_sendMailToModeration2($this->getContextItem(),$room_change);
                } elseif ( $room_moderation == 'community' ) {
                   $this->_sendMailToModeration2($this,$room_change);
                } else {
                   include_once('functions/error_functions.php');trigger_error('lost room moderation',E_USER_WARNING);
                }
   }

   function _sendMailToModeration2 ($room_item, $room_change) {
      $translator = $this->_environment->getTranslationObject();
      $default_language = 'de';
      
      // maybe in archive mode
      $toggle_archive = false;
        if ( $this->_environment->isArchiveMode() ) {
            $toggle_archive = true;
            $this->_environment->toggleArchiveMode();
        }

       global $symfonyContainer;
       $emailFrom = $symfonyContainer->getParameter('commsy.email.from');
       $default_sender_address = $emailFrom;

      $current_portal = $this->_environment->getCurrentPortalItem();
      if ( empty($current_portal)
           or !$current_portal->isPortal()
         ) {
         $current_portal = $this->getContextItem();
      }
      $current_user = $this->_environment->getCurrentUserItem();
      $fullname = $current_user->getFullname();
      if ( empty($fullname) ) {
         $current_user = $this->_environment->getRootUserItem();
         $email = $current_user->getEmail();
         if ( empty($email)
              and !empty($default_sender_address)
              and $default_sender_address != '@'
            ) {
         	$current_user->setEmail($default_sender_address);
         }	
      }
      
      if ( $toggle_archive ) {
      	$this->_environment->toggleArchiveMode();
      }
      unset($toggle_archive);
      
      $moderator_list = $room_item->getModeratorList();

      // get moderators
      $receiver_array = array();
      $moderator_name_array = array();

                if ( $moderator_list->isNotEmpty() ) {
                   $mod_item = $moderator_list->getFirst();
                   while ($mod_item) {
                      if ($mod_item->getOpenRoomWantMail() == 'yes') {
                         $language = $room_item->getLanguage();
                         if ($language == 'user') {
                                 $language = $mod_item->getLanguage();
                                 if ($language == 'browser') {
                                    $language = $default_language;
                                 }
                         }
                         $receiver_array[$language][] = $mod_item->getEmail();
                         $moderator_name_array[] = $mod_item->getFullname();
                      }
                      $mod_item = $moderator_list->getNext();
                   }
      }

      // now email information
     foreach ($receiver_array as $key => $value) {
         $subject = '';
         if ( $room_item->isPortal() ){
            $subject .= $room_item->getTitle().': ';
         }
         $save_language = $translator->getSelectedLanguage();
         $translator->setSelectedLanguage($key);
         $title = str_ireplace('&amp;', '&', $this->getTitle());
         if ( $room_change == 'open' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_OPEN',$title);
         } elseif ( $room_change == 'reopen' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_REOPEN',$title);
         } elseif ( $room_change == 'delete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_DELETE',$title);
         } elseif ( $room_change == 'undelete' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNDELETE',$title);
         } elseif ( $room_change == 'archive' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_ARCHIVE',$title);
         } elseif ( $room_change == 'link' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LINK',$title);
         } elseif ( $room_change == 'lock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_LOCK',$title);
         } elseif ( $room_change == 'unlock' ) {
            $subject = $translator->getMessage('PROJECT_MAIL_SUBJECT_UNLOCK',$title);
         }
         $body  = $translator->getMessage('MAIL_AUTO',$translator->getDateInLang(getCurrentDateTimeInMySQL()),$translator->getTimeInLang(getCurrentDateTimeInMySQL()));
         $body .= LF.LF;
         if ( $room_change == 'open' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_OPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_OPEN');
         } elseif ( $room_change == 'reopen' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_REOPEN');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_REOPEN');
         } elseif ( $room_change == 'delete' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_DELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_DELETE');
         } elseif ( $room_change == 'undelete' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNDELETE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNDELETE');
         } elseif ( $room_change == 'archive' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_ARCHIVE');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_ARCHIVE');
         } elseif ( $room_change == 'lock' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_LOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_LOCK');
         } elseif ( $room_change == 'unlock' ) {
            $body .= $translator->getMessage('COMMUNITY_MAIL_BODY_UNLOCK');
            $room_change_action = $translator->getMessage('PROJECT_MAIL_BODY_ACTION_UNLOCK');
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('PROJECT_MAIL_BODY_INFORMATION',str_ireplace('&amp;', '&', $this->getTitle()),$current_user->getFullname(),$room_change_action);
         if ( $room_change != 'delete' ) {

             global $symfonyContainer;

             $url = $symfonyContainer->get('router')->generate(
                 'commsy_room_home',
                 array('roomId' => $this->getItemID()),
                 true
             );
             $requestStack = $symfonyContainer->get('request_stack');
             $currentRequest = $requestStack->getCurrentRequest();
             if ($currentRequest) {
                 $url = $currentRequest->getSchemeAndHttpHost() . $url;
             }
  			   $body .= LF.$url;
         	
  			   #$body .= LF.'http://'.$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'].'?cid='.$this->getContextID().'&room_id='.$this->getItemID();
         }
         $body .= LF.LF;
         $body .= $translator->getMessage('MAIL_SEND_TO',implode(LF,$moderator_name_array));
         $body .= LF.LF;
         if ( $room_item->isPortal() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PORTAL',$room_item->getTitle());
         } elseif ( $room_item->isCommunityRoom() ) {
            $body .= $translator->getMessage('MAIL_SEND_WHY_COMMUNITY',$room_item->getTitle());
         } else {
            $body .= $translator->getMessage('MAIL_SEND_WHY_PROJECT',$room_item->getTitle());
         }

         // send email
         include_once('classes/cs_mail.php');
         $mail = new cs_mail();
         $mail->set_to(implode(',',$value));
         $mail->set_from_email($default_sender_address);
         if ( isset($current_portal) ) {
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$current_portal->getTitle()));
         } else {
            $server_item = $this->_environment->getServerItem();
            $mail->set_from_name($translator->getMessage('SYSTEM_MAIL_MESSAGE',$server_item->getTitle()));
            unset($server_item);
         }
         $mail->set_reply_to_name($current_user->getFullname());
         $mail->set_reply_to_email($current_user->getEmail());
         $mail->set_subject($subject);
         $mail->set_message($body);
         $mail->send();
         $translator->setSelectedLanguage($save_language);
         unset($save_language);
         unset($mail);
      }
      unset($current_portal);
      unset($current_user);
   }

   function getCountUsedAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $project_id_array = $this->getProjectIDArray();
      $project_id_array[] = $this->getItemID();
      $user_manager->setContextArrayLimit($project_id_array);
      $retour = $user_manager->getCountUsedAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountOpenAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $project_id_array = $this->getProjectIDArray();
      $project_id_array[] = $this->getItemID();
      $user_manager->setContextArrayLimit($project_id_array);
      $retour = $user_manager->getCountOpenAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountAllAccounts ($start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $project_id_array = $this->getProjectIDArray();
      $project_id_array[] = $this->getItemID();
      $user_manager->setContextArrayLimit($project_id_array);
      $retour = $user_manager->getCountAllAccounts($start,$end);
      unset($user_manager);

      return $retour;
   }

   function getCountPluginWithLinkedRooms ($plugin, $start, $end) {
      $retour = 0;

      $user_manager = $this->_environment->getUserManager();
      $user_manager->resetLimits();
      $project_id_array = $this->getProjectIDArray();
      $project_id_array[] = $this->getItemID();
      $user_manager->setContextArrayLimit($project_id_array);
      $retour = $user_manager->getCountPlugin($plugin,$start,$end);
      unset($user_manager);

      return $retour;
   }

   function _setObjectLinkItems($changed_key) {
      if ( $changed_key == CS_PROJECT_TYPE ) {
         $array = array();
         if ( !empty($this->_data[$changed_key])
              and is_object($this->_data[$changed_key])
            ) {
            $item = $this->_data[$changed_key]->getFirst();
            while ( $item ) {
               $array[] = $item->getItemID();
               $item = $this->_data[$changed_key]->getNext();
            }
         }
         $this->setInternalProjectIDArray($array);
      }
      parent::_setObjectLinkItems($changed_key);
   }

   function _setIDLinkItems($changed_key) {
      if ( $changed_key == CS_PROJECT_TYPE ) {
         if ( !empty($this->_data[$changed_key])
              and is_array($this->_data[$changed_key])
            ) {
            $this->setInternalProjectIDArray($this->_data[$changed_key]);
         }
      }
      parent::_setIDLinkItems($changed_key);
   }
}
?>