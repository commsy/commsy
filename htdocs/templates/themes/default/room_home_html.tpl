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
	            	<a href="" title="___HOME_SMARTY_{$rubric@key|upper}_ACTION_NEW___">
	            		<img src="{$basic.tpl_path}img/btn_ci_add.gif" alt="___HOME_SMARTY_ACTION_NEW___" />
	            	</a>
                    <a href="" class="open_close" title="___HOME_SMARTY_ACTION_CLOSE___">
                    	<img src="{$basic.tpl_path}img/btn_ci_close.gif" alt="___HOME_SMARTY_ACTION_CLOSE___" />
                    </a>
                </div>
                <h2>
                	___COMMON_{$rubric@key|upper}_INDEX___
                	<span>
                		({$rubric.message_tag})
                	</span>
                </h2>

                <div class="clear"> </div>

                <div class="list_wrap">

	                {foreach $rubric.items as $item}
	                	<div class="{if $item@iteration is odd}row_odd{else}row_even{/if}">
	                    	<div class="column_400">
	                        	<p>
								{if $item.noticed != ''}
									<a href="" class="new_item"><img title="{$item.noticed}" class="new_item" src="{$basic.tpl_path}img/flag_neu.gif" alt="*" /></a>
         						{/if}
	                            	<a href="">{$item.column_1}</a>
	                            </p>
	                        </div>
	                        <div class="seperator">
	                        	<div class="column_140">
	                            	<p>{$item.column_2}</p>
	                            </div>
		                        <div class="column_194">
		                        	<p>
		                            	<a href="">{$item.column_3}</a>
		                            </p>
		                        </div>
		                    </div>
		                    <div class="clear"> </div>
	                    </div>
	                {/foreach}

                </div>
            </div> <!-- Ende content_item -->
		{/foreach}


                        {*






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