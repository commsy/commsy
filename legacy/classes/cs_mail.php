<?PHP
// $Id$
//
// Release $Name$
//
// Copyright (c)2002-2003 Matthias Finck, Dirk Fust, Oliver Hankel, Iver Jackewitz, Michael Janneck,
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

include_once ('classes/external_classes/mail.php');

/**
*  The class cs_mail makes it easy only for purposes of CommSy
*  to send mails. It inherits from the class Mail.
*/

class cs_mail extends Mail
{

   var $_file_added = false;

   var $_error_array = array();

   var $_as_html = false;

   /** standard constructor information
    */
   function __construct() {
      $this->mail = new Mail();
      $this->mime_mail=$this->mail->factory('mime');
   }

   /** set_to information
    *
    * set the recipients. the email-adresses should be divided by ","
    *
    * @param string $recipients
    */
   function set_to($recipients){
      $this->recipients = $recipients;
   }

   /** set_cc_to information
    *
    * set the recipients
    *
    * @param string $recipients
    */
   function set_cc_to($recipients){
      if (is_array($recipients)) {
         $recipients = implode(', ', $recipients);
      }
      $this->cc_recipients = $recipients;
   }

   /** set_bcc_to information
    *
    * set the recipients
    *
    * @param string $recipients
    */
   function set_bcc_to($recipients){
      if (is_array($recipients)) {
          $recipients = implode(', ', $recipients);
       }
      $this->bcc_recipients = $recipients;
   }

   /** set_from_email information
    *
    * set the from-email in the header of the mail
    *
    * @param string $from_email
    */
   function set_from_email($from_email){
      $this->from_email = $from_email;
   }

   /** set_from_name information
    *
    * set the from-name in the header of the mail
    *
    * @param string $from_name
    */
   function set_from_name($from_name) {
      $from_name = str_replace(',','',$from_name);
      $from_name = str_replace(':','',$from_name);
      $this->from_name = encode(AS_MAIL,$from_name);
   }

   /** set_reply_to_name information
    *
    * set the reply_to-name in the header of the mail
    *
    * @param string $reply_to_name
    */
   function set_reply_to_name($reply_to_name){
      $reply_to_name = str_replace(',','',$reply_to_name);
      $this->reply_to_name = encode(AS_MAIL,$reply_to_name);
   }

   /** set_reply_to information
    *
    * set the reply_to in the header of the mail
    *
    * @param string $reply_to_email
    */
   function set_reply_to_email($reply_to_email){
      $this->reply_to_email = $reply_to_email;
   }

   /** set_subject information
    *
    * set the subject for the mail
    *
    * @param string $subject
    */
   function set_subject($subject){
      $this->subject = encode(AS_MAIL,$subject);
   }

   /** set_message information
    *
    * set the subject for the mail
    *
    * @param string $message
    */
   function set_message($message){
      $this->message = encode(AS_MAIL,$message);
   }

   /** add_file information
    *
    * add a file as an attachment to the mail
    *
    * @param string $file
    */
   function add_file($file){
      $this->mime_mail->addAttachment($file);
      $this->_file_added = true;
   }

   function setSendAsHTML () {
      $this->_as_html = true;
   }

