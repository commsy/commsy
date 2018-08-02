{extends file="overwrite_html.tpl"}

{block name=top_menu}
	<div id="top_menu">
		<div id="tm_wrapper_outer">
		<div id="tm_wrapper">
			<div id="tm_icons_bar">
				{if !$environment.is_guest}<a href="commsy.php?cid={$environment.cid}&mod=context&fct=logout&iid={$environment.user_item_id}" id="tm_logout" title="___LOGOUT___">&nbsp;</a>{/if}
				{if $environment.is_guest}<a href="commsy.php?cid={$environment.pid}&mod=home&fct=index&room_id={$environment.cid}&login_redirect=1" class="tm_user" style="width:70px;" title="___MYAREA_LOGIN_BUTTON___">___MYAREA_LOGIN_BUTTON___</a>{/if}
				<div class="clear"></div>
			</div>

			<div id="tm_pers_bar">
				<a href="#" id="tm_user">
					{* login / logout *}
					{if !$environment.is_guest}
						___COMMON_WELCOME___, {$environment.username|truncate:20}
					{/if}
					{if $environment.is_guest}
						___COMMON_WELCOME___, ___COMMON_GUEST___
					{/if}
				</a>
			</div>

			{if !$environment.is_guest}
				<div id="tm_icons_bar">
					{if $cs_bar.show_widgets == '1'}
						<a href="#" id="tm_widgets" title="___MYWIDGETS_INDEX___">&nbsp;</a>
					{/if}
					{if $cs_bar.show_calendar == '1'}
						<a href="#" id="tm_mycalendar" title="___MYCALENDAR_INDEX___">&nbsp;</a>
					{/if}
					{if $cs_bar.show_stack == '1'}
						<a href="#" id="tm_stack" title="___COMMON_ENTRY_INDEX___">&nbsp;</a>
					{/if}
					<a href="#" id="tm_clipboard" title="___MYAREA_MY_COPIES___">&nbsp;</a>
					{if ($environment.count_copies > 0)}
						<span id="tm_clipboard_copies">{$environment.count_copies}</span>
               {else}
                  <span id="tm_clipboard_copies"></span>
					{/if}
					<div class="clear"></div>
				</div>
			{/if}

			<div id="tm_breadcrumb">
				<a href="#" id="tm_bread_crumb">___COMMON_GO_BUTTON___: {$room.room_information.room_name}</a>
			</div>
			{if $environment.is_moderator}
				<div id="tm_icons_left_bar">
					<a href="#" id="tm_settings" title="___COMMON_CONFIGURATION___">&nbsp;</a>
					{if ($environment.count_new_accounts >0)}
						<span id="tm_settings_count_new_accounts">{$environment.count_new_accounts}</span>
					{/if}
					<div class="clear"></div>
				</div>
			{/if}
			<div class="clear"></div>
		</div>
	</div>

	<div id="tm_menus">
		<div id="tm_dropmenu_breadcrumb" class="hidden"></div>
		<div id="tm_dropmenu_widget_bar" class="hidden"></div>
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
                <div id="maincontent">
                	<div class="paddingArea">
	                	<h3>___AGB_CHANGE_TITLE___</h3>
	                	<br />
	                	{$agb.text}
	               		<br /><br />
	               		{if !$smarty.get.agb}
	               		<form action="commsy.php?cid={$environment.cid}&mod=agb&fct=index" method="post">
	               			<input type="submit" name="submit[accept]" value="___AGB_ACCEPTANCE_BUTTON___" />
		               		<input type="submit" name="submit[cancel]" value="___COMMON_CANCEL_BUTTON___" />
		               		<input type="submit" name="submit[not_accept]" value="___AGB_ACCEPTANCE_NOT_BUTTON_ROOM___" />
	               		</form>
	               		{/if}
                	</div>
                </div>

            </div> <!-- Ende left_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}