<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup">

			<div id="popup_head">
				<h2>Neue Diskussion erstellen</h2>
				<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/pop_close_btn.gif" alt="___COMMON_CLOSE___" /></a>

				<div class="clear"> </div>
			</div>

			<div id="popup_content">

				<div id="content_row_three">
					<div class="input_row">
						<span class="input_label">Titel</span> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_200 mandatory" />
						
						{if $popup.edit == false}
							<span class="input_label">Art der Diskussion</span>
							<input type="radio" name="form_data[discussion_type]" value="1" checked="checked">___DISCUSSION_SIMPLE___
							<input type="radio" name="form_data[discussion_type]" value="2">___DISCUSSION_THREADED___
						{/if}
					</div>
					
					{if $popup.edit == false}
						<div id="pop_editor">
							<h2 id="pop_editor_head">Initialbeitrag der Diskussion</h2
							<span class="input_label">Betreff</span>
							<input type="text" class="size_200 mandatory" name="form_data[subject]" value="Initialbeitrag"/>

							<input type="hidden" value="" name="iid"/>
							<input type="hidden" value="{$detail.item_id}" name="discussion_id"/>
							<input type="hidden" value="1" name="ref_position"/>
							<div class="editor_content">
								<div id="popup_ckeditor"></div>
								<input type="hidden" id="popup_ckeditor_content" name="form_data[description]" value=""/>
							</div>
						</div>
					{/if}

					<div class="tab_navigation">
						{if $popup.edit == false}<a href="" class="pop_tab_active">Dateien anh&auml;ngen</a>{/if}
						<a href="" class="pop_tab{if $popup.edit == true}_active{/if}">Zugriffsrechte</a>
						{if isset($popup.buzzwords)}<a href="" class="pop_tab">Schlagwörter</a>{/if}
						{if isset($popup.tags)}<a href="" class="pop_tab">Kategorien</a>{/if}

						<div class="clear"> </div>
					</div>

					<div id="popup_tabcontent">
						{if $popup.edit == false}
							<div class="settings_area">

								<div class="sa_col_left">
									<div id="file_finished"></div>
									<input id="uploadify" name="uploadify" type="file" />

									<div>
										<a id="uploadify_doUpload">
											<img src="{$basic.tpl_path}img/uploadify/button_upload_{$environment.lang}.png" />
										</a>
										<a id="uploadify_clearQuery">
											<img src="{$basic.tpl_path}img/uploadify/button_abort_{$environment.lang}.png" />
										</a>
									</div>
								</div>

								<div class="sa_col_right">
									<p class="info_notice">
									<img src="{$basic.tpl_path}img/file_info_icon.gif" alt="Info"/>
									{i18n tag=MATERIAL_MAX_FILE_SIZE param1=$popup.general.max_upload_size}
									</p>
								</div>

								<div class="clear"> </div>
							</div>
						{/if}
						
						<div class="settings_area{if $popup.edit == false} hidden{/if}">
							{if $popup.config.with_activating}
								<input type="checkbox" name="form_data[private_editing]" value="1"{if $item.private_editing == true} checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
								<input type="checkbox" name="form_data[hide]" value="1"{if $item.is_not_activated} checked="checked"{/if}>___COMMON_HIDE___
								___DATES_HIDING_DAY___ <input type="text" name="form_data[dayStart]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
								___DATES_HIDING_TIME___ <input type="text" name="form_data[timeStart]" value="{if isset($item.activating_time)}{$item.activating_time}{/if}"/>

							{else}
								{if $popup.edit == false}
									<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
									<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
								{else}
									{*
									$current_user = $this->_environment->getCurrentUser();
									$creator = $this->_item->getCreatorItem();

									if ($current_user->getItemID() == $creator->getItemID() or $current_user->isModerator()) {
									$this->_form->addRadioGroup('public',$this->_translator->getMessage('RUBRIC_PUBLIC'),$this->_translator->getMessage('RUBRIC_PUBLIC_DESC'),$this->_public_array);
									} else {
									$this->_form->addHidden('public','');
									}
									*}
								{/if}
							{/if}		
						</div>
						
						{if isset($popup.buzzwords)}
							<div class="settings_area hidden">
								<div id="buzzwords_unassigned_title"><h2>nicht zugewiesen</h2></div>
								<div id="buzzwords_assigned_title"><h2>zugewiesen</h2></div>
								
								{* display all not assigned *}
								<ul id="buzzwords_unassigned" class="popup_buzzword_list">
									{foreach $popup.buzzwords as $buzzword}
										{if $popup.item_id == 'NEW' || !$buzzword.assigned}
											<li id="buzzword_{$buzzword.item_id}" class="ui-state-default popup_buzzword_item">
												{$buzzword.name}
												<span class="float-right"><img src="" alt="add"/></span>
											</li>
										{/if}
									{/foreach}
								</ul>
								
								{* display all assigned *}
								<ul id="buzzwords_assigned" class="popup_buzzword_list">
									{foreach $popup.buzzwords as $buzzword}
										{if $popup.item_id != 'NEW' && $buzzword.assigned}
											<li id="buzzword_{$buzzword.item_id}" class="ui-state-default popup_buzzword_item">
												{$buzzword.name}
												<span class="float-right"><img src="" alt="remove"/></span>
											</li>
										{/if}
									{/foreach}
								</ul>
								
								<div class="clear"></div>
							</div>
						{/if}
						
						{if isset($popup.tags)}
							<div class="settings_area hidden">
								<div id="tag_tree">
									{block name=sidebar_tagbox_treefunction}
										{* Tags Function *}
										{function name=tag_tree level=0}
											<ul>
											{foreach $nodes as $node}
												<li	id="node_{$node.item_id}"
													{if $node.children|count > 0}class="folder"{/if}>
													{if $node.match == true}<b>{$node.title}</b>
													{else}{$node.title}
													{/if}
												{if $node.children|count > 0}	{* recursive call *}
													{tag_tree nodes=$node.children level=$level+1}
												{/if}
											{/foreach}
											</ul>
										{/function}
									{/block}

									{* call function *}
									{tag_tree nodes=$popup.tags}
								</div>
							</div>
						{/if}
					</div>

				</div>

				<div id="content_row_four">
					<div id="crt_actions_area">
						<input id="popup_button_create" class="popup_button" type="button" name="" value="Diskussion anlegen" />
						<input id="popup_button_abort" class="popup_button" type="button" name="" value="abbrechen" /> 
					</div>
				</div>

			</div>

		</div>
	</div>
</div>