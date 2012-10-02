{if !$popup.overflow}
	<div class="tab {if $item.edit_type != 'netnavigation'}hidden{/if}" id="netnavigation_tab">
		<div class="settings_area">
	
	{*			<div id="popup_netnavigation_outer_bottom" class="popup_netnavigation_outer">
					<a id="popup_netnavigation_attach_new" href="#" title="___COMMON_ITEM_ATTACH___">
						<span id="attach_show" class="hidden">___COMMON_ITEM_ATTACH_SHOW___</span>
						<span id="attach_hide">___COMMON_ITEM_ATTACH_HIDE___</span>
					</a>
				</div>
	*}
	
				<div id="popup_netnavigation">
					<div id="content_row_two_max">
	                    <div id="crt_content">
	                        <div id="crt_col_left">
	                            <div id="crt_row_area">
	                            </div>
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
	
				<div id="netnavigation_list">
					<ul class="netnavigation">
						{foreach $popup.netnavigation.items as $entry}
							<li id="item_{$entry.linked_iid}">
								<div class="netnavigation">
									<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$entry.module}&fct=detail&iid={$entry.linked_iid}" title="{$entry.title}">
										<img src="{$basic.tpl_path}img/netnavigation/{$entry.img}" title="{$entry.title}"/>
									</a>
									<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$entry.module}&fct=detail&iid={$entry.linked_iid}" title="{$entry.title}">
										{$entry.link_text}
									</a>
								</div>
							</li>
						{/foreach}
					</ul>
				</div>
	
			<div class="clear"></div>
		</div>
	</div>
{/if}