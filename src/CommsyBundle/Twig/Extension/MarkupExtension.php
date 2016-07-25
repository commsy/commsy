<?php

namespace CommsyBundle\Twig\Extension;

class MarkupExtension extends \Twig_Extension
{
    public function getFilters()
    {
        return array(
            new \Twig_SimpleFilter('commsyMarkup', array($this, 'commsyMarkup')),
        );
    }

    public function commsyMarkup($text)
    {
        $text = $this->_decode_backslashes($text);

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

        $text = $this->commsyMarkupLists($text);

        return $text;
    }

    public function getName()
    {
        return 'text_extension';
    }


    /**
    * commsy markup format method for lists and horizontal lines
    * markups signs (#, - and ---)
    **/
    private function commsyMarkupLists ($text) {
        $html = '';
        $matches = array();
        $list_type = '';
        $last_list_type = '';
        $list_open = false;

        //split up paragraphs in lines
        $lines = preg_split('~\s*\n~uU', $text);
        foreach ($lines as $line) {
            $line_html = '';
            $hr_line = false;
            //find horizontal rulers
            if (preg_match('~^--(-+)\s*($|\n|<)~u', $line)) {
                if ($list_open) {
                    $line_html .= $this->_close_list($last_list_type);
                    $list_open = false;
                }
                $line_html .= LF.'<hr/>'.LF;
                $hr_line = true;
            }

            //process lists
            elseif (!($hr_line) and preg_match('~^(-|#)(\s*)(.*)~su', $line, $matches)) {
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

            //All other lines without anything special
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

    private function _decode_backslashes ($text) {
        $retour = $text;
        $retour = str_replace("\*","&ast;",$retour);
        $retour = str_replace("\_","&lowbar;",$retour);
        $retour = str_replace("\!","&excl;",$retour);
        $retour = str_replace("\-","&macr;",$retour);
        $retour = str_replace("\#","&num;",$retour);
        $retour = str_replace("\\\\","&bsol;",$retour);
      return $retour;
    }

    /**
    * returns the html-code for opening a list
    */
    private function _open_list ($list_type) {
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
   private function _close_list ($list_type) {
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