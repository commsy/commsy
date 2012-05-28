{extends file="layout_html.tpl"}

{block name=top_menu}
	<div id="top_menu">
		<div id="tm_wrapper_outer">
		<div id="tm_wrapper">
			<div id="tm_icons_bar">
				{if !$environment.is_guest}<a href="" id="tm_logout" title="Logout">&nbsp;</a>{/if}

				<div class="clear"></div>
			</div>

			<div id="tm_pers_bar">
				<a href="" id="tm_user">
					{*<span class="tm_dropdown">*}
						{* login / logout *}
						{if !$environment.is_guest}
							___COMMON_WELCOME___, {$environment.username|truncate:20}
						{/if}



						{*
						{if $environment.is_guest}
							login maske
						{else}
							<span class="mm_bl"><a href="" id="mm_logout">Abmelden</a></span>


							<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Mein CommSy</a></span>
							{if $environment.is_moderator}
								<span class="mm_br mm_bl"><a href="" class="mm_dropdown">Admin</a></span>
							{/if}


							<span class="mm_br">___COMMON_WELCOME___, {$environment.username|truncate:12}</span>
						{/if}
						*}
					{*</span>*}
				</a>
			</div>

			<div id="tm_icons_bar">
				<a href="" id="tm_widgets" title="___MYWIDGETS_INDEX___">&nbsp;</a>
				<a href="" id="tm_mycalendar" title="___MYCALENDAR_INDEX___">&nbsp;</a>
				<a href="" id="tm_stack" title="___COMMON_ENTRY_INDEX___">&nbsp;</a>
				<a href="" id="tm_clipboard" title="___MYAREA_MY_COPIES___">&nbsp;</a>
				<div class="clear"></div>
			</div>

			<div id="tm_breadcrumb">
				<a href="">___COMMON_GO_BUTTON___: {$room.room_information.room_name}</a>
			</div>

			<div id="tm_icons_left_bar">
				<a href="" id="tm_settings" title="___COMMON_CONFIGURATION___">&nbsp;</a>
				<div class="clear"></div>
			</div>

			<div class="clear"></div>
		</div>
	</div>

	<div id="tm_menus">
		<div id="tm_dropmenu_breadcrumb" class="hidden"></div>
		<div id="tm_dropmenu_widget_bar" class="hidden"></div>
		<div id="tm_dropmenu_pers_bar" class="hidden"></div>
	</div>
	</div>
{/block}

