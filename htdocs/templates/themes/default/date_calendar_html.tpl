{extends file="room_list_html.tpl"}

{block name=room_site_actions}
    <a href="commsy.php?cid={$environment.cid}&mod=date&fct=index&mode=list" title="___COMMON_LIST_VIEW___"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="___COMMON_LIST_VIEW___" /></a>
    <a title="___DATES_CHANGE_CALENDAR___"><img src="{$basic.tpl_path}img/btn_calendar_view_active.gif" alt="___DATES_CHANGE_CALENDAR___" /></a>

	</div>
	<div id="site_actions">

	<a id="abo_entries" href="webcal://{$date.ical_adress}" title="___DATES_ABBO___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_remember.gif" alt="___DATES_ABBO___" />
	</a>

	<a id="export_entries" href="http://{$date.ical_adress}" title="___DATES_EXPORT___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_export_todisk.gif" alt="___DATES_EXPORT___" />
	</a>

	<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>

    {if $index.actions.new}
		<a class="open_popup" data-custom="iid: 'NEW', module: '{$environment.module}'" href="#" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}

{/block}

{block name=room_list_content}
	{$cc = $date.calendar_content}

	<div class="tab_navigation" style="height:29px;">
    	{if $cc.mode == "week"}
    		<a class="pop_tab_active" href="commsy.php?cid={$environment.cid}&mod=date&fct=index{params params=$cc.header.change_presentation_params_week}">___DATES_CALENDAR_LINK_WEEK___</a>
        	<a class="pop_tab" href="commsy.php?cid={$environment.cid}&mod=date&fct=index{params params=$cc.header.change_presentation_params_month}">___DATES_CALENDAR_LINK_MONTH___</a>
		{else}
    		<a class="pop_tab" href="commsy.php?cid={$environment.cid}&mod=date&fct=index{params params=$cc.header.change_presentation_params_week}">___DATES_CALENDAR_LINK_WEEK___</a>
        	<a class="pop_tab_active" href="commsy.php?cid={$environment.cid}&mod=date&fct=index{params params=$cc.header.change_presentation_params_month}">___DATES_CALENDAR_LINK_MONTH___</a>
		{/if}
	</div>
	<div class="clear"> </div>

	<div id="calendar">
        <div id="cal_head">
            {if $cc.mode == "month"}
            	<strong>{$cc.header.current_month} {$cc.header.current_year} (___DATES_CALENDARWEEKS_SHORT___ {$cc.header.current_calendarweek_first}-{$cc.header.current_calendarweek_last}) </strong>
	           	<a href="commsy.php?cid={$environment.cid}&mod=date&fct=index{restriction_params params=$environment.params_array key=$cc.mode value=$cc.header.prev}" id="cal_left"><img src="{$basic.tpl_path}img/cal_arrow_left.gif" alt=""/></a>
				<select size="1" class="size_200" >
		            <option value="" >{$cc.header.current_month} {$cc.header.current_year}</option>
	 			</select>
           		<a href="commsy.php?cid={$environment.cid}&mod=date&fct=index{restriction_params params=$environment.params_array key=$cc.mode value=$cc.header.next}" id="cal_right"><img src="{$basic.tpl_path}img/cal_arrow_right.gif" alt="" /></a>
            {else if $cc.mode == "week"}
            	<strong> {$cc.header.current_week_start} - {$cc.header.current_week_last}</strong>
	           	<a href="commsy.php?cid={$environment.cid}&mod=date&fct=index{restriction_params params=$environment.params_array key=$cc.mode value=$cc.header.prev}" id="cal_left"><img src="{$basic.tpl_path}img/cal_arrow_left.gif" alt=""/></a>
				<select size="1" class="size_200" >
		            <option value="" >___DATES_CALENDARWEEK___  {$cc.header.current_week}</option>
	 			</select>
           		<a href="commsy.php?cid={$environment.cid}&mod=date&fct=index{restriction_params params=$environment.params_array key=$cc.mode value=$cc.header.next}" id="cal_right"><img src="{$basic.tpl_path}img/cal_arrow_right.gif" alt="" /></a>
            {/if}

        </div>

        <div id="cal_table_{$cc.mode}" {if $cc.mode == "week"}class="tablehead"{/if}>

        	{if $cc.mode == "week"}
        		<table id="hour_index_head" cellspacing="0" cellpadding="0" border="0">
        			<tr>
        				<th></th>
        			</tr>
        			<tr>
       					<td>0-24</td>
	        		</tr>
	       		</table>
        	{/if}

            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                	{if $cc.mode == "month"}
                		<th>___COMMON_DATE_MONDAY___</th>
                		<th>___COMMON_DATE_TUESDAY___</th>
                		<th>___COMMON_DATE_WEDNESDAY___</th>
                		<th>___COMMON_DATE_THURSDAY___</th>
                		<th>___COMMON_DATE_FRIDAY___</th>
                		<th>___COMMON_DATE_SATURDAY___</th>
                		<th>___COMMON_DATE_SUNDAY___</th>
                	{else if $cc.mode == "week"}
                		{section name=week_tablehead loop=7}
                			{$i = $smarty.section.week_tablehead.index}

                			<th>{$cc.content.tablehead[$i]}</th>
                		{/section}
                	{/if}

                </tr>
                {if $cc.mode == "month"}
                	{section name=rows loop=6}
	                	{$i = $smarty.section.rows.index}

	                	<tr>
	                		{section name=columns loop=7}
	                			{$j = $smarty.section.columns.index}
	                			{$pos = $i * 7 + $j}

	                			{* nonactive_day / active_day / this_today *}
	               				<td class="{$cc.content.days[$pos].state}">

	                				<div class="cal_daynumber">{$cc.content.days[$pos].day}</div>

	                				{if isset($cc.content.days[$pos].dates) && !empty($cc.content.days[$pos].dates)}
	                					<div class="cal_days_events">
		                					{foreach $cc.content.days[$pos].dates as $date}
		                						<a href="{$date.href}" class="event_{$date.color}">{$date.title|truncate:11:"...":true}</a>


		                						{* build and style tooltips here

		                						available date values are:
		                							"title"
									      			"date"
									      			"place"
									      			"participants"	- array of
									      				"name"
									      			"color"			- used for css markup
									      			"context"		- room title?
									      			"href"			- link to date detail
									      		*}

		                						<div class="tooltip tooltip_with_400">
																<a href="{$date.href}">
													<div class="tooltip_inner tooltip_inner_with_400">

														<div class="tooltip_title">
															<div class="header">{$date.title}</div>
														</div>
														<div class="scrollable">
															<div class="tooltip_content">
																<table id="hover_table">
																	<tr>
																		<td class="key">
																			___DATES_END_DAY___:
																		</td>
																		<td class="value">
																			{$date.date[1]}
																		</td>
																	</tr>
																		{if !empty($date.place)}
																		<tr>
																			<td class="key">
																				___DATES_PLACE___:
																			</td>
																			<td class="value">
																				{$date.place}
																			</td>
																		</tr>
																	{/if}
																	<tr>
																		<td class="key">
																			___DATE_PARTICIPANTS___:
																		</td>
																		<td class="value">
																			{if !empty($date.participants)}
																				{$date.participants}
																			{else}
																				___TODO_NO_PROCESSOR___
																			{/if}
																		</td>
																	</tr>
																</table>
															</div>
														</div>
													</div>
																</a>
												</div>
		                					{/foreach}
	                					</div>
	                				{/if}
	                			</td>
	                		{/section}
	                    </tr>
	                {/section}
                {else if $cc.mode == "week"}
                	<tr>
                		{section name=columns_fullday loop=7}
                			{$i = $smarty.section.columns_fullday.index}

                			{* nonactive_day / active_day / this_today *}
                			<td class="active_day">
                				{foreach $cc.content.display[-1][$i] as $date}
                					<a href="{$date.href}" class="event_{$date.color}">{$date.title|truncate:11:"...":true}</a>
                				{/foreach}
                			</td>
               			{/section}
                	</tr>
                </table>
        	</div>
        	<div id="cal_table_{$cc.mode}">
        		<table id="hour_index" cellspacing="0" cellpadding="0" border="0">
        			{section name=time loop=24}
        				<tr>
        					<td>
        						{if $smarty.section.time.index == 8}
        							<a name="day_start"></a>
        						{/if}
        						{$smarty.section.time.index}
        					</td>
	        			</tr>
        			{/section}
        		</table>
                <table cellspacing="0" cellpadding="0" border="0">
                	{section name=rows loop=24}
                		{$i = $smarty.section.rows.index}

                		<tr>
                			{section name=columns loop=7}
	                			{$j = $smarty.section.columns.index}
	                			{$pos = $i * 7 + $j}

	                			{* nonactive_day / active_day / this_today *}
	                			<td class="{$cc.content.days[$pos].state}">
	                				{$numDates = $cc.content.display[$i][$j]|count}
	                				
	                				{* if there is more than one date to display in this cell, shrink them *}
	                				{$width = (98 - 10 * $numDates) / $numDates}
	                				
	                				{foreach $cc.content.display[$i][$j] as $date}
	                					<a href="{$date.href}" class="event_{$date.color} float-left" style="margin-top: {$date.topMargin}px; height: {$date.dateHeight}px; width: {$width}px;">{$date.title|truncate:11:"...":true}</a>
	                				{/foreach}
	                				<div class="clear"></div>
	                			</td>
                			{/section}
                		</tr>
                	{/section}
       			{/if}

            </table>
        </div>

        <div id="cal_hint">
        	___DATES_TIPP_FOR_ENTRIES___
        </div>
    </div>
{/block}

{block name=room_list_footer}
{/block}