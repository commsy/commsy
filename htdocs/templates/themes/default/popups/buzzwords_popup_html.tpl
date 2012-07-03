<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>___BUZZWORDS_EDIT_HEADER___</h2>
					<div class="clear"> </div>
				</div>

				<div id="popup_content_wrapper">
					<div id="profile_content_row_three">

						<div class="tab_navigation">
		                    <a href="add_tab" class="pop_tab_active">hinzuf&uuml;gen</a>
		                    <a href="merge_tab" class="pop_tab">zusammenlegen</a>
		                    <a href="edit_tab" class="pop_tab">bearbeiten</a>

		                    <div class="clear"> </div>
		                </div>

						<div id="popup_tabcontent">
							<div id="add_tab" class="tab">
								<div id="content_row_one">
									<div class="input_row">
										<input id="buzzword_create_name" type="text" class="size_200 mandatory" />
										<input id="buzzword_create" class="popup_button submit" data-custom="part: 'add'" type="button" name="form_data[buzzword_create]" value="___BUZZWORDS_NEW_BUTTON___" />
									</div>
								</div>

								<div id="content_row_two" class="overflow_auto">
									<ul class="popup_buzzword_list">
										{foreach $popup.buzzwords as $buzzword}
											<li class="ui-state-default popup_buzzword_item">{$buzzword.name}</li>
										{/foreach}
										<div class="clear"></div>
									</ul>
								</div>
							</div>

							<div id="merge_tab" class="tab hidden">
								<div id="content_row_one">
									<div class="input_row">
										<select id="buzzword_merge_one" class="size_200" size="1">
											{foreach $popup.buzzwords as $buzzword}
												<option value="{$buzzword.item_id}">{$buzzword.name}</option>
											{/foreach}
										</select>
										
										<select id="buzzword_merge_two" class="size_200" size="1">
											{foreach $popup.buzzwords as $buzzword}
												<option{if $buzzword@index == 0} disabled="disabled"{/if} value="{$buzzword.item_id}">{$buzzword.name}</option>
											{/foreach}
										</select>
										
										<input id="buzzword_merge" class="popup_button submit" data-custom="part: 'merge'" type="button" name="form_data[buzzword_merge]" value="___BUZZWORDS_COMBINE_BUTTON___" />
									</div>
								</div>

								<div id="content_row_two" class="overflow_auto">
									<ul class="popup_buzzword_list">
										{foreach $popup.buzzwords as $buzzword}
											<li class="ui-state-default popup_buzzword_item">{$buzzword.name}</li>
										{/foreach}
										<div class="clear"></div>
									</ul>
								</div>
							</div>

							<div id="edit_tab" class="tab hidden">
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
								
								<div id="content_row_two_max">
									<div class="open_close_head">
				                        <strong>Eintr&auml;ge zuordnen</strong> 
				                        (<span class="text_important">&bdquo;Bereitstellung&rdquo;</span> &ndash; 8 Eintr&auml;ge sind bereits ausgew&auml;hlt)
				                        <a href="" class="row_open_close" title="Ansicht maximieren"><img src="{$basic.tpl_path}img/pop_max_btn.gif" alt="maximieren" /></a>
				                        
				                        <div class="clear"> </div>  
				                    </div>
				                    
				                    <div id="crt_content">
				                        <div id="crt_col_left">
				                            
				                            <div id="crt_row_area">
				                            
				                                <div class="pop_row_odd">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                    
				                                <div class="pop_row_even">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                
				                                <div class="pop_row_odd">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                    
				                                <div class="pop_row_even">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                
				                                <div class="pop_row_odd">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                    
				                                <div class="pop_row_even">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                
				                                <div class="pop_row_odd">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                    
				                                <div class="pop_row_even">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                
				                                <div class="pop_row_odd">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                    
				                                <div class="pop_row_even">
				                                    <div class="pop_col_25">
				                                        <input type="checkbox" name="" value="" />
				                                    </div>
				                                    <div class="pop_col_220">
				                                        Lorem ipsum dolor sit
				                                    </div>
				                                    <div class="pop_col_90">
				                                        00.00.0000
				                                    </div>
				                                    <div class="pop_col_150">
				                                        Dennis Mustermann
				                                    </div>
				                                    <div class="clear"> </div>  
				                                </div>
				                                
				                            </div>
				                            
				                            <div id="crt_actions_area">
				                                <input class="popup_button" type="button" name="" value="ausgew&auml;hlte Eintr&auml;ge zuordnen" /> 
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
				                                <input type="text" value="Suchbegriff" class="size_150_color" /> 
				                                <br/>
				                                <select name="" size="1" class="size_150_color">
				                                    <option>Rubrik w&auml;hlen</option>
				                                </select>
				                                <br/>
				                                <select name="" size="1" class="size_150_color">
				                                    <option>Art der Eintr&auml;ge</option>
				                                </select>
				                                <br/>
				                                <input type="checkbox" name="" value="" /> <span class="sitenote">nur verkn&uuml;pfte Eintr&auml;ge</span>
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