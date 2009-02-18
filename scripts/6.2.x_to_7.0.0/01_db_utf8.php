<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

include_once('../migration.conf.php');
include_once('../db_link.dbi.php');
include_once('../update_functions.php');

function getCreateFieldForTable ( $array, $charset ) {
   $retour = '';
   $retour .= $array['Field'].' '.$array['Type'];
   if ( !empty($array['Collation']) ) {
      if ( $charset == 'utf8' ) {
         $array['Collation'] = 'utf8_general_ci';
      }
      $retour .= ' COLLATE '.$array['Collation'];
   }
   if ( !empty($array['Null']) ) {
      if ( $array['Null'] == 'YES' ) {
          $retour .= ' NULL';
      } else {
          $retour .= ' NOT NULL';
      }
   }
   if ( isset($array['Default']) and $array['Default'] != '' ) {
      if ( $array['Default'] == 'CURRENT_TIMESTAMP' ) {
         $retour .= ' default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP';
      } else {
         $retour .= ' default \''.$array['Default'].'\'';
      }
   }
   return $retour;
}

function getPrimaryKeyForTable ( $array ) {
   $retour = '';
   $prikey_array = array();
   foreach ( $array as $column ) {
      if ( !empty($column['Key'])
           and $column['Key'] == 'PRI'
         ) {
         $prikey_array[] = $column['Field'];
      }
   }
   $retour = 'PRIMARY KEY ('.implode(',',$prikey_array).')';
   return $retour;
}

function getKeyForTable ( $array ) {
   $retour = '';
   $key_array = array();
   foreach ( $array as $column ) {
      if ( !empty($column['Key'])
           and $column['Key'] == 'MUL'
         ) {
         $key_array[] = $column['Field'];
      }
   }

   if ( !empty($key_array) ) {
      $first = true;
      foreach ( $key_array as $key ) {
         if ( $first ) {
            $first = false;
         } else {
            $retour .= ', ';
         }
         $retour .= 'KEY '.$key.' ('.$key.')';
      }
   }

   return $retour;
}

function getCreateTableSQL ( $name, $array, $charset = 'latin1' ) {
   $field_array = array();
   $retour = 'CREATE TABLE '.$name.' (';
   foreach ($array as $row ) {
      $field_array[] = getCreateFieldForTable($row,$charset);
   }
   $retour .= implode(', ',$field_array);
   $retour .= ', '.getPrimaryKeyForTable($array);
   $keys = getKeyForTable($array);
   if ( !empty($keys) ) {
      $retour .= ', '.$keys;
   }
   if ( $charset == 'utf8' ) {
      $retour .= ') ENGINE = MYISAM DEFAULT CHARSET = utf8 COLLATE = utf8_general_ci;';
   } else {
      $retour .= ') ENGINE = MYISAM DEFAULT CHARSET = latin1 COLLATE = latin1_german1_ci;';
   }
   return $retour;
}

// time management for this script
$time_start = getmicrotime();

// warning
/*
echo('DATABASE: migrate content from latin1 to utf8');
echo(LINEBREAK);
echo('---------------------------------------------');
echo(LINEBREAK);
echo(LINEBREAK);
echo('WARNING: please dump your database before run this script');
echo(LINEBREAK);
echo('         script will start in 10 seconds');
echo(LINEBREAK);
echo('         please stop this script, if you haven\'t made a dump');
echo(LINEBREAK);

$count = 10;
init_progress_bar($num,'Wait 10 seconds','10 sec');
for ( $i = 0; $i < 10; $i++ ) {
   sleep(1);
   update_progress_bar($count);
}
*/

// begin migration
echo('1.STEP: set character set and collation to latin1');
echo(LINEBREAK);
flush();

$success_array = array();

