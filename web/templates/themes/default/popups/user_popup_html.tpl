<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_USER___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						<div class="input_row">
							<div class="input_label_100">___COMMON_TITLE___:</div>
							<input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_80" />
						</div>

						<div class="input_row">
							<div class="input_label_100">___USER_BIRTHDAY___:</div>
							<input type="text" value="{if isset($item.birthday)}{$item.birthday}{/if}" name="form_data[birthday]" class="size_80" />
						</div>

						<div class="input_row">
							<div class="input_label_100" for="data_picture">___USER_PICTURE_UPLOADFILE___:</div>

							<div class="uploader-single">
								<form method="post" action="UploadFile.php" id="myForm" enctype="multipart/form-data" >
								   <input id="data_picture" class="fileSelector"></input>
								   <div class="filePreview"></div>
								   <div class="fileList"></div>
								</form>
							</div>
							<div class="clear"></div>
						</div>

						{if !empty($item.picture)}
							<div class="input_row">
								<div class="input_container" style="margin-left:106px;">
									<img class="input_image" src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$item.picture}" alt="___USER_PICTURE_UPLOADFILE___" />
								</div>
							</div>

							<div class="input_row">
								<div class="input_container" style="margin-left:106px;">
									<input id="delete_picture" class="float-left" type="checkbox" name="form_data[delete_picture]" value="1"/>
									<label for="delete_picture" class="float-left">___USER_DEL_PIC_BUTTON___</label>
									<div class="clear"></div>
								</div>
							</div>
						{/if}
						<div class="input_row">
							<div class="input_label_100">___USER_EMAIL___:<span class="required">*</span></div>
							<input type="text" value="{if isset($item.email)}{$item.email}{/if}" name="form_data[email]" class="size_400" /><br/>
							<div class="input_container" style="margin-left:106px;">
								<input type="checkbox" name="form_data[email_visibility]" value="1" {if !$item.email_visibility}checked="checked"{/if}/> ___USER_EMAIL_VISIBILITY_VALUE___
							</div>
						</div>

						<div class="input_row" style="margin-top:40px;">
							<div class="input_label_150">___USER_TELEPHONE___:</div>
							<input type="text" value="{if isset($item.telephone)}{$item.telephone}{/if}" name="form_data[telephone]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_CELLULARPHONE___:</div>
							<input type="text" value="{if isset($item.cellularphone)}{$item.cellularphone}{/if}" name="form_data[cellularphone]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_STREET___:</div>
							<input type="text" value="{if isset($item.street)}{$item.street}{/if}" name="form_data[street]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_ZIPCODE___:</div>
							<input type="text" value="{if isset($item.zipcode)}{$item.zipcode}{/if}" name="form_data[zipcode]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_CITY___:</div>
							<input type="text" value="{if isset($item.city)}{$item.city}{/if}" name="form_data[city]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_ROOM___:</div>
							<input type="text" value="{if isset($item.room)}{$item.room}{/if}" name="form_data[room]" class="size_200" />
						</div>

						<div class="input_row" style="margin-top:40px;">
							<div class="input_label_150">___USER_ORGANISATION___:</div>
							<input type="text" value="{if isset($item.organisation)}{$item.organisation}{/if}" name="form_data[organisation]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_POSITION___:</div>
							<input type="text" value="{if isset($item.position)}{$item.position}{/if}" name="form_data[position]" class="size_200" />
						</div>
						<div class="input_row">
							<div class="input_label_150">___USER_HOMEPAGE___:</div>
							<input type="text" value="{if isset($item.homepage)}{$item.homepage}{/if}" name="form_data[homepage]" class="size_200" />
						</div>


						<div class="input_row" style="margin-top:40px;">
							<div class="input_label_150">___USER_MESSENGER_NUMBERS___:</div>
							<input type="text" value="{if isset($item.msn)}{$item.msn}{/if}" name="form_data[msn]" class="size_80" /> ___USER_MSN___
							<div class="input_container" style="margin-left:156px; margin-top:10px;">
								<input type="text" value="{if isset($item.skype)}{$item.skype}{/if}" name="form_data[skype]" class="size_80" /> ___USER_SKYPE___
							</div>
							<div class="input_container" style="margin-left:156px; margin-top:10px;">
								<input type="text" value="{if isset($item.icq)}{$item.icq}{/if}" name="form_data[icq]" class="size_80" /> ___ICQ___
							</div>
							<div class="input_container" style="margin-left:156px;  margin-top:10px;">
								<input type="text" value="{if isset($item.yahoo)}{$item.yahoo}{/if}" name="form_data[yahoo]" class="size_80" /> ___USER_YAHOO___
							</div>
						</div>

						<div class="editor_content" style="margin-top:40px;">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
					</div>


					<div id="popup_tabs">
						<div class="tab_navigation">
							{if $popup.is_owner == true}
								<a href="account_tab" class="pop_tab_active">___PROFILE_ACCOUNT_DATA___</a>
								<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_GROUPS___</a>
							{else}
								<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab_active">___COMMON_ATTACHED_GROUPS___</a>
							{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{if $popup.is_owner == true}
								<div class="tab" id="account_tab">
									<div class="settings_area">
										<span  class="input_label_230">___COMMON_LANGUAGE___<span class="required">*</span>:</span>
										<select id="language" name="form_data[language]" size="1" class="size_200" >
								            <option value="none" {if $item.language == 'none'} selected="selected" {/if} >* ___BROWSER___</option>
								            <option value="de" {if $item.language == 'de'} selected="selected" {/if}>___DE___</option>
								            <option value="en" {if $item.language == 'en'} selected="selected" {/if}>___EN___</option>
				         				</select>
										{if $environment.room_type_commnunity}
											<div class="input_row">
												<span  class="input_label_230">___VISIBLE_PROPERTY___<span class="required">*</span>:</span>
												<input type="radio" value="1" {if $item.commsy_visible  == '1' }checked="checked"{/if}" name="form_data[commsy_visible]"  />___VISIBLE_ONLY_LOGGED___
												<input type="radio" value="2" {if !$item.commsy_visible == '2'} checked="checked"{/if}" name="form_data[commsy_visible]"  />___VISIBLE_ALWAYS___
											</div>
										{/if}
										{if $environment.is_moderator}
											{if $environment.room_type_commnunity}
												<div class="input_row">
													<span  class="input_label_230">___USER_MAIL_PUBLISH_MATERIAL___<span class="required">*</span>:</span>
													<input type="radio" value="yes" {if $item.want_mail_publish_material  == 'yes' }checked="checked"{/if}" name="form_data[want_mail_publish_material]"  />___COMMON_YES___
													<input type="radio" value="no" {if !$item.want_mail_publish_material == 'no'} checked="checked"{/if}" name="form_data[want_mail_publish_material]"  />___COMMON_NO___
												</div>
											{/if}
											<div class="input_row">
												<div class="input_label_230">___USER_MAIL_GET_ACCOUNT___<span class="required">*</span>:</div>
												<input type="radio" value="yes" {if $item.want_mail_get_account =='yes'}checked="checked"{/if}" name="form_data[want_mail_get_account]"  />___COMMON_YES___
												<input type="radio" value="no" {if $item.want_mail_get_account == 'no'}checked="checked"{/if}" name="form_data[want_mail_get_account]" />___COMMON_NO___
											</div>
											<div class="clear"> </div>
											<div class="input_row">
												<span  class="input_label_230">___USER_MAIL_ROOM___<span class="required">*</span>:</span>
												<input type="radio" value="yes" {if $item.want_mail_open_room  == 'yes' }checked="checked"{/if}" name="form_data[want_mail_open_room]"  />___COMMON_YES___
												<input type="radio" value="no" {if $item.want_mail_open_room == 'no'} checked="checked"{/if}" name="form_data[want_mail_open_room]"  />___COMMON_NO___
											</div>
											<div class="input_row">
												<span  class="input_label_230">___DELETE_ENTRY_WANT_MAIL___<span class="required">*</span>:</BR></BR></span>
												<input type="radio" value="yes" {if $item.mail_delete_entry  == 'yes' }checked="checked"{/if}" name="form_data[mail_delete_entry]"  />___COMMON_YES___
												<input type="radio" value="no" {if $item.mail_delete_entry == 'no'} checked="checked"{/if}" name="form_data[mail_delete_entry]"  />___COMMON_NO___
											</div>
										{/if}
										<div class="clear"> </div>
									</div>
								</div>
							{/if}


							<div id="netnavigation_tab" class="tab{if $popup.is_owner == true} hidden{/if}">
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
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
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