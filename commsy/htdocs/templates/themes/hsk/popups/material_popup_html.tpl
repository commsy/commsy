{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">

			<div id="popup_inner"{if $popup.overflow} class="scrollPopup"{/if}>


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
	{if $item.edit_type == 'netnavigation'}
						<h2>___COMMON_NETNAVIGATION_ENTRIES___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/netnavigation_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>
	{elseif $item.edit_type == 'buzzwords'}
						<h2>___COMMON_BUZZWORDS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_BUZZWORD_ATTACH___{else}___COMMON_BUZZWORD_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/buzzwords_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{elseif $item.edit_type == 'tags'}
						<h2>___COMMON_TAGS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_TAG_ATTACH___{else}___COMMON_TAG_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/tags_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{else}
					<h2>___COMMON_MATERIAL___</h2>
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
							<div class="input_label_100">___COMMON_TITLE___<span class="required">*</span>:</div> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
						</div>


						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="buzzwords_tab" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="tags_tab" class="pop_tab">___COMMON_TAGS___</a>{/if}
							{if $item.with_workflow == true}<a href="workflow_tab" class="pop_tab">___COMMON_WORKFLOW___</a>{/if}
							<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{include file="popups/include/files_tab_include_html.tpl"}

							{include file="popups/include/rights_tab_include_html.tpl"}

							{include file="popups/include/buzzwords_tab_include_html.tpl"}

							{include file="popups/include/tags_tab_include_html.tpl"}

							{if $item.with_workflow == true}
								<div class="tab hidden" id="workflow_tab">
									<div class="settings_area">
										{if $item.with_workflow_traffic_light == true}
											<fieldset class="fieldset">
												<legend>___COMMON_WORKFLOW_TRAFFIC_LIGHT___</legend>

												<div class="input_row_100">
													<input id="workflow_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="3_none"{if $item.workflow_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="0_green"{if $item.workflow_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;" src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="1_yellow"{if $item.workflow_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="2_red" {if $item.workflow_traffic_light == '2_red'} checked="checked"{/if}/>
				         							<label for="workflow_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
												<div class="input_row"><hr class="float-left hr_400" /><div class="clear"></div></div>
											</fieldset>
										{/if}


										{if $item.with_workflow_resubmission == true}
											<fieldset>
												<legend>___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE___</legend>

												<div class="input_row_100">
													<label for="workflow_resubmission">___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE___:</label>
													<input id="workflow_resubmission" type="checkbox" style="vertical-align: bottom;" name="form_data[workflow_resubmission]"{if $item.workflow_resubmission == true} checked="checked"{/if} />
													<input id="workflow_resubmission_date" class="datepicker" type="text" name="form_data[workflow_resubmission_date]" value="{show var=$item.workflow_resubmission_date}" />
												</div>


												<div class="input_row">
													___COMMON_WORKFLOW_RESUBMISSION_WHO___:
												</div>

												<div class="input_row">
													<input id="workflow_resubmission_who" class="float-left" type="radio" name="form_data[workflow_resubmission_who]" value="creator"{if $item.workflow_resubmission_who == 'creator'} checked="checked"{/if} />
													<label for="workflow_resubmission_who" class="auto_width">
														___COMMON_WORKFLOW_RESUBMISSION_CREATOR___ (<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.workflow_creator_id}">{$item.workflow_creator_fullname}</a>)
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row">
													<input id="workflow_resubmission_who" class="float-left" type="radio" name="form_data[workflow_resubmission_who]" value="modifier"{if $item.workflow_resubmission_who == 'modifier'} checked="checked"{/if} />
													<label for="workflow_resubmission_who" class="auto_width">
													___COMMON_WORKFLOW_RESUBMISSION_MODIFIER___
													{if !empty($item.workflow_modifier)}
														(
														{foreach $item.workflow_modifier as $modifier}
															{if isset($modifier.id)}
																<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$modifier.id}">{$modifier.name}</a>
															{else}
																{$modifier.name}
															{/if}

															{if !$modifier@last}, {/if}
														{/foreach}
														)
													{/if}
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row" style="margin-bottom:20px;">
													<label for="workflow_resubmission_who_additional">___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL___ (___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR___)</label>
													<input id="workflow_resubmission_who_additional" type="text" name="form_data[workflow_resubmission_who_additional]" value="{show var=$item.workflow_resubmission_who_additional}" />
												</div>

												<div class="input_row">
													___COMMON_WORKFLOW_RESUBMISSION_TRAFFIC_LIGHT___:
												</div>

												<div class="input_row_100">
													<input id="workflow_resubmission_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="3_none"{if $item.workflow_resubmission_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_resubmission_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_resubmission_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="0_green"{if $item.workflow_resubmission_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_resubmission_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_resubmission_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="1_yellow"{if $item.workflow_resubmission_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_resubmission_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_resubmission_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="2_red"{if $item.workflow_resubmission_traffic_light == '2_red'} checked="checked"{/if} />
				         							<label for="workflow_resubmission_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
												<div class="input_row"><hr class="float-left hr_400" /><div class="clear"></div></div>
											</fieldset>
										{/if}

										{if $item.with_workflow_validity == true}
											<fieldset>
												<legend>___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_VALUE___</legend>

												<div class="input_row_150">
													<label for="workflow_validity">___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_VALUE___:</label>
													<input id="workflow_validity" style="vertical-align:bottom;" type="checkbox" name="form_data[workflow_validity]"{if $item.workflow_validity_date == true} checked="checked"{/if} />
													<input id="workflow_validity_date" class="datepicker" type="text" name="form_data[workflow_validity_date]" value="{show var=$item.workflow_validity_date}" />
												</div>

												<div class="input_row">
													___COMMON_WORKFLOW_VALIDITY_WHO___:
												</div>

												<div class="input_row_150">
													<input id="workflow_validity_who" class="float-left" type="radio" name="form_data[workflow_validity_who]" value="creator"{if $item.workflow_validity_who == 'creator'} checked="checked"{/if} />
													<label for="workflow_validity_who" class="auto_width">
														___COMMON_WORKFLOW_RESUBMISSION_CREATOR___ (<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.workflow_creator_id}">{$item.workflow_creator_fullname}</a>)
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row">
													<input id="workflow_validity_who" class="float-left" type="radio" name="form_data[workflow_validity_who]" value="modifier"{if $item.workflow_validity_who == 'modifier'} checked="checked"{/if} />
													<label for="workflow_validity_who" class="auto_width">
													___COMMON_WORKFLOW_RESUBMISSION_MODIFIER___
													{if !empty($item.workflow_modifier)}
														(
														{foreach $item.workflow_modifier as $modifier}
															{if isset($modifier.id)}
																<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$modifier.id}">{$modifier.name}</a>
															{else}
																{$modifier.name}
															{/if}

															{if !$modifier@last}, {/if}
														{/foreach}
														)
													{/if}
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row" style="margin-bottom:20px">
													<label for="workflow_validity_who_additional">___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL___ (___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR___)</label>
													<input id="workflow_validity_who_additional" type="text" name="form_data[workflow_validity_who_additional]" value="{show var=$item.workflow_validity_who_additional}" />
												</div>


												<div class="input_row">
													___COMMON_WORKFLOW_VALIDITY_TRAFFIC_LIGHT___:
												</div>

												<div class="input_row_100">
													<input id="workflow_validity_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="3_none"{if $item.workflow_validity_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_validity_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_validity_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="0_green"{if $item.workflow_validity_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_validity_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_validity_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="1_yellow"{if $item.workflow_validity_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_validity_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_validity_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="2_red"{if $item.workflow_validity_traffic_light == '2_red'} checked="checked"{/if} />
				         							<label for="workflow_validity_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
											</fieldset>
										{/if}
									</div>
								</div>
							{/if}

							{include file="popups/include/netnavigation_tab_include_html.tpl"}

						</div>

						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								{if $popup.edit}<input id="popup_button_new_version" class="popup_button submit" data-custom="part: 'version'" type="button" name="" value="___MATERIAL_VERSION_BUTTON___" />{/if}
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>



					</div>
				</div>


				{/if}

			</div>


			<div class="clear"></div>
		</div>
	</div>
</div>