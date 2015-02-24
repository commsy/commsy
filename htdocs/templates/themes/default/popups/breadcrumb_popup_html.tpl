
<div id="popup_top_wrapper">
	<div id="popup_my_area">
		<div id="popup_frame_my_area">
			<div id="popup_inner_my_area">

				<div id="popup_pagetitle">
					{if !$environment.is_guest}
						<a id="popup_close" href="" title="{i18n tag=COMMON_CLOSE language=$environment.user_language}"><img src="{$basic.tpl_path}img/popup_close.gif" alt="{i18n tag=COMMON_CLOSE language=$environment.user_language}" /></a>
						<span class="float-right">
							<a id="edit_roomlist" href="#" title="{i18n tag=COMMON_EDIT language=$environment.user_language}" class="btn_head_rc2"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="{i18n tag=COMMON_EDIT language=$environment.user_language}" /></a>
						</span>
					{else}
						<a id="popup_close" href="" title="{i18n tag=COMMON_CLOSE language=$environment.user_language}"><img src="{$basic.tpl_path}img/popup_close.gif" alt="{i18n tag=COMMON_CLOSE language=$environment.user_language}" /></a>
					{/if}
					<h2>
						{i18n tag=COMMON_YOUR_ARE_HERE language=$environment.user_language}:
						{foreach $popup.breadcrumb as $crumb}
							{if !$crumb@first}
								<span class="tm_bcb_next">
							{/if}
							{if !$crumb@last}
								{if $environment.pid == $crumb.id}
										<a class="tm_breadcrumb_h2" href="commsy.php?cid={$crumb.id}&mod=home&fct=index&room_id={$environment.cid}">{$crumb.title|truncate:40:'...':true}</a>
								{else}
										<a class="tm_breadcrumb_h2" href="commsy.php?cid={$crumb.id}&mod=home&fct=index">{$crumb.title|truncate:40:'...':true}</a>
								{/if}
							{else}
								<strong>{$crumb.title|truncate:40:'...':true}</strong>
							{/if}

							{if !$crumb@first}
								</span>
							{/if}
						{/foreach}
					</h2>
					<div class="clear"> </div>
				</div>
				{if !$environment.is_guest}
					<div id="popup_content_wrapper">
						<div id="profile_content_row_three">
							{foreach $popup.rooms as $headline}
								{if $headline@key !== 'unchecked'}
									<div class="room_block">
										<h2 class="room_block">{$headline@key}</h2>
										{foreach $headline as $subline}
											{if !empty($subline@key)}<h3>{$subline@key}</h3>{/if}
											<div class="breadcrumb_room_area">
												{foreach $subline.rooms as $room}
													{if $room.item_id == -3}
														<div class="room_dummy room_dummy_no_border"></div>
													{else}
														<div class="room_change_item" title="{$room.title}" data-custom="href: '{$basic.commsy_path}commsy.php?cid={$room.item_id}&mod=home&fct=index'">
															<input type="hidden" name="hidden_item_id" value="{$room.item_id}"/>
															<div class="room_change_content">
																<div class="room_change_room_box">
																	<div class="room_change_title">
																		<h3 class="room_change_title_h3"> {$room.title|truncate:28:'...':true} </h3>
																	</div>

																	<div class="room_change_content_element_wrapper">
																		<div class="room_change_content_element">
																			<p>
																				{if $room.new_entries == 1}
																					{i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.time_spread language=$environment.user_language}: {$room.new_entries}
																				{else}
																					{i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.time_spread language=$environment.user_language}: {$room.new_entries}
																				{/if}

																			</p>
																			<p>{i18n tag=ACTIVITY_PAGE_IMPRESSIONS language=$environment.user_language}: {$room.page_impressions}</p>
																			<p class="float-left">{i18n tag=ACTIVITY_ACTIVE_MEMBERS_DESC_NEW language=$environment.user_language}: {$room.activity_array.active} / {$room.activity_array.all_users}</p>
																		</div>
																	</div>
																</div>
															</div>
														</div>
													{/if}
												{/foreach}

												<div class="clear"></div>
											</div>
										{/foreach}
									</div>
								{/if}
							{/foreach}
						</div>

						<div id="profile_content_row_four" class="hidden">
							<div class="room_block">
								<div class="room_block_h2">{i18n tag=COMMON_NO_DISPLAY language=$environment.user_language}</div>

								<div class="breadcrumb_room_area">
									{foreach $popup.rooms.unchecked as $subline}
										{if !empty($subline@key)}<h3>{$subline@key}</h3>{/if}

											{foreach $subline.rooms as $room}
												<div class="room_change_item" title="{$room.title}" data-custom="href: 'commsy.php?cid={$room.item_id}&mod=home&fct=index'">
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
																			{i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.time_spread language=$environment.user_language}: {$room.new_entries}
																		{else}
																			{i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.time_spread language=$environment.user_language}: {$room.new_entries}
																		{/if}

																	</p>
																	<p>{i18n tag=ACTIVITY_PAGE_IMPRESSIONS language=$environment.user_language}: {$room.page_impressions}</p>
																	<p class="float-left">{i18n tag=ACTIVITY_ACTIVE_MEMBERS_DESC_NEW language=$environment.user_language}: {$room.activity_array.active} / {$room.activity_array.all_users}</p>
																</div>
															</div>
														</div>
													</div>
												</a>
											{/foreach}

											<div class="clear"></div>
									{foreachelse}
										<div class="room_dummy room_dummy_no_border"></div>

										<div class="clear"></div>
									{/foreach}
								</div>
							</div>
						</div>
					{/if}
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>