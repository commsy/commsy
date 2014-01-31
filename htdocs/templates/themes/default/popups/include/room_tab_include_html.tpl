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
                              {if empty($room.url)}
                                 <div class="room_change_item" title="{$room.title}" data-custom="href: 'commsy.php?cid={$room.item_id}&mod=home&fct=index'">
                              {else}
                                 <div class="room_change_item" title="{$room.title}" data-custom="href: '{$room.url}'">
                              {/if}
                                 <input type="hidden" name="hidden_item_id" value="{$room.item_id}"/>
                                 <div class="room_change_content">
                                    <div class="room_change_room_box">
                                       <div class="room_change_title">
                                          <h3 class="room_change_title_h3"> {$room.title|truncate:28:'...':true} </h3>
                                       </div>
                                       <div class="room_change_content_element_wrapper">
                                          <div class="room_change_content_element">
                                             <p class="room_connection_content_element">
                                                {if $room.new_entries == 1}
                                                   {i18n tag=ACTIVITY_NEW_ENTRIES_NEW_SINGULAR param1=$room.time_spread}: {$room.new_entries}
                                                {else}
                                                   {i18n tag=ACTIVITY_NEW_ENTRIES_NEW param1=$room.time_spread}: {$room.new_entries}
                                                {/if}     
                                             </p>
                                             <p class="room_connection_content_element">___ACTIVITY_PAGE_IMPRESSIONS___: {$room.page_impressions}</p>
                                             <p class="float-left">___ACTIVITY_ACTIVE_MEMBERS_DESC_NEW___: {$room.activity_array.active} / {$room.activity_array.all_users}</p>
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
   </div>
{/if}