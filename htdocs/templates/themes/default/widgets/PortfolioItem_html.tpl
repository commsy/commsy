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
            
            <div>
            	{* add row *}
            	<div class="ep_vert_col_cell" data-dojo-attach-point="lastVerticalTag">
	                <div class="ep_vert_col_title">
	                    <a href="" class="tagEdit" data-custom="tagId: 'NEW', position: 'row', module: 'tagPortfolio'"><strong>+</strong></a>
	                </div>
	                
	                <div class="clear"></div>
	            </div>
            </div>
            
        </div>
        
        <div id="ep_table">
            <table cellspacing="0" cellpadding="0" border="0" class="float-left" data-dojo-attach-point="tableNode">
            </table>
            
            {* add column *}
	    	<div id="epColumnAdd" class="float-left">
	            <a href=""><img src="{$basic.tpl_path}img/ep_hor_edit.jpg" alt="" /></a>
	            <a class="ep_edit_head tagEdit" data-custom="tagId: 'NEW', position: 'column', module: 'tagPortfolio'" href=""><strong>+</strong></a>
	            <strong>&nbsp;</strong>
	        </div>
	        
	        <div class="clear"></div>
        </div>
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