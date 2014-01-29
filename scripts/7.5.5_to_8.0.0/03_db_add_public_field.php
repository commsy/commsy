<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2012 Dr. Iver Jackewitz
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
$this->_flushHeadline('db: add public field to several tables');

$success = true;

if ( !$this->_existsField('annotations','public') ) {
   $sql = "ALTER TABLE annotations ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('discussionarticles','public') ) {
   $sql = "ALTER TABLE discussionarticles ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('section','public') ) {
   $sql = "ALTER TABLE section ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('step','public') ) {
   $sql = "ALTER TABLE step ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
   $success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('zzz_annotations','public') ) {
	$sql = "ALTER TABLE zzz_annotations ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
	$success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('zzz_discussionarticles','public') ) {
	$sql = "ALTER TABLE zzz_discussionarticles ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
	$success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('zzz_section','public') ) {
	$sql = "ALTER TABLE zzz_section ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
	$success = $success AND $this->_select($sql);
}

if ( !$this->_existsField('zzz_step','public') ) {
	$sql = "ALTER TABLE zzz_step ADD public TINYINT( 11 ) NOT NULL DEFAULT '0'";
	$success = $success AND $this->_select($sql);
}

$this->_flushHTML(BRLF);
?>