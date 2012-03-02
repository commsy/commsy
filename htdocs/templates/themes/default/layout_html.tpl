{* Params Function *}
{function name=params}{foreach $params as $param}&{$param@key}={$param}{/foreach}{/function}

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
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}ui.dynatree.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}uploadify.css" />
    
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
    <div id="wrapper"> 
    
        <div id="meta_area"> <!-- Start meta_area -->
            {block name=meta_area}{/block}
        </div> <!-- Ende meta_area -->
        
        <div id="header"> <!-- Start header -->
            <div id="logo_area">
                <a href=""><img src="{$basic.tpl_path}img/commsy_logo.gif" alt="CommSy" /></a> <!-- Logo-Hoehe 60 Pixel -->
            </div>
            
            <div id="search_area">
                <div id="search_navigation">
                    <span class="sa_sep"><a href="" id="sa_active">___CAMPUS_SEARCH_ONLY_THIS_ROOM___</a></span>
                    {*<span class="sa_sep"><a href="">alle meine R&auml;ume</a></span>*}
                    {*<span id="sa_options"><a href=""><img src="{$basic.tpl_path}img/sa_dropdown.gif" alt="O" /></a></span>*}
                    
                    <div class="clear"> </div>
                    
                    <div id="commsy_search">
                    	<form action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post">
                    		{if $environment.module != 'home'}
                    			<input type="hidden" name="form_data[selrubric]" value="{$environment.module}"/>
                    		{elseif isset($environment.post.form_data.selrubric) && !empty($environment.post.form_data.selrubric)}
                    			<input type="hidden" name="form_data[selrubric]" value="{$environment.post.form_data.selrubric}"/>
                    		{/if}
                        	<input name="form_data[keywords]" id="search_input" type="text" value="Suche ..." />
                        	<input id="search_suggestion" type="text" value="" />
                        	<input id="search_submit" type="image" src="{$basic.tpl_path}img/btn_search.gif" alt="absenden" />
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="clear"> </div>
        </div> <!-- Ende header -->
        
        {block name=layout_content}{/block}
        
        <div id="footer"> <!-- Start footer -->
            <div id="footer_left">
                <p>CommSy 8.0</p>
            </div>
            
            <div id="footer_right">
                <p>
                    <span>{$smarty.now|date_format:"%d."} {$translation.act_month_long} {$smarty.now|date_format:"%Y, %H:%M"}
                    {*15. November 2011, 15:00*}</span>
                    <a href="commsy.php?cid={$environment.cid}&mod=mail&fct=to_moderator">___MAIL_TO_MODERATOR_HEADLINE___</a>
                    <a href="">TODO: ___COMMON_MAIL_TO_SERVICE2___</a>
                </p>
            </div>
        
            <div class="clear"> </div>
        </div> <!-- Ende footer -->
        
        
        <!-- hier Google Adwords -->
        
        
    </div> <!-- Ende wrapper -->
    {block name=room_overlay}{/block}
</body>

</html>