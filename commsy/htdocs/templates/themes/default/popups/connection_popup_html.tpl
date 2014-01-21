{* include template functions *}
{include file="include/functions.tpl" inline}

{if $popup.with_tabs === 1}

<div id="popup_top_wrapper">
   <div id="popup_my_area">
      <div id="popup_frame_my_area">
         <div id="popup_inner_my_area">

            <div id="popup_pagetitle">
               <a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
               <span class="float-right">
                  <a id="edit_connections" href="#" title="___COMMON_EDIT___" class="btn_head_rc2"><img src="{$basic.tpl_path}img/btn_edit_rc.gif" alt="___COMMON_EDIT___" /></a>
               </span>
               <h2>___CS_BAR_CONNECTION___</h2>
               <div class="clear"> </div>
            </div>
            <div id="popup_content_wrapper">
               <div id="profile_content_row_three">
                  
                  <div id="tabs">
                     <div class="tab_navigation">
                        {foreach $popup.tabs as $tab}
                           {if $tab@first}
                              <a href="{$tab.id}" class="pop_tab_active">{$tab.title}</a>
                           {else}
                              <a href="{$tab.id}" class="pop_tab">{$tab.title}</a>
                           {/if}
                        {/foreach}
                        <div class="clear"> </div>
                     </div>

                     <div id="popup_tabcontent">
                        {foreach $popup.tabs as $tab}
                           {if $tab@first}
                              <div class="tab" id="{$tab.id}">
                                {include file="popups/include/room_tab_include_html.tpl" inline}
                              </div>
                           {else}
                              <div class="tab hidden notloaded" id="{$tab.id}">
                                 ___CS_BAR_CONNECTION_PLEASE_WAIT___
                              </div>
                           {/if}
                        {/foreach}
                     </div>            
                  </div>
                  
                  {* edit *}
                  <div id="tabs_edit"{if !empty($popup.tabs)} class="hidden"{/if}>
                     <div class="room_block">
                        <fieldset>                        
                           ___CS_BAR_CONNECTION_EDIT_TEXT___
                        </fieldset>
                     </div>
                     <div class="room_block">
                        <h2 class="room_block">___CS_BAR_CONNECTION_EDIT_HEADLINE_CURRENT___</h2>
                        <fieldset>
                           {foreach $popup.tabs as $key => $tab}
                              <div class="input_row">
                                 <input type="hidden" name="form_data[tabid_{$key}]" value="{$tab.id}"/>
                                 <label for="{$tab.id}">{$tab.server_name}<span class="tm_bcb_next">{$tab.title_orig}</label>
                                 <input id="{$tab.id}" type="text" class="size_200 mandatory" name="form_data[name_{$tab.id}]" value="{show var=$tab.title}"/>
                                 <input name="form_data[delete_{$tab.id}]" type="checkbox" value="1"/>___COMMON_DELETE_BUTTON___
                              </div>
                           {/foreach}
                           <div class="hidden" id="new_tabs_for_edit"></div>
                           <!--
                           <label for="wishList">___CS_BAR_CONNECTION_EDIT_ORDER___</label>                    
                           <ol id="wishListNode" dojoType="dojo.dnd.Source" class="container">
                              {foreach $popup.tabs as $tab}
                                  <li class="dojoDndItem">{$tab.title}</li>
                              {/foreach}
                           </ol>
                           -->
                           <div class="input_row" style="margin-bottom:40px;">
                              {if !empty($popup.tabs)}
                                 <input id="submit_current" class="submit popup_button" data-custom="part: 'connection'" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
                              {/if}
                           </div>
                           <div class="clear"></div>
                        </fieldset>
                     </div>
                     <div class="room_block">
                        <h2 class="room_block">___CS_BAR_CONNECTION_EDIT_HEADLINE_NEW___</h2>
                        <div class="breadcrumb_room_area">
                           <fieldset>                        
                              <div class="input_row">
                                 <label for="new_portal">___COMMON_PORTAL___</label>
                                 <select name="form_data[new_portal]" size="1">
                                    <option value="-1">*___CS_BAR_CONNECTION_EDIT_NEW_PORTAL_CHOOSE___</option>
                                    <option value="-2" disabled="disabled">------------</option>
                                    {foreach $popup.server as $key => $portalarray}
                                       {if !empty(portalarray)}
                                          <option value="-2" disabled="disabled"></option>
                                          <option value="-2" disabled="disabled">{$key}</option>
                                          {foreach $portalarray as $portalinfo}
                                             <option value="{$portalinfo.server_id}_{$portalinfo.id}">- {$portalinfo.title}</option>
                                          {/foreach}
                                       {/if}
                                    {/foreach}
                                 </select>
                              </div>
                              <div class="input_row">
                                 <label for="new_userid">___COMMON_ACCOUNT___</label> 
                                 <input id="new_userid" type="text" class="size_200 mandatory" name="form_data[new_userid]" value=""/>
                              </div>
                              <div class="input_row">
                                 <label for="new_pwd">___COMMON_PASSWORD___</label>  
                                 <input id="new_pwd" type="password" class="size_200 mandatory" name="form_data[new_pwd]" value=""/>
                              </div>
                              <input id="submit_new" class="submit popup_button" data-custom="part: 'connection'" type="button" name="save_new" value="___CS_BAR_CONNECTION_EDIT_NEW_PORTAL_SAVE___"/>
                           </fieldset>
                           <div class="clear"></div>
                        </div>
                     </div>
                  </div>
                  
               </div>
            </div>
         </div>
         <div class="clear"></div>
      </div>
   </div>
</div>

{else}
   {include file="popups/include/room_tab_include_html.tpl" inline}
{/if}