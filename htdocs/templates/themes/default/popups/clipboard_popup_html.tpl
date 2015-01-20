{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_my_area">
		<div id="popup_frame_my_area">
			<div id="popup_inner_my_area">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>
						___MYAREA_MY_COPIES___
					</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
						<div id="popup_accounts">
							<div id="content_row_two_max">
			                    <div id="crt_content">
			                        <div id="crt_col_left_full_2">

			                        	<div class="table_head">
		                            		<h3 class="pop_col_286 pop_h3">___COMMON_TITLE___</h3>
		                            		<h3 class="pop_col_90 pop_h3">___COMMON_RUBRIC___</h3>
		                            		<h3 class="pop_col_270 pop_h3">___COMMON_MODIFIED_BY___</h3>
		                            		<h3 class="pop_col_150 pop_h3">___COMMON_MODIFIED_AT___</h3>

		                            		<div class="clear"></div>
		                            	</div>

			                            <div id="crt_row_area">
			                            </div>
			                        </div>

			                        <div class="clear"> </div>

			                        <div class="pop_button_area">
			                        	<div class="list_action_select_all">
			                        		<input type="checkbox" id="selectAllClipboard">___COMMON_ALL_ENTRIES___
			                        	</div>
			                        	<select id="list_action" size="1">
			                        		<option value="-1">*___COMMON_LIST_ACTION_NO___</option>
			                        		<option disabled="disabled">------------------------------</option>
                                       {if $popup.archive.status === false && $popup.environment.inPortal === false && !$environment.is_read_only }
			                        		   <option selected="selected" value="paste">___CLIPBOARD_PASTE_BUTTON___</option>
			                           {else}
                                          <option value="-1" class="disabled" disabled="disabled">___CLIPBOARD_PASTE_BUTTON___</option>
                                       {/if}
			                        		<option value="paste_stack">___CLIPBOARD_PASTE_STACK___</option>
			                        		<option value="delete">___CLIPBOARD_DELETE_BUTTON___</option>
			                        	</select>

			                        	<input id="list_action_submit" type="submit" class="popup_button" value="___COMMON_LIST_ACTION_BUTTON_GO___" />
			                        </div>
			                    </div>
			                </div>
			            </div>
					</div>
				</div>
			<div class="clear"></div>
		</div>
	</div>
</div>