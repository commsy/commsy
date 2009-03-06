<?php
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2007 Dirk Bloessl, Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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
   if ( !empty($array['Extra']) ) {
      $retour .= ' '.$array['Extra'];
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

function utf8_encode_extras ( $value ) {
   $retour = '';
   if ( !empty($value) ) {
      $array = unserialize($value);
      $retour = serialize(utf8_encode_array($array));
   }
   return $retour;
}

function utf8_encode_array ( $array ) {
   $retour = array();
   if ( !empty($array) ) {
      foreach ( $array as $key => $value ) {
         if ( is_array($value) ) {
            $retour[$key] = utf8_encode_array($value);
         } else {
           if ( is_utf8($value) ) {
              $retour[$key] = $value;
           } else {
              $retour[$key] = utf8_encode($value);
           }
         }
      }
   }
   return $retour;
}

function utf8_decode_extras ( $value ) {
   $retour = '';
   if ( !empty($value) ) {
      $array = unserialize($value);
      $retour = serialize(utf8_decode_array($array));
   }
   return $retour;
}

function utf8_decode_array ( $array ) {
   $retour = array();
   if ( !empty($array) ) {
      foreach ( $array as $key => $value ) {
         if ( is_array($value) ) {
            $retour[$key] = utf8_decode_array($value);
         } else {
            $retour[$key] = utf8_decode($value);
         }
      }
   }
   return $retour;
}

function is_utf8($string) {
   // From http://w3.org/International/questions/qa-forms-utf-8.html
   return preg_match('%^(?:
         [\x09\x0A\x0D\x20-\x7E]            # ASCII
       | [\xC2-\xDF][\x80-\xBF]            # non-overlong 2-byte
       |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
       | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
       |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
       |  \xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
       | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
       |  \xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
   )*$%xs', $string);
}


// time management for this script
$time_start = getmicrotime();

// warning
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
echo(LINEBREAK);

// begin migration
echo(LINEBREAK);
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
echo(LINEBREAK);

// copy content
echo(LINEBREAK);
echo('STEP2: rename tables with content');
echo(LINEBREAK);
init_progress_bar(count($table_array));
$table_no_copy_array = array();
$table_no_copy_array[] = 'file_multi_upload';
$table_no_copy_array[] = 'session';

foreach ( $table_array as $table ) {
   if ( !in_array($table,$table_no_copy_array) ) {
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
   }
   update_progress_bar(count($table_array));
}
echo(LINEBREAK);

// create utf8 tables
echo(LINEBREAK);
echo('STEP3: create utf8-tables');
echo(' and copy content for tables without extras');
echo(LINEBREAK);
flush();

$tabel_extra_array = array();
$tabel_extra_array[] = 'auth_source';
$tabel_extra_array[] = 'files';
$tabel_extra_array[] = 'labels';
$tabel_extra_array[] = 'materials';
$tabel_extra_array[] = 'portal';
$tabel_extra_array[] = 'room';
$tabel_extra_array[] = 'server';
$tabel_extra_array[] = 'user';

init_progress_bar(count($table_array));
foreach ( $table_array as $table ) {
   if ( !in_array($table,$table_no_copy_array) ) {
      $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
      $result = select($sql);
      $column_array = array();
      while ($row = mysql_fetch_assoc($result) ) {
          $column_array[] = $row;
      }

      $sql  = 'DROP TABLE IF EXISTS utf8_'.$table.';';
      $success_array[] = select($sql);

      if ( in_array($table,$tabel_extra_array) ) {
         $sql = getCreateTableSQL('utf8_'.$table,$column_array,'utf8');
         $success_array[] = select($sql);
      } else {
         $sql = getCreateTableSQL('utf8_'.$table,$column_array);
         $success_array[] = select($sql);

         $sql = 'INSERT INTO utf8_'.$table.' SELECT * FROM '.$table.';';
         $success_array[] = select($sql);

         $sql = 'ALTER TABLE utf8_'.$table.' CONVERT TO CHARACTER SET utf8 COLLATE utf8_general_ci;';
         $success_array[] = select($sql);
      }
   }
   update_progress_bar(count($table_array));
}
echo(LINEBREAK);

// copy content from old to utf8
echo(LINEBREAK);
echo('STEP4: copy content');
echo(' for tables with extras');
echo(LINEBREAK);
flush();

$sql = 'SHOW TABLES;';
$result = select($sql);
$table_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   $table_full_array[] = $row['Tables_in_'.$DB_Name];
   if ( stristr($row['Tables_in_'.$DB_Name],'old_') ) {
      $table_array[] = $row['Tables_in_'.$DB_Name];
   }
}