echo('database: '.$DB_Name);
$sql = 'SHOW VARIABLES LIKE "collation_database";';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( empty($row['Value']) or $row['Value'] != 'latin1_german1_ci' ) {
   $sql = 'ALTER DATABASE '.$DB_Name.' CHARACTER SET latin1 COLLATE latin1_german1_ci;';
   if ( select($sql) ) {
      $success[] = true;
      echo(' done');
   } else {
      $success[] = false;
      echo(' error');
   }
} else {
   echo(' nothing to do');
}
echo(LINEBREAK);
flush();

echo('tables');
echo(LINEBREAK);
flush;

$sql = 'SHOW TABLES;';
$result = select($sql);
$table_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   $table_full_array[] = $row['Tables_in_'.$DB_Name];
   if ( !stristr($row['Tables_in_'.$DB_Name],'old_')
        and !stristr($row['Tables_in_'.$DB_Name],'utf8_')
      ) {
      $table_array[] = $row['Tables_in_'.$DB_Name];
   }
}

init_progress_bar(count($table_array));
foreach ( $table_array as $table ) {
   $sql = 'SHOW TABLE STATUS FROM '.$DB_Name.' WHERE name="'.$table.'";';
   $result = select($sql);
   $row = mysql_fetch_assoc($result);
   if ( empty($row['Collation']) or $row['Collation'] != 'latin1_german1_ci' ) {
      $sql = 'ALTER TABLE '.$table.' CHARACTER SET latin1 COLLATE latin1_german1_ci;';
      $success[] = select($sql);
   }

   $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
   $result = select($sql);
   $column_array = array();
   while ($row = mysql_fetch_assoc($result) ) {
      if ( !empty($row['Collation'])
           and $row['Collation'] != 'latin1_german1_ci'
           and $row['Collation'] != 'latin1_german2_ci'
         ) {
         $column_array[] = $row;
      }
   }
   if ( !empty($column_array) ) {
      foreach ( $column_array as $column ) {
         $sql = ' ALTER TABLE '.$table.' CHANGE '.$column['Field'].' '.$column['Field'].' '.$column['Type'].' CHARACTER SET latin1 COLLATE latin1_german1_ci';
         if ( !empty($column['Null']) and $column['Null'] == 'YES' ) {
            $sql .= ' NULL DEFAULT ';
            if ( !empty($column['Default']) ) {
               $sql .= $column['Default'];
            } else {
               $sql .= 'NULL';
            }
         } else {
            $sql .= ' NOT NULL';
         }
         $sql .= ';';
         $success[] = select($sql);
      }
   }

   update_progress_bar(count($table_array));
}

// copy content
echo(LINEBREAK);
echo(LINEBREAK);
echo('STEP2: rename tables with content');
echo(LINEBREAK);
init_progress_bar(count($table_array));
$table_no_copy_array = array();
$table_no_copy_array[] = 'file_multi_upload';
$table_no_copy_array[] = 'session';
foreach ( $table_array as $table ) {
   if ( !in_array($table,$table_no_copy_array)
         and !in_array('old_'.$table,$table_full_array)
      ) {
      $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
      $result = select($sql);
      $column_array = array();
      while ($row = mysql_fetch_assoc($result) ) {
          $column_array[] = $row;
      }

      $sql  = 'DROP TABLE IF EXISTS old_'.$table.';';
      $success_array[] = select($sql);

      $sql = getCreateTableSQL('old_'.$table,$column_array);
      $success_array[] = select($sql);

      $sql = 'INSERT INTO old_'.$table.' SELECT * FROM '.$table.';';
      $success_array[] = select($sql);

      $sql = 'DROP TABLE '.$table.';';
      #$success_array[] = select($sql);
   }
   update_progress_bar(count($table_array));
}
echo(LINEBREAK);

// create utf8 tables
echo(LINEBREAK);
echo('STEP3: create utf8-tables');
echo(LINEBREAK);
flush();

