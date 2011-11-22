{extends file="layout_html.tpl"}

{block name=layout_content}
	<div id="columnset"> <!-- Start columnset -->
        
            <div id="left_column"> <!-- Start left_column -->
                
                <div id="main_navigation">
                    <ul>
                        <li id="active"><a href="commsy.php?cid={$environment.cid}"><span id="ho_act"><span> </span></span><br/>Home</a></li>
                        <li class="non_active"><a href="" ><span id="an_non_act"></span><br/>Ank&uuml;ndigungen</a></li>
                        <li class="non_active"><a href="" ><span id="te_non_act"></span><br/>Termine</a></li>
                        <li class="non_active"><a href="" ><span id="ma_non_act"></span><br/>Material</a></li>
                        <li class="non_active"><a href="" ><span id="di_non_act"><span> </span></span><br/>Diskussionen</a></li>
                        <li class="non_active"><a href="" ><span id="pe_non_act"></span><br/>Personen</a></li>
                        <li class="non_active"><a href="" ><span id="gr_non_act"><span> </span></span><br/>Gruppen</a></li>
                        <li class="non_active"><a href="" ><span id="au_non_act"></span><br/>Aufgaben</a></li>
                        <li class="non_active"><a href="" ><span id="th_non_act"></span><br/>Themen</a></li>
                    </ul>
                    <div class="clear"> </div>
                    
                    
                    <div id="site_actions">
                    	{block name=room_site_actions}{/block}
                    </div>
                    
                    <h1>{block name=room_navigation_rubric_title}{/block}</h1>
                    
                    <div class="clear"> </div>
                </div>
                
                <div id="maincontent">
                	{block name=room_main_content}{/block}
                </div>
                
            </div> <!-- Ende left_column -->
            
            <div id="right_column"> <!-- Start right_column -->
            
                <div id="info_addon">
                    <div id="info_area">
                        <div id="infos_left">
                            <h2>Rauminfos:</h2>
                            <p>neue Beitr&auml;ge: 17</p>
                            <p>Seitenaufrufe: 904</p>
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
                            <a href="" title="Wiki"><img src="{$basic.tpl_path}/img/addon_wiki.png" alt="Wiki" /></a>
                            <a href="" title="RSS"><img src="{$basic.tpl_path}/img/addon_rss.png" alt="RSS" /></a>
                            <a href="" title="Chat"><img src="{$basic.tpl_path}/img/addon_chat.png" alt="Chat" /></a>
                            <a href="" title="Wordpress"><img src="{$basic.tpl_path}/img/addon_wordpress.png" alt="Wordpress" /></a>
                        </p>
                        <div class="clear"> </div>
                    </div>
                    
                    <div class="clear"> </div>
                </div>
                
                <div id="rc_portlet_area">
                
                    <div class="portlet_rc">
                        <a href="" title="schlie&szlig;en" class="btn_head_rc"><img src="{$basic.tpl_path}/img/btn_close_rc.gif" alt="close" /></a>
                        <h2>Schlagw&ouml;rter</h2>
                        
                        <div class="clear"> </div>
                        
                        <a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}/img/btn_edit_rc.gif" alt="close" /></a>
                        <div class="portlet_rc_body">
                            <a href="" class="keywords_s2">Lorem</a>
                            <a href="" class="keywords_s1">ipsum</a>
                            <a href="" class="keywords_s4">dolor</a>
                            <a href="" class="keywords_s1">amet</a>
                            <a href="" class="keywords_s3">consectetuer</a>
                            <a href="" class="keywords_s1">Nullam</a>
                            <a href="" class="keywords_s2">luctus</a>
                            <a href="" class="keywords_s4">fringilla</a>
                            <a href="" class="keywords_s3">adipiscing</a>
                        </div>
                    </div>
                    
                    <div class="portlet_rc">
                        <a href="" title="schlie&szlig;en" class="btn_head_rc"><img src="{$basic.tpl_path}/img/btn_close_rc.gif" alt="close" /></a>
                        <h2>Kategorien</h2>
                        
                        <div class="clear"> </div>
                        
                        <a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}/img/btn_edit_rc.gif" alt="close" /></a>
                        <div class="portlet_rc_body">
                        
                        <img src="{$basic.tpl_path}/img/dummy_kategorien.jpg" alt="Dummy - hier die bestehende Architektur einsetzen bitte"/>
                        </div>
                    </div>   
                     
                </div>
                
            </div> <!-- Ende right_column -->
            
            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}