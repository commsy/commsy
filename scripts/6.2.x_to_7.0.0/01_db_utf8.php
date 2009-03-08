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

set_time_limit(0);

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
      $array = mb_unserialize($value);
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
      $array = mb_unserialize($value);
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

function changeCharInArray ( $array, $array2, $char_array ) {
   if ( is_array($array) and is_array($array2) ) {
      foreach ( $array as $key => $value ) {
         if ( is_array($value) ) {
            $array2[$key] = changeCharInArray($array[$key],$array2[$key],$char_array);
         } else {
            $array2[$key] = changeCharInString($array[$key],$array2[$key],$char_array);
         }
      }
   }
   return $array2;
}

function changeCharInString ( $string, $string2, $char_array ) {
   $string = html_entity_decode($string);
   $string2 = html_entity_decode($string2);
   if ( $string != $string2 ) {
      $array_orig = str_split($string);
      $array_utf8 = str_split($string2);
      $diff_array = array_diff($array_orig,$array_utf8);
      foreach ($diff_array as $place => $char ) {
         if ( !empty($char_array[ord($char)]) ) {
            if ( $string2[$place] != '?' ) {
               for ($i=($place-10); $i<=($place+10); $i++) {
                  if ( $string2[$i] == '?' ) {
                     if ( strlen($char_array[ord($char)]) == 1 ) {
                        $string2[$place] = $char_array[ord($char)];
                     } else {
                        $string2 = substr($string2,0,$place).$char_array[ord($char)].substr($string2,($place+1));
                     }
                     $string2[$i] = $char_array[ord($char)];
                     break;
                  }
               }
            } else {
               if ( strlen($char_array[ord($char)]) == 1 ) {
                  $string2[$place] = $char_array[ord($char)];
               } else {
                  $string2 = substr($string2,0,$place).$char_array[ord($char)].substr($string2,($place+1));
               }
            }
         } else {
            #pr($string);
            #pr($string2);
            echo(LINEBREAK);
            echo('keine &Uuml;bersetzung f&uuml;r '.$char.' ('.ord($char).')');
            echo(LINEBREAK);
            #exit();
         }
      }
   }
   return $string2;
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
         select($sql,true);
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
         select($sql,true);
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

// verifying some tables
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

/*
$table_array_dont_verify[] = 'annotations';
$table_array_dont_verify[] = 'announcement';
$table_array_dont_verify[] = 'auth';
$table_array_dont_verify[] = 'auth_source';
$table_array_dont_verify[] = 'dates';
$table_array_dont_verify[] = 'discussionarticles';
$table_array_dont_verify[] = 'discussions';
$table_array_dont_verify[] = 'files';
$table_array_dont_verify[] = 'homepage_page';
$table_array_dont_verify[] = 'labels';
$table_array_dont_verify[] = 'materials';
$table_array_dont_verify[] = 'portal';
$table_array_dont_verify[] = 'room';
$table_array_dont_verify[] = 'section';
$table_array_dont_verify[] = 'server';
$table_array_dont_verify[] = 'step';
$table_array_dont_verify[] = 'tag';
$table_array_dont_verify[] = 'todos';
#$table_array_dont_verify[] = 'user';
*/

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
$char_trans_array[131] = 'f';   // mathematisches f für Formel
$char_trans_array[132] = '"';
$char_trans_array[133] = '...';
$char_trans_array[134] = ':';   // †
$char_trans_array[135] = ':';   // Zwei Kreuze übereinander
$char_trans_array[136] = '^';   // ˆ
$char_trans_array[137] = '%';   // ‰
$char_trans_array[138] = 'S';   // Š
$char_trans_array[139] = '<';   //
$char_trans_array[140] = "CE";  // Œ
$char_trans_array[141] = " ";   //
$char_trans_array[142] = "Z";   // Ž
$char_trans_array[143] = " ";   //
$char_trans_array[144] = " ";   //
$char_trans_array[145] = "'";   //
$char_trans_array[146] = "'";
$char_trans_array[147] = '"';
$char_trans_array[148] = '"';
$char_trans_array[149] = '*';   // Aufzählungspunkt
$char_trans_array[150] = '-';
$char_trans_array[151] = '-';
$char_trans_array[152] = '~';   // ˜
$char_trans_array[153] = 'TM';  // TM hochgestellt
$char_trans_array[154] = 's';   // š
$char_trans_array[155] = '>';   // ›
$char_trans_array[156] = 'ae';  // œ
$char_trans_array[157] = ' ';   //
$char_trans_array[158] = 'z';   // ž
$char_trans_array[159] = 'Y';   // Ÿ
$char_trans_array[160] = ' ';   //
$char_trans_array[161] = '!';   // ¡
$char_trans_array[162] = 'e';   // ¢
$char_trans_array[163] = 'L';   // £
$char_trans_array[164] = 'o';   // ¤
$char_trans_array[165] = 'Y';   // ¥
$char_trans_array[166] = ':';   // ¦
$char_trans_array[167] = '§';   // §
$char_trans_array[168] = '-';   // ¨
$char_trans_array[169] = 'c';   // ©
$char_trans_array[170] = 'a';   // ª
$char_trans_array[171] = '<<';  // «
$char_trans_array[172] = '-';   // ¬
$char_trans_array[173] = ' ';   //
$char_trans_array[174] = 'R';   // ®
$char_trans_array[175] = '-';   // ¯
$char_trans_array[176] = 'o';   // °
$char_trans_array[177] = '+';   // ±
$char_trans_array[178] = '2';   // ²
$char_trans_array[179] = '3';   // ³
$char_trans_array[180] = "'";   // ´
$char_trans_array[181] = 'y';   // µ
$char_trans_array[182] = 'q';   // ¶
$char_trans_array[183] = '.';   // ·
$char_trans_array[184] = ',';   // ¸
$char_trans_array[185] = '1';   // ¹
$char_trans_array[186] = '0';   // º
$char_trans_array[187] = '>>';  // »
$char_trans_array[188] = '1/4';   // ¼
$char_trans_array[189] = '1/2';   // ½
$char_trans_array[190] = '3/4';   // ¾
$char_trans_array[191] = '?';   // ¿
$char_trans_array[192] = 'A';   // À
$char_trans_array[193] = 'A';   // Á
$char_trans_array[194] = 'A';   // Â
$char_trans_array[195] = 'A';   // Ã
$char_trans_array[196] = 'Ä';   // Ä
$char_trans_array[197] = 'A';   // Å
$char_trans_array[198] = 'AE';   // Æ
$char_trans_array[199] = 'C';   // Ç
$char_trans_array[200] = 'E';   // È
$char_trans_array[201] = 'E';   // É
$char_trans_array[202] = 'E';   // Ê
$char_trans_array[203] = 'E';   // Ë
$char_trans_array[204] = 'I';   // Ì
$char_trans_array[205] = 'I';   // Í
$char_trans_array[206] = 'I';   // Î
$char_trans_array[207] = 'I';   // Ï
$char_trans_array[208] = 'D';   // Ð
$char_trans_array[209] = 'N';   // Ñ
$char_trans_array[210] = 'O';   // Ò
$char_trans_array[211] = 'O';   // Ó
$char_trans_array[212] = 'O';   // Ô
$char_trans_array[213] = 'O';   // Õ
$char_trans_array[214] = 'Ö';   // Ö
$char_trans_array[215] = 'x';   // ×
$char_trans_array[216] = 'O';   // Ø
$char_trans_array[217] = 'U';   // Ù
$char_trans_array[218] = 'U';   // Ú
$char_trans_array[219] = 'U';   // Û
$char_trans_array[220] = 'Ü';   // Ü
$char_trans_array[221] = 'Y';   // Ý
$char_trans_array[222] = 'p';   // Þ
$char_trans_array[223] = 'ß';   // ß
$char_trans_array[224] = 'a';   // à
$char_trans_array[225] = 'a';   // á
$char_trans_array[226] = 'a';   // â
$char_trans_array[227] = 'a';   // ã
$char_trans_array[228] = 'ä';   // ä
$char_trans_array[229] = 'a';   // å
$char_trans_array[230] = 'ae';  // æ
$char_trans_array[231] = 'c';   // ç
$char_trans_array[232] = 'e';   // è
$char_trans_array[233] = 'e';   // é
$char_trans_array[234] = 'e';   // ê
$char_trans_array[235] = 'e';   // ë
$char_trans_array[236] = 'i';   // ì
$char_trans_array[237] = 'i';   // í
$char_trans_array[238] = 'i';   // î
$char_trans_array[239] = 'i';   // ï
$char_trans_array[240] = 'o';   // ð
$char_trans_array[241] = 'n';   // ñ
$char_trans_array[242] = 'o';   // ò
$char_trans_array[243] = 'o';   // ó
$char_trans_array[244] = 'o';   // ô
$char_trans_array[245] = 'o';   // õ
$char_trans_array[246] = 'ö';   // ö
$char_trans_array[247] = '+';   // ÷
$char_trans_array[248] = 'o';   // ø
$char_trans_array[249] = 'u';   // ù
$char_trans_array[250] = 'u';   // ú
$char_trans_array[251] = 'u';   // û
$char_trans_array[252] = 'ü';   // ü
$char_trans_array[253] = 'y';   // ý
$char_trans_array[254] = 'p';   // þ
$char_trans_array[255] = 'y';   // ÿ

foreach ( $table_array as $table ) {
   echo(LINEBREAK);
   echo(str_replace('old_','',$table));
   $sql = 'SELECT count(*) as count FROM '.$table;
   #$sql .= ' WHERE item_id="1237290"';
   $sql .= ';';
   $result = select($sql);
   $row = mysql_fetch_assoc($result);
   $count = $row['count'];
   if ( $count > 0 ) {
      init_progress_bar($count);
      for ( $i=0; $i<$count; $i++ ) {
         $sql = 'SELECT * FROM '.$table;
         #$sql .= ' WHERE item_id="1237290"';
         $sql .= ' LIMIT '.$i.',1;';
         $result = select($sql);
         $row = mysql_fetch_assoc($result);
         $row_orig = $row;

         $sql2 = 'SELECT * FROM '.str_replace('old_','utf8_',$table);
         #$sql2 .= ' WHERE item_id="1237290"';
         $sql2 .= ' LIMIT '.$i.',1;';
         $result2 = select($sql2,false,'utf8');
         $row2 = mysql_fetch_assoc($result2);
         $row2_orig = $row2;

         if ( !empty($row) and !empty($row2) ) {
            foreach ( $row2 as $key2 => $value2 ) {
               if ( $key2 != 'extras' ) {
                  $row2[$key2] = utf8_decode($value2);
               }
            }
            $diff = array();
            $diff = array_diff($row,$row2);

            // extra zerschossen?
            $del_extra = false;
            foreach ( $diff as $key_ex => $value_ex ) {
               if ( $key_ex == 'extras'
                    and substr_count($row['extras'], '?') == substr_count($row2['extras'], '?')
                  ) {
                  $del_extra = true;
               }
            }
            if ( $del_extra ) {
               unset($diff['extras']);
            }

            if ( !empty($diff) ) {
               foreach ( $diff as $key => $value ) {
                  if ( $value != 'NULL' ) {
                     if ( $key == 'extras' ) {
                        $row2[$key] = utf8_decode_array(mb_unserialize($row2_orig[$key]));
                        if ( empty($row2[$key]) ) {
                           $row2[$key] = '';
                        }
                        $row[$key] = mb_unserialize($row[$key]);
                        if ( empty($row[$key]) ) {
                           $row[$key] = '';
                        }
                        if ( !empty($row[$key]) and empty($row2[$key]) ) {
                           #if ( $row2['item_id'] == 1237290 ) {
                           #pr($row2['item_id']);
                           $row2[$key] = utf8_decode_array(cs_unserialize_min($row2_orig[$key]));
                           #pr($row2[$key]);
                           #exit();
                           #}
                        }
                     }
                     if ( stristr($table,'log_') or $table == 'log' ) {
                        $error_log_array[$table] = str_replace('old_','',$table);
                     } else {
                        if ( is_array($row[$key]) ) {
                           #if ( $row2['item_id'] == 1237290 ) {
                           #pr($row[$key]);
                           #pr('______________________________________________');
                           #pr($row2[$key]);
                           #pr($row2['item_id']);
                           $row2[$key] = changeCharInArray($row[$key],$row2[$key],$char_trans_array);
                           #pr('______________________________________________');
                           #pr($row2[$key]);
                           #exit();
                           #}
                        } else {
                           $array_orig = str_split($row[$key]);
                           $array_utf8 = str_split($row2[$key]);
                           $diff_array = array_diff($array_orig,$array_utf8);

                           foreach ($diff_array as $place => $char ) {
                              if ( !empty($char_trans_array[ord($char)]) ) {
                                 $row2[$key][$place] = $char_trans_array[ord($char)];
                              } else {
                                 echo(LINEBREAK);
                                 echo('keine &Uuml;bersetzung f&uuml;r '.$char.' ('.ord($char).')');
                                 echo(LINEBREAK);
                              }
                           }
                        }
                        if ( is_array($row2[$key]) ) {
                           $string_to_proof = serialize(utf8_encode_array($row2[$key]));
                        } else {
                           $string_to_proof = utf8_encode($row2[$key]);
                        }

                        if ( $row2_orig[$key] != $string_to_proof ) {

                           $sql  = 'UPDATE '.str_replace('old_','utf8_',$table);
                           if ($key == 'extras' ) {
                              $sql .= ' SET '.$key.'="'.addslashes(serialize(utf8_encode_array($row2[$key]))).'"';
                           } else {
                              $sql .= ' SET '.$key.'="'.addslashes(utf8_encode($row2[$key])).'"';
                           }
                           $sql .= ' WHERE ';

                           $first = true;
                           foreach ( $row2_orig as $key_update => $value_update ) {
                              if ( is_numeric($value_update)
                                   or ( str_replace('old_','utf8_',$table) == 'utf8_auth'
                                        and $key_update == 'user_id'
                                      )
                                 ) {
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
                        }
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
echo(getProcessedTimeInHTML($time_start));
?>