init_progress_bar(count($table_array));
foreach ( $table_array as $table ) {
   if ( !in_array($table,$table_no_copy_array)
         and !in_array('utf8_'.$table,$table_full_array)
      ) {
      $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
      $result = select($sql);
      $column_array = array();
      while ($row = mysql_fetch_assoc($result) ) {
          $column_array[] = $row;
      }

      $sql  = 'DROP TABLE IF EXISTS utf8_'.$table.';';
      $success_array[] = select($sql);

      $sql = getCreateTableSQL('utf8_'.$table,$column_array,'utf8');
      $success_array[] = select($sql);
   }
   update_progress_bar(count($table_array));
}
echo(LINEBREAK);

// rest tables to utf8
echo(LINEBREAK);
echo('STEP6: set tabel session and file_multi_upload to utf8');
echo(LINEBREAK);
flush();

init_progress_bar(count($table_no_copy_array));
foreach ( $table_no_copy_array as $table ) {
   $sql = 'SHOW TABLE STATUS FROM '.$DB_Name.' WHERE name="'.$table.'";';
   $result = select($sql);
   $row = mysql_fetch_assoc($result);
   if ( empty($row['Collation']) or $row['Collation'] != 'utf8_general_ci' ) {
      $sql = 'ALTER TABLE '.$table.' CHARACTER SET utf8 COLLATE utf8_general_ci;';
      #$success[] = select($sql);
   }

   $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
   $result = select($sql);
   $column_array = array();
   while ($row = mysql_fetch_assoc($result) ) {
      if ( !empty($row['Collation'])
           and $row['Collation'] != 'utf8_general_ci'
         ) {
         $column_array[] = $row;
      }
   }

   if ( !empty($column_array) ) {
      foreach ( $column_array as $column ) {
         $sql = ' ALTER TABLE '.$table.' CHANGE '.$column['Field'].' '.$column['Field'].' '.$column['Type'].' CHARACTER SET utf8 COLLATE utf8_general_ci';
         if ( !empty($column['Null']) and $column['Null'] == 'YES' ) {
            $sql .= ' NULL DEFAULT ';
            if ( !empty($column['Default']) ) {
               $sql .= $column['Default'];
            } else {
               $sql .= 'NULL';
            }
         } else {
            $sql .= ' NOT NULL';
         }
         $sql .= ';';
         #$success[] = select($sql);
      }
   }

   update_progress_bar(count($table_no_copy_array));
}

echo(LINEBREAK);
flush();

// delete old_tables
echo(LINEBREAK);
echo('STEP7: delete old tables');
echo(LINEBREAK);
flush();
$sql = 'SHOW TABLES;';
$result = select($sql);
$table_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   if ( stristr($row['Tables_in_'.$DB_Name],'old_')
        or stristr($row['Tables_in_'.$DB_Name],'utf8_')
      ) {
      $table_array[] = $row['Tables_in_'.$DB_Name];
   }
}
foreach ($table_array as $table) {
   $sql = 'DROP TABLE '.$table.';';
   #$success_array[] = select($sql);
}
echo('done');
echo(LINEBREAK);
flush();

// database to utf8
/*
echo('STEP8: set '.$DB_Name.' to utf8');
echo(LINEBREAK);
flush();
$sql = 'SHOW VARIABLES LIKE "collation_database";';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( empty($row['Value']) or $row['Value'] != 'utf8_general_ci' ) {
   $sql = 'ALTER DATABASE '.$DB_Name.' CHARACTER SET utf8 COLLATE utf8_general_ci;';
   if ( select($sql) ) {
      $success[] = true;
      echo('done');
   } else {
      $success[] = false;
      echo('error');
   }
} else {
   echo('nothing to do');
}
echo(LINEBREAK);
flush();
*/

// last step
$success = true;
foreach ( $success_array as $success_item ) {
   $success = $success && $success_item;
}

// end of execution time
$time_end = getmicrotime();
$time = round($time_end - $time_start,3);
if ( empty($bash) or !$bash) {
   echo "<br/>";
} else {
   echo "\n";
}
echo "Execution time: ".sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>