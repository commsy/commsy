<?php

/*
 * This file is part of CommSy.
 *
 * (c) Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
 * Martti Jeenicke, Detlev Krause, Irina L. Marinescu, Timo Nolte, Bernd Pape,
 * Edouard Simon, Monique Strauss, Jose Mauel Gonzalez Vazquez, Johannes Schultze
 *
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

class misc_text_converter
{
    private ?cs_environment $_environment = null;
    private array $_file_array = [];
    private ?HTMLPurifier $_HTMLPurifier = null;
    private ?HTMLPurifier $_FullHTMLPurifier = null;
    private bool $_processMath = false;

    public function __construct($params)
    {
        if (!empty($params['environment'])) {
            $this->_environment = $params['environment'];
            $this->_constructHTMLPurifier();
        } else {
            trigger_error('no environment defined '.__FILE__.' '.__LINE__, E_USER_ERROR);
        }
    }

    private function _getFileArray()
    {
        return $this->_file_array;
    }

    private function _cleanBadCode($text)
    {
        $search = [];
        $replace = [];

        $search[] = '~<([/]{0,1}[j|J][a|A][v|V][a|A][s|S][c|C][r|R][i|I][p|P][t|T])~u';
        $replace[] = '&lt;$1';
        $search[] = '~<([/]{0,1}[s|S][c|C][r|R][i|I][p|P][t|T])~u';
        $replace[] = '&lt;$1';
        $search[] = '~<([/]{0,1}[j|J][s|S][c|C][r|R][i|I][p|P][t|T])~u';
        $replace[] = '&lt;$1';
        $search[] = '~<(\?)~u';
        $replace[] = '&lt;$1';
        $search[] = '~(\?)>~u';
        $replace[] = '$1&gt;';

        $search[] = '~<([/]{0,1}[e|E][m|M][b|B][e|E][d|D])~u';
        $replace[] = '&lt;$1';
        $search[] = '~<([/]{0,1}[o|O][b|B][j|J][e|E][c|C][t|T])~u';
        $replace[] = '&lt;$1';

        $search[] = '~<([/]{0,1}[i|I][f|F][r|R][a|A][m|M][e|E])~u';
        $replace[] = '&lt;$1';

        $text = preg_replace($search, $replace, (string) $text);

        return $text;
    }

    public function _cs_htmlspecialchars($text)
    {
        return $this->_cleanBadCode($text);
    }

    public function _cs_htmlspecialchars2($text)
    {
        return $this->_cleanBadCode($text);
    }

    public function text_as_html_long($text, $htmlTextArea = true)
    {
        // preg_match('~<!-- KFC TEXT -->[\S|\s]*<!-- KFC TEXT -->~u',$text,$values);
        // security KFC
        preg_match('~<!-- KFC TEXT [a-z0-9]* -->[\S|\s]*<!-- KFC TEXT [a-z0-9]* -->~u', (string) $text, $values);
        foreach ($values as $key => $value) {
            $text = str_replace($value, 'COMMSY_FCKEDITOR'.$key, (string) $text);
        }
        $text = $this->_text_as_html_long1($text, $htmlTextArea);
        foreach ($values as $key => $value) {
            $text = str_replace('COMMSY_FCKEDITOR'.$key, $this->_text_as_html_long2($value), (string) $text);
        }

        return $text;
    }

    // text not from FCKeditor
    public function _text_as_html_long1($text, $htmlTextArea = false)
    {
        $text = $this->_cs_htmlspecialchars($text);
        $text = nl2br((string) $text);
        $text = $this->_decode_backslashes_1($text);
        $text = $this->_preserve_whitespaces($text);
        $text = $this->_newFormating($text);
        $text = $this->_emphasize_text($text);
        $text = $this->_activate_urls($text);
        $text = $this->_display_headers($text);
        $text = $this->_format_html_long($text);
        $text = $this->_parseText2ID($text);
        $text = $this->_decode_backslashes_2($text);
        $text = $this->_delete_unnecassary_br($text);
        $text = $this->_br_with_nl($text);

        return $text;
    }

    // text from FCKeditor
    public function _text_as_html_long2($text)
    {
        $text = $this->_cs_htmlspecialchars($text);
        $text = $this->_decode_backslashes_1_fck($text);
        $text = $this->_newFormating($text);
        $text = $this->_decode_backslashes_2_fck($text);
        $text = $this->_activate_urls($text);
        $text = $this->_parseText2ID($text);
        // html bug of fckeditor
        $text = str_replace('<br type="_moz" />', '<br />', (string) $text);

        return $text;
    }

    public function text_as_html_short($text)
    {
        // $text = htmlspecialchars($text, ENT_NOQUOTES, 'UTF-8');
        $text = $this->_emphasize_text($text);
        return $this->_decode_backslashes($text);
    }

    public function text_as_form($text)
    {
        $text = $this->_cs_htmlspecialchars($text);
        return str_replace('"', '&quot;', (string) $text);
    }

    // private function _decode_backslashes ($text) {
    public function _decode_backslashes($text)
    {
        $retour = $text;
        $retour = str_replace("\*", '&ast;', (string) $retour);
        $retour = str_replace("\_", '&lowbar;', $retour);
        $retour = str_replace("\!", '&excl;', $retour);
        $retour = str_replace("\-", '&macr;', $retour);
        $retour = str_replace("\#", '&num;', $retour);
        return str_replace('\\\\', '&bsol;', $retour);
    }

    // private function _emphasize_text ($text) {
    public function _emphasize_text($text)
    {
        // bold
        // $text = preg_replace('/(^|\n|\t|\s|[ >\/_[{(])\*([^*]+)\*($|\n|\t|[ <\/_.)\]},!?;])/', '$1<span style="font-weight:bold;">$2</span>$3', $text);
        $text = preg_replace('~\*([^*]+)\*~uU', '<span style="font-weight:bold;">$1</span>', (string) $text);

        // italic
        preg_match('~<!-- DNC -->.*<!-- DNC -->~us', $text, $values);
        foreach ($values as $key => $value) {
            $text = str_replace($value, 'COMMSY_DNC'.$key.' ', $text);
        }
        $text = preg_replace('~(^|\n|\t|\s|[ >\/[{(])_([^_]+)_($|\n|\t|:|[ <\/.)\]},!?;])~uU', '$1<span style="font-style:italic;">$2</span>$3', $text);
        foreach ($values as $key => $value) {
            $text = str_replace('COMMSY_DNC'.$key.' ', $value, $text);
        }

        // search (with yellow background)
        $text = preg_replace('~\(:mainsearch_text_yellow:\)(.+)\(:mainsearch_text_yellow_end:\)~uU', '<span class="searched_text_yellow">$1</span>', $text);

        // search (with green background)
        $text = preg_replace('~\(:mainsearch_text_green:\)(.+)\(:mainsearch_text_green_end:\)~uU', '<span class="searched_text_green">$1</span>', $text);

        // search
        // maybe with yellow or orange background ???
        $text = preg_replace('~\(:search:\)(.+)\(:search_end:\)~uU', '<span style="font-style:italic;">$1</span>', $text);
        // $text = preg_replace('~\(:search:\)(.+)\(:search_end:\)~u', '<span class="searched_text">$1</span>', $text);

        return $text;
    }

    // private function _activate_urls ($text) {
    public function _activate_urls($text)
    {
        preg_match('~<!-- KFC TEXT [a-z0-9]* -->~u', (string) $text, $values);
        foreach ($values as $key => $value) {
            $text = str_replace($value, 'COMMSY_FCKEDITOR'.$key.' ', (string) $text);
        }
        $text = ' '.$text;
        $url_string = '^(?<=([\s|\n|>|\(]{1}))((http://|https://|ftp://|www\.)'; // everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
      // $url_string = '^(?<=([\s|\n|>|\(]{1}))((http://|https://|ftp://|www\.)'; //everything starting with http, https or ftp followed by "://" or www. is a url and will be avtivated
        $url_string .= '(['.RFC1738_CHARS.']+?))'; // All characters allowed for FTP an HTTP URL's by RFC 1738 (non-greedy because of potential trailing punctuation marks)
      // separating Links from COMMSY_FCKEDITOR tag
        $url_string .= '(?=([\.\?:\),;!]*($|\s|<|&quot;|&nbsp;|COMMSY_FCKEDITOR)))'; // behind the url is a space character- and perhaps before it a punctuation mark (which does not belong to the url)
        $url_string .= '(?![\s\w\d]*</a>)^u'; // if there's a </a>-tag behind the link, it is assumed that there's already a complete <a href="">link</a> contruct comming from the editor. These links are omitted.

        // $text = preg_replace($url_string, '$1<a href="$2" target="_blank" title="$2">$2</a>$5', $text);
        $text = preg_replace($url_string, '<a href="$2" target="_blank" title="$2">$2</a>', $text);
        $text = preg_replace_callback('~">(.[^"]+)</a>~u', 'spezial_chunkURL', $text);
        $text = preg_replace('~<a href="www~u', '<a href="http://www', $text); // add "http://" to links that were activated with www in front only
        // mailto. A space or a linebreak has to be in front of everymail link. No links in bigger words (especially in urls) will be activated
        $text = preg_replace('^( |\^|>|\n)(mailto:)?((['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*)@(['.RFC2822_CHARS.']+(\.['.RFC2822_CHARS.']+)*\.([A-z]{2,})))^u', '$1<a href="mailto:$3">$3</a>', $text);
        $text = substr($text, 1, strlen($text));
        foreach ($values as $key => $value) {
            $text = str_replace('COMMSY_FCKEDITOR'.$key.' ', $value, $text);
        }

        return $text;
    }

    // private function _display_headers ($text) {
    public function _display_headers($text)
    {
        $matches = [];

        while (preg_match('~(^|\n)(\s*)(!+)(\s*)(.*)~u', (string) $text, $matches)) {
            $bang_number = mb_strlen($matches[3]);
            $head_level = max(5 - $bang_number, 1); // normal (one '!') is h4, biggest is h1; The more '!', the bigger the heading
            $heading = '<h'.$head_level.'>'."\n   ".$matches[5]."\n".'</h'.$head_level.'>'."\n";
            $text = preg_replace('~(^|\n)(\s*)(!+)(\s*)(.*)~u', $heading, (string) $text, 1);
        }

        return $text;
    }

    public function _format_html_long($text)
    {
        $html = '';
        $matches = [];
        $list_type = '';
        $last_list_type = '';
        $list_open = false;

        // split up paragraphs in lines
        $lines = preg_split('~\s*\n~uU', (string) $text);
        foreach ($lines as $line) {
            $line_html = '';
            $hr_line = false;
            // find horizontal rulers
            if (preg_match('~^--(-+)\s*($|\n|<)~u', (string) $line)) {
                if ($list_open) {
                    $line_html .= $this->_close_list($last_list_type);
                    $list_open = false;
                }
                $line_html .= LF.'<hr/>'.LF;
                $hr_line = true;
            }

            // process lists
            elseif (!$hr_line and preg_match('~^(-|#)(\s*)(.*)~su', (string) $line, $matches)) {
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
                $line_html .= '<li>'.$matches[3].'</li>'.LF;
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
            $list_open = false;
        }

        return $html;
    }

    private function _decode_backslashes_1_fck($text)
    {
        $retour = $text;
        return str_replace("\(:", "\WIKIBEGIN", (string) $retour);
    }

    private function _decode_backslashes_2_fck($text)
    {
        $retour = $text;
        return str_replace("\WIKIBEGIN", '(:', (string) $retour);
    }

    /**
     * returns the html-code for opening a list.
     */
    private function _open_list($list_type)
    {
        $html = '';
        if ('#' == $list_type) {
            $html .= '<ol>'."\n";
        } elseif ('-' == $list_type) {
            $html .= '<ul>'."\n";
        }

        return $html;
    }

    /**
     * returns the html-code for closing a list.
     */
    private function _close_list($list_type)
    {
        $html = '';
        if ('#' == $list_type) {
            $html .= '</ol>'."\n";
        } elseif ('-' == $list_type) {
            $html .= '</ul>'."\n";
        }

        return $html;
    }

    // private function _br_with_nl ($text) {
    public function _br_with_nl($text)
    {
        $text = str_replace('<br />', '<br />'.LF, (string) $text);

        return $text;
    }

    private function _parseText2Id($text)
    {
        $matches_stand_alone = [];
        $matches_with_text = [];

        // ids with text: <text>[<number>] becomes a link under <text> to the commsy-object with id <number>
        preg_match_all('~([\w.'.SPECIAL_CHARS.'&;-]+)\[(\d+)\]~iu', (string) $text, $matches_with_text);
        if ((is_countable($matches_with_text[0]) ? count($matches_with_text[0]) : 0) > 0) {
            $result = $text;
            $word_part = $matches_with_text[1];
            $reference_part = $matches_with_text[2];
            for ($i = 0; $i < (is_countable($word_part) ? count($word_part) : 0); ++$i) {
                $word = $word_part[$i];
                $reference = $reference_part[$i];
                if ($reference <= 100) {
                    if ('discussion' == $this->_environment->getCurrentModule()) {
                        $params = [];
                        $params['iid'] = $this->_environment->getValueOfParameter('iid');
                        $result = preg_replace('~'.$word.'\['.$reference.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, $word, $word, '', 'anchor'.$reference), (string) $result);
                        unset($params);
                    }
                } else {
                    $params = [];
                    $params['iid'] = $reference;
                    $result = preg_replace('~'.$word.'\['.$reference.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', '', ''), (string) $result);
                    unset($params);
                }
            }
            $text = $result;
        }

      // urls with text: <text>[<url>] becomes a link under <text> to the url <url>
        preg_match_all('^([.\w'.SPECIAL_CHARS.'-]+)\[(https?:\/\/['.RFC1738_CHARS.']*)\]^iu', (string) $text, $matches_with_urls); // preg_match_all('/(\S+)(\[http:\/\/\S*\])[.:,;-?!]*($|\n|\t|<| )/', $text, $matches_with_urls);
        if ((is_countable($matches_with_urls[0]) ? count($matches_with_urls[0]) : 0) > 0) {
            $result = $text;
            $word_part = $matches_with_urls[1];
            $http_part = $matches_with_urls[2];
            for ($i = 0; $i < (is_countable($word_part) ? count($word_part) : 0); ++$i) {
                $word = $word_part[$i];
                $http = $http_part[$i];
                if (!empty($word)) {
                    if (!mb_stristr((string) $word, '|')) {
                        $result = preg_replace('~'.$word.'\['.$http.'\]~u', '<a href="'.$http.'" target="_blank">'.$word.'</a>', (string) $result);
                    }
                } else {
                    $result = preg_replace('~'.$word.'\['.$http.'\]~u', '<a href="'.$http.'" target="_blank">'.$http_part[$i].'</a>', (string) $result);
                }
            }
            $text = $result;
        }

        // long urls: [<url>|<sentence with spaces>|<flag>] becomes a link to <url> under <sentence with spaces>
        // <flag> cann be "internal" or "_blank". Internal opens <url> in this browser window, _blank uses another
        preg_match_all('^\[(http?://['.RFC1738_CHARS.']*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\|(\w+)\]^u', (string) $text, $matches_with_long_urls);
        if ((is_countable($matches_with_long_urls[0]) ? count($matches_with_long_urls[0]) : 0) > 0) {
            $result = $text;
            $http_part = $matches_with_long_urls[1];
            $word_part = $matches_with_long_urls[2];
            $flag_part = $matches_with_long_urls[3];
            for ($i = 0; $i < (is_countable($http_part) ? count($http_part) : 0); ++$i) {
                $http = $http_part[$i];
                $word = $word_part[$i];
                $flag = $flag_part[$i];
                if (!empty($word) and !empty($http) and !empty($flag)) {
                    $search = '['.$http.'|'.$word.'|'.$flag.']';
                    $replace = '<a href="'.$http.'" target="_blank">'.$word.'</a>';
                    if ('internal' == $flag) {
                        $replace = '<a href="'.$http.'">'.$word.'</a>';
                    }
                    $result = str_replace($search, $replace, (string) $result);
                }
            }
            $text = $result;
        }

        // long urls: [ITEM_ID|<sentence with spaces>] becomes a link to <url> under <sentence with spaces>
        preg_match_all('^\[([0-9]*)\|([\w'.SPECIAL_CHARS.' \)?!&;-]+)\]^u', (string) $text, $matches_with_long_urls);
        // preg_match_all('§\[([0-9]*)\|([\w'.SPECIAL_CHARS.' -]+)\]§', $text, $matches_with_long_urls);
        if ((is_countable($matches_with_long_urls[0]) ? count($matches_with_long_urls[0]) : 0) > 0) {
            $result = $text;
            $http_part = $matches_with_long_urls[1];
            $word_part = $matches_with_long_urls[2];
            for ($i = 0; $i < (is_countable($http_part) ? count($http_part) : 0); ++$i) {
                $http = $http_part[$i];
                $word = $word_part[$i];
                if (!empty($word) and !empty($http)) {
                    $search = '['.$http.'|'.$word.']';
                    $params = [];
                    $params['iid'] = $http;
                    $replace = ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word);
                    $result = str_replace($search, $replace, (string) $result);
                }
            }
            $text = $result;
        }

      // ids without text: [<number>] becomes a link under [<number>] to the commsy-object with id <number>
        preg_match_all('~\[(\d+)\]~u', (string) $text, $matches_stand_alone); // (^| |\n|>|\t)\[(\d+)\][.:,;-?!]*(<| |$)
        $matches_stand_alone = array_unique($matches_stand_alone[1]);
        if (!empty($matches_stand_alone)) {
            $result = $text;
            foreach ($matches_stand_alone as $item) {
                if ($item <= 100) {
                    if ('discussion' == $this->_environment->getCurrentModule()) {
                        $params = [];
                        $params['iid'] = $this->_environment->getValueOfParameter('iid');
                        $result = preg_replace('~\['.$item.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'discussion', 'detail', $params, '['.$item.']', '['.$item.']', '', 'anchor'.$item), (string) $result);
                        unset($params);
                    }
                } else {
                    $params = [];
                    $params['iid'] = $item;
                    $result = preg_replace('~\['.$item.'\]~iu', ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, '['.$item.']', '', '', ''), (string) $result);
                    unset($params);
                }
            }
            $text = $result;
        }

        return $text;
    }

    private function _decode_backslashes_2($text)
    {
        $retour = $text;
        $retour = str_replace("\STERN", '*', (string) $retour);
        $retour = str_replace("\STRICH", '_', $retour);
        $retour = str_replace("\AUSRUFEZEICHEN", '!', $retour);
        $retour = str_replace("\MINUS", '-', $retour);
        $retour = str_replace("\SCHWEINEGATTER", '#', $retour);
        $retour = str_replace("\WIKIBEGIN", '(:', $retour);

        return $retour;
    }

    public function _delete_unnecassary_br($text)
    {
        $text = preg_replace('~<br( /)?>(</h\d>)~u', '$2', (string) $text);
        return preg_replace('~<br( /)?>(</li>)~u', '</li>', $text);
    }

    /** Wenn im Text Gruppierungen von zwei oder mehr Leerzeichen
     *  vorkommen, werden diese durch entsprechende &nbsp; Tags
     *  ersetzt, um die Ursprüngliche formatierung zu bewaren.
     *
     *  Wurde aufgrund folgenden Bugs erstellt:
     *  http://sourceforge.net/tracker/index.php?func=detail&aid=1062265&group_id=49014&atid=516467
     */
    public function _preserve_whitespaces($text)
    {
        preg_match_all('~ {2,}~u', (string) $text, $matches);
        $matches = array_unique($matches[0]);
        rsort($matches);
        foreach ($matches as $match) {
            $replacement = ' ';
            for ($x = 1; $x < mb_strlen((string) $match); ++$x) {
                $replacement .= '&nbsp;';
            }
            $text = str_replace($match, $replacement, (string) $text);
        }

        return $text;
    }

    private function _decode_backslashes_1($text)
    {
        $retour = $text;
        $retour = str_replace("\*", "\STERN", (string) $retour);
        $retour = str_replace("\_", "\STRICH", $retour);
        $retour = str_replace("\!", "\AUSRUFEZEICHEN", $retour);
        $retour = str_replace("\-", "\MINUS", $retour);
        $retour = str_replace("\#", "\SCHWEINEGATTER", $retour);
        return str_replace("\(:", "\WIKIBEGIN", $retour);
    }

    public function _getArgs($data, $reg_exp)
    {
        $variable_array = [];
        $matches = [];
        $found = preg_match_all($reg_exp, (string) $data, $matches);
        $j = 0;
        while (isset($matches[$j][0])) {
            $variable_array[$j] = trim((string) $matches[$j][0]);
            ++$j;
        }

        return $variable_array;
    }

    public function _getArgs2($data, $reg_exp)
    {
        $reg_exp = str_replace('?)?', ')', (string) $reg_exp);
        $variable_array = [];
        $matches = [];
        $found = preg_match_all($reg_exp, (string) $data, $matches);
        $j = 0;
        while (isset($matches[$j][0])) {
            $variable_array[$j] = trim((string) $matches[$j][0]);
            ++$j;
        }
        $last_element = array_pop($variable_array);
        if (!empty($last_element)) {
            $temp_array = explode(' ', $last_element);
            $komma = false;
            $cache = '';
            foreach ($temp_array as $value) {
                if (!strstr($value, "'")) {
                    if (!$komma) {
                        $variable_array[] = $value;
                    } else {
                        $cache .= ' '.$value;
                    }
                }
                if (strstr($value, "'")) {
                    if (!$komma) {
                        if (1 == substr_count($value, "'") % 2) {
                            $komma = true;
                            $cache .= ' '.$value;
                        } else {
                            $result_array[] = $value;
                        }
                    } else {
                        if (1 == substr_count($value, "'") % 2) {
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

    public function _parseArgs($x)
    {
        $z = [];
        $x = str_replace('&#39;', "'", (string) $x);
        $x = str_replace('&quot;', '"', $x);
        preg_match_all('~([-+]|(?>(\\w+)[:=]{0,1}))?("[^"]*"|\'[^\']*\'|\\S+)~u', $x, $terms, PREG_SET_ORDER);
        foreach ($terms as $t) {
            $v = preg_replace('~^([\'"])?(.*)\\1$~u', '$2', $t[3]);
            if ($t[2]) {
                $z['#'][] = $t[2];
                $z[$t[2]] = $v;
            }
            // bugfix since php 5.4.9
            elseif (empty($t[2])) {
                $z[$t[0]][] = $v;
            } else {
                $z['#'][] = $t[1];
                $z[$t[1]][] = $v;
            }
            $z['#'][] = $v;
        }

        return $this->_checkSecurity($z);
    }

    // needed for Wiki-Export
    public function parseArgs($x)
    {
        return $this->_parseArgs($x);
    }

    private function _checkSecurity($array)
    {
        $retour = [];
        foreach ($array as $key => $value) {
            if ('float' == $key
                 and 'left' != $value
                 and 'right' != $value
            ) {
            } elseif ('text' == $key
                       or 'alt' == $key
                       or 'gallery' == $key
                       or 'image' == $key
                       or 'server' == $key
            ) {
                $retour[$key] = $this->_htmlentities_small($value);
            } elseif ('width' == $key
                       and !is_numeric($value)
            ) {
            } elseif ('height' == $key
                       and !is_numeric($value)
            ) {
            } elseif ('width' == $key
                       and $value > 1000
            ) {
            } elseif ('height' == $key
                       and $value > 1000
            ) {
            } elseif ('icon' == $key
                       and 'true' != $value
                       and 'false' != $value
            ) {
            } elseif ('size' == $key
                       and 'true' != $value
                       and 'false' != $value
            ) {
            } elseif ('play' == $key
                       and 'true' != $value
                       and 'false' != $value
            ) {
            } elseif ('navigation' == $key
                       and 'true' != $value
                       and 'false' != $value
            ) {
            } elseif ('orientation' == $key
                       and 'portrait' != $value
                       and 'landscape' != $value
            ) {
            } elseif ('target' == $key
                       and '_blank' != $value
                       and '_top' != $value
                       and '_parent' != $value
            ) {
            } else {
                $retour[$key] = $value;
            }
        }

        return $retour;
    }

    public function _htmlentities_cleanbadcode($value)
    {
        $value = $this->_htmlentities_small($value);
        $value = $this->_cleanBadCode($value);

        return $value;
    }

    private function _htmlentities_small($value)
    {
        $value = str_replace('<', '&lt;', (string) $value);
        $value = str_replace('>', '&gt;', $value);
        $value = str_replace('"', '&quot;', $value);
        $value = str_replace('\'', '&#039;', $value);

        return $value;
    }

    private function _htmlentities_smaller($value)
    {
        $value = str_replace('<', '&lt;', (string) $value);
        $value = str_replace('>', '&gt;', $value);
        $value = str_replace('"', '&quot;', $value);

        return $value;
    }

    /**
     * Extended implementation of the standard PHP-Function.
     *
     * Needed to ensure proper searching in CommSy with standard PHP settings
     * When the 'locale' setting of PHP is not set properly, the search for language specific characters
     * like 'ä', 'ü', 'ö', 'á' etc doesn't work correct, because the standard PHP strtolower doesn't translate
     * them (http://de3.php.net/manual/en/function.strtolower.php)
     *
     * Our extended implementation translates correct without respect to 'locale'
     */
    private function _cs_strtolower($value)
    {
        return mb_strtolower(strtr($value, UC_CHARS, LC_CHARS), 'UTF-8');
    }

    private function _getSubText($text, $search)
    {
        $retour = '';
        $pos = strpos((string) $text, (string) $search);
        $run = true;
        $komma_closed = false;
        $end_tag_begin = false;
        for ($i = $pos + strlen((string) $search); $i < strlen((string) $text); ++$i) {
            if ($end_tag_begin
                 and ')' == $text[$i]
                 and $komma_closed) {
                break;
            }
            if ("'" == $text[$i]) {
                $komma_closed = !$komma_closed;
            }
            if (':' == $text[$i]) {
                $end_tag_begin = true;
            } else {
                $end_tag_begin = false;
            }
        }
        if ($end_tag_begin) {
            $retour = substr((string) $text, $pos, $i - $pos + 1);
        }

        return $retour;
    }

    /**
     * Encodes the following chars in all given attributes: ':', '(' and ')'.
     *
     * @param $attr_array - Array von Attributen
     * @param $text - text
     *
     * @return encoded text
     */
    private function _encode_attr($attr_array, $text)
    {
        // loop through all tags
        foreach ($attr_array as $attr) {
            // find tags and content
            $reg_exp = "~$attr='(.*?)'~eu";
            $found = preg_match_all($reg_exp, (string) $text, $matches);

            if ($found > 0) {
                // eleminate duplicates
                $matches[1] = array_unique($matches[1]);

                // replace chars
                foreach ($matches[1] as $string) {
                    // $new_tag_content = htmlentities($string);
                    $new_tag_content = $this->_decode_tag_chars($string);
                    $text = str_replace("$attr='$string'", "$attr='$new_tag_content'", (string) $text);
                }
            }
        }

        return $text;
    }

    private function _decode_tag_chars($text)
    {
        $text = str_replace('(', '&#040;', (string) $text);
        $text = str_replace(')', '&#041;', $text);
        return str_replace(':', '&#058;', $text);
    }

    /**
     * Encodes file names.
     *
     * @param $text - text
     *
     * @return encoded text
     */
    private function _encode_file_names($text)
    {
        $reg_exp = '~\\(:.*? (.*?)\\.([a-zA-Z0-9]*)~eu';
        $found = preg_match_all($reg_exp, (string) $text, $matches);

        if ($found > 0) {
            for ($i = 0; $i < $found; ++$i) {
                $new_file_name = $this->_decode_tag_chars($matches[1][$i]);
                $new_file_extension = $this->_decode_tag_chars($matches[2][$i]);
                $text = str_replace($matches[1][$i].'.'.$matches[2][$i], "$new_file_name.$new_file_extension", (string) $text);
            }
        }

        return $text;
    }

     // private function _newFormating ( $text ) {
     public function _newFormating($text)
     {
         $reg_exp_image = [];
         $file_array = $this->_getFileArray();

         $reg_exp_father_array = [];
         $reg_exp_father_array[] = '~\\(:(.*?):\\)~eu';
         $reg_exp_father_array[] = '~\[(.*?)\]~eu';

         $reg_exp_array = [];

         // reference
         // $reg_exp_array['[']         = '~\\[[0-9]+\|[\w]+\]~eu';
         $reg_exp_array['(:image'] = '~\\(:image\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
         $reg_exp_array['(:item'] = '~\\(:item\\s([0-9]*?)(\\s.*?)?\\s*?:\\)~eu';
         $reg_exp_array['(:link'] = '~\\(:link\\s(.*?:){0,1}(.*?)(\\s.*?)?\\s*?:\\)~eu';
         $reg_exp_array['(:file'] = '~\\(:file\\s(.*?)(\\s.*?)?\\s*?:\\)~eu';

         // Test auf erforderliche Software; Windows-Server?
         // $reg_exp_array['(:pdf']       = '/\\(:pdf (.*?)(\\s.*?)?\\s*?:\\)/e';

        // Lightbox für Bilder die über den CkEditor in das Beschreibungsfeld eingefügt wurden
         $reg_exp_image['<img'] = '~\\<img(.*?)\\>~u'; // \<img.*?\>
        // $reg_exp_image['link']      = '~<a(.*?)><img(.*?)></a>~eu'; // \<img.*?\>

         // jsMath for latex math fonts
         // see http://www.math.union.edu/~dpvc/jsMath/
         global $c_jsmath_enable;
         if (isset($c_jsmath_enable)
             and $c_jsmath_enable
         ) {
             $reg_exp_father_array[] = '~\\{\\$[\\$]{0,1}(.*?)\\$[\\$]{0,1}\\}~eu';
             $reg_exp_array['{$$'] = '~\\{\\$\\$(.*?)\\$\$\\}~eu'; // must be before next one
             $reg_exp_array['{$'] = '~\\{\\$(.*?)\\$\\}~eu';
         }

         // is there wiki syntax ?
         if (!empty($reg_exp_array)) {
             $reg_exp_keys = array_keys($reg_exp_array);
             $clean_text = false;
             foreach ($reg_exp_keys as $key) {
                 if (mb_stristr((string) $text, $key)) {
                     $clean_text = true;
                     break;
                 }
             }
         }
         // ########### lightbox images ckEditor ###############
         $matchesImages = [];
         preg_match_all('~<a.*?>(<img.*?>)</a>~u', (string) $text, $matchesLink);
         foreach ($reg_exp_image as $key => $exp) {
             $found = preg_match_all($exp, (string) $text, $matchesImages);
             if ($found > 0) {
                 foreach ($matchesImages[0] as $value) {
                     // found an <a> tag dont use lightbox
                     if (!in_array($value, $matchesLink[1])) {
                         // found an image tag
                         $args_array = $this->_getArgs($value, $exp);
                         // search for src attribute
                         $src = $this->_getArgs($args_array[1], '~src\=\"(.*?)\\"~eu');
                         $value_new = $value;
                         if ('<img' == $key and mb_stristr((string) $value_new, '<img')) {
                             $params = $this->_environment->getCurrentParameterArray();

                             $value_new = $this->_formatImageLightboxCkEditor($text, $args_array[0], $src[1],
                                 $params['iid']);
                             $text = str_replace($value, $value_new, (string) $text);
                             unset($value_new);
                         }
                     }
                 }
             }
         }

         // ########### lightbox images ckEditor ###############

         // clean wikistyle text from HTML-Code (via fckeditor)
         // and replace wikisyntax
         if ($clean_text) {
             $matches = [];
             foreach ($reg_exp_father_array as $exp) {
                 $found = preg_match_all($exp, (string) $text, $matches);
                 if ($found > 0) {
                     $matches[0] = array_unique($matches[0]); // doppelte einsparen
                     foreach ($matches[0] as $value) {
                         // delete HTML-tags and string conversion #########
                         $value_new = strip_tags((string) $value);
                         $value_new = str_replace('&nbsp;', ' ', $value_new);
                         // #################################################

                         foreach ($reg_exp_array as $key => $reg_exp) {
                             $check = false;
                             $args_array = $this->_getArgs($value_new, $reg_exp);
                             foreach ($args_array as $arg_value) {
                                 if (strstr((string) $arg_value, "'")
                                     and (substr_count((string) $arg_value, "'") % 2) == 1
                                 ) {
                                     $check = true;
                                     break;
                                 }
                             }
                             if ($check) {
                                 $value = $this->_getSubText($text, $value);
                                 // delete HTML-tags and string conversion #########
                                 $value_new = strip_tags((string) $value);
                                 $value_new = str_replace('&nbsp;', ' ', $value_new);
                                 // #################################################
                                 $args_array = $this->_getArgs2($value_new, $reg_exp);
                             }

                             if ('(:item' == $key and mb_stristr($value_new, '(:item')) {
                                 $value_new = $this->_formatItem($value_new, $args_array);
                                 break;
                             } elseif ('(:link' == $key and mb_stristr($value_new, '(:link')) {
                                 $value_new = $this->_formatLink($value_new, $args_array);
                                 break;
                             } elseif ('(:file' == $key and mb_stristr($value_new, '(:file')) {
                                 $value_new = $this->_formatFile($value_new, $args_array, $file_array);
                                 break;
                             }
                         }

                         $text = str_replace($value, $value_new, (string) $text);
                     }
                 }
             }
             if ($this->_processMath) {
                 $text .= '<script type="text/javascript">jsMath.Process();</script>'.LF;
             }
         }

         return $text;
     }

    private function _formatFile($text, $array, $file_name_array)
    {
        $image_text = '';
        if (!empty($array[1])
             and !empty($file_name_array)
        ) {
            $temp_file_name = htmlentities((string) $array[1], ENT_NOQUOTES, 'UTF-8');
            if (!empty($file_name_array[$temp_file_name])) {
                $file = $file_name_array[$temp_file_name];
            } elseif (!empty($file_name_array[html_entity_decode($temp_file_name, ENT_COMPAT, 'UTF-8')])) {
                $file = $file_name_array[html_entity_decode($temp_file_name, ENT_COMPAT, 'UTF-8')];
            }
            if (isset($file)) {
                if (!empty($array[2])) {
                    $args = $this->_parseArgs($array[2]);
                } else {
                    $args = [];
                }

                $icon = '';

                if (empty($args['size'])
                     or (!empty($args['size'])
                          and 'true' == $args['size']
                     )
                ) {
                    $kb = ' ('.$file->getFileSize().' KB)';
                } else {
                    $kb = '';
                }
                if (!empty($args['text'])) {
                    $name = $args['text'];
                } else {
                    $name = $file->getDisplayName();
                }

                if (!empty($args['target'])) {
                    $target = ' target="'.$args['target'].'"';
                } elseif (!empty($args['newwin'])) {
                    $target = ' target=_blank;';
                } else {
                    $target = '';
                }
                $source = $file->getUrl();
                if (('jpg' == $file->getExtension()) or ('gif' == $file->getExtension()) or ('png' == $file->getExtension())) {
                    $image_text = '<a href="'.$source.'"'.$target.' rel="lightbox">'.$icon.$name.'</a>'.$kb;
                } else {
                    $image_text = '<a href="'.$source.'"'.$target.'>'.$icon.$name.'</a>'.$kb;
                }
            }
        }

        if (!empty($image_text)) {
            $text = str_replace($array[0], $image_text, (string) $text);
        }

        return $text;
    }

    private function _formatImageLightboxCkEditor($text, $imgTag, $link, $fileID)
    {
        $image_text = null;
        $retour = '';
        $image_text .= '<a class="lightbox_'.$fileID.'" href="'.$link.'" target="blank">';
        $image_text .= $imgTag;
        $image_text .= '</a>';

        if (!empty($image_text)) {
            $retour = $image_text;
        }

        return $retour;
    }

    private function _formatItem($text, $array)
    {
        $retour = '';
        $image_text = '';
        if (!empty($array[2])) {
            $args = $this->_parseArgs($array[2]);
        } else {
            $args = [];
        }

        if (!empty($args['text'])) {
            $word = $args['text'];
        } else {
            $word = '';
        }

        if (!empty($args['target'])) {
            $target = $args['target'];
        } elseif (!empty($args['newwin'])) {
            $target = '_blank';
        } else {
            $target = '';
        }

        if (!empty($array[1])) {
            $params = [];
            $params['iid'] = $array[1];
            if (empty($word)) {
                $word = $array[1];
            }
            // determ between type of item id
            $item_manager = $this->_environment->getItemManager();
            $item_manager->resetLimits();
            $type = $item_manager->getItemType($word);
            unset($item_manager);

            if (CS_ROOM_TYPE == $type ||
                  CS_COMMUNITY_TYPE == $type ||
                  CS_PRIVATEROOM_TYPE == $type ||
                  CS_GROUPROOM_TYPE == $type ||
                  CS_MYROOM_TYPE == $type ||
                  CS_PROJECT_TYPE == $type ||
                  CS_PORTAL_TYPE == $type/* ||
                $type == CS_SERVER_TYPE*/) {
                $image_text = ahref_curl($word, 'home', 'index', '', $word);
            } else {
                $image_text = ahref_curl($this->_environment->getCurrentContextID(), 'content', 'detail', $params, $word, '', $target, '');
            }
        }
        if (!empty($image_text)) {
            $text = str_replace($array[0], $image_text, (string) $text);
        }

        $retour = $text;

        return $retour;
    }

    private function _formatLink($text, $array)
    {
        $retour = '';
        $image_text = '';
        if (!empty($array[3])) {
            $args = $this->_parseArgs($array[3]);
        } else {
            $args = [];
        }

        if (!empty($args['text'])) {
            $word = $args['text'];
        } else {
            $word = '';
        }

        if (!empty($args['target'])) {
            $target = ' target="'.$args['target'].'"';
        } elseif (!empty($args['newwin'])) {
            $target = ' target=_blank;';
        } else {
            $target = '';
        }

        if (empty($array[1])) {
            $source = 'http://'.$array[2];
        } else {
            $source = $array[1].$array[2];
        }

        if (!empty($source)) {
            if (empty($word)) {
                $word = $source;
            }
            $image_text = '<a href="'.$source.'"'.$target.'>'.$word.'</a>';
        }
        if (!empty($image_text)) {
            $text = str_replace($array[0], $image_text, (string) $text);
        }

        $retour = $text;

        return $retour;
    }

    public function encode($mode, $value)
    {
        $retour = '';
        if (!empty($value)) {
            if (is_array($value)) {    // nicht in eine if-Anweisung, sonst
                if (count($value) > 0) {  // werden leere Arrays an die _text_encode weitergegeben
                    return $this->_array_encode($value, $mode);
                }
            } else {
                return $this->_text_encode($value, $mode);
            }
        } else {
            return $value;
        }
    }

    private function _array_encode($array, $mode)
    {
        if (FROM_FORM == $mode) {
            // security KFC
            $array = $this->_array_encode_fck_security($array);
        }
        $retour_array = [];
        foreach ($array as $key => $value) {
            if (is_array($value)) {    // nicht in eine if-Anweisung, sonst
                if (count($value) > 0) {  // werden leere Arrays an die _text_encode weitergegeben
                    $retour_array[$key] = $this->_array_encode($value, $mode);
                }
            } else {
                $retour_array[$key] = $this->_text_encode($value, $mode);
            }
        }

        return $retour_array;
    }

    // security KFC
    private function _array_encode_fck_security($array)
    {
        $retour = [];
        $fck_array = [];
        foreach ($array as $key => $value) {
            if (is_string($value)
                 and strstr($value, '<!-- KFC TEXT')
                 and !stristr((string) $key, '_fck_hidden')
            ) {
                $fck_array[$key] = $value;
            } else {
                $retour[$key] = $value;
            }
        }
        if (!empty($fck_array)) {
            foreach ($fck_array as $key => $value) {
                if (isset($retour[$key.'_fck_hidden'])) {
                    $values = [];
                    preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u', $value, $values);
                    if (!empty($values[1])) {
                        $hash = $values[1];
                        $temp_text = str_replace('<!-- KFC TEXT '.$hash.' -->', '', $value);

                        // html bug of fckeditor
                        $temp_text = str_replace('<br type="_moz" />', '<br />', $temp_text);
                        // ist dies das unmotivierte br ??? cs_view.php Zeile 283

                        $hidden_value = str_replace('COMMSY_AMPERSEND', '&', (string) $retour[$key.'_fck_hidden']);
                        $hidden_value = str_replace('COMMSY_QUOT', '"', $hidden_value);

                        $hidden_values = [];
                        preg_match('~<!-- KFC TEXT ([a-z0-9]*) -->~u', $hidden_value, $hidden_values);
                        if (!empty($hidden_values[1])) {
                            $hidden_hash = $hidden_values[1];
                            $hidden_value = str_replace('<!-- KFC TEXT '.$hidden_hash.' -->', '', $hidden_value);
                        }

                        $new_hash = getSecurityHash($temp_text);
                        $retour[$key] = '<!-- KFC TEXT '.$new_hash.' -->'.$temp_text.'<!-- KFC TEXT '.$new_hash.' -->';
                    } else {
                        $retour[$key] = $value;
                    }
                } else {
                    $retour[$key] = $value;
                }
            }
        }

        return $retour;
    }

    private function _text_encode($text, $mode)
    {
        switch ($mode) {
            case NONE:
                return $text;
            case AS_HTML_LONG:
                return $this->text_as_html_long($text); // nein
            case AS_HTML_SHORT:
                return $this->text_as_html_short($text); // nein
            case AS_MAIL:
                return $this->_text_php2mail($text); // ja
            case AS_RSS:
                return $this->_text_php2rss($text); // ja
            case AS_FORM:
                return $this->text_as_form($text); // nein
            case AS_DB:
                return $this->_text_php2db($text); // ja
            case AS_FILE:
                return $this->_text_php2file($text); // ja
            case HELP_AS_HTML_LONG:
                return $this->_help_as_html_long($text); // ja
            case FROM_FORM:
                return $this->_text_form2php($text); // ja
            case FROM_DB:
                return $this->_text_db2php($text); // ja
            case FROM_FILE:
                return $this->_text_file2php($text); // ja
            case FROM_GET:
                return $this->_text_get2php($text); // ja
        }
        trigger_error('You need to specify a mode for text translation.', E_USER_WARNING);
    }

    private function _text_php2db($text)
    {
        $db_connection = $this->_environment->getDBConnector();
        return $db_connection->text_php2db($text);
    }

    private function _text_db2php($text)
    {
        // jsMath for latex math fonts
        // see http://www.math.union.edu/~dpvc/jsMath/
        global $c_jsmath_enable;
        if (isset($c_jsmath_enable)
             and $c_jsmath_enable
        ) {
            if (strstr((string) $text, '{$')) {
                $matches = [];
                $exp = '~\\{\\$(.*?)\\$\\}~eu';
                $found = preg_match_all($exp, (string) $text, $matches);
                if ($found > 0) {
                    foreach ($matches[0] as $key => $value) {
                        $value_new = 'COMMSYMATH'.$key;
                        $text = str_replace($value, $value_new, (string) $text);
                    }
                }
            }
        }

        // $text = preg_replace('~\\\(?!\*|_|!|-|#|\(:|n)~u', '', $text);

        // jsMath for latex math fonts
        // see http://www.math.union.edu/~dpvc/jsMath/
        if (!empty($found)
             and $found > 0
        ) {
            foreach ($matches[0] as $key => $value) {
                $value_new = 'COMMSYMATH'.$key;
                $text = str_replace($value_new, $value, (string) $text);
            }
        }

        return $text;
    }

    private function _text_get2php($text)
    {
        $text = rawurldecode((string) $text);
        if (strstr($text, '<')) {
            $text = $this->_cleanBadCode($text);
        }

        return $text;
    }

    private function _text_file2php($text)
    {
        return str_replace('&quot;', '"', (string) $text);
    }

    private function _help_as_html_long($text)
    {
        $text = nl2br((string) $text);
        $text = $this->_emphasize_text($text);
        $text = $this->_activate_urls($text);
        $text = $this->_display_headers($text);
        $text = $this->_format_html_long($text);
        $text = $this->_parseText2ID($text);
        $text = $this->_decode_backslashes($text);
        $text = $this->_br_with_nl($text);

        return $text;
    }

    private function _text_php2mail($text)
    {
        return $text;
    }

    private function _text_php2rss($text)
    {
        $text = $this->_text_objectTag2rss($text);
        $text = str_replace('&', '&amp;', (string) $text);
        $text = str_replace('<', '&lt;', $text);

        return $text;
    }

    private function _text_objectTag2rss($text)
    {
        // find object tags and replace them with a hint and a link
        $translator = $this->_environment->getTranslationObject();
        $translation = $translator->getMessage('RSS_OBJECT_TAG_REPLACE');
        $replace = '<a href="\\1">['.$translation.']</a>';

        return preg_replace('/<object.*>.*value="(.*)".*<\/object>/U', $replace, (string) $text);
    }

    private function _text_php2file($text)
    {
        $text = str_replace('"', '&quot;', (string) $text);
        $text = str_replace('&lt;', '<', $text);
        $text = str_replace('&gt;', '>', $text);

        return $text;
    }

    private function _text_form2php($text)
    {
        // Fix up line feed characters from different clients (Windows, Mac => Unix)
        $text = mb_ereg_replace('~\r\n?~u', "\n", (string) $text);
        $text = trim($text);

        // clean text from word
        $text = $this->cleanTextFromWord($text);

        return $text;
    }

    public function cleanTextFromWord($value, $force = false)
    {
        $retour = $value;
        if ($force
             or stristr((string) $value, '<w:WordDocument>')
             or stristr((string) $value, 'class="Mso')
        ) {
            $retour = str_replace('<o:p></o:p>', '', (string) $retour);
            $retour = mb_eregi_replace(' class="[A-Za-z0-9-]*"', '', $retour);
            $retour = mb_eregi_replace(' lang="[A-Za-z0-9-]*"', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}u[0-9]{1}:[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}st1:[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}o:[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}v:[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}meta[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}link[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<!--[{}A-Za-z0-9 \[\]\!&]*-->', '', $retour);

            // ms word if - statements
            while (stristr($retour, '<![endif]-->')) {
                $pos1 = strpos($retour, '<!--[');
                $pos2 = strpos($retour, '<![endif]-->');
                $len = (int) ($pos2 - $pos1) + strlen('<![endif]-->');
                $sub = substr($retour, $pos1, $len);
                $retour = str_replace($sub, '', $retour);
            }

            // ms word style definitions
            $retour = str_replace(' style=""', '', $retour);
            $retour = mb_eregi_replace(' style="[^"]*"', '', $retour);
            while (stristr($retour, '</style>') and stristr($retour, '<style')) {
                $pos1 = strpos($retour, '<style');
                $pos2 = strpos($retour, '</style>');
                $len = (int) ($pos2 - $pos1) + strlen('</style>');
                $sub = substr($retour, $pos1, $len);
                $retour = str_replace($sub, '', $retour);
            }

            // HTML-tags
            $retour = mb_eregi_replace('<[/]{0,1}font[^>]*>', '', $retour);
            $retour = mb_eregi_replace('<[/]{0,1}span>', '', $retour);
            $retour = str_replace('<p></p>', '', $retour);
            $retour = str_replace('<blink></blink>', '', $retour);

            $retour = trim($retour);
        }

        return $retour;
    }

    public function convertPercent($text, $empty = true, $urlencode = false)
    {
        if (strstr((string) $text, '%')) {
            $current_user = $this->_environment->getCurrentUserItem();
            if (isset($current_user)) {
                $user_id = $current_user->getUserID();
                if ($urlencode) {
                    $user_id = rawurlencode($user_id);
                }
                if (!empty($user_id)) {
                    $text = str_replace('%USERID%', $user_id, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%USERID%', '', (string) $text);
                }
                $firstname = $current_user->getFirstName();
                if ($urlencode) {
                    $firstname = rawurlencode($firstname);
                }
                if (!empty($firstname)) {
                    $text = str_replace('%FIRSTNAME%', $firstname, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%FIRSTNAME%', '', (string) $text);
                }
                $lastname = $current_user->getLastName();
                if ($urlencode) {
                    $lastname = rawurlencode($lastname);
                }
                if (!empty($lastname)
                     and 'GUEST' != $lastname
                ) {
                    $text = str_replace('%LASTNAME%', $lastname, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%LASTNAME%', '', (string) $text);
                }
                $email = $current_user->getEMail();
                if ($urlencode) {
                    $email = rawurlencode($email);
                }
                if (!empty($email)) {
                    $text = str_replace('%EMAIL%', $email, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%EMAIL%', '', (string) $text);
                }
                unset($current_user);
            } elseif ($empty) {
                $text = str_replace('%USERID%', '', (string) $text);
                $text = str_replace('%FIRSTNAME%', '', $text);
                $text = str_replace('%LASTNAME%', '', $text);
                $text = str_replace('%EMAIL%', '', $text);
            }

            $current_context = $this->_environment->getCurrentContextItem();
            if (isset($current_context)) {
                $title = $current_context->getTitle();
                if ($urlencode) {
                    $title = rawurlencode($title);
                }
                if (!empty($title)) {
                    $text = str_replace('%TITLE%', $title, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%TITLE%', '', (string) $text);
                }
                unset($current_context);
            } elseif ($empty) {
                $text = str_replace('%TITLE%', '', (string) $text);
            }

            $current_portal = $this->_environment->getCurrentPortalItem();
            if (isset($current_portal)) {
                $title = $current_portal->getTitle();
                if ($urlencode) {
                    $title = rawurlencode($title);
                }
                if (!empty($title)) {
                    $text = str_replace('%PORTAL%', $title, (string) $text);
                } elseif ($empty) {
                    $text = str_replace('%PORTAL%', '', (string) $text);
                }
                unset($current_portal);
            } elseif ($empty) {
                $text = str_replace('%PORTAL%', '', (string) $text);
            }
        }

        return $text;
    }

     private function _constructHTMLPurifier()
     {
         global $symfonyContainer;
         $projectDir = $symfonyContainer->getParameter('kernel.project_dir');
         require_once $projectDir.'/vendor/ezyang/htmlpurifier/library/HTMLPurifier.auto.php';

         // Allow Full HTML
         $configFullHTML = $this->_getFullHTMLPurifierConfig();
         $this->_FullHTMLPurifier = new HTMLPurifier($configFullHTML);

         // Do not allow HTML
         $configHTML = $this->_getHTMLPurifierConfig();
         $this->_HTMLPurifier = new HTMLPurifier($configHTML);
     }

     private function _getHTMLPurifierConfig()
     {
         $config = HTMLPurifier_Config::createDefault();

         global $symfonyContainer;
         $projectDir = $symfonyContainer->getParameter('kernel.project_dir');

         $config->set('Cache.SerializerPath', $projectDir.'/var/cache/htmlpurifier');

         $config->set('HTML.Allowed', '');

         return $config;
     }

     private function _getFullHTMLPurifierConfig()
     {
         $config = HTMLPurifier_Config::createDefault();

         global $symfonyContainer;
         $projectDir = $symfonyContainer->getParameter('kernel.project_dir');

         $config->set('Cache.SerializerPath', $projectDir.'/var/cache/htmlpurifier');

         $config->set('HTML.Allowed', null);

         $config->set('HTML.SafeIframe', true);
         $config->set('URI.SafeIframeRegexp', '%^(https?:)?//(www\.youtube(?:-nocookie)?\.com/embed/|www\.podcampus\.de/nodes/|www\.slideshare\.net/slideshow/embed_code/|lecture2go\.uni-hamburg\.de/lecture2go-portlet/player/iframe/)%');

         // allow target=
         $config->set('Attr.AllowedFrameTargets', '_blank,_self,_top,_parent');

         $def = $config->getHTMLDefinition(true);

         // <div/>-Definition
         $def->addAttribute('div', 'data-type', 'Text');

         // Attribute for object
         $def->addAttribute('object', 'autoplay', 'Enum#true,false');
         $def->addAttribute('object', 'classid', 'Text');
         $def->addAttribute('object', 'codebase', 'Text');
         $def->addAttribute('object', 'standby', 'Text');
         $def->addAttribute('object', 'commsytype', 'Text');

         // Attribute for enlarging iFrames
         $def->addAttribute('iframe', 'allowfullscreen', 'Text');

         // Attribute for param
         $def->addAttribute('param', 'bgcolor', 'Text');
//
//        // Attribute for embed
//        $def->addAttribute('embed', 'autoplay', 'Enum#true,false');
//        $def->addAttribute('embed', 'bgcolor', 'Text');
//        $def->addAttribute('embed', 'controller', 'Text');
//        $def->addAttribute('embed', 'devicefont', 'Text');
//        $def->addAttribute('embed', 'loop', 'Text');
//        $def->addAttribute('embed', 'pluginspage', 'Text');
//        $def->addAttribute('embed', 'quality', 'Text');
//        $def->addAttribute('embed', 'scale', 'Text');
//        $def->addAttribute('embed', 'type', 'Text');
//        $def->addAttribute('embed', 'autostart', 'Text');
//        $def->addAttribute('embed', 'showcontrols', 'Text');
//        $def->addAttribute('embed', 'showstatusbar', 'Text');
//        $def->addAttribute('embed', 'standby', 'Text');
//        $def->addAttribute('embed', 'commsytype', 'Text');

         // <video/>-Definition
         $def->addElement('video', 'Block', 'Flow', 'Common', []);
         $def->addAttribute('video', 'width', 'Text');
         $def->addAttribute('video', 'height', 'Text');
         $def->addAttribute('video', 'controls', 'Bool');
         $def->addAttribute('video', 'src', 'URI');

         // <audio/>-Definition
         $def->addElement('audio', 'Block', 'Flow', 'Common', []);
         $def->addAttribute('audio', 'width', 'Text');
         $def->addAttribute('audio', 'height', 'Text');
         $def->addAttribute('audio', 'controls', 'Bool');
         $def->addAttribute('audio', 'src', 'URI');

         $def->addElement('source', 'Block', 'Flow', 'Common', []);
         $def->addAttribute('source', 'src', 'URI');
         $def->addAttribute('source', 'type', 'Text');

         return $config;
     }

    public function sanitizeHTML($text)
    {
        $clean_html = $this->_HTMLPurifier->purify($text);

        return $clean_html;
    }

    /*
     *    This function uses HTMLPurifier to clean user input
     *    Allows HTML Tags
     */
    public function sanitizeFullHTML($text)
    {
        return $this->_FullHTMLPurifier->purify($text);
    }

    public function emphasizeFilename($text)
    {
        // search (with yellow background)
        $text = preg_replace('~\(:mainsearch_text_yellow:\)(.+)\(:mainsearch_text_yellow_end:\)~uU', '<span class="searched_text_yellow">$1</span>', (string) $text);

        // search (with green background)
        $text = preg_replace('~\(:mainsearch_text_green:\)(.+)\(:mainsearch_text_green_end:\)~uU', '<span class="searched_text_green">$1</span>', $text);

        // search
        // maybe with yellow or orange background ???
        $text = preg_replace('~\(:search:\)(.+)\(:search_end:\)~uU', '<span style="font-style:italic;">$1</span>', $text);
        // $text = preg_replace('~\(:search:\)(.+)\(:search_end:\)~u', '<span class="searched_text">$1</span>', $text);

        return $text;
    }

    /*
     *    format full html content
     */
    public function textFullHTMLFormatting($text)
    {
        // TODO Fehler in der Anzeige von H2. Bild wird über css angehängt
        // $text = $this->_display_headers($text);
        // $text = $this->_emphasize_text($text);
        // $text = $this->_format_html_long($text);
        // ersetzt durch _old_htmlformat

        // nl 2 br
        // $text = nl2br($text);

        $text = $this->_decode_backslashes($text);

        // bold italic list healines separator etc
        $text = $this->_old_htmlformat($text);

        // Formatierungsfunktionen auf Text anwenden
        $text = $this->_newFormating($text);

        // format reference to link
        $text = $this->_parseText2ID($text);

        // activate url which is not added by the
        $text = $this->_activate_urls($text);

        // $text = $this->sanitize($text);

//       $text = $this->_cs_htmlspecialchars($text,$htmlTextArea);
//       $text = nl2br($text);
//       $text = $this->_decode_backslashes_1($text); ?
//       $text = $this->_preserve_whitespaces($text); ?
//       $text = $this->_newFormating($text);         -
//       $text = $this->_emphasize_text($text);       -
//       $text = $this->_activate_urls($text);        -
//       $text = $this->_display_headers($text);         -
//       $text = $this->_format_html_long($text);     ?
//       $text = $this->_parseText2ID($text);         -
//       $text = $this->_decode_backslashes_2($text); ?
//       $text = $this->_delete_unnecassary_br($text);   ?
//       $text = $this->_br_with_nl($text);           ?
        return $text;
    }

    /*
     *    This function replaces:
     *    #     to a numeric list
     *    -     to a list
     *    ---      to horizontal line
     *    *text*   to bold text
     *    _text_   to italic text
     *    !text to headline4
     *    !!text   to headline3
     *    !!!text to headline2
     */
    public function _old_htmlformat($text)
    {
        //
        // display header !text !!text !!!text
        $text = $this->_display_headers($text);
        // Listen, Trennlinie // # , - , ---
        $text = $this->_format_html_long($text);
        // use emphasized color search !? // bold kursiv
        $text = $this->_emphasize_text($text);

        return $text;
    }
}
