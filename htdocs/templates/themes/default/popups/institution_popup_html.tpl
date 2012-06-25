<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_INSTITUTION___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>

					<div id="popup_content">
						<div class="input_row">
							<div class="input_label_80">___COMMON_NAME___<span class="required">*</span>:</div>
							<input type="text" value="{if isset($item.name)}{$item.name}{/if}" name="form_data[name]" class="size_400" />
						</div>

						<div class="input_row">
							<div class="input_label_80" for="data_picture">___USER_PICTURE_UPLOADFILE___:</div>
							<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=rubric_popup&action=save" method="post">
								<input type="hidden" name="module" value="institution" />
								<input type="hidden" name="additional[action]" value="upload_picture" />
								<input id="upload_hidden_iid" type="hidden" name="additional[iid]" value="" />
								<input id="data_picture" size="45" type="file" class="float-left" name="form_data[picture]" accept="image/*" />
							</form>
							<div class="clear"></div>
						</div>

						{if !empty($item.picture)}
							<div class="input_row">
								<div class="input_container">
									<img class="input_image" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$item.picture}" alt="___USER_PICTURE_UPLOADFILE___" />
								</div>
							</div>

							<div class="input_row">
								<div class="input_container">
									<input id="delete_picture" class="float-left" type="checkbox" name="form_data[delete_picture]" value="1"/>
									<label for="delete_picture" class="float-left">___USER_DEL_PIC_BUTTON___</label>
									<div class="clear"></div>
								</div>
							</div>
						{/if}

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>

					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							{if $popup.is_owner == true}
							   <a href="rights_tab" class="pop_tab_active">___COMMON_RIGHTS___</a>
							   <a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							{else}
							   <a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab_active">___COMMON_ATTACHED_ENTRIES___</a>
							{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{if $popup.is_owner == true}
								<div class="tab" id="rights_tab">
									<div class="settings_area">
										<input type="radio" name="form_data[public]" value="1" {if $item.public == '1'}checked="checked"{/if}/>___RUBRIC_PUBLIC_YES___<br/>
										<input type="radio" name="form_data[public]" value="0" {if $item.public == '0'}checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
									</div>
								</div>
							{/if}
							
							<div class="tab{if $popup.is_owner == true} hidden{/if}">
								<div class="settings_area">							
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
							
							                                {if $popup.activating}
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
													<li id="item_{$entry.linked_iid}" class="netnavigation">
														<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$entry.module}&fct=detail&iid={$entry.linked_iid}" title="{$entry.title}">
															<img src="{$basic.tpl_path}img/netnavigation/{$entry.img}" title="{$entry.title}"/>
														</a>
														<a target="_self" href="commsy.php?cid={$environment.cid}&mod={$entry.module}&fct=detail&iid={$entry.linked_iid}" title="{$entry.title}">
															{$entry.link_text}
														</a>
													</li>
												{/foreach}
											</ul>
										</div>
							
									<div class="clear"></div>
								</div>
							</div>

						</div>

						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button" type="button" name="" value="{if $popup.edit == false}___COMMON_NEW_ITEM___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>



					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>