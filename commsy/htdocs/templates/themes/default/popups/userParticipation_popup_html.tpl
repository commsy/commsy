<div id="popup_wrapper">
   <div id="popup_edit">
      <div id="popup_frame">
         <div id="popup_inner">

            <div id="popup_pagetitle">
               <a id="popup_close" href="" title="___COMMON_CLOSE___"><img
                  src="{$basic.tpl_path}img/popup_close.gif"
                  alt="___COMMON_CLOSE___" />
               </a>
               <h2>___USER_CLOSE_FORM___</h2>
               <div class="clear"></div>
            </div>
            <div id="popup_content_wrapper">
               <div id="popup_content">
                  {if $popup.datenschutz.overwrite === true}
                     {i18n tag=PREFERENCES_REALLY_DELETE_DESC_ROOM param1=$popup.room.room_title param2=___PREFERENCES_LOCK_BUTTON_ROOM___ param3=___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___}
                  {/if}
                  {if $popup.datenschutz.overwrite === false}
                     {i18n tag=PREFERENCES_REALLY_DELETE_DESC_ROOM_NOT_OVERWRITE param1=$popup.room.room_title param2=___PREFERENCES_LOCK_BUTTON_ROOM___ param3=___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___}
                  {/if}
                  <div id="content_buttons">
                     <div id="crt_actions_area" style="border-bottom:0px;">
                        <input id="popup_button_room_lock" class="popup_button submit" data-custom="part: 'all', user_id: {$popup.user.item_id}, context_id: {$popup.room.room_id}, action: 'room_lock'" type="button" name="" value="___PREFERENCES_LOCK_BUTTON_ROOM___" />
                        <input id="popup_button_room_delete" class="popup_button submit" data-custom="part: 'all', user_id: {$popup.user.item_id}, context_id: {$popup.room.room_id}, action: 'room_delete'" type="button" name="" value="___PREFERENCES_REALLY_DELETE_BUTTON_ROOM___" />
                     </div>
                  </div>
               </div>
               <div id="popup_content">
                  {i18n tag=PREFERENCES_REALLY_DELETE_DESC param1=$popup.portal.portal_title param2={i18n tag=PREFERENCES_LOCK_BUTTON param1=$popup.portal.portal_title} param3={i18n tag=PREFERENCES_REALLY_DELETE_BUTTON param1=$popup.portal.portal_title}}
                  <div id="content_buttons">
                     <div id="crt_actions_area">
                        <input id="popup_button_portal_lock" class="popup_button submit" data-custom="part: 'all', user_id: {$popup.user.item_id}, context_id: {$popup.portal.portal_id}, action: 'portal_lock'" type="button" name="" value="{i18n tag=PREFERENCES_LOCK_BUTTON param1=$popup.portal.portal_title}" />
                        <input id="popup_button_portal_delete" class="popup_button submit" data-custom="part: 'all', user_id: {$popup.user.item_id}, context_id: {$popup.portal.portal_id}, action: 'portal_delete'" type="button" name="" value="{i18n tag=PREFERENCES_REALLY_DELETE_BUTTON param1=$popup.portal.portal_title}" />
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="clear"></div>
      </div>
   </div>
</div>
