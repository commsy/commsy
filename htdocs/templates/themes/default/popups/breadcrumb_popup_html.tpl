<div id="popup_wrapper">
	<div id="popup_background"></div>

	<div class="tm_dropmenu hidden">
		<div class="tm_di_ground_solid">

			<div class="popup">
				<div id="popup_content">
					<div class="tab_navigation">
						<p>
							Sie sind hier:
								{foreach $popup.breadcrumb as $crumb}
									{if !$crumb@first}
										<span class="tm_bcb_next">
									{/if}
									{if !$crumb@last}
										<a class="tab_navigation" href="commsy.php?cid={$crumb.id}&mod=home&fct=index">{$crumb.title|truncate:40:'...':true}</a>
									{else}
										<strong>{$crumb.title|truncate:40:'...':true}</strong>
									{/if}

									{if !$crumb@first}
										</span>
									{/if}
								{/foreach}
								
								<span class="float-right">
									<a id="edit_roomlist" href="" title="___COMMON_EDIT___" class="btn_head_rc2"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>
								</span>
							</p>
					</div>
					<div id="profile_content_row_three">
						{foreach $popup.rooms as $headline}
							<div class="room_block">
								<h2>{$headline@key}</h2>
								{foreach $headline as $subline}
									{if !empty($subline@key)}<h3>{$subline@key}</h3>{/if}
									<div class="breadcrumb_room_area">
										{foreach $subline.rooms as $room}
											{if $room.item_id == -3}
												<div class="room_dummy room_dummy_no_border"></div>
											{else}
												<a class="room_change_item" title="{$room.title}" href="commsy.php?cid={$room.item_id}&mod=home&fct=index">
													<input type="hidden" name="hidden_item_id" value="{$room.item_id}"/>
													<div class="room_change_content">
														<div class="room_change_room_box">
															<div class="room_change_title">
																<h3 class="room_change_title_h3"> {$room.title|truncate:28:'...':true} </h3>
															</div>
															
															<div class="room_change_content_element_wrapper">
																<div class="room_change_content_element"{if $room.color_array.content_background} style="background-color:{$room.color_array.tabs_background}; text-shadow: 0 0px #999; background-image:none; color:{$room.color_array.tabs_title}{/if}">
																	<p>
																		{if $room.new_entries == 1}
																			{i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.time_spread}: {$room.new_entries}
																		{else}
																			{i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.time_spread}: {$room.new_entries}
																		{/if}
								
																	</p>
																	<p>___ACTIVITY_PAGE_IMPRESSIONS___: {$room.page_impressions}</p>
																	<p class="float-left">___ACTIVITY_ACTIVE_MEMBERS_DESC_NEW___: {$room.activity_array.active} / {$room.activity_array.all_users}</p>
																</div>
															</div>
														</div>
													</div>
												</a>
											{/if}
										{/foreach}
										
										<div class="clear"></div>
									</div>
								{/foreach}
							</div>
						{/foreach}
					</div>
					
					<div id="profile_content_row_four">
						edit
					</div>
				</div>
			</div>
		</div>
	</div>
</div>