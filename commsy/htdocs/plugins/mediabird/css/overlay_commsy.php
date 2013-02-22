<?php

header("Content-type: text/css");
// load required classes
chdir('../../../..');
include_once('etc/cs_constants.php');
include_once('etc/cs_config.php');
include_once('classes/cs_environment.php');
// create environment of this page
$color = $cs_color['DEFAULT'];

// find out the room we're in
if (!empty($_GET['cid'])) {
   $cid = $_GET['cid'];
   $environment = new cs_environment();
   $environment->setCurrentContextID($cid);
   $room = $environment->getCurrentContextItem();
   $color = $room->getColorArray();
}
?>

div.mediabird-overlay {
   z-index: 1000;
   background-color: #F3F1FA;
   position: absolute;
   top: 0;
   left: 0;
   border: 2px solid <?php echo($color['tabs_background'])?>;
   overflow: hidden;
   display: none;
}

div.mediabird-overlay a.closer {
   background: transparent no-repeat 0 0;
   -moz-box-shadow: 0px;
   -moz-border-radius: 0px 0px;
   -webkit-border-radius: 0px 0px;
   -webkit-box-shadow: 0px;
   color: #CCC;
   padding: 0px 0px 10px 0px;
   width: 10px;
   margin-top: 8px;
   height: 40px;
   float: right;
}

div.mediabird-overlay div.bar {
   color: #CCC;
   vertical-align:top;
   margin:0px;
   padding:0px 5px 5px 10px;
   font-size: 14pt;
   -moz-box-shadow: 0px;
   -moz-border-radius: 0px 0px;
   -webkit-border-radius: 0px 0px;
   -webkit-box-shadow: 0px;
   background: url(../images_commsy/top_menu_bg.jpg) repeat-x;
   height: 40px;
}

div.mediabird-overlay a.expander {
   margin: 4px;
   background: transparent no-repeat 0 0;
   border: 1px solid white;
   border-top: 2px solid white;
   width: 40px;
   height: 40px;
   float: right;
   -moz-border-radius: 0px;
   -webkit-border-radius: 0px;
}

div.mediabird-overlay a.expander.expanded {
   background: transparent no-repeat 0 0;
   border: 1px solid #CCC;
   border-top: 2px solid #CCC;
   width: 10px;
   height: 10px;
   margin-top:10px;
   margin-right: 8px;
}

div.mediabird-overlay iframe, div.mediabird-overlay div.flipContainer {
    top: 34px;
    z-index:100;
	position: absolute;
	bottom: 0;
}

div.mediabird-overlay div.resize-handle {
	height: 16px;
	width: 16px;
	background: transparent url('../images/overlay-handle-sw.png') no-repeat 0 0;
	left: 0;
	bottom: 0;
	cursor: sw-resize;
	z-index: 300;
	position: absolute;
	-moz-border-radius: 0px 6px;
	-webkit-border-radius: 0px 6px;
}

div.mediabird-overlay div.resize-handle.right {
	background: transparent url('../images/overlay-handle-se.png') no-repeat 0 0;
	left: auto;
	right: 0;
	cursor: se-resize;
}

div.mediabird-overlay div.title {
   padding-top: 8px;
}
