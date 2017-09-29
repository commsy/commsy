<?php

namespace CommsyBundle\Twig\Extension;

use Commsy\LegacyBundle\Services\LegacyMarkup;
use Commsy\LegacyBundle\Utils\ItemService;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

class MarkupExtension extends \Twig_Extension
{
    private $router;
    private $itemService;
    private $legacyMarkup;

    public function __construct(Router $router, ItemService $itemService, LegacyMarkup $legacyMarkup)
    {
        $this->router = $router;
        $this->itemService = $itemService;
        $this->legacyMarkup = $legacyMarkup;
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

        $text = $this->legacyMarkup->convertToHTML($text);

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
}