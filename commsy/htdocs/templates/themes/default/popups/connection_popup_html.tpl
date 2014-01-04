{* include template functions *}
{include file="include/functions.tpl" inline}

{if $popup.with_tabs === 1}

<div id="popup_top_wrapper">
  <div id="popup_my_area">
    <div id="popup_frame_my_area">
      <div id="popup_inner_my_area">

        <div id="popup_pagetitle">
          <a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
          <h2>
            ___CS_BAR_CONNECTION___
          </h2>
          <div class="clear"> </div>
        </div>
        <div id="popup_content_wrapper">
          <div id="profile_content_row_three">
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
        </div>
      </div>
      <div class="clear"></div>
    </div>
  </div>
</div>

{else}
   {include file="popups/include/room_tab_include_html.tpl" inline}
{/if}