<div class="{literal}${baseClass}{/literal} widget_full">

	<div id="e-portfolio" data-dojo-attach-point="portfolioNode">
        
        <div id="ep_left_col">
            <div id="ep_title">
            	{*
                <div id="ep_title_nav">
                    <a href="" title="zur&uuml;ck"><img src="{$basic.tpl_path}img/ep_skip_left.gif" alt="zur&uuml;ck" /></a> 
                    <a href="" title="n&auml;chster"><img src="{$basic.tpl_path}img/ep_skip_right.gif" alt="n&auml;chster" /></a>
                </div>
                *}
                <div class="float-right" data-dojo-attach-point="portfolioEditDivNode">
                	<a href="" class="ep_edit_head" data-dojo-attach-point="editPortfolioNode"><img src="{$basic.tpl_path}img/ep_icon_editdarkgrey.gif" alt="" /></a>
                </div>
                <div id="ep_tides">
                    <strong>{literal}${title}{/literal}</strong>
                    <p data-dojo-attach-point="descriptionNode"></p>
                </div>
                
                <div class="clear"></div>
            </div>
            
            <div data-dojo-attach-point="portfolioEditRowNode">
            	{* add row *}
            	<div id="epRowAdd" class="ep_vert_col_cell" data-dojo-attach-point="lastVerticalTag">
            		<a class="ep_vert_edit">
            			<img src="{$basic.tpl_path}img/ep_vert_edit.jpg" alt=""/>
            		</a>
            		
	                <div class="ep_vert_col_title">
	                    <a href="" class="tagEdit" data-custom="tagId: 'NEW', position: 'row', module: 'tagPortfolio'"><strong>+</strong></a>
	                </div>
	                
	                <div class="clear"></div>
	            </div>
            </div>
            
        </div>
        
        <div>
        	{* add column *}
	    	<div id="epColumnAdd" class="float-right" data-dojo-attach-point="portfolioEditColumnNode">
	            <a href=""><img src="{$basic.tpl_path}img/ep_hor_edit.jpg" alt="" /></a>
	            <a class="ep_edit_head tagEdit" data-custom="tagId: 'NEW', position: 'column', module: 'tagPortfolio'" href=""><strong>+</strong></a>
	            <strong>&nbsp;</strong>
	        </div>
        </div>
        
        <div id="ep_table">
            <table cellspacing="0" cellpadding="0" border="0" class="float-left" data-dojo-attach-point="tableNode">
            </table>
        </div>
        
        <div class="clear"></div>
    </div>
</div>