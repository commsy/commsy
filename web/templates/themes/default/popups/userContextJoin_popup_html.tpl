<div id="popup_wrapper">
   <div id="popup_edit">
      <div id="popup_frame">
         <div id="popup_inner">

            <div id="popup_pagetitle">
               <a id="popup_close" href="" title="___COMMON_CLOSE___"><img
                  src="{$basic.tpl_path}img/popup_close.gif"
                  alt="___COMMON_CLOSE___" />
               </a>
               <h2>___CONTEXT_JOIN___</h2>
               <div class="clear"></div>
            </div>
            <div id="popup_content_wrapper">
               <div id="popup_content">
                   <div id="popup_content">
                   		<div class="input_row" id="error_wrong_code" style="margin: 10px 0 20px 180px; border: 2px solid red; padding:10px; display:none;">
							<div>
								___ACCOUNT_PROCESS_ROOM_CODE_ERROR_NEW___
							</div>
						</div>
						<div class="input_row" id="error_agb_accept" style="margin: 10px 0 20px 180px; border: 2px solid red; padding:10px; display:none;">
							<div>
								___ACCOUNT_PROCESS_ROOM_AGB_ERROR___
							</div>
						</div>
                   		{if $popup.room.check_with_code}
						<div class="input_row">
							<div class="input_container_180">
								___ACCOUNT_GET_CODE_TEXT___<br/>
								___ACCOUNT_GET_CODE_TEXT_DONT_KNOW___
							</div>
						</div>
						<div class="input_row">
							<span class="input_label_150">___ACCOUNT_PROCESS_ROOM_CODE___:</span>
							<div class="input_container_180">
								<input type="text" size="50" name="form_data[code]" />
							</div>
						</div>
						{/if}
    				    <div class="input_row">
							<div class="input_container_180">___ACCOUNT_GET_4_TEXT___</div>
						</div>
						<div class="input_row">
							<span class="input_label_150">___ACCOUNT_PROCESS_ROOM_REASON___:</span>
							<div class="input_container_180">
								<textarea cols="80" rows="10" name="form_data[description_user]"></textarea>
								<br/>
								{if $popup.agb.agb_datasecurity}
								<input type="checkbox" name="form_data[agb]" value="1">
								{i18n tag=AGB_COMFIRMATION_TEXT} <a onclick="window.open(href, target, 'toolbar=no, location=no, directories=no, status=no, menubar=yes, scrollbars=yes, resizable=yes, copyhistory=yes, width=600, height=400');" target="agb" href="commsy.php?cid={$popup.room.room_id}&mod=agb&fct=index&agb=1">{i18n tag=AGB_CONFIRMATION}</a> {i18n tag=AGB_COMFIRMATION_TEXT2}
								{/if}
							</div>
						</div>
						<div class="clear"></div>
    				</div>
                  <div id="content_buttons">
                     <div id="crt_actions_area">
                        <input id="popup_button_context_join" class="popup_button submit" data-custom="part: 'all', user_id: {$popup.user.item_id}, context_id: {$popup.room.room_id}, action: 'context_join'" type="button" name="" value="___ACCOUNT_GET_MEMBERSHIP_BUTTON___" />
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
