<div id="popup_wrapper">
	<div id="popup_background"></div>
	<div id="popup_w3col">
		<div id="popup">

			<div id="popup_head">
				<h2>___BUZZWORDS_EDIT_HEADER___</h2>
				<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/pop_close_btn.gif" alt="___COMMON_CLOSE___" /></a>

				<div class="clear"> </div>
			</div>

			<div id="popup_content">

				<div class="tab_navigation">
                    <a href="" class="pop_tab_active">hinzuf&uuml;gen</a>
                    <a href="" class="pop_tab">zusammenlegen</a>
                    <a href="" class="pop_tab">bearbeiten</a>
                    
                    <div class="clear"> </div>
                </div>
				
				<div id="popup_tabcontent">
					<div class="tab">
						<div id="content_row_one">
							<div class="input_row">
								<input id="buzzword_create_name" type="text" class="size_200 mandatory" />
								<input id="buzzword_create" class="popup_button" type="button" name="form_data[buzzword_create]" value="___BUZZWORDS_NEW_BUTTON___" />
							</div>
						</div>

						<div id="content_row_two">
							&nbsp;
						</div>
					</div>
					
					<div class="tab hidden">
						___BUZZWORDS_COMBINE_BUTTON___
					</div>
					
					<div class="tab hidden">
						<div id="content_row_one">
							{foreach $popup.buzzwords as $buzzword}
								<div class="input_row">
									<input type="text" value="{$buzzword.name}" class="buzzword_change_name size_200" /> 
									<input class="popup_button buzzword_change mandatory" type="button" name="form_data[{$buzzword.item_id}]" value="___BUZZWORDS_CHANGE_BUTTON___" /> 
									<input class="popup_button buzzword_attach" type="button" name="form_data[{$buzzword.item_id}]" value="___COMMON_ATTACH_BUTTON___" /> 
									<input class="popup_button buzzword_delete" type="button" name="form_data[{$buzzword.item_id}]" value="___COMMON_DELETE_BUTTON___" />
								</div>
							{/foreach}
						</div>
						
						<div id="content_row_two">
							&nbsp;
						</div>
					</div>
				</div>

			</div>

		</div>
	</div>
</div>