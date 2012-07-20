<div id="popup_wrapper">
	<div id="popup_delete">
		<div id="popup_frame">
			<div id="popup_inner">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___COMMON_DELETE_BOX_INDEX_TITLE___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>___COMMON_DELETE_BUTTON___</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						___COMMON_DELETE_BOX_DESCRIPTION___
					</div>

					<div id="content_buttons">
						<div id="crt_actions_area">
							{if $popup.recurrence}
								<input id="popup_button_delete" class="popup_button float-right submit" data-custom="del: 'recurrence'" type="button" name="" value="___COMMON_DELETE_RECURRENCE_BUTTON___" />
							{/if}
							<input id="popup_button_delete" class="popup_button float-right submit" data-custom="del: 'normal'" type="button" name="" value="___COMMON_DELETE_BUTTON___" />
							<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
						</div>
					</div>

				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>