{block name=layout_content}
	<div id="columnset"> <!-- Start columnset -->

            <div id="left_column"> <!-- Start left_column -->

                <div id="main_navigation">
                    <ul>
                    	<!--  <li id="active"><a href="commsy.php?cid={$environment.cid}&mod=home&fct=index"><span id="ho_act"></span><br/>Home</a></li>-->
                    	{foreach $room.rubric_information as $rubric}
                    		<li {if $rubric.active}id="active"{else}class="non_active"{/if}>
                    			<a href="commsy.php?cid={$environment.cid}&mod={$rubric.name}&fct=index">
                    				<span id="{if $rubric.active}{$rubric.span_prefix}_act{else}{$rubric.span_prefix}_non_act{/if}"></span>
                    				{if $rubric.translate}___COMMON_{$rubric.name|upper}_INDEX___{else}{$rubric.name}{/if}
                    			</a>
                    		</li>
                    	{/foreach}
							{* profil *}
							{$entry = $room.second_navigation.profil}
							{if $entry.access}
								<li {if $entry.active}id="active" class="active_spe"{else}class="non_active spe"{/if}>
									<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$entry.item_id}">
										<span id="{if $entry.active}{$entry.span_prefix}_act{else}{$entry.span_prefix}_non_act{/if}"></span>
										___USER_OWN_INFORMATION_LINK___
									</a>
								</li>
							{/if}
                    </ul>
                    <div class="clear"> </div>


                    <div id="site_actions">
                    	{block name=room_site_actions}{/block}
                    </div>

                    <h1>{block name=room_navigation_rubric_title}{/block}</h1>

                    <div class="clear"> </div>
                </div>

                <div id="maincontent">
                	{block name=room_main_content}{/block}
                </div>

            </div> <!-- Ende left_column -->

            <div id="right_column"> <!-- Start right_column -->
            	{block name=room_right_info_addon}
					<div id="info_addon">
						<div id="info_area_row_{$room.addon_information.rows}">
							<div id="infos_left">
								<p>
									{if $room.room_information.new_entries == 1}
										{i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.room_information.time_spread}: {$room.room_information.new_entries}
									{else}
										{i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.room_information.time_spread}: {$room.room_information.new_entries}
									{/if}

								</p>
								<p>___ACTIVITY_PAGE_IMPRESSIONS___: {$room.room_information.page_impressions}</p>
								<p class="float-left">___ACTIVITY_ACTIVE_MEMBERS_DESC_NEW___: {$room.room_information.active_persons} / {$room.room_information.all_persons}</p>
							</div>

							<div class="clear"> </div>
						</div>

						<div id="addon_area_row_{$room.addon_information.rows}">
							<p>
								{if $room.addon_information.wiki.active}
									{$w = $room.addon_information.wiki}
									<a href="{$w.path}/wikis/{$environment.cid}/{$w.item_id}/{$w.session}" title="___COMMON_WIKI_LINK___: {$room.addon_information.wiki.title}" target="_blank">
										<img src="{$basic.tpl_path}img/addon_wiki.png" alt="___COMMON_WIKI_LINK___" />
									</a>
								{/if}
								{if $room.addon_information.chat.active}
									{$c = $room.addon_information.chat}
									<a href="commsy.php?cid={$environment.cid}&module=context&fct=forward&tool=etchat" title="___CHAT_CHAT___" onclick="window.open(href, target, 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600');">
										<img src="{$basic.tpl_path}img/addon_chat.png" alt="___CHAT_CHAT___" />
									</a>
								{/if}
								{if $room.addon_information.wordpress.active}
									{$wo = $room.addon_information.wordpress}
									<a href="{$wo.path}/{$environment.cid}_{$wo.item_id}/{$wo.session}" title="___COMMON_WORDPRESS_LINK___: {$wo.title}" target="_blank">
										<img src="{$basic.tpl_path}img/addon_wordpress.png" alt="___COMMON_WORDPRESS_LINK___" />
									</a>
								{/if}
								{if $room.addon_information.rss.active}
									{$r = $room.addon_information.rss}
									<a href="rss.php?cid={$r.item_id}{$r.hash}" title="___RSS_SUBSCRIBE_LINK___" target="_blank">
										<img src="{$basic.tpl_path}img/addon_rss.png" alt="___RSS_SUBSCRIBE_LINK___" />
									</a>
								{/if}
							</p>
							<div class="clear"> </div>
						</div>

						<div class="clear"> </div>
					</div>
				{/block}

				{if ($environment.module != 'group' and $environment.module != 'project' and $environment.module != 'topic' and $environment.module != 'institution') or ($environment.function != 'index')}
           		<div id="rc_portlet_area">
           			{block name=room_right_portlets}
           				{if $room.sidebar_configuration.active.buzzwords}
	           				{$h = $room.sidebar_configuration.hidden.buzzwords}
							<div class="portlet_rc">
								{if $room.sidebar_configuration.editable.buzzwords}
									<a id="edit_buzzwords" href="" title="___COMMON_EDIT___" class="btn_head_rc2"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>
								{/if}
		<!--
								<a href="" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="btn_head_rc">
									<img src="{$basic.tpl_path}img/{if $h}btn_open_rc.gif{else}btn_close_rc.gif{/if}" alt="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
								</a>
		-->
								<h2>
									{block name=sidebar_buzzwordbox_title}
										___COMMON_BUZZWORD_BOX___
									{/block}
								</h2>

								<div class="clear"> </div>
		<!--
								<a href="" title="bearbeiten" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		-->
								<div class="portlet_rc_body{if $h} hidden{/if}">
									{foreach $room.buzzwords as $buzzword}
										{block name=sidebar_buzzwordbox_buzzword}
											<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&selbuzzword={$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
										{/block}
									{foreachelse}
										___COMMON_NONE___
									{/foreach}
								</div>
							</div>
						{/if}

						{if $room.sidebar_configuration.active.tags}
							{$h = $room.sidebar_configuration.hidden.tags}
							<div class="portlet_rc">
								{if $room.sidebar_configuration.editable.tags}
									<a href="" title="___COMMON_EDIT___" class="btn_head_rc2"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>
								{/if}
		<!--
								<a href="" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="btn_head_rc">
									<img src="{$basic.tpl_path}img/{if $h}btn_open_rc.gif{else}btn_close_rc.gif{/if}" alt="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
								</a>
		-->
								<h2>
									{block name=sidebar_tagbox_title}
										___COMMON_TAG_BOX___
									{/block}
								</h2>

								<div class="clear"> </div>

		<!--
									<a href="" title="___COMMON_EDIT___" class="btn_body_rc"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="close" /></a>
		-->
								<div class="portlet_rc_body{if $h} hidden{/if}">
									<div id="tag_tree">
										{block name=sidebar_tagbox_treefunction}
											{* Tags Function *}
											{function name=tag_tree level=0}
												<ul>
												{foreach $nodes as $node}
													<li	id="node_{$node.item_id}"
														{if $node.children|count > 0}class="folder"{/if}
														data="url:'commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&name=selected&seltag_{$level}={$node.item_id}&seltag=yes'">{$node.title}
													{if $node.children|count > 0}	{* recursive call *}
														{tag_tree nodes=$node.children level=$level+1}
													{/if}
												{/foreach}
												</ul>
											{/function}
										{/block}

										{* call function *}
										{tag_tree nodes=$room.tags}
									</div>
								</div>
							</div>
						{/if}
					{/block}
           		</div>
           		{/if}
            </div> <!-- Ende right_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}

{block name=room_overlay}
        {if $confirm}
		<div id="delete_confirm_overlay_background" class="delete_confirm_background"></div>
		<div id="delete_confirm_overlay_box" class="delete_confirm_box" style="display: block;">
			<form method="post" action="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&delete_sel_cookie=true">
				<h2 style="text-align: center;">{i18n tag=COMMON_DELETE_BOX_TITLE}</h2>
				<p style="text-align: left;">{i18n tag=COMMON_DELETE_BOX_DESCRIPTION}</p>
				<div>
					<input type="submit" value="{i18n tag=COMMON_DELETE_BUTTON}" name="form_data[confirm][listoption_confirm_delete]" style="float: right;">
					<input type="submit" value="{i18n tag=COMMON_CANCEL_BUTTON}" name="form_data[confirm][listoption_confirm_cancel]" style="float: left;">
				</div>
			</form>
		</div>
		{/if}
{/block}