{extends file="room_html.tpl"}

{block name=room_site_actions}
	<a href="" title="Ansicht in Reihen"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="Reihen" /></a>
    <a href="" title="Ansicht in Portlets"><img src="{$basic.tpl_path}img/btn_portlet_view.gif" alt="Portlets" /></a>
{/block}

{block name=room_navigation_rubric_title}
	&Uuml;bersicht Projektraum (Home)
{/block}

{block name=room_main_content}
	<div id="full_width_content">
		{foreach $room.home_content as $rubric}
			<div class="content_item"> <!-- Start content_item -->
            	<div class="ci_head_actions">
	            	<a href="" title="neue Ank&uuml;ndigung erstellen"><img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="neu" /></a>
                    <a href="" class="open_close" title="schlie&szlig;en"><img src="{$basic.tpl_path}img/btn_ci_close.gif" alt="schlie&szlig;en" /></a>
                </div>
                <h2>___COMMON_{$rubric.title|upper}_INDEX___<span>(4 g&uuml;ltige von 100)</span></h2>
                
                <div class="clear"> </div>
                
                {foreach $rubric.items as $item}
                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
                    	<div class="column_430">
                        	<p>
                            	<a href="">{$item.title}</a>
                            </p>
                        </div>
                        <div class="seperator">
                        	<div class="column_120">
                            	<p>{$item.modification_date}</p>
                            </div>
	                        <div class="column_184">
	                        	<p>
	                            	<a href="">{$item.creator}</a>
	                            </p>
	                        </div>
	                    </div>
	                    <div class="clear"> </div>
                    </div>
                {/foreach}
            </div> <!-- Ende content_item -->
		{/foreach}
	
	
                        {*
                        
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neuen Termin erstellen"><img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="schlie&szlig;en"><img src="{$basic.tpl_path}img/btn_ci_close.gif" alt="schlie&szlig;en" /></a>
                            </div>
                            <h2>Termine<span>(4 heute und in der Zukunft von 20)</span></h2>
                            
                            <div class="clear"> </div>
                            
 <div class="row_odd">
                                <div class="column_430">
                                    <p>
                                    <span class="new_item"><a href="">Donec pede justo, fringilla vel, aliquet vulputate eget</a></span>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">Max Mustermann</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_even">
                                <div class="column_430">
                                    <p>
                                    <a href="">Aliquet nec vulputate eget</a>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">Dennis Mustermann</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_odd">
                                <div class="column_430">
                                    <p>
                                    <a href="">Quisque rutrum</a>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">Silke Musterfrau</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_even">
                                <div class="column_430">
                                    <p>
                                    <a href="">Nam quam nunc, blandit vel</a>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">John Doe</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                        </div> <!-- Ende content_item -->
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neues Material anlegen"><img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="schlie&szlig;en"><img src="{$basic.tpl_path}img/btn_ci_close.gif" alt="schlie&szlig;en" /></a>
                            </div>
                            <h2>Materialien<span>(4 aus den letzten 7 Tagen von 55)</span></h2>
                            
                            <div class="clear"> </div>
                            
 <div class="row_odd">
                                <div class="column_430">
                                    <p>
                                    <span class="new_item"><a href="">Cras dapibus vivamus elementum</a></span>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">John Doe</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_even">
                                <div class="column_430">
                                    <p>
                                    <span class="new_item"><a href="">Curabitur ullamcorper ultricies nisi</a></span>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">Silke Musterfrau</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_odd">
                                <div class="column_430">
                                    <p>
                                    <a href="">Duis leo sed fringilla mauris sit amet</a>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">Max Mustermann</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                            <div class="row_even">
                                <div class="column_430">
                                    <p>
                                    <a href="">Lorem ipsum dolor sit amet, consectetuer adipiscing elit</a>
                                    </p>
                                </div>
                                <div class="seperator">
                                    <div class="column_120">
                                        <p>
                                        00.00.0000
                                        </p>
                                    </div>
                                    <div class="column_184">
                                        <p>
                                        <a href="">John Doe</a>
                                        </p>
                                    </div>
                                </div>
                                <div class="clear"> </div>
                            </div>
                            
                        </div> <!-- Ende content_item -->
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neue Gruppe erstellen"><img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="&ouml;ffnen"><img src="{$basic.tpl_path}img/btn_ci_open.gif" alt="&ouml;ffnen" /></a>
                            </div>
                            <h2>Gruppen<span>(alle 10 Gruppen)</span></h2>
                            
                            <div class="clear"> </div>
                        </div> <!-- Ende content_item -->
                        
                        <div class="content_item"> <!-- Start content_item -->
                            <div class="ci_head_actions">
                                <a href="" title="neues Thema erstellen"><img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="neu" /></a>
                                <a href="" class="open_close" title="&ouml;ffnen"><img src="{$basic.tpl_path}img/btn_ci_open.gif" alt="&ouml;ffnen" /></a>
                            </div>
                            <h2>Themen<span>(alle 4 Themen)</span></h2>
                            
                            <div class="clear"> </div>
                        </div> <!-- Ende content_item -->        
                        
                        
                        *}                                                   
                        
                    </div>
{/block}