foreach ( $table_array as $table ) {
   if ( in_array(str_replace('old_','',$table),$tabel_extra_array) ) {
      echo(LINEBREAK);
      echo(str_replace('old_','',$table));
      $sql = 'SELECT count(*) as count FROM '.$table.';';
      $result = select($sql);
      $row = mysql_fetch_assoc($result);
      $count = $row['count'];
      if ( $count > 0 ) {
         init_progress_bar($count);
         for ( $i=0; $i<$count; $i++ ) {
            $sql = 'SELECT * FROM '.$table.' LIMIT '.$i.',1;';
            $result = select($sql);
            $row = mysql_fetch_assoc($result);
            if ( !empty($row) ) {
               $sql = 'INSERT INTO '.str_replace('old_','utf8_',$table).' SET';
               $first = true;
               foreach ( $row as $key => $value ) {
                  if ( isset($value) ) {
                     if ( $first ) {
                        $first = false;
                     } else {
                        $sql .= ',';
                     }
                     $sql .= ' '.$key;
                     if ( $value == 'NULL' ) {
                        $sql .= ' = NULL';
                     } elseif ( $key == 'extras' ) {
                        $sql .= '="'.addslashes(utf8_encode_extras($value)).'"';
                     } else {
                        if ( !is_utf8($value) ) {
                           $sql .= '="'.addslashes(utf8_encode($value)).'"';
                        } else {
                           $sql .= '="'.addslashes($value).'"';
                        }
                        // mysql_real_escape_string ???
                     }
                  }
               }
               $sql .= ';';
               $success_array[] = insert($sql,'utf8');
            }
            update_progress_bar($count);
         }
      } else {
         echo(LINEBREAK);
         echo('nothing to do');
         echo(LINEBREAK);
      }
      echo(LINEBREAK);
   }
}
flush();

// copy content from old to utf8
echo(LINEBREAK);
echo('STEP5: verifying');
echo(LINEBREAK);
flush();

$table_array_dont_verify = array();
$table_array_dont_verify[] = 'external2commsy_id';
$table_array_dont_verify[] = 'hash';
$table_array_dont_verify[] = 'homepage_link_page_page';
$table_array_dont_verify[] = 'items';
$table_array_dont_verify[] = 'item_link_file';
$table_array_dont_verify[] = 'links';
$table_array_dont_verify[] = 'link_items';
$table_array_dont_verify[] = 'link_modifier_item';
$table_array_dont_verify[] = 'log';
$table_array_dont_verify[] = 'log_ads';
$table_array_dont_verify[] = 'log_archive';
$table_array_dont_verify[] = 'log_error';
$table_array_dont_verify[] = 'log_message_tag';
$table_array_dont_verify[] = 'noticed';
$table_array_dont_verify[] = 'reader';
$table_array_dont_verify[] = 'tag2tag';
$table_array_dont_verify[] = 'tasks';


$sql = 'SHOW TABLES;';
$result = select($sql);
$table_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   $table_full_array[] = $row['Tables_in_'.$DB_Name];
   if ( stristr($row['Tables_in_'.$DB_Name],'old_')
        and !in_array(str_replace('old_','',$row['Tables_in_'.$DB_Name]),$table_array_dont_verify)
      ) {
      $table_array[] = $row['Tables_in_'.$DB_Name];
   }
}

