<div class="{literal}${baseClass}{/literal}">
	<div class="innerWidgetArea">
		<div class="widget_head">
			<h3 class="pop_widget_h3">___TAG_EDIT_FORM_TITLE___</h3>
		</div>
		<div class="widget_body" data-dojo-attach-point="widgetBodyNode">
			<div class="tab_navigation">
				<a href="edit_tab" class="pop_tab_active">___COMMON_EDIT___</a>
                <a href="combine_tab" class="pop_tab">___BUZZWORDS_COMBINE_BUTTON___</a>
                <a href="attach_tab" class="pop_tab" data-dojo-attach-point="attachActivatorNode">___COMMON_ATTACH_BUTTON___</a>

                <div class="clear"> </div>
            </div>
              
        	<div id="popup_tabcontent" class="popup_tabcontent">
				<div id="edit_tab" class="tab">
					<div id="content_row_two_max">
						<div class="tree float-left"></div>
						
						<input id="tag_sort_abc" class="popup_button float-right" data-dojo-attach-event="onclick:onClickSortABC" type="button" value="___TAG_SORT_ABC___">
						
						<div class="clear"></div>
					</div>
				</div>
				
				<div id="combine_tab" class="tab hidden">
					<div id="content_row_one">
						<div class="input_row">
							<select id="tag_merge_one" class="size_200" size="1" data-dojo-attach-point="mergeSelectNodeOne" data-dojo-attach-event="onchange:onChangeSelectNodeOne"></select>
							<select id="tag_merge_two" class="size_200" size="1" data-dojo-attach-point="mergeSelectNodeTwo" data-dojo-attach-event="onchange:onChangeSelectNodeTwo"></select>
							
							<input id="tag_merge" class="popup_button submit" data-dojo-attach-event="onclick:onClickCombine" value="___TAG_COMBINE_BUTTON___" />
						</div>
					</div>
				</div>
				
				<div id="attach_tab" class="tab hidden">
					<div class="hidden" data-dojo-attach-point="attachTranslationNode">___COMMON_ATTACH_BUTTON___</div>
					<div id="content_row_one" data-dojo-attach-point="listAttachNode"></div>
					
					<div id="content_row_two_max">
						<div class="open_close_head">
	                        <strong>___COMMON_ITEM_ATTACH___</strong> 
	                        (<span class="text_important" data-dojo-attach-point="listAttachTitleNode"></span>)
	                        
	                        {*
	                        	TODO: CS 8.0.1 - hopefully someone would't see this in CS 10
	                        	<a href="" class="row_open_close" title="Ansicht maximieren"><img src="{$basic.tpl_path}img/pop_max_btn.gif" alt="maximieren" /></a>
	                        *}
	                        
	                        <div class="clear"> </div>  
	                    </div>
						
	                    <div id="content_expand_wrapper">
		                    <div id="crt_content">
		                        <div id="crt_col_left">
		                            <div class="crt_row_area"></div>
		                        </div>
		                        
		                        <div id="crt_col_right">
		                            <div class="pop_item_navigation">
		                                <a id="first" href="#"><img src="{$basic.tpl_path}img/btn_ar_start2.gif" alt="Start" /></a>
		                                <a id="prev" href="#"><img src="{$basic.tpl_path}img/btn_ar_left2.gif" alt="zur&uuml;ck" /></a>
		                                <span>___COMMON_PAGE___ <span id="pop_item_current_page" class="pop_item_current_page"></span>/<span id="pop_item_pages" class="pop_item_pages"></span>
		                                <a id="next" href="#"><img src="{$basic.tpl_path}img/btn_ar_right2.gif" alt="weiter" /></a>
		                                <a id="last" href="#"><img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="Ende" /></a>
		                            </div>
		
		                            <div class="pop_item_content">
		                                <input name="netnavigation_search_restriction" type="text" value="___HOME_SEARCH_SHORT_TO___" class="size_170" />
		                                <br/>
		                                <span class="sitenote">___SEARCH_RUBRIC_RESTRICTION___</span><br/>
		                                <select name="netnavigation_rubric_restriction" size="1" class="size_170_select"></select>
		                                <br/>
		                                
		                                {if $own.with_activating}
			                                <span class="sitenote">___COMMON_SHOW_ACTIVATING_ENTRIES___</span><br/>
			                                <select name="netnavigation_type_restriction" size="1" class="size_170_select">
			                                    <option value="1">___COMMON_ALL_ENTRIES___</option>
			                                    <option value="-2" disabled="disabled">------------------------------</option>
			                                    <option value="2" selected="selected">___COMMON_SHOW_ONLY_ACTIVATED_ENTRIES___</option>
			                                </select>
			                                <br/>
		                                {/if}
		
		                                <input name="netnavigation_linked_restriction" type="checkbox" value="true" /> <span class="sitenote">___SEARCH_LINKED_ENTRIES_ONLY___</span>
		                                <br/>
		                                <input name="netnavigation_submit_restrictions" type="submit" value="___COMMON_SEARCH_OVERLAY_RESTRICTION_OPTIONS___" />
		                            </div>
		                        </div>
		                        
		                        <div class="clear"> </div>  
		                    </div>
	                    </div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>