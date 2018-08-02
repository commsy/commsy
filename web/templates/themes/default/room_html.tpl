{extends file="overwrite_html.tpl"}

{block name=warning}
	{if $environment.use_problems.show}
		<div id="warning">
			<div id="wn_wrapper_outer">
				<strong>___COMMSY_USE_PROBLEMS___ {$environment.use_problems.content}</strong>
			<div class="clear"></div>
		</div>
		</div>
	{/if}
{/block}


{block name=top_menu}
	<div id="top_menu">
		<div id="tm_wrapper_outer">
			<div id="tm_wrapper">
				{block name=logout}
				<div id="tm_icons_bar">
					{if !$environment.is_guest}<a href="commsy.php?cid={$environment.cid}&mod=context&fct=logout&iid={$environment.user_item_id}" id="tm_logout" title="{i18n tag=LOGOUT language=$environment.user_language}">&nbsp;</a>{/if}
					{if $environment.is_guest}<a href="commsy.php?cid={$environment.pid}&mod=home&fct=index&room_id={$environment.cid}&login_redirect=1" class="tm_user" style="width:70px;" title="{i18n tag=MYAREA_LOGIN_BUTTON language=$environment.user_language}">{i18n tag=MYAREA_LOGIN_BUTTON language=$environment.user_language}</a>{/if}
					<div class="clear"></div>
				</div>
				{/block}
	
				{block name=user_area}
				<div id="tm_pers_bar">
				   {if !$environment.archive_mode}
					<a href="#" id="tm_user">
						{* login / logout *}
						{if !$environment.is_guest}
							{i18n tag=COMMON_WELCOME language=$environment.user_language}, {$environment.username|truncate:20}
						{/if}
						{if $environment.is_guest}
							___COMMON_WELCOME___, ___COMMON_GUEST___
						{/if}
					</a>
               {/if}					
               {if $environment.archive_mode}
               <div id="tm_user">
                  {* login / logout *}
                  {if !$environment.is_guest}
                     {i18n tag=COMMON_WELCOME language=$environment.user_language}, {$environment.username|truncate:20}
                  {/if}
                  {if $environment.is_guest}
                     ___COMMON_WELCOME___, ___COMMON_GUEST___
                  {/if}
               </div>
               {/if}             
				</div>
				{/block}
	
				{block name=widgets}
				{if !$environment.is_guest}
					<div id="tm_icons_bar">
					   {if !empty($cs_bar.addon_information.plugins)}
					      {foreach key=no item=plugin_data from=$cs_bar.addon_information.plugins}
					         {if !empty($plugin_data.active) and $plugin_data.active and !empty($plugin_data.href) and !empty($plugin_data.title) and !empty($plugin_data.text) and !empty($plugin_data.img) and !empty($plugin_data.name)}
					            <style type="text/css">
                           <!--
                           #tm_{$plugin_data.name} {
                               background: url({$plugin_data.img}) no-repeat;
                           }
                           
                           #tm_{$plugin_data.name}:hover,
                           .tm_{$plugin_data.name}_hover {
                               background: url({$plugin_data.img}) -36px 0px no-repeat !important;
                           }
                           -->
                           </style>
   			               <a href="{$plugin_data.href}" title="{$plugin_data.title}" target="_blank" id="tm_{$plugin_data.name}">{$plugin_data.text}</a>
					         {/if}
					      {/foreach}
					   {/if}
	               {if $cs_bar.addon_information.wiki.active}
	                  {$w = $cs_bar.addon_information.wiki}
	                  <a href="{$w.path}/wikis/{$w.portal_id}/{$w.item_id}/index.php{$w.session}" title="{i18n tag=COMMON_WIKI_LINK language=$environment.user_language}: {$w.title}" target="_blank" id="tm_wiki">&nbsp;</a>
	               {/if}
	               {if $cs_bar.addon_information.wordpress.active}
	                  {$wo = $cs_bar.addon_information.wordpress}
	                  <a href="{$wo.path}/{$environment.pid}_{$wo.item_id}/{$wo.session}" title="{i18n tag=COMMON_WORDPRESS_LINK language=$environment.user_language}: {$wo.title}" target="_blank" id="tm_wordpress">&nbsp;</a>
	               {/if}
                  {if $cs_bar.show_connection == "1"}
                     <a href="#" id="tm_connection" title="{i18n tag=CS_BAR_CONNECTION language=$environment.user_language}">&nbsp;</a>
                  {/if}
						{if $cs_bar.show_portfolio == "1"}
							<a href="#" id="tm_portfolio" title="{i18n tag=CS_BAR_PORTFOLIO language=$environment.user_language}">&nbsp;</a>
						{/if}
						{if $cs_bar.show_widgets == '1'}
							<a href="#" id="tm_widgets" title="{i18n tag=MYWIDGETS_INDEX language=$environment.user_language}">&nbsp;</a>
						{/if}
						{if $cs_bar.show_calendar == '1'}
							<a href="#" id="tm_mycalendar" title="{i18n tag=MYCALENDAR_INDEX language=$environment.user_language}">&nbsp;</a>
						{/if}
						{if $cs_bar.show_stack == '1'}
							<a href="#" id="tm_stack" title="{i18n tag=COMMON_ENTRY_INDEX language=$environment.user_language}">&nbsp;</a>
						{/if}
						<a href="#" id="tm_clipboard" title="{i18n tag=MYAREA_MY_COPIES language=$environment.user_language}">&nbsp;</a>
						{if ($environment.count_copies > 0)}
							<span id="tm_clipboard_copies">{$environment.count_copies}</span>
                  {else}
                     <span id="tm_clipboard_copies"></span>
						{/if}
						<div class="clear"></div>
					</div>
				{/if}
				{/block}
	
				{block name=breadcrumb}
				{if isset($room.old_room_switcher) and $room.old_room_switcher == 'yes'}
					<div id="tm_breadcrumb_old">
						{$room.room_switcher_select_box}
					</div>
				{else}
					<div id="tm_breadcrumb">
						<a href="#" id="tm_bread_crumb">{i18n tag=COMMON_GO_BUTTON language=$environment.user_language}: {$room.room_information.room_name}</a>
					</div>
				{/if}
				{if $environment.is_moderator}
					<div id="tm_icons_left_bar">
						<a href="#" id="tm_settings" title="{i18n tag=COMMON_CONFIGURATION language=$environment.user_language}">
							{if ($environment.count_new_accounts > 0)}
								<span id="tm_settings_count_new_accounts">{$environment.count_new_accounts}</span>
							{else}
								&nbsp;
							{/if}
						</a>
						
						{if $cs_bar.show_limesurvey == true}
							<a href="#" id="tm_limesurvey" title="{i18n tag=LIMESURVEY_CONFIGURATION_LINK language=$environment.user_language}">&nbsp;</a>
						{/if}
						<div class="clear"></div>
					</div>
				{/if}
				{/block}
				<div class="clear"></div>
			</div>
		</div>
	
		<div id="tm_menus">
			<div id="tm_dropmenu_breadcrumb" class="hidden"></div>
			<div id="tm_dropmenu_widget_bar" class="hidden"></div>
         <div id="tm_dropmenu_connection" class="hidden"></div>
			<div id="tm_dropmenu_portfolio" class="hidden"></div>
			<div id="tm_dropmenu_mycalendar" class="hidden"></div>
			<div id="tm_dropmenu_stack" class="hidden"></div>
			<div id="tm_dropmenu_pers_bar" class="hidden"></div>
			<div id="tm_dropmenu_clipboard" class="hidden"></div>
			<div id="tm_dropmenu_configuration" class="hidden"></div>
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
                    				<div id="{$rubric.span_prefix}_text">{if $rubric.translate}___COMMON_{$rubric.name|upper}_INDEX___{else}{$rubric.name}{/if}</div>
                    			</a>
                    		</li>
                    	{/foreach}
							{* profil *}
							{$entry = $room.second_navigation.profil}
							{if $entry.access}
								<li {if $entry.active}id="active" class="active_spe"{else}class="non_active spe"{/if}>
									<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$entry.item_id}">
										<span id="{if $entry.active}{$entry.span_prefix}_act{else}{$entry.span_prefix}_non_act{/if}"></span>
										<div id="{$rubric.span_prefix}_text">___USER_OWN_INFORMATION_LINK___</div>
									</a>
								</li>
							{/if}
                    </ul>
                    <div class="clear"> </div>

					{block name=outer_room_site_actions}
                    <div id="site_actions">
                    	{block name=room_site_actions}{/block}
                    </div>
					{/block}
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
									<a href="{$w.path}/wikis/{$w.portal_id}/{$w.item_id}/index.php{$w.session}" title="___COMMON_WIKI_LINK___: {$room.addon_information.wiki.title}" target="_blank">
										<img src="{$basic.tpl_path}img/addon_wiki.png" alt="___COMMON_WIKI_LINK___" />
									</a>
								{/if}
								{if $room.addon_information.chat.active}
									{$c = $room.addon_information.chat}
									<a href="commsy.php?cid={$environment.cid}&mod=context&fct=forward&tool=etchat" title="___CHAT_CHAT___" onclick="window.open(href, '_blank', 'toolbar=no, location=no, directories=no, status=no, menubar=no, scrollbars=yes, resizable=yes, copyhistory=yes, width=970, height=600'); return false;">
										<img src="{$basic.tpl_path}img/addon_chat.png" alt="___CHAT_CHAT___" />
									</a>
								{/if}
								{if $room.addon_information.wordpress.active}
									{$wo = $room.addon_information.wordpress}
									<a href="{$wo.path}/{$environment.pid}_{$wo.item_id}/{$wo.session}" title="___COMMON_WORDPRESS_LINK___: {$wo.title}" target="_blank">
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
           				{if $room.usage_info_content.show}
							<div class="portlet_rc">
								<h2>{$room.usage_info_content.title}</h2>
								<div class="clear"> </div>
								<div class="portlet_rc_body">
									{$room.usage_info_content.content}
								</div>
							</div>
           				{/if}
           				{if $room.sidebar_configuration.active.limesurvey}
           					<div class="portlet_rc">
           						<div class="btn_head_rc2" style="padding-top:0px">
           							<a href="#" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="divToggle" data-custom="toggleId: 'limesurveyToggle'">
										<img src="{$basic.tpl_path}img/{if $h}btn_open_rc.gif{else}btn_close_rc.gif{/if}" alt="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
									</a>
           						</div>
           						
           						<h2>___LIMESURVEY_SIDEBAR_TITLE___</h2>
           						
           						<div class="clear"> </div>

								<div id="limesurveyToggle" class="portlet_rc_body">
									
									<div class="limesurveyList">
										<div id="limesurveyLoading">
											<img src="{$basic.tpl_path}img/ajax_loader.gif"/>
										</div>
									</div>
								</div>
           					</div>
           				{/if}
           				{if $room.sidebar_configuration.active.buzzwords}
	           				{$h = $room.sidebar_configuration.hidden.buzzwords}
							<div class="portlet_rc">


								<div class="btn_head_rc2" style="padding-top:0px;">
									{if $room.sidebar_configuration.editable.buzzwords}
										<a id="edit_buzzwords" class=" open_popup" data-custom="module: 'buzzwords'" href="#" title="___COMMON_EDIT___"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>&nbsp;&nbsp;
									{/if}
									<a href="#" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="divToggle" data-custom="toggleId: 'buzzwordToggle'">
										<img src="{$basic.tpl_path}img/{if $h}btn_open_rc.gif{else}btn_close_rc.gif{/if}" alt="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
									</a>
								</div>

								<h2>
									{block name=sidebar_buzzwordbox_title}
										___COMMON_BUZZWORD_BOX___
									{/block}
								</h2>

								<div class="clear"> </div>

								<div id="buzzwordToggle" class="portlet_rc_body{if $h} hidden{/if}">
									{foreach $room.buzzwords as $buzzword}
										{block name=sidebar_buzzwordbox_buzzword}
											<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index{restriction_params params=$environment.params_array key=selbuzzword value=$buzzword.to_item_id}" class="keywords_s{$buzzword.class_id}">{$buzzword.name}</a>
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

								<div class="btn_head_rc2" style="padding-top:0px;">
									{if $room.sidebar_configuration.editable.tags}
										<a href="#" title="___COMMON_EDIT___" class="open_popup" data-custom="module: 'tags'"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>&nbsp;&nbsp;
									{/if}
									<a href="#" title="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" class="divToggle" data-custom="toggleId: 'tagsToggle'">
										<img src="{$basic.tpl_path}img/{if $h}btn_open_rc.gif{else}btn_close_rc.gif{/if}" alt="{if $h}___COMMON_SHOW___{else}___COMMON_HIDE___{/if}" />
									</a>
								</div>


								<h2>
									{block name=sidebar_tagbox_title}
										___COMMON_TAG_BOX___
									{/block}
								</h2>

								<div class="clear"> </div>

								<div id="tagsToggle" class="portlet_rc_body{if $h} hidden{/if}">
									<div class="tree">
										<img src="{$basic.tpl_path}img/ajax_loader.gif" />
									</div>
								</div>
							</div>
						{/if}

					{/block}
           		</div>
           		{elseif $environment.module == 'topic' }
           			{if $list.restriction_text_parameters || $room.usage_info_content.show}
           				<div id="rc_portlet_area">
           				{if $list.restriction_text_parameters}
						    <div class="portlet_rc">
								<h2>___COMMON_RESTRICTIONS___</h2>
								<div class="clear"> </div>
								<div class="portlet_rc_body">
									{foreach $list.restriction_text_parameters as $restriction}
										{if (!empty($restriction))}
											<span class="restriction" title="{$restriction.name}">{$restriction.name|truncate:25:'...':true}
									   			<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$restriction.link_parameter}"><img src="{$basic.tpl_path}img/cross.gif" alt="x" border="0"/></a>
									   		</span>
									   	{/if}
									{/foreach}
								</div>
							</div>
							{/if}
							{if $room.usage_info_content.show}
							<div class="portlet_rc">
								<h2>{$room.usage_info_content.title}</h2>
								<div class="clear"> </div>
								<div class="portlet_rc_body">
									{$room.usage_info_content.content}
								</div>
							</div>
           				{/if}
						</div>
				   {/if}

           		{elseif $environment.module == 'group' || $environment.module == 'project' || $environment.module == 'institution'}
           			{if $room.usage_info_content.show}
           				<div id="rc_portlet_area">
							   <div class="portlet_rc">
								   <h2>{$room.usage_info_content.title}</h2>
								   <div class="clear"> </div>
								   <div class="portlet_rc_body">
									   {$room.usage_info_content.content}
						         </div>
							   </div>
						   </div>
				      {/if}
           		{/if}
            </div> <!-- Ende right_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}

{block name=room_overlay}
        {if isset($confirm) and $confirm}
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
						<h2>{i18n tag=COMMON_DELETE_BUTTON}</h2>
						<div class="clear"> </div>
					</div>
					<div id="popup_content">
						{i18n tag=COMMON_DELETE_BOX_INDEX_DESCRIPTION_2}

						<div id="content_buttons">
							<div id="crt_actions_area" style="height:20px; border-bottom:none;">
								<form method="post" action="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&delete_sel_cookie=true">
									<input type="submit" id="popup_button_delete" class="popup_button" value="{i18n tag=COMMON_DELETE_BUTTON}" name="form_data[confirm][listoption_confirm_delete]" style="float: right;">
									<input type="submit" class="popup_button" value="{i18n tag=COMMON_CANCEL_BUTTON}" name="form_data[confirm][listoption_confirm_cancel]" style="float: left;">
								</form>
							</div>
						</div>
					</div>

				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>
		{/if}
{/block}