    /** send information
    *
    * send the mail.
    * The "to", "cc" and "bcc" in the header of the mail is restricted to 2048 characters.
    * In this method the size recipients is divided into one mail for one receiver, if
    * the pristine size of the recipients is greater than 2048.
    */
   function send($recipients = '', $headers = '', $body = '', $return = '')  {
      global $c_send_email;
      if ( !isset($c_send_email) or $c_send_email ) {
         if ( $this->_file_added ) {
            $this->mime_mail->setHTMLBody($this->message);
            $this->mime_mail->setTXTBody($this->message);
         } else {
            if ( $this->_as_html ) {
               $this->mime_mail->setHTMLBody($this->message);
            } else {
               $this->mime_mail->setTXTBody($this->message);
            }
         }
         $multipart_message = $this->mime_mail->get();

         $multipart_header =  $this->mime_mail->headers();
         if ( isset($this->from_name) ) {
            $from_name_temp = $this->from_name;
            if ( !empty($multipart_header['Content-Type'])
                 and stristr($multipart_header['Content-Type'],'utf')
               ) {
              mb_language('uni');
              mb_internal_encoding("UTF-8");
              $from_name_temp = mb_encode_mimeheader($from_name_temp,"UTF-8");
            }
            $multipart_header["From"] = $from_name_temp."<".$this->from_email.">";
         } else {
            $multipart_header["From"] = $this->from_email;
         }
         if ( isset($this->reply_to_name) ) {
            $reply_to_name_temp = $this->reply_to_name;
            if ( !empty($multipart_header['Content-Type'])
                 and stristr($multipart_header['Content-Type'],'utf')
               ) {
              mb_language('uni');
              mb_internal_encoding("UTF-8");
              $reply_to_name_temp = mb_encode_mimeheader($reply_to_name_temp,"UTF-8");
            }
            $multipart_header["Reply-To"] = $reply_to_name_temp."<".$this->reply_to_email.">";
         } elseif ( isset($this->reply_to_email) ) {
            $multipart_header["Reply-To"] = $this->reply_to_email;
         } else {
            $multipart_header["Reply-To"] = $this->from_email;
         }
         if (isset($this->subject)){
            $multipart_header["Subject"] = $this->subject;
         }else{
            $multipart_header["Subject"] = '';
         }

         $return_mail_address = '';

         global $symfonyContainer;
         $c_return_path_mail_address = $symfonyContainer->getParameter('commsy.settings.return_path_mail_address');

         if ( isset($c_return_path_mail_address)
              and !empty($c_return_path_mail_address)
            ) {
            if ( $c_return_path_mail_address == 'SENDER'
                 and !empty($this->from_email)
               ) {
               $return_mail_address = $this->from_email;
            } else {
               $return_mail_address = $c_return_path_mail_address;
            }
         }

         $success = true;
         $range = 2048;
         $this->recipients = $this->_cleanRecipients($this->recipients);

         if ( isset($this->cc_recipients) ) {
            $this->cc_recipients = $this->_cleanRecipients($this->cc_recipients);
         }
         if ( isset($this->bcc_recipients) ) {
            $this->bcc_recipients = $this->_cleanRecipients($this->bcc_recipients);
         }

         // send email seperately (one email for one receiver)
         if ( mb_strlen($this->recipients)>$range
              or ( isset($this->cc_recipients) and mb_strlen($this->cc_recipients)>$range )
              or ( isset($this->bcc_recipients) and mb_strlen($this->bcc_recipients)>$range )
            ) {
            $to_array = array();
            $to_array = explode(',',$this->recipients);
            if ( isset($this->cc_recipients) ) {
               $cc_array = array();
               $cc_array = explode(',',$this->cc_recipients);
               $to_array = array_merge($to_array,$cc_array);
            }
            if ( isset($this->bcc_recipients) ) {
               $bcc_array = array();
               $bcc_array = explode(',',$this->bcc_recipients);
               $to_array = array_merge($to_array,$bcc_array);
            }
            $to_array = array_unique($to_array);
            foreach ($to_array as $email) {
               if ( !isset($c_send_email) or ($c_send_email and $c_send_email !== 'print' and $c_send_email !== 'error_log') ) {
                  $result = $this->mail->send($email, $multipart_header, $multipart_message,$return_mail_address);
                  if (!$result) {
                     $this->_error_array[] = $email;
                  }
                  $success = $success && $result;
               } elseif ( $c_send_email === 'print' ) {
                  echo('<hr/>'.LF);
                  echo('TO: '.$email.BRLF);
                  echo('HEADER: '.LF);
                  echo('BODY:'.BRLF.nl2br($multipart_message).LF);
                  echo('<hr/>'.LF);
                  
                  $body = '<hr/>'.LF;
                  $body .= 'TO: '.$email.BRLF;
                  $body .= 'HEADER: '.LF;
                  $body .= 'BODY:'.BRLF.nl2br($multipart_message).LF;
                  
                  $datei = fopen('mailtest.txt','a+');
                  fwrite($datei, $body);
                  fclose($datei);
                  
               } else if ($c_send_email === '') {
                  error_log(print_r('------------------', true));
                  error_log(print_r('TO: '.$email, true));
                  error_log(print_r('HEADER: ', true));
                  error_log(print_r($multipart_header, true));
                  error_log(print_r('BODY:'.BRLF.nl2br($multipart_message), true));
                  error_log(print_r('------------------', true));
               }

            }
         }

         // send one email to all receiver
         else {
            if ( isset($this->cc_recipients) ) {
               $multipart_header["Cc"] = $this->cc_recipients;
            }
            if ( isset($this->bcc_recipients) ) {
               $multipart_header["Bcc"] = $this->bcc_recipients;
            }
            if ( !isset($c_send_email) or ($c_send_email and $c_send_email !== 'print' and $c_send_email !== 'error_log') ) {
               $result = $this->mail->send($this->recipients, $multipart_header, $multipart_message,$return_mail_address);
               if (!$result) {
                  $this->_error_array[] = $this->recipients;
               }
               $success = $success && $result;
            } elseif ( $c_send_email === 'print' ) {
               echo('<hr/>'.LF);
               echo('TO: '.$this->recipients.BRLF);
               echo('HEADER: '.LF);
               pr($multipart_header);
               echo('BODY:'.BRLF.nl2br($this->message).LF);
               echo('<hr/>'.LF);
            } else if ($c_send_email === 'error_log') {
               error_log(print_r('------------------', true));
               error_log(print_r('TO: '.$this->recipients.BRLF, true));
               error_log(print_r('HEADER: ', true));
               error_log(print_r($multipart_header, true));
               error_log(print_r('BODY:'.BRLF.nl2br($multipart_message), true));
               error_log(print_r('------------------', true));
            }
         }

         return $success;
      } else {
         return true;
      }
   }

   function getErrorArray () {
      return $this->_error_array;
   }

   private function _cleanRecipients ( $value ) {
      $retour = $value;
      $retour = str_replace(', ',',',$retour);
      if ( mb_substr_count($retour,'@') != mb_substr_count($retour,',')+1 ) {
         $retour_array = explode(',',$retour);
         $retour2_array = array();
         $mail_address = '';
         foreach ($retour_array as $value) {
            if ( strstr($value,'@') ) {
               $mail_address .= ' '.$value;
               $retour2_array[] = $mail_address;
               $mail_address = '';
            } else {
               $mail_address .= ' '.$value;
            }
         }
         $retour = implode(',',$retour2_array);
         unset($retour_array);
         unset($retour2_array);
      }
      return $retour;
   }
}
?>