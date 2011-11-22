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
    
    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}/styles.css" />
    
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
            <div id="meta_area_content">
            
                <div id="breadcrumb">
                    <span><a href="" class="mm_right">CommSy Projekt</a></span>
                    <span><a href="" class="mm_dropdown">CommSy Community</a></span>
                </div>
                
                <div id="meta_menu">
                	{* login / logout *}
                	{if $environment.is_guest}
                    	login maske
                    {else}
                    	<span class="mm_bl"><a href="" id="mm_logout">Abmelden</a></span>
                    	<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Mein CommSy</a></span>
                    	{if $environment.is_moderator}
                    		<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Admin</a></span>
                    	{/if}
                    	<span class="mm_br">___COMMON_WELCOME___, {$environment.username}</span>
                    {/if}
                </div>

                <div class="clear"> </div>
            </div>
        </div> <!-- Ende meta_area -->
        
        <div id="header"> <!-- Start header -->
            <div id="logo_area">
                <a href=""><img src="{$basic.tpl_path}/img/commsy_logo.gif" alt="CommSy" /></a> <!-- Logo-Hoehe 60 Pixel -->
            </div>
            
            <div id="search_area">
                <div id="search_navigation">
                    <span class="sa_sep"><a href="" id="sa_active">nur dieser Raum</a></span>
                    <span class="sa_sep"><a href="">alle meine R&auml;ume</a></span>
                    <span id="sa_options"><a href=""><img src="{$basic.tpl_path}/img/sa_dropdown.gif" alt="O" /></a></span>
                    
                    <div class="clear"> </div>
                    
                    <div id="commsy_search">
                        <input id="search_input" type="text" value="Suche ..." /><input type="image" src="{$basic.tpl_path}/img/btn_search.gif" alt="absenden" />
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
                    <span>15. November 2011, 15:00</span>
                    <a href="">E-Mail an die Moderation</a>
                    <a href="">Support-Anfrage stellen</a>
                </p>
            </div>
        
            <div class="clear"> </div>
        </div> <!-- Ende footer -->
        
        
        <!-- hier Google Adwords -->
        
        
    </div> <!-- Ende wrapper -->
</body>

</html>