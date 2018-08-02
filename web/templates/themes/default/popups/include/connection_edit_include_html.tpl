                     <div class="room_block">
                        <fieldset>                        
                           ___CS_BAR_CONNECTION_EDIT_TEXT___
                        </fieldset>
                     </div>
                     <div class="room_block">
                        <h2 class="room_block">___CS_BAR_CONNECTION_EDIT_HEADLINE_CURRENT___</h2>
                        <fieldset>
                           {foreach $popup.tabs as $key => $tab}
                              <div class="input_row" id="delete_tab">
                                 <input type="hidden" name="form_data[tabid_{$key}]" value="{$tab.id}"/>
                                 <label for="{$tab.id}">{$tab.server_name}<span class="tm_bcb_next">{$tab.title_orig}</label>
                                 <input id="{$tab.id}" type="text" class="size_200 mandatory" name="form_data[name_{$tab.id}]" value="{show var=$tab.title}"/>
                                 <input name="form_data[delete_{$tab.id}]" type="checkbox" value="1"/>___COMMON_DELETE_BUTTON___
                              </div>
                           {/foreach}
                           <div class="hidden" id="new_tabs_for_edit"></div>
                           {if count($popup.tabs) > 1 }
                           <div class="input_row">
                              <label for="wishList">___CS_BAR_CONNECTION_EDIT_ORDER___</label>
                              <div class="input_container_180">                  
                                 <ol id="wishListNode" dojoType="dojo.dnd.Source" class="container">
                                    {foreach $popup.tabs as $key => $tab}
                                       <li class="dojoDndItem netnavigation">{$tab.title}<input name="form_data[sort_{$key}]" value="{$tab.id}" type="hidden"></li>
                                    {/foreach}
                                 </ol>
                              </div>
                           </div>
                           {/if}
                           <div class="input_row" style="margin-bottom:20px;">
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
                              <div class="input_row" id="edit_tab_new">
                                 {if empty($popup.tabs)}
                                    {include file="popups/include/connection_new_include_html.tpl" inline}
                                 {else}
                                    ___CS_BAR_CONNECTION_PLEASE_WAIT___
                              {/if}
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