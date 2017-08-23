<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 18.08.17
 * Time: 16:43
 */

namespace Commsy\LegacyBundle\Services;

use Symfony\Bundle\FrameworkBundle\Routing\Router;

class LegacyMarkup
{
    private $legacyEnvironment;

    private $router;

    private $files;

    public function __construct(LegacyEnvironment $legacyEnvironment, Router $router)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
    }

    public function setFiles($files)
    {
        $this->files = $files;
    }

    public function convertToHTML($text)
    {
        $regExpFatherArray = [
            '~\\(:(.*?):\\)~eu',
            '~\[(.*?)\]~eu',
        ];

        $regExpArray = [];
        $regExpArray['(:file'] = '~\\(:file\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:image'] = '~\\(:image\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:item'] = '~\\(:item\\s([0-9]*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:link'] = '~\\(:link\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';

        $matches = [];

        foreach ($regExpFatherArray as $exp) {
            $found = preg_match_all($exp, $text, $matches);

            if ($found) {
                $matches[0] = array_unique($matches[0]); // doppelte einsparen

                foreach ($matches[0] as $value) {
                    # delete HTML-tags and string conversion #########
                    $valueNew = strip_tags($value);
                    $valueNew = str_replace('&nbsp;',' ', $valueNew);
                    ##################################################

                    foreach ($regExpArray as $markup => $regExp) {
                        $check = false;
                        $args = $this->getArgs($valueNew, $regExp);

                        foreach ($args as $arg) {
                            if (strstr($arg, "'") &&
                                (substr_count($arg, "'") % 2) == 1)
                            {
                                $check = true;
                                break;
                            }
                        }

                        if ($check) {
                            $valueNew = $this->getSubText($text, $value);

                            # delete HTML-tags and string conversion #########
                            $valueNew = strip_tags($value);
                            $valueNew = str_replace('&nbsp;', ' ', $valueNew);
                            ##################################################

                            $args = $this->getArgs2($valueNew, $regExp);
                        }

                        if ($markup == '(:file' && mb_stristr($valueNew, '(:file')) {
//                            $valueNew = $this->formatFile($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:image' && mb_stristr($valueNew, '(:image')) {
                            $valueNew = $this->formatImage($valueNew, $args);
                        }

                        if ($markup == '(:item' && mb_stristr($valueNew, '(:item')) {
                            $valueNew = $this->formatItem($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:link' && mb_stristr($valueNew, '(:link')) {
                            $valueNew = $this->formatLink($valueNew, $args);
                            break;
                        }

//                        if ($markup == '(:mdo' && mb_stristr($valueNew,'(:mdo')) {
//                            $valueNew = $this->formatMDO($valueNew, $args);
//                            break;
//                        }
                    }

                    $text = str_replace($value, $valueNew, $text);
                }
            }
        }

        return $text;
    }

    private function getArgs($data,$reg_exp)
    {
        $variable_array = array();
        $matches = array();
        preg_match_all($reg_exp,$data,$matches);
        $j = 0;
        while (isset($matches[$j][0])) {
            $variable_array[$j] = trim($matches[$j][0]);
            $j++;
        }
        return $variable_array;
    }

    private function getArgs2($data,$reg_exp)
    {
        $reg_exp = str_replace('?)?',')',$reg_exp);
        $variable_array = array();
        $matches = array();
        preg_match_all($reg_exp,$data,$matches);
        $j = 0;
        while (isset($matches[$j][0])) {
            $variable_array[$j] = trim($matches[$j][0]);
            $j++;
        }
        $last_element = array_pop($variable_array);
        if ( !empty($last_element) ) {
            $temp_array = explode(' ',$last_element);
            $komma = false;
            $cache = '';
            foreach ($temp_array as $value) {
                if ( !strstr($value,"'") ) {
                    if ( !$komma ) {
                        $variable_array[] = $value;
                    } else {
                        $cache .= ' '.$value;
                    }
                }
                if ( strstr($value,"'") ) {
                    if ( !$komma ) {
                        if ( substr_count($value,"'") % 2 == 1 ) {
                            $komma = true;
                            $cache .= ' '.$value;
                        } else {
                            $result_array[] = $value;
                        }
                    } else {
                        if ( substr_count($value,"'") % 2 == 1 ) {
                            $komma = false;
                            $cache .= ' '.$value;
                            $variable_array[] = trim($cache);
                            $cache = '';
                        } else {
                            $cache .= ' '.$value;
                        }
                    }
                }
            }
        }
        return $variable_array;
    }

    private function getSubText($text, $search)
    {
        $retour = '';
        $pos = strpos($text,$search);
        $komma_closed = false;
        $end_tag_begin = false;
        for ( $i = $pos+strlen($search); $i < strlen($text); $i++ ) {
            if ( $end_tag_begin
                and $text[$i] == ")"
                and $komma_closed ) {
                break;
            }
            if ( $text[$i] == "'") {
                $komma_closed = !$komma_closed;
            }
            if ( $text[$i] == ":") {
                $end_tag_begin = true;
            } else {
                $end_tag_begin = false;
            }
        }
        if ($end_tag_begin) {
            $retour = substr($text,$pos,$i-$pos+1);
        }
        return $retour;
    }

    private function parseArgs($x)
    {
        $z = array();
        $x = str_replace('&#39;', "'", $x);
        $x = str_replace('&quot;', '"', $x);
        preg_match_all('~([-+]|(?>(\\w+)[:=]{0,1}))?("[^"]*"|\'[^\']*\'|\\S+)~u',$x, $terms, PREG_SET_ORDER);
        foreach($terms as $t) {
            $v = preg_replace('~^([\'"])?(.*)\\1$~u', '$2', $t[3]);
            if ($t[2]) { $z['#'][] = $t[2]; $z[$t[2]] = $v; }
            // bugfix since php 5.4.9
            elseif (empty($t[2])) { $z[$t[0]][] = $v; }
            else { $z['#'][] = $t[1]; $z[$t[1]][] = $v; }
            $z['#'][] = $v;
        }
        return $z;
    }

    private function formatImage($text, $array)
    {
        if (!isset($this->files)) {
            return $text;
        }

        $args = [];
        if (isset($array[3])) {
            $args = $this->parseArgs($array[3]);
        }

        $src = $array[1] . $array[2];
        if (empty($array[1])) {
            if (!empty($array[2])) {
                $lookupFileName = htmlentities($array[2], ENT_NOQUOTES, 'UTF-8');
                if (!isset($this->files[$lookupFileName])) {
                    $lookupFileName = $array[2];
                }

                $file = $this->files[$lookupFileName];

                if ($file) {
                    $lowerFilename = mb_strtolower($file->getFilename(), 'UTF-8');
                    if (    mb_stristr($lowerFilename, 'png') ||
                            mb_stristr($lowerFilename, 'jpg') ||
                            mb_stristr($lowerFilename, 'jpeg') ||
                            mb_stristr($lowerFilename, 'gif')) {

                        $src = $this->router->generate('commsy_file_getfile', [
                            'fileId' => $file->getFileID(),
                            'disposition' => 'inline',
                        ]);
                    }
                }

            }
        }

        $imageHTML = '<div class="ckeditor-commsy-image"><img src="' . $src . '"';

        if (isset($args['width']) && is_numeric($args['width'])) {
            $imageHTML .= ' width="' . $args['width'] . '"';
        }

        if (isset($args['height']) && is_numeric($args['height'])) {
            $imageHTML .= ' height="' . $args['height'] . '"';
        }

        if (isset($args['alt'])) {
            $imageHTML .= ' alt="' . $args['alt'] . '"';
        }

        $imageHTML .= '/></div>';

        return $imageHTML;
    }

    private function formatItem($text, $array)
    {
        $args = [];
        if (!empty($array[2])) {
            $args = $this->parseArgs($array[2]);
        }

        $word = '';
        if (!empty($args['text'])) {
            $word = $args['text'];
        }

        if ( !empty($args['target']) ) {
            $target = $args['target'];
        } elseif ( !empty($args['newwin']) ) {
            $target = '_blank';
        } else {
            $target = '';
        }

        if (!empty($array[1])) {
            $itemId = $array[1];

            if (empty($word)) {
                $word = $itemId;
            }

            $url = $this->router->generate('commsy_goto_goto', [
                'itemId' => $itemId,
            ]);

            $text = '<a href="' . $url . '" target="' . $target . '">' . $word . '</a>';
        }

        return $text;
    }

    private function formatLink($text, $array)
    {
        $image_text = '';
        if ( !empty($array[3]) ) {
            $args = $this->parseArgs($array[3]);
        } else {
            $args = array();
        }

        if ( !empty($args['text']) ) {
            $word = $args['text'];
        } else {
            $word = '';
        }

        if ( !empty($args['target']) ) {
            $target = ' target="'.$args['target'].'"';
        } elseif ( !empty($args['newwin']) ) {
            $target = ' target=_blank;';
        } else {
            $target = '';
        }

        if ( empty($array[1]) ) {
            $source = 'http://'.$array[2];
        } else {
            $source = $array[1].$array[2];
        }

        if ( !empty($source) ) {
            if ( empty($word) ) {
                $word = $source;
            }
            $image_text = '<a href="'.$source.'"'.$target.'>' . $word . '</a>';
        }
        if ( !empty($image_text) ) {
            $text = str_replace($array[0],$image_text,$text);
        }

        return $text;
    }

//    private function formatFile($text, $array)
//    {
//        $file_name_array = $this->itemService->getItemFileList(123);
//
//
//        $image_text = '';
//        if ( !empty($array[1])
//            and !empty($file_name_array)
//        ) {
//            $temp_file_name = htmlentities($array[1], ENT_NOQUOTES, 'UTF-8');
//            if ( !empty($file_name_array[$temp_file_name]) ) {
//                $file = $file_name_array[$temp_file_name];
//            } elseif ( !empty($file_name_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')]) ) {
//                $file = $file_name_array[html_entity_decode($temp_file_name,ENT_COMPAT,'UTF-8')];
//            }
//            if ( isset($file) ) {
//
//                if ( !empty($array[2]) ) {
//                    $args = $this->parseArgs($array[2]);
//                } else {
//                    $args = array();
//                }
//
//                if ( empty($args['icon'])
//                    or ( !empty($args['icon'])
//                        and $args['icon'] == 'true'
//                    )
//                ) {
//                    $icon = $file->getFileIcon().' ';
//                } else {
//                    $icon = '';
//                }
//                if ( empty($args['size'])
//                    or ( !empty($args['size'])
//                        and $args['size'] == 'true'
//                    )
//                ) {
//                    $kb = ' ('.$file->getFileSize().' KB)';
//                } else {
//                    $kb = '';
//                }
//                if ( !empty($args['text']) ) {
//                    $name = $args['text'];
//                } else {
//                    $name = $file->getDisplayName();
//                }
//
//                if ( !empty($args['target']) ) {
//                    $target = ' target="'.$args['target'].'"';
//                } elseif ( !empty($args['newwin']) ) {
//                    $target = ' target=_blank;';
//                } else {
//                    $target = '';
//                }
//                $source = $file->getUrl();
//                if(($file->getExtension() == 'jpg') or ($file->getExtension() == 'gif') or ($file->getExtension() == 'png')){
//                    $image_text = '<a href="'.$source.'"'.$target.' rel="lightbox">'.$icon.$name.'</a>'.$kb;
//                } else {
//                    $image_text = '<a href="'.$source.'"'.$target.'>'.$icon.$name.'</a>'.$kb;
//                }
//            }
//        }
//
//        if ( !empty($image_text) ) {
//            $text = str_replace($array[0],$image_text,$text);
//        }
//
//        return $text;
//    }
}