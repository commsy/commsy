<?php
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

   switch( $tempModule )
   {
      case 'ACCOUNT':
         $tempMessage = getMessage('COMMON_PAGETITLE_ACCOUNT');
         break;
      case 'AGB':
         $tempMessage = getMessage('COMMON_PAGETITLE_AGB');
         break;
      case 'ANNOTATION':
         $tempMessage = getMessage('COMMON_PAGETITLE_ANNOTATION');
         break;
      case 'ANNOUNCEMENT':
         $tempMessage = getMessage('COMMON_PAGETITLE_ANNOUNCEMENT');
         break;
      case 'BUZZWORDS':
         $tempMessage = getMessage('COMMON_PAGETITLE_BUZZWORDS');
         break;
      case 'CAMPUS_SEARCH':
         $tempMessage = getMessage('COMMON_PAGETITLE_CAMPUS_SEARCH');
         break;
      case 'CHAT':
         $tempMessage = getMessage('COMMON_PAGETITLE_CHAT');
         break;
      case 'CONFIGURATION':
         $tempMessage = getMessage('COMMON_PAGETITLE_CONFIGURATION');
         break;
      case 'DATE':
         $tempMessage = getMessage('COMMON_PAGETITLE_DATE');
         break;
      case 'DISCARTICLE':
         $tempMessage = getMessage('COMMON_PAGETITLE_DISCARTICLE');
         break;
      case 'DISCUSSION':
         $tempMessage = getMessage('COMMON_PAGETITLE_DISCUSSION');
         break;
      case 'GROUP':
         $tempMessage = getMessage('COMMON_PAGETITLE_GROUP');
         break;
      case 'HELP':
         $tempMessage = getMessage('COMMON_PAGETITLE_HELP');
         break;
      case 'HOME':
         $tempMessage = getMessage('COMMON_PAGETITLE_HOME');
         break;
      case 'INSTITUTION':
         $tempMessage = getMessage('COMMON_PAGETITLE_INSTITUTION');
         break;
      case 'LABELS':
         $tempMessage = getMessage('COMMON_PAGETITLE_LABELS');
         break;
      case 'LANGUAGE':
         $tempMessage = getMessage('COMMON_PAGETITLE_LANGUAGE');
         break;
      case 'MAIL':
         $tempMessage = getMessage('COMMON_PAGETITLE_MAIL');
         break;
      case 'MATERIAL':
         $tempMessage = getMessage('COMMON_PAGETITLE_MATERIAL');
         break;
      case 'MATERIALTYPE':
         $tempMessage = getMessage('COMMON_PAGETITLE_MATERIALTYPE');
         break;
      case 'MATERIAL_ADMIN':
         $tempMessage = getMessage('COMMON_PAGETITLE_MATERIAL_ADMIN');
         break;
      case 'MYROOM':
         $tempMessage = getMessage('COMMON_PAGETITLE_MYROOM');
         break;
      case 'PROJECT':
         $tempMessage = getMessage('COMMON_PAGETITLE_PROJECT');
         break;
      case 'SECTION':
         $tempMessage = getMessage('COMMON_PAGETITLE_SECTION');
         break;
      case 'TODO':
         $tempMessage = getMessage('COMMON_PAGETITLE_TODO');
         break;
      case 'TOPIC':
         $tempMessage = getMessage('COMMON_PAGETITLE_TOPIC');
         break;
      case 'USER':
         $tempMessage = getMessage('COMMON_PAGETITLE_USER');
         break;
      default:
         $tempMessage = getMessage('COMMON_MESSAGETAG_ERROR');
         break;
   }
?>