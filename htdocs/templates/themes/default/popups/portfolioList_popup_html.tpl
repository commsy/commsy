<div id="popup_wrapper">
	<div id="popup_edit_stack">
		<div id="popup_frame">

			<div id="popup_inner" class="scrollPopup">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img
						src="{$basic.tpl_path}img/popup_close.gif"
						alt="___COMMON_CLOSE___" />
					</a>
					<h2>___CS_BAR_PORTFOLIO_CELL_HEADER___</h2>
					<div class="clear"></div>
				</div>
				<div id="popup_content_wrapper">
    				<div id="popup_content">
    					
    					
    					<div id="ep_content_row_one">
                    <div class="ep_open_close_head">
                        <strong>___COMMON_ENTRIES___</strong> ({$popup.numItems} ___COMMON_NETNAVIGATION_ENTRIES___)
                        {*<a href="" class="ep_row_open_close" title="Ansicht maximieren"><img src="{$basic.tpl_path}img/pop_max_btn.gif" alt="maximieren" /></a>*}
                            
                        <div class="clear"></div>
                    </div>
                
                    <div class="ep_crt_content">
                    
                    	{foreach $popup.items as $item}
                    		 <div class="pop_{if $item@iteration is odd}row_odd{else}row_even{/if}">
                                    <div class="pop_col_330">
                                        <a href="#" class="openDetailPopup" data-custom="iid: {$item.itemId}, module: '{$item.module}', contextId: {$popup.privateRoomId}">{$item.title}</a>
                                    </div>
                                    <div class="pop_col_90">
                                        {$item.modificationDate}
                                    </div>
                                    <div class="pop_col_150">
                                        {$item.modificator}
                                    </div>
                                    <div class="clear"> </div>  
                                </div>
                    	{/foreach}
                    	
                        <div class="clear"></div>
                    </div>
                    
                </div>
                
                
                <div id="ep_content_row_two">
                    <div class="ep_open_close_head">
                        <strong>___CS_BAR_PORTFOLIO_COMMENTS_HEADER___</strong> ({$popup.numAnnotations} ___CS_BAR_PORTFOLIO_COMMENTS_EXISTS___)
                        
                        <a id="portfolioListCreateAnnotation" class="open_popup float-right" href="#" data-custom="module: 'annotation', iid: 'NEW', portfolioId: {$popup.portfolioId}, contextId: {$popup.privateRoomId}"><strong>+</strong></a>
                        {*<a href="" class="ep_row_open_close" title="Ansicht maximieren"><img src="{$basic.tpl_path}img/pop_max_btn.gif" alt="maximieren" /></a>*}
                        
                        <div class="clear"></div>  
                    </div>
                    
                    <div class="ep_crt_content">
                    
                    	{foreach $popup.annotationItems as $item}
                    		 <div class="pop_{if $item@iteration is odd}row_odd{else}row_even{/if}">
                                 <div class="pop_col_330">
                                     <a href="#" class="openDetailPopupAnnotation" data-custom="iid: {$item.itemId}, module: 'annotation', contextId: {$popup.privateRoomId}, portfolioId: {$popup.portfolioId}">{$item.title}</a>
                                 </div>
                                 <div class="pop_col_90">
                                     {$item.modificationDate}
                                 </div>
                                 <div class="pop_col_150">
                                     {$item.modificator}
                                 </div>
                                 <div class="clear"> </div>  
                             </div>
                    	{/foreach}
                    	
                        <div class="clear"></div>  
                    </div>
                </div>
    					
    				</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
