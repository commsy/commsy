<?php
/**
 * Created by PhpStorm.
 * User: cschoenf
 * Date: 18.08.17
 * Time: 16:43
 */

namespace Commsy\LegacyBundle\Services;

use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Translation\TranslatorInterface;

class LegacyMarkup
{
    private $legacyEnvironment;

    private $router;

    private $translator;

    private $files = [];

    public function __construct(LegacyEnvironment $legacyEnvironment, Router $router, TranslatorInterface $translator)
    {
        $this->legacyEnvironment = $legacyEnvironment->getEnvironment();
        $this->router = $router;
        $this->translator = $translator;
    }

    public function addFiles($files)
    {
        $this->files = array_merge($this->files, $files);
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

        $regExpArray['(:quicktime'] = '~\\(:quicktime\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:wmplayer'] = '~\\(:wmplayer\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:youtube'] = '~\\(:youtube\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:podcampus'] = '~\\(:podcampus\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:googlevideo'] = '~\\(:googlevideo\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:vimeo'] = '~\\(:vimeo\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:mp3'] = '~\\(:mp3\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:lecture2go'] = '~\\(:lecture2go\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:slideshare'] = '~\\(:slideshare\\s(.*?):\\)~eu';
        $regExpArray['[slideshare'] = '~\[slideshare\\s(.*?)\]~eu';
        $regExpArray['(:flickr'] = '~\\(:flickr\\s(.*?):\\)~eu';
        $regExpArray['(:mdo'] = '~\\(:mdo\\s(.*?):\\)~eu';

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
                            $value = $this->getSubText($text, $value);

                            # delete HTML-tags and string conversion #########
                            $valueNew = strip_tags($value);
                            $valueNew = str_replace('&nbsp;', ' ', $valueNew);
                            ##################################################

                            $args = $this->getArgs2($valueNew, $regExp);
                        }

                        if ($markup == '(:file' && mb_stristr($valueNew, '(:file')) {
                            $valueNew = $this->formatFile($valueNew, $args);
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

                        if ($markup == '(:wmplayer' && mb_stristr($valueNew, '(:wmplayer')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);

                            break;
                        }

                        if ($markup == '(:quicktime' && mb_stristr($valueNew, '(:quicktime')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:youtube' && mb_stristr($valueNew, '(:youtube')) {
                            $valueNew = $this->formatYoutube($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:googlevideo' && mb_stristr($valueNew, '(:googlevideo')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:podcampus' && mb_stristr($valueNew, '(:podcampus')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:vimeo' && mb_stristr($valueNew, '(:vimeo')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:mp3' && mb_stristr($valueNew, '(:mp3')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }
//
//                        if ($markup == '(:lecture2go' && mb_stristr($valueNew, '(:lecture2go')) {
//                            $valueNew = $this->formatLecture2Go($valueNew, $args);
//                            break;
//                        }
//
//                        if ($markup == '(:slideshare' && mb_stristr($valueNew, '(:slideshare')) {
//                            $valueNew = $this->formatSlideshare($valueNew, $args);
//                            break;
//                        }
//
//                        if ($markup == '[slideshare' && mb_stristr($valueNew, '[slideshare')) {
//                            $valueNew = $this->formatSlideshare($valueNew, $args);
//                            break;
//                        }

                        if ($markup == '(:flickr' && mb_stristr($valueNew, '(:flickr')) {
                            $valueNew = $this->formatDeprecated($valueNew, $args);
                            break;
                        }
                    }

                    $text = str_replace($value, $valueNew, $text);
                }
            }
        }

        // CS8-Video
        $cs8VideoRegex = '~<div\sclass="commsyPlayer">.*?<source\ssrc=".*?iid=(\d*?)".*?<\/div>~us';
        $cs8VideoFound = preg_match_all($cs8VideoRegex, $text, $cs8VideoMatches, PREG_SET_ORDER);

        if ($cs8VideoFound) {
            foreach ($cs8VideoMatches as $cs8VideoMatch) {
                $cs8VideoHTML = $cs8VideoMatch[0];
                $cs8VideoReplace = $this->formatCS8Video($cs8VideoHTML, $cs8VideoMatch[1]);

                $text = str_replace($cs8VideoHTML, $cs8VideoReplace, $text);
            }
        }

        // CS8-Images
        $cs8ImageRegex = '~<img.*?src=".*?iid=(\d*?)".*?\/>~u';
        $cs8ImageFound = preg_match_all($cs8ImageRegex, $text, $cs8ImageMatches, PREG_SET_ORDER);

        if ($cs8ImageFound) {
            foreach ($cs8ImageMatches as $cs8ImageMatch) {
                $cs8ImageHTML = $cs8ImageMatch[0];
                $cs8ImageReplace = $this->formatCS8Image($cs8ImageHTML, $cs8ImageMatch[1]);

                $text = str_replace($cs8ImageHTML, $cs8ImageReplace, $text);
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

                if (isset($this->files[$lookupFileName])) {
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

        if (isset($args['lfloat'])) {
            $imageHTML .= ' style="float: left;"';
        }

        if (isset($args['rfloat'])) {
            $imageHTML .= ' style="float: right;"';
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

    private function formatFile($text, $array)
    {
        if (!isset($this->files)) {
            return $text;
        }

        if (empty($array[1])) {
            return $text;
        }

        $tempFileName = htmlentities($array[1], ENT_NOQUOTES, 'UTF-8');

        if (!empty($this->files[$tempFileName])) {
            $file = $this->files[$tempFileName];
        } elseif (!empty($this->files[html_entity_decode($tempFileName, ENT_COMPAT, 'UTF-8')])) {
            $file = $this->files[html_entity_decode($tempFileName, ENT_COMPAT, 'UTF-8')];
        }

        if (isset($file)) {
            $args = [];
            if (!empty($array[2])) {
                $args = $this->parseArgs($array[2]);
            }

            $name = $file->getDisplayName();
            if (!empty($args['text'])) {
                $name = $args['text'];
            }

            $src = $this->router->generate('commsy_file_getfile', [
                'fileId' => $file->getFileId(),
            ]);

            $fileText = '<a href="' . $src . '">' . $name . '</a>';

            return str_replace($array[0], $fileText, $text);
        }

        return $text;
    }

    private function formatDeprecated($text, $array)
    {
        $return = '';

        $return .= '<div class="uk-alert" data-uk-alert><a href="" class="uk-alert-close uk-close"></a><p>';
        $return .= $this->translator->trans('deprecated markup', [
            '%markup%' => $array[0],
        ], 'error');
        $return .= '</p></div>';


        return $return;
    }

    private function formatYoutube($text, $array)
    {
        if (empty($array[1])) {
            return $text;
        }

        $src = 'https://www.youtube.com/embed/' . $array[1];

        $args = [];
        if (!empty($array[2])) {
            $args = $this->parseArgs($array[2]);
        }

        $youTubeHTML = '<div class="ckeditor-commsy-video"><iframe allowfullscreen frameborder="0" src="' . $src . '"';

        if (isset($args['width']) && is_numeric($args['width'])) {
            $youTubeHTML .= ' width="' . $args['width'] . '"';
        }

        if (isset($args['height']) && is_numeric($args['height'])) {
            $youTubeHTML .= ' height="' . $args['height'] . '"';
        }

        $youTubeHTML .= '></iframe></div>';

        return $youTubeHTML;
    }

    private function formatCS8Video($text, $fileId)
    {
        $src = $this->router->generate('commsy_file_getfile', [
            'fileId' => $fileId,
            'disposition' => 'inline',
        ]);

        $videoHTML = '<div class="ckeditor-commsy-video" data-type="commsy"><video class="video-js vjs-default-skin" controls src="' . $src . '"';

        $videoHTML .= '></video></div>';

        return $videoHTML;
    }

    private function formatCS8Image($text, $fileId)
    {
        $src = $this->router->generate('commsy_file_getfile', [
            'fileId' => $fileId,
            'disposition' => 'inline',
        ]);

        $altRegex = '~<img.*?alt="(\w*?)".*?\/>~u';
        $altFound = preg_match($altRegex, $text, $altMatches);
        $alt = '';
        if ($altFound) {
            $alt = $altMatches[1];
        }

        $styleRegex = '~<img.*?style="(.*?)".*?\/>~u';
        $styleFound = preg_match($styleRegex, $text, $styleMatches);
        $style = '';
        if ($styleFound) {
            $style = $styleMatches[1];
        }

        $imageHTML = '<img alt="' . $alt . '" src="' . $src . '" style="' . $style . '"/>';

        return $imageHTML;
    }
}