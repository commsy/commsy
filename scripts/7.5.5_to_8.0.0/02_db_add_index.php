<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2011 Dr. Iver Jackewitz
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

// headline
$this->_flushHeadline('db: add indexes to tables and zzz-equivalents');

$success = true;

if ( !$this->_existsIndex('labels','type') ) {
   $sql = "ALTER TABLE labels ADD INDEX ( type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_labels','type') ) {
   $sql = "ALTER TABLE zzz_labels ADD INDEX ( type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('user','deletion_date') ) {
   $sql = "ALTER TABLE user ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_user','deletion_date') ) {
   $sql = "ALTER TABLE zzz_user ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('user','deleter_id') ) {
   $sql = "ALTER TABLE user ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_user','deleter_id') ) {
   $sql = "ALTER TABLE zzz_user ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('user','status') ) {
   $sql = "ALTER TABLE user ADD INDEX ( status );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_user','status') ) {
   $sql = "ALTER TABLE zzz_user ADD INDEX ( status );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('link_items','first_item_type') ) {
   $sql = "ALTER TABLE link_items ADD INDEX ( first_item_type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_link_items','first_item_type') ) {
   $sql = "ALTER TABLE zzz_link_items ADD INDEX ( first_item_type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('link_items','second_item_type') ) {
   $sql = "ALTER TABLE link_items ADD INDEX ( second_item_type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_link_items','second_item_type') ) {
   $sql = "ALTER TABLE zzz_link_items ADD INDEX ( second_item_type );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('link_items','deletion_date') ) {
   $sql = "ALTER TABLE link_items ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_link_items','deletion_date') ) {
   $sql = "ALTER TABLE zzz_link_items ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('link_items','deleter_id') ) {
   $sql = "ALTER TABLE link_items ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_link_items','deleter_id') ) {
   $sql = "ALTER TABLE zzz_link_items ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('user','is_contact') ) {
   $sql = "ALTER TABLE user ADD INDEX ( is_contact );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_user','is_contact') ) {
   $sql = "ALTER TABLE zzz_user ADD INDEX ( is_contact );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('homepage_link_page_page','to_item_id') ) {
   $sql = "ALTER TABLE homepage_link_page_page ADD INDEX ( to_item_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_homepage_link_page_page','to_item_id') ) {
   $sql = "ALTER TABLE zzz_homepage_link_page_page ADD INDEX ( to_item_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('tag2tag','deletion_date') ) {
   $sql = "ALTER TABLE tag2tag ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_tag2tag','deletion_date') ) {
   $sql = "ALTER TABLE zzz_tag2tag ADD INDEX ( deletion_date );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('tag2tag','deleter_id') ) {
   $sql = "ALTER TABLE tag2tag ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsIndex('zzz_tag2tag','deleter_id') ) {
   $sql = "ALTER TABLE zzz_tag2tag ADD INDEX ( deleter_id );";
   $success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>