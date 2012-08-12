<div class="{literal}${baseClass}{/literal} widget_full">

	<div id="e-portfolio">
        
        <div id="ep_left_col">
            <div id="ep_title">
                <div id="ep_title_nav">
                    <a href="" title="zur&uuml;ck"><img src="{$basic.tpl_path}img/ep_skip_left.gif" alt="zur&uuml;ck" /></a> 
                    <a href="" title="n&auml;chster"><img src="{$basic.tpl_path}img/ep_skip_right.gif" alt="n&auml;chster" /></a>
                </div>
                <div class="float-right">
                	<a href="" class="ep_edit_head" data-dojo-attach-point="editPortfolioNode"><img src="{$basic.tpl_path}img/ep_icon_editdarkgrey.gif" alt="" /></a>
                </div>
                <div id="ep_tides">
                    <strong>{literal}${title}{/literal}</strong>
                    <p data-dojo-attach-point="descriptionNode"></p>
                </div>
                
                <div class="clear"></div>
            </div>
            
            <div data-dojo-attach-point="verticalTags">
            	{*
            		<div class="ep_vert_col_cell">
		                <a href="" class="ep_vert_edit"><img src="{$basic.tpl_path}img/ep_vert_edit.jpg" alt="" /></a>
		                
		                <div class="ep_vert_col_title">
		                    <a href=""><img src="{$basic.tpl_path}img/ep_icon_editdarkgrey.gif" alt="" /></a>
		                    <strong>Beschriftung</strong>
		                </div>
		                
		                <div class="clear"></div>
		            </div>
            	*}
            	
            	{* add row *}
            	<div class="ep_vert_col_cell">
	                <div class="ep_vert_col_title">
	                    <a href=""><strong>+</strong></a>
	                </div>
	                
	                <div class="clear"></div>
	            </div>
            </div>
            
        </div>
        
        <div id="ep_table">
            <table cellspacing="0" cellpadding="0" border="0">
                <tr data-dojo-attach-point="horizontalTags">
                	{*
                	<th>
                        <a href=""><img src="{$basic.tpl_path}img/ep_hor_edit.jpg" alt="" /></a>
                        <a class="ep_edit_head" href=""><img src="{$basic.tpl_path}img/ep_icon_editdarkgrey.gif" alt="" /></a>
                        <strong>Beschriftung</strong>
                    </th>
                	*}
                </tr>
                <tr>
                {*
                    <td>
                        <div class="ep_cell_content"> <!-- immer die neuesten/letzten 3 Eintraege anzeigen -->
                        <a href="">
                            <span>Lorem ipsum dolor nato ...</span> <!-- Text bitte so abschneiden, dass er in eine Zeile passt -->
                            <span>Aenean massa cum sociis ...</span>
                            <span>Phasellus viverra nulla ut ...</span>
                        </a>
                        </div>
                        <div class="ep_cell_actions">
                            <p class="ep_item_count">12</p>
                            <p class="ep_item_comment">12</p>
                            <a href=""><img src="{$basic.tpl_path}img/ep_icon_editgrey.gif" alt="" /></a>
                            
                            <div class="clear"></div>
                        </div>
                    </td>
                    *}
                </tr>
            </table>
        </div>
        
        {* add column *}
        {*
                	<th>
                        <a href=""><img src="{$basic.tpl_path}img/ep_hor_edit.jpg" alt="" /></a>
                        <a class="ep_edit_head" href=""><strong>+</strong></a>
                        <strong>&nbsp;</strong>
                    </th>
                    *}
        
        <div class="clear"></div>
    </div>
    
    

{*
	<div class="innerWidgetArea">
		<div class="widget_head">
			<h3 class="pop_widget_h3 float-left">___COMMON_RSS_TICKER___</h3>
			<a id="edit_buzzwords" class="btn_head_rc2 edit" href="#" data-dojo-attach-point="rssEditNode" title="Bearbeiten">
				<img src="{$basic.tpl_path}img/templates/themes/default/img/btn_edit_rc.gif" alt="Bearbeiten">
			</a>
			<div class="clear"></div>
		</div>
		
		<div class="widget_body" data-dojo-attach-point="widgetBodyNode">
			<div data-dojo-attach-point="rssContentNode"></div>
		</div>
	</div>
	
	*}
</div>