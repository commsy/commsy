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

    <link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />

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
        </div> <!-- Ende meta_area -->

        <div id="header"> <!-- Start header -->
            <div id="logo_area">
                <a href=""><img src="{$basic.tpl_path}img/commsy_logo.gif" alt="CommSy" /></a> <!-- Logo-Hoehe 60 Pixel -->
            </div>

            <div id="search_area">
                <div id="search_navigation">
                    <span class="sa_sep"><a href="" id="sa_active">nur dieser Raum</a></span>
                    <span class="sa_sep"><a href="">alle meine R&auml;ume</a></span>
                    <span id="sa_options"><a href=""><img src="{$basic.tpl_path}img/sa_dropdown.gif" alt="O" /></a></span>

                    <div class="clear"> </div>

                    <div id="commsy_search">
                    	<form action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post">
                        	<input name="form_data[keywords]" id="search_input" type="text" value="Suche ..." />
                        	<input id="search_suggestion" type="text" value="" />
                        	<input id="search_submit" type="submit" class="search_button" value="___COMMON_GO_BUTTON2___!" />
                        </form>
                    </div>
                </div>
            </div>

            <div class="clear"> </div>
        </div> <!-- Ende header -->

        <div id="columnset"> <!-- Start columnset -->

            <div id="left_column"> <!-- Start left_column -->

                <div id="main_navigation">
                    <ul>
                    	<!--  <li id="active"><a href="commsy.php?cid={$environment.cid}&mod=home&fct=index"><span id="ho_act"></span><br/>Home</a></li>-->
                    	{foreach $room.rubric_information as $rubric}
                    		<li class="non_active">
                    			<a href="commsy.php?cid={$environment.cid}&mod={$rubric.name}&fct=index">
                    				<span id="{if $rubric.active}{$rubric.span_prefix}_act{else}{$rubric.span_prefix}_non_act{/if}"></span><br/>
                    				{if $rubric.translate}___COMMON_{$rubric.name|upper}_INDEX___{else}{$rubric.name}{/if}
                    			</a>
                    		</li>
                    	{/foreach}
                    </ul>
                    <div class="clear"> </div>


                    <div id="site_actions">
                    	{block name=room_site_actions}{/block}
                    </div>

                    <h1>{block name=room_navigation_rubric_title}{/block}</h1>

                    <div class="clear"> </div>
                </div>

                <div id="maincontent">
                	___{$exception.message_tag}___
                </div>

            </div> <!-- Ende left_column -->

            <div id="right_column"> <!-- Start right_column -->
            	{block name=room_right_info_addon}
					<div id="info_addon">
						<div id="info_area">
							<div id="infos_left">
								<h2>Rauminfos:</h2>
								<p>
									___ACTIVITY_NEW_ENTRIES___: {$room.room_information.new_entries}
								</p>
								<p>
									___ACTIVITY_PAGE_IMPRESSIONS___: {$room.room_information.page_impressions}
								</p>
							</div>

							<div id="infos_right">
								<div id="info_bar">
									<p>999</p>
								</div>
							</div>

							<div class="clear"> </div>
						</div>

						<div id="addon_area">
							<p>
								<a href="" title="Wiki"><img src="{$basic.tpl_path}img/addon_wiki.png" alt="Wiki" /></a>
								<a href="" title="RSS"><img src="{$basic.tpl_path}img/addon_rss.png" alt="RSS" /></a>
								<a href="" title="Chat"><img src="{$basic.tpl_path}img/addon_chat.png" alt="Chat" /></a>
								<a href="" title="Wordpress"><img src="{$basic.tpl_path}img/addon_wordpress.png" alt="Wordpress" /></a>
							</p>
							<div class="clear"> </div>
						</div>

						<div class="clear"> </div>
					</div>
				{/block}


           		<div id="rc_portlet_area">
           		</div>
            </div> <!-- Ende right_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->

        <div id="footer"> <!-- Start footer -->
            <div id="footer_left">
                <p>CommSy 8.0</p>
            </div>

            <div id="footer_right">
                <p>
                </p>
            </div>

            <div class="clear"> </div>
        </div> <!-- Ende footer -->


        <!-- hier Google Adwords -->


    </div> <!-- Ende wrapper -->
</body>

</html>