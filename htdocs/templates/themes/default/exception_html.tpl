{extends file="room_detail_html.tpl"}

{block name=room_detail_content}
	<div class="item_actions">
		<div id="top_item_actions">
			<span> &nbsp; </span>
		</div>
	</div>
	<div class="item_body"> <!-- Start item body -->
		<h2>___ERRORBOX_TITLE___</h2>
		<div class="clear"> </div>
			<div class="detail_content">
				<div class="row_even_no_hover" style="padding:10px">
					<p>___ITEM_NOT_AVAILABLE___</p>
					<br/>
					<br/>
					<p><a href="{$exception.link}">___COMMON_BACK_BUTTON___</a></p>
				</div>
			</div>
	</div> <!-- Ende item body -->

{/block}

{block name=room_right_portlets}
	<div class="portlet_rc">
	</div>
{/block}

{block name=room_site_actions}
{/block}

{block name=room_right_info_addon}
	<div id="info_addon">
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
                    <h1></h1>

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
           			{if $list.restriction_text_parameters}
           				<div id="rc_portlet_area">
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
						</div>
				{/if}

           		{/if}
            </div> <!-- Ende right_column -->

            <div class="clear"> </div>
        </div> <!-- Ende columnset -->
{/block}
