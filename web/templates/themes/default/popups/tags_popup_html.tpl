<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">
			<div id="popup_inner"{if $popup.overflow} class="scrollPopup"{/if}>


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___TAG_EDIT_FORM_TITLE___</h2>
					<div class="clear"> </div>
				</div>

				<div id="popup_content_wrapper">
					<div id="profile_content_row_three">

						<div class="tab_navigation">
							<a href="edit_tab" class="pop_tab_active">___COMMON_EDIT___</a>
		                    <a href="combine_tab" class="pop_tab">___BUZZWORDS_COMBINE_BUTTON___</a>
		                    <a href="attach_tab" class="pop_tab list_activator">___COMMON_ATTACH_BUTTON___</a>

		                    <div class="clear"> </div>
		                </div>

						<div id="popup_tabcontent">
							<div id="edit_tab" class="tab">
								<div id="content_row_two_max">
									<div class="tree float-left"></div>
									<input id="tag_sort_abc" class="popup_button float-right submit" type="button" name="form_data[sort_abc]" data-custom="part: 'sort_abc'" value="___TAG_SORT_ABC___">

									<div class="clear"></div>
								</div>
							</div>

							<div id="combine_tab" class="tab hidden">
								<div id="content_row_one">
									<div class="input_row">
										{function name=build_tag_tree_merge level=0}
											{foreach $tags as $tag}
												<option value="{$tag.item_id}">{$tag.title}</option>

												{if $tag.children|count > 0}				{* recursive call *}
												{build_tag_tree_merge tags=$tag.children level=level+1 }
											{/if}
											{/foreach}
										{/function}
										<select id="tag_merge_one" class="size_200" size="1">
											{build_tag_tree_merge tags=$popup.room_tags}
										</select>

										{function name=build_tag_tree_merge2 level=0}
											{foreach $tags as $tag}
												<option{if $tag.item_id == $popup.room_tags[0].item_id} disabled="disabled"{/if} value="{$tag.item_id}">{$tag.title}</option>

												{if $tag.children|count > 0}				{* recursive call *}
												{build_tag_tree_merge2 tags=$tag.children level=level+1 }
											{/if}
											{/foreach}
										{/function}
										<select id="tag_merge_two" class="size_200" size="1">
											{build_tag_tree_merge2 tags=$popup.room_tags}
										</select>

										<input id="tag_merge" class="popup_button submit" data-custom="part: 'merge'" type="button" name="form_data[tag_merge]" value="___TAG_COMBINE_BUTTON___" />
									</div>
								</div>

								{*
								<div id="content_row_two" class="overflow_auto">
									{function name=build_tag_tree_list level=0}
										{foreach $tags as $tag}
											<li class="ui-state-default popup_buzzword_item popup_tag_item">{$tag.title}</li>

											{if $tag.children|count > 0}				{* recursive call *}{*
											{build_tag_tree_list tags=$tag.children level=level+1 }
										{/if}
										{/foreach}
									{/function}

									<ul class="popup_buzzword_list popup_tag_list">
										{build_tag_tree_list tags=$popup.room_tags}
										<div class="clear"></div>
									</ul>
								</div>
								*}
							</div>

							<div id="attach_tab" class="tab hidden">
								<div id="content_row_one">
									{function name=build_tag_tree level=0}
										{foreach $tags as $tag}
											<div class="input_row">
												<label for="{$tag.item_id}">{$tag.title}</label>
												<input class="popup_button tag_attach" type="button" name="form_data[{$tag.item_id}]" id="{$tag.item_id}" value="___COMMON_ATTACH_BUTTON___">
											</div>

											{if $tag.children|count > 0}				{* recursive call *}
											{build_tag_tree tags=$tag.children level=level+1 }
										{/if}
										{/foreach}
									{/function}

									{build_tag_tree tags=$popup.room_tags}
								</div>

								<div id="content_row_two_max">
									<div class="open_close_head">
				                        <strong>___COMMON_ITEM_ATTACH___</strong>
				                        (<span class="text_important">&bdquo;{$popup.room_tags[0].title}&rdquo;</span>)

				                        {*
				                        	TODO: CS 8.0.1 - hopefully someone would't see this in CS 10
				                        	<a href="" class="row_open_close" title="Ansicht maximieren"><img src="{$basic.tpl_path}img/pop_max_btn.gif" alt="maximieren" /></a>
				                        *}

				                        <div class="clear"> </div>
				                    </div>

				                    <div id="content_expand_wrapper">
					                    <div id="crt_content">
					                        <div id="crt_col_left">
					                            <div id="crt_row_area"></div>
					                        </div>

					                        <div id="crt_col_right">
					                            <div class="pop_item_navigation">
					                                <a id="first" href="#"><img src="{$basic.tpl_path}img/btn_ar_start2.gif" alt="Start" /></a>
					                                <a id="prev" href="#"><img src="{$basic.tpl_path}img/btn_ar_left2.gif" alt="zur&uuml;ck" /></a>
					                                <span>___COMMON_PAGE___ <span id="pop_item_current_page"></span>/<span id="pop_item_pages"></span></span>
					                                <a id="next" href="#"><img src="{$basic.tpl_path}img/btn_ar_right2.gif" alt="weiter" /></a>
					                                <a id="last" href="#"><img src="{$basic.tpl_path}img/btn_ar_end2.gif" alt="Ende" /></a>
					                            </div>

					                            <div class="pop_item_content">
					                                <input name="netnavigation_search_restriction" type="text" value="___HOME_SEARCH_SHORT_TO___" class="size_170" />
					                                <br/>
					                                <span class="sitenote">___SEARCH_RUBRIC_RESTRICTION___</span><br/>
					                                <select name="netnavigation_rubric_restriction" size="1" class="size_170_select"></select>
					                                <br/>

					                                {if $popup.config.with_activating}
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
		</div>
	</div>
</div>