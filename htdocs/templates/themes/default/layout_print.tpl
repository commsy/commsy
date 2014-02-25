{* include template functions *}
{include file="include/functions.tpl" inline}

<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="de" lang="de">

<head>
    <meta http-equiv="Content-Type" content="text/html;charset=utf-8" />
    <meta name="robots" content="index, follow" />
    <meta name="revisit-after" content="7 days" />
    <meta name="language" content="German, de, deutsch" />
    <meta name="author" content="" />
    <meta name="page-topic" content="" />
    <meta name="keywords" content="" />
    <meta name="description" content="" />
    <meta name="copyright" content="" />

    <!-- CSS -->
    <link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dijit/themes/tundra/tundra.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/cbtree/themes/tundra/tundra.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/form/resources/UploaderFileList.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/image/resources/Lightbox.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="javascript/commsy8_dojo/libs/dojox/widget/ColorPicker/ColorPicker.css" />

	{block name="css"}
	    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
	    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles_print.css" />
	    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles_addon.css" />
	    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}cs_dojo.css" />
	{/block}
	
    <link rel="stylesheet" type="text/css" media="print" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="print" href="{$basic.tpl_path}styles_print.css" />
	    
    <title>{$environment.room_title} - ___{$environment.module_name}___</title>

    <!--
    **********************************************************************
    build in: November 2011
    copyright: Mark Thabe, banality GmbH
    location: Essen-Germany/Bielefeld-Germany, www.banality.de
    **********************************************************************
    -->
</head>

<body>
    <div id="wrapper_print">
        {block name=layout_content}{/block}
    </div>
</body>

</html>