$error_log_array = array();
$char_trans_array = array();
$char_trans_array[128] = 'EUR'; // EURO Zeichen
$char_trans_array[130] = ",";
$char_trans_array[131] = ",";
$char_trans_array[131] = 'f';   // mathematisches f f�r Formel
$char_trans_array[132] = '"';
$char_trans_array[133] = '...';
$char_trans_array[133] = ':';   // Zwei Kreuze �bereinander
$char_trans_array[145] = "'";
$char_trans_array[146] = "'";
$char_trans_array[147] = '"';
$char_trans_array[148] = '"';
$char_trans_array[149] = '*';   // Aufz�hlungspunkt
$char_trans_array[150] = '-';
$char_trans_array[151] = '-';
$char_trans_array[153] = 'TM';   // TM hochgestellt

foreach ( $table_array as $table ) {
   echo(LINEBREAK);
   echo(str_replace('old_','',$table));
   $sql = 'SELECT count(*) as count FROM '.$table.';';
   $result = select($sql);
   $row = mysql_fetch_assoc($result);
   $count = $row['count'];
   if ( $count > 0 ) {
      init_progress_bar($count);
      for ( $i=0; $i<$count; $i++ ) {
         $sql = 'SELECT * FROM '.$table.' LIMIT '.$i.',1;';
         $result = select($sql);
         $row = mysql_fetch_assoc($result);
         $row_orig = $row;

         $sql2 = 'SELECT * FROM '.str_replace('old_','utf8_',$table).' LIMIT '.$i.',1;';
         $result2 = select($sql2,false,'utf8');
         $row2 = mysql_fetch_assoc($result2);
         $row2_orig = $row2;

         if ( !empty($row) and !empty($row2) ) {
            foreach ( $row2 as $key2 => $value2 ) {
               if ( $key2 == 'extras' ) {
                  $row2[$key2] = utf8_decode_array(unserialize($value2));
                  if ( empty($row2[$key2]) ) {
                     $row2[$key2] = '';
                  }
                  $row[$key2] = unserialize($row[$key2]);
                  if ( empty($row[$key2]) ) {
                     $row[$key2] = '';
                  }
               } else {
                  $row2[$key2] = utf8_decode($value2);
               }
            }
            $diff = array();
            $diff = array_diff($row,$row2);
            if ( !empty($diff) ) {
               foreach ( $diff as $key => $value ) {
                  if ( $value != 'NULL' ) {
                     if ( stristr($table,'log_') or $table == 'log' ) {
                        $error_log_array[$table] = str_replace('old_','',$table);
                     } else {
                        // str_split() expects parameter 1 to be string, array given
                        // extras ???
                        $array_orig = str_split($row[$key]);
                        $array_utf8 = str_split($row2[$key]);
                        $diff_array = array();
                        foreach ($array_orig as $place => $char) {
                           if ( $char != $array_utf8[$place] ) {
                              $diff_array[$place] = $char;
                           }
                        }

                        foreach ($diff_array as $place => $char ) {
                           #$row2[$key][$place] = chr(ord($char));
                           if ( !empty($char_trans_array[ord($char)]) ) {
                              // extras anders behandeln !!!
                              $row2[$key][$place] = $char_trans_array[ord($char)];
                           } else {
                              echo(LINEBREAK);
                              echo('keine �bersetzung f�r '.$char.' ('.ord($char).')');
                              echo(LINEBREAK);
                           }
                        }

                        $sql  = 'UPDATE '.str_replace('old_','utf8_',$table);
                        $sql .= ' SET '.$key.'="'.addslashes(utf8_encode($row2[$key])).'"';
                        $sql .= ' WHERE ';

                        $first = true;
                        foreach ( $row2_orig as $key_update => $value_update ) {
                           if ( is_numeric($value_update) ) {
                              if ( $first ) {
                                 $first = false;
                              } else {
                                 $sql .= ' and ';
                              }
                              $sql .= $key_update.'="'.addslashes($value_update).'"';
                           }
                        }
                        $sql .= ';';
                        $success_array[] = select($sql,false,'utf8');

                        /*
                        $sql  = 'SELECT '.$key.' FROM '.str_replace('old_','utf8_',$table);
                        $sql .= ' WHERE ';

                        $first = true;
                        foreach ( $row2_orig as $key_update => $value_update ) {
                           if ( is_numeric($value_update) ) {
                              if ( $first ) {
                                 $first = false;
                              } else {
                                 $sql .= ' and ';
                              }
                              $sql .= $key_update.'="'.addslashes($value_update).'"';
                           }
                        }
                        $sql .= ';';
                        $result_update = select($sql,false,'utf8');
                        $row_update = mysql_fetch_assoc($result_update);
                        if ( utf8_decode($row_update[$key]) != $row_orig[$key] ) {
                           pr($row_orig[$key]);
                           pr($row_update[$key]);
                           pr(utf8_decode($row_update[$key]));
                           #$success_array[] = false;
                        }
                        */
                     }
                  }
               }
            }
         }
         update_progress_bar($count);
      }
   } else {
      echo(LINEBREAK);
      echo('nothing to do');
      echo(LINEBREAK);
   }
   echo(LINEBREAK);
}

