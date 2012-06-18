{extends file="room_list_html.tpl"}

{block name=room_site_actions}
	<a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=index&mode=print" title="___COMMON_LIST_PRINTVIEW___" target="_blank">
		<img src="{$basic.tpl_path}img/btn_print.gif" alt="___COMMON_LIST_PRINTVIEW___" />
	</a>

    {if $index.actions.new}
		<a id="create_new" href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct=edit&iid=NEW" title="___COMMON_NEW_ITEM___">
	    	<img src="{$basic.tpl_path}img/btn_add_new.gif" alt="___COMMON_NEW_ITEM___" />
	    </a>
    {/if}
    {if $index.actions.user}
		<a id="own_user" href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$index.actions.user_iid}" title="___COMMON_OWN_USER___">
	    	<img src="{$basic.tpl_path}img/btn_own_user.gif" alt="___COMMON_OWN_USER___" />
	    </a>
    {/if}
    
    <a href="commsy.php?cid={$environment.cid}&mod=date&fct=index&mode=list" title="Ansicht in Reihen"><img src="{$basic.tpl_path}img/btn_row_view.gif" alt="Reihen" /></a>
{/block}

{block name=room_list_content}
	<div id="calendar">
        <div id="cal_head">
            <a href="" id="cal_left"><img src="{$basic.tpl_path}img/cal_arrow_left.gif" alt=""/></a>
            <a href="">heute</a>
            <a href="" id="cal_right"><img src="{$basic.tpl_path}img/cal_arrow_right.gif" alt="" /></a>
            
            <strong>Kalenderwochen 22-24 | Juni 2012</strong>
        </div>
        
        <div id="cal_table_month">
            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <th>
                    Montag
                    </th>
                    <th>
                    Dienstag
                    </th>
                    <th>
                    Mittwoch
                    </th>
                    <th>
                    Donnerstag
                    </th>
                    <th>
                    Freitag
                    </th>
                    <th>
                    Samstag
                    </th>
                    <th>
                    Sonntag
                    </th>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">28</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">29</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">30</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">31</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">1</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">2</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">3</div>
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        <div class="cal_daynumber">4</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">5</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">6</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">7</div>
                        <div class="cal_days_events">
                            <a href="" class="event_blue">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_blue">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_red">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">8</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">9</div>
                        <div class="cal_days_events">
                            <a href="" class="event_blue">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_red">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">10</div>
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        <div class="cal_daynumber">11</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">12</div>
                    </td>
                    <td class="this_today">
                        <div class="cal_daynumber">13</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">14</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">15</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">16</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">17</div>
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        <div class="cal_daynumber">18</div>
                        <div class="cal_days_events">
                            <a href="" class="event_purple">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_yellow">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">19</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">20</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">21</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">22</div>
                        <div class="cal_days_events">
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_red">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">23</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">24</div>
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        <div class="cal_daynumber">25</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">26</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">27</div>
                        <div class="cal_days_events">
                            <a href="" class="event_purple">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">28</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">29</div>
                    </td>
                    <td class="active_day">
                        <div class="cal_daynumber">30</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">1</div>
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">2</div>
                        <div class="cal_days_events">
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">3</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">4</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">5</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">6</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">7</div>
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_daynumber">8</div>
                    </td>
                </tr>
            </table>
        </div>
        
        <div id="cal_hint">
        Tipp: Klicken Sie auf einen Tag, um einen neuen Termin einzutragen.
        </div>
    </div>
{/block}

{block name=room_list_footer}
{/block}

{*
<div id="calendar">
        <div id="cal_head">
            <a href="" id="cal_left"><img src="{$basic.tpl_path}img/cal_arrow_left.gif" alt=""/></a>
            <a href="">heute</a>
            <a href="" id="cal_right"><img src="{$basic.tpl_path}img/cal_arrow_right.gif" alt="" /></a>
            
            <strong>11.06.2012 - 17.06.2012 | Kalenderwoche 24</strong>
        </div>
        
        <div id="cal_table_week">
        
            <div id="hour_index">
                <div class="cal_hi_hour">&nbsp;</div>
                <div class="cal_hi_hour">8</div>
                <div class="cal_hi_hour">9</div>
                <div class="cal_hi_hour">10</div>
                <div class="cal_hi_hour">11</div>
                <div class="cal_hi_hour">12</div>
                <div class="cal_hi_hour">13</div>
                <div class="cal_hi_hour">14</div>
                <div class="cal_hi_hour">15</div>
                <div class="cal_hi_hour">16</div>
                <div class="cal_hi_hour">17</div>
                <div class="cal_hi_hour">18</div>
                <div class="cal_hi_hour">19</div>
                <div class="cal_hi_hour">20</div>
                <div class="cal_hi_hour">21</div>
                <div class="cal_hi_hour">22</div>
            </div>
        
            <table cellspacing="0" cellpadding="0" border="0">
                <tr>
                    <th>
                    Mo, 11.06.
                    </th>
                    <th>
                    Di, 12.06.
                    </th>
                    <th>
                    Mi, 13.06.
                    </th>
                    <th>
                    Do, 14.06.
                    </th>
                    <th>
                    Fr, 15.06.
                    </th>
                    <th>
                    Sa, 16.06.
                    </th>
                    <th>
                    So, 17.06.
                    </th>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        <div class="cal_hour_events">
                            <a href="" class="event_blue">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        <div class="cal_hour_events">
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        <div class="cal_hour_events">
                            <a href="" class="event_green">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                            <a href="" class="event_red">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                    <td class="active_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        <div class="cal_hour_events">
                            <a href="" class="event_purple">Lorem ipsum</a> <!-- bitte den Text kuerzen, damit er maximal eine Zeile einnimmt -->
                        </div>
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
                <tr>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="this_today">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                    <td class="nonactive_day">
                        
                    </td>
                </tr>
            </table>
            
            <div class="clear"> </div>
        </div>
        
        <div id="cal_hint">
        Tipp: Klicken Sie auf einen Tag, um einen neuen Termin einzutragen.
        </div>
    </div>
    *}
