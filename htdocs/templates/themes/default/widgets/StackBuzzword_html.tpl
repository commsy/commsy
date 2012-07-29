<div class="{literal}${baseClass}{/literal}">
	<div class="innerWidgetArea">
		<div class="widget_head">
			<h3 class="pop_widget_h3">___BUZZWORDS_EDIT_HEADER___</h3>
		</div>
		<div class="widget_body" data-dojo-attach-point="widgetBodyNode">
			<div class="tab_navigation">
				<a href="add_tab" class="pop_tab_active">___COMMON_ADD_BUTTON___</a>
                <a href="merge_tab" class="pop_tab">___BUZZWORDS_COMBINE_BUTTON___</a>
                <a href="edit_tab" class="pop_tab" data-dojo-attach-point="editActivatorNode">___COMMON_EDIT___</a>

                <div class="clear"> </div>
            </div>
              
        	<div id="popup_tabcontent" class="popup_tabcontent">
				<div id="add_tab" class="tab">
					<div id="content_row_one">
						<div class="input_row">
							<input id="buzzword_create_name" type="text" class="size_200 mandatory" data-dojo-attach-point="addInputNode"/>
							<input id="buzzword_create" class="popup_button submit" type="button" data-dojo-attach-event="onclick:onCreateNewBuzzword" value="___BUZZWORDS_NEW_BUTTON___" />
						</div>
					</div>

					<div id="content_row_two" class="overflow_auto">
						<ul class="popup_buzzword_list" data-dojo-attach-point="addBuzzwordListNode"></ul>
					</div>
				</div>
				
				<div id="merge_tab" class="tab hidden">
					<div id="content_row_one">
						<div class="input_row">
							<select id="buzzword_merge_one" class="size_200" size="1" data-dojo-attach-point="mergeSelectOne" data-dojo-attach-event="onchange:onChangeSelectOne">
							</select>
							
							<select id="buzzword_merge_two" class="size_200" size="1" data-dojo-attach-point="mergeSelectTwo" data-dojo-attach-event="onchange:onChangeSelectTwo">
							</select>
							
							<input id="buzzword_merge" class="popup_button submit" data-dojo-attach-event="onclick:onClickMerge" type="button" value="___BUZZWORDS_COMBINE_BUTTON___" />
						</div>
					</div>

					<div id="content_row_two" class="overflow_auto">
						<ul class="popup_buzzword_list" data-dojo-attach-point="mergeBuzzwordListNode"></ul>
					</div>
				</div>
				
				<div id="edit_tab" class="tab hidden">
					<div class="hidden" data-dojo-attach-point="changeTranslationNode">___BUZZWORDS_CHANGE_BUTTON___</div>
					<div class="hidden" data-dojo-attach-point="deleteTranslationNode">___COMMON_DELETE_BUTTON___</div>
					<div class="hidden" data-dojo-attach-point="attachTranslationNode">___COMMON_ATTACH_BUTTON___</div>
					
					<div id="content_row_one" data-dojo-attach-point="editInputNode"></div>
					
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
		                                <span>___COMMON_PAGE___ <span id="pop_item_current_page" class="pop_item_current_page"></span>/<span id="pop_item_pages" class="pop_item_pages"></span></span>
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