if ( isset($error_log_array) and !empty($error_log_array) ) {
   echo(LINEBREAK);
   echo('errors occur in the following log-tabels');
   foreach ( $error_log_array as $table ) {
      echo(LINEBREAK);
      echo('- '.$table);
   }
   echo(LINEBREAK);
   echo('don\'t worry about that');
   echo(LINEBREAK);
}
flush();

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
      $success_array[] = select($sql);
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
         $success_array[] = select($sql);
      }
   }

   update_progress_bar(count($table_no_copy_array));
}

echo(LINEBREAK);
flush();

// copy content
echo(LINEBREAK);
echo('STEP7: rename utf8-tables with content');
echo(LINEBREAK);

$sql = 'SHOW TABLES;';
$result = select($sql,false,'utf8');
$utf8_table_array = array();
while ($row = mysql_fetch_assoc($result) ) {
   if ( stristr($row['Tables_in_'.$DB_Name],'utf8_') ) {
      $utf8_table_array[] = $row['Tables_in_'.$DB_Name];
   }
}

init_progress_bar(count($utf8_table_array));
foreach ( $utf8_table_array as $table ) {
   $sql = 'SHOW FULL COLUMNS FROM '.$table.';';
   $result = select($sql,false,'utf8');
   $column_array = array();
   while ($row = mysql_fetch_assoc($result) ) {
       $column_array[] = $row;
   }

   $sql  = 'DROP TABLE IF EXISTS '.str_replace('utf8_','',$table).';';
   $success_array[] = select($sql,false,'utf8');

   $sql = getCreateTableSQL(str_replace('utf8_','',$table),$column_array,'utf8');
   $success_array[] = select($sql,false,'utf8');

   $sql = 'INSERT INTO '.str_replace('utf8_','',$table).' SELECT * FROM '.$table.';';
   $success_array[] = select($sql,false,'utf8');

   $sql = 'DROP TABLE '.$table.';';
   $success_array[] = select($sql,false,'utf8');

   update_progress_bar(count($utf8_table_array));
}
echo(LINEBREAK);
flush();

// delete old_tables
echo(LINEBREAK);
echo('STEP8: delete old tables');
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
   $success_array[] = select($sql);
}
echo('done');
echo(LINEBREAK);
flush();

// database to utf8
echo(LINEBREAK);
echo('STEP9: set '.$DB_Name.' to utf8');
echo(LINEBREAK);
flush();
$sql = 'SHOW VARIABLES LIKE "collation_database";';
$result = select($sql);
$row = mysql_fetch_assoc($result);
if ( empty($row['Value']) or $row['Value'] != 'utf8_general_ci' ) {
   $sql = 'ALTER DATABASE '.$DB_Name.' CHARACTER SET utf8 COLLATE utf8_general_ci;';
   if ( select($sql) ) {
      $success_array[] = true;
      echo('done');
   } else {
      $success_array[] = false;
      echo('error');
   }
} else {
   echo('nothing to do');
}
echo(LINEBREAK);
flush();

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
echo "Execution time: ".mb_sprintf("%02d:%02d:%02d", (int)($time/3600), (int)(fmod($time,3600)/60), (int)fmod(fmod($time,3600), 60))."\n";
?>