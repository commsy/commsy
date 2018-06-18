<?PHP
// $Id$
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
//    along with CommSy

class cs_membershipInfo {
      var $_room_id;
      var $_source_item_id;
      var $_role_type;
      var $_source;

      function __construct($room_id, $source_item_id, $role_type, $source) {
         $this->_room_id = $room_id;
         $this->source_item_id = $source_item_id;
         $this->_role_type = $role_type;
         $this->_source = $source;
      }

      function getSourceSystem() {
         return $this->_source;
      }

      function getRoomId() {
         return $this->_room_id;
      }

      function getSourceItemId() {
         return $this->source_item_id;
      }

      function getRoleType() {
         return $this->_role_type;
      }
   }
?>
