<?php

namespace CommsyBundle\Twig\Extension;

use Commsy\LegacyBundle\Utils\ItemService;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class MarkupExtension extends \Twig_Extension
{
    private $router;
    private $itemService;

    public function __construct(Router $router, ItemService $itemService)
    {
        $this->router = $router;
        $this->itemService = $itemService;
    }

    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('commsyMarkup', array($this, 'commsyMarkup')),
        );
    }

    public function commsyMarkup($text)
    {
        $text = $this->commsyMarkupEscapes($text);
        $text = $this->commsyMarkupHeadings($text);
        $text = $this->commsyMarkupLists($text); // lists / divider

        // bold
        $text = preg_replace('~\*([^*]+)\*~uU', '<span style="font-weight:bold;">$1</span>', $text);

        // italic
        preg_match('~<!-- DNC -->.*<!-- DNC -->~us',$text,$values);
        foreach ($values as $key => $value) {
            $text = str_replace($value,'COMMSY_DNC'.$key.' ',$text);
        }
        $text = preg_replace('~(^|\n|\t|\s|[ >\/[{(])_([^_]+)_($|\n|\t|:|[ <\/.)\]},!?;])~uU', '$1<span style="font-style:italic;">$2</span>$3', $text);
        foreach ($values as $key => $value) {
            $text = str_replace('COMMSY_DNC'.$key.' ',$value,$text);
        }

        $text = $this->commsyMarkupSpecial($text);

//        // format reference to link
//        $text = $this->_parseText2ID($text);

        // links
        $text = $this->interpreteLinks($text);

        return $text;
    }

    public function getName()
    {
        return 'text_extension';
    }

    private function interpreteLinks($text)
    {
//        $text = preg_replace(
//            '~(?i)\b((?:(?:https?|ftps?)://|ftp\.|ftps\.|www\d{0,3}[.])(?:[^\s()<>]|\((?:[^\s()<>]|(?:\([^\s()<>]+\)))*\))+(?:\((?:[^\s()<>]|(?:\([^\s()<>]+\)))*\)|[^\s`!()\[\]{};:\'".,<>?«»“”‘’]))~',
//            '<a href="$1">$1</a>',
//            $text);
//
//        return $text;

        $RFC1738_CHARS = "A-Za-z0-9\?:@&=/;_\.\+!\*'(,%\$~#-";
        $RFC2822_CHARS = "A-Za-z0-9!#\$%&'\*\+/=\?\^_`{\|}~-";

        preg_match('~<!-- KFC TEXT [a-z0-9]* -->~u', $text, $values);
        foreach ($values as $key => $value) {
            $text = str_replace($value,'COMMSY_FCKEDITOR'.$key.' ',$text);
        }
        $text = ' ' . $text;

        $url_string = '^(?<=([\s|\n|>|\(]{1}))((http://|https://|ftp://|www\.)'; //everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
        $url_string .= "([" . $RFC1738_CHARS . "]+?))"; //All characters allowed for FTP an HTTP URL's by RFC 1738 (non-greedy because of potential trailing punctuation marks)

        // separating Links from COMMSY_FCKEDITOR tag
        $url_string .= '(?=([\.\?:\),;!]*($|\s|<|&quot;|&nbsp;|COMMSY_FCKEDITOR)))'; //behind the url is a space character- and perhaps before it a punctuation mark (which does not belong to the url)
        $url_string .= '(?![\s\w\d]*</a>)^u'; // if there's a </a>-tag behind the link, it is assumed that there's already a complete <a href="">link</a> contruct comming from the editor. These links are omitted.

        $text = preg_replace($url_string, '<a href="$2" target="_blank" title="$2">$2</a>', $text);
        $text = preg_replace_callback('~">(.[^"]+)</a>~u', 'spezial_chunkURL', $text);
        $text = preg_replace('~<a href="www~u', '<a href="http://www', $text); //add "http://" to links that were activated with www in front only

        // mailto. A space or a linebreak has to be in front of everymail link. No links in bigger words (especially in urls) will be activated
        $text = preg_replace('^( |\^|>|\n)(mailto:)?(([' . $RFC2822_CHARS . ']+(\.[' . $RFC2822_CHARS . ']+)*)@([' . $RFC2822_CHARS . ']+(\.[' . $RFC2822_CHARS . ']+)*\.([A-z]{2,})))^u', '$1<a href="mailto:$3">$3</a>', $text);
        $text = substr($text, 1, strlen($text));

        foreach ($values as $key => $value) {
            $text = str_replace('COMMSY_FCKEDITOR'.$key.' ',$value,$text);
        }

        return $text;
    }

    /**
     * Markup formatting for headings
     *
     * @param $text string to process
     * @return string the processed string
     */
    private function commsyMarkupHeadings($text)
    {
        $matches = array();

        while (preg_match('~(^|\n)(\s*)(!+)(\s*)(.*)~u', $text, $matches)) {
            $numBangs = mb_strlen($matches[3]);

            // normal (one '!') is h4, biggest is h1; The more bang '!', the bigger the heading
            $headingLevel = max(5 - $numBangs, 1);
            $heading = "<h$headingLevel>$matches[5]</h$headingLevel>\n";

            $text = preg_replace('~(^|\n)(\s*)(!+)(\s*)(.*)~u', $heading, $text, 1);
        }

        return $text;
    }

    /**
     * Markup formatting for lists and horizontal lines (#, -, ---)
     *
     * @param $text string to process
     * @return string the processed string
     */
    private function commsyMarkupLists($text)
    {
        $html = '';
        $matches = [];
        $last_list_type = '';
        $list_open = false;

        //split up paragraphs in lines
        $lines = preg_split('~\s*\n~uU', $text);
        foreach ($lines as $line) {
            $line_html = '';
            // find horizontal rulers
            if (preg_match('~^--(-+)\s*($|\n|<)~u', $line)) {
                if ($list_open) {
                    $line_html .= $this->_close_list($last_list_type);
                    $list_open = false;
                }
                $line_html .= LF.'<hr/>'.LF;
            }

            // process lists
            elseif (preg_match('~^(-|#)(\s*)(.*)~su', $line, $matches)) {
                $list_type = $matches[1];

                if (!$list_open) {
                    $line_html .= $this->_open_list($list_type);
                    $list_open = true;
                    if ($list_type != $last_list_type) {
                        $last_list_type = $list_type;
                    }
                } else {
                    if ($list_type != $last_list_type) {
                        $line_html .= $this->_close_list($last_list_type);
                        $line_html .= $this->_open_list($list_type);
                        $last_list_type = $list_type;
                    }
                }
                $line_html.= '<li>'.$matches[3].'</li>'.LF;
            }

            // All other lines without anything special
            else {
                if ($list_open) {
                    $line_html .= $this->_close_list($last_list_type);
                    $list_open = false;
                }
                $line_html .= $line;
            }
            $html .= $line_html."\r\n";
        }

        if ($list_open) {
            $html .= $this->_close_list($last_list_type);
        }

        return $html;
    }

    /**
     * Interpretes escapes
     *
     * @param $text
     * @return string
     */
    private function commsyMarkupEscapes($text)
    {
        $text = str_replace("\*","&ast;", $text);
        $text = str_replace("\_","&lowbar;", $text);
        $text = str_replace("\!","&excl;", $text);
        $text = str_replace("\-","&macr;", $text);
        $text = str_replace("\#","&num;", $text);
        $text = str_replace("\\\\","&bsol;", $text);

        return $text;
    }

    private function commsyMarkupSpecial($text)
    {
        $regExpFatherArray = [
            '~\\(:(.*?):\\)~eu',
            '~\[(.*?)\]~eu',
        ];

        $regExpArray = [];
        $regExpArray['(:item']        = '~\\(:item\\s([0-9]*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:link']        = '~\\(:link\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
        $regExpArray['(:file']        = '~\\(:file\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';

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
                            $valueNew = str_replace('&nbsp;',' ', $valueNew);
                            ##################################################

                            $args = $this->getArgs2($valueNew, $regExp);
                        }

                        if ($markup == '(:item' && mb_stristr($valueNew,'(:item')) {
                            $valueNew = $this->formatItem($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:link' && mb_stristr($valueNew,'(:link')) {
                            $valueNew = $this->formatLink($valueNew, $args);
                            break;
                        }

                        if ($markup == '(:file' && mb_stristr($valueNew,'(:file')) {
//                            $valueNew = $this->formatFile($valueNew, $args);
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

    /**
    * returns the html-code for opening a list
    */
    private function _open_list($list_type)
    {
        $html = '';
        if ($list_type == '#') {
            $html.= '<ol>'."\n";
        }
        elseif ($list_type == '-') {
            $html.= '<ul>'."\n";
        }
        return $html;
    }

    /**
     * returns the html-code for closing a list
     */
    private function _close_list($list_type)
    {
        $html = '';
        if ($list_type == '#') {
            $html.= '</ol>'."\n";
        }
        elseif ($list_type == '-') {
            $html.= '</ul>'."\n";
        }
        return $html;
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