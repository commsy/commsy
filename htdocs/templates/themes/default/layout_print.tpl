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

    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery-ui-custom-theme/jquery-ui-1.8.17.custom.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="print" href="{$basic.tpl_path}styles.css" />
<!--
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/individual/styles_cid.css" />
-->
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}ui.dynatree.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}uploadify.css" />
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}jquery-lightbox/jquery.lightbox-0.5.css" />

    <script type="text/javascript">
	      <!--
	   var datepicker_language = 'de';
	   var datepicker_choose = 'Datum auswählen';
	      -->
    </script>

    <script data-main="javascript/commsy8/main.js" src="javascript/commsy8/require.js"></script>

    <title>CommSy 8.0 - Home</title>

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