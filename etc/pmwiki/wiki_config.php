<?php if (!defined('PmWiki')) exit();

include_once('commsy_config.php');
if ( !empty($COMMSY_WIKI_TITLE) ) {
   $WikiTitle = $COMMSY_WIKI_TITLE;
} else {
   $WikiTitle = 'CommSyWiki';
}
if ( !empty($COMMSY_ADMIN_PASSWD) ) {
   $DefaultPasswords['admin'] = crypt($COMMSY_ADMIN_PASSWD);
}
if ( !empty($COMMSY_UPLOAD_PASSWD) ) {
   $EnableUpload = 1;
   $DefaultPasswords['upload'] = crypt($COMMSY_UPLOAD_PASSWD);
   $UploadMaxSize = 1000000000;
}
if ( !empty($COMMSY_EDIT_PASSWD) ) {
   $DefaultPasswords['edit'] = crypt($COMMSY_EDIT_PASSWD);
}
if ( !empty($COMMSY_READ_PASSWD) ) {
   $DefaultPasswords['read'] = crypt($COMMSY_READ_PASSWD);
}
if ( !empty($COMMSY_SKIN) ) {
   $Skin = $COMMSY_SKIN;
}
if ( !empty($COMMSY_LANGUAGE) ) {
   XLPage($COMMSY_LANGUAGE,'PmWikiDe.XLPage');
}
?>