<div id="popup_wrapper">
   <div id="popup_edit">
      <div id="popup_frame">
         <div id="popup_inner">

            <div id="popup_pagetitle">
               <a id="popup_close" href="" title="___COMMON_CLOSE___"><img
                  src="{$basic.tpl_path}img/popup_close.gif"
                  alt="___COMMON_CLOSE___" />
               </a>
               <h2>___COMMON_CONFIGURATION_WIKI_ENABLE_RSS___</h2>
               <div class="clear"></div>
            </div>
            <div id="popup_content_wrapper">
               <div id="popup_content">
                   <div id="popup_content">
                   		<div class="translationDelete hidden">___COMMON_DELETE_BUTTON___</div>
                   		
                   		<div class="input_row">
                   			<span class="input_label_150">___PORTLET_CONFIGURATION_RSS___:</span>
                   			<div class="input_container_180" id="rssList">
                   				{foreach $popup.feeds as $feed}
                   					<div class="rowWrapper">
	                   					<input type="checkbox" name="form_data[feeds]" value="feed_{$feed@index}"{if $feed.display == "1"} checked="checked"{/if} />
	                   					<input type="text" name="form_data[feedsName]" size="15" value="{$feed.title}" />
	                   					<input type="text" name="form_data[feedsAddress]" size="30" value="{$feed.adress}" />
	                   					<input class="deleteButton" type="button" value="___COMMON_DELETE_BUTTON___" />
                   					</div>
                   				{/foreach}
                   			</div>
                   			<div class="clear"></div>
                   		</div>
                   		
                   		<div class="input_row">
                   			<span class="input_label_150">___COMMON_CONFIGURATION_WIKI_ENABLE_RSS___ ___PORTLET_CONFIGURATION_RSS_ADD_BUTTON___</span>
                   			<div class="input_container_180">
                   				___PORTLET_CONFIGURATION_RSS_DESCRIPTION___<br/>
                   				<input id="rssNewTitle" type="text" size="15" />
                   				<input id="rssNewAddress" type="text" size="30" />
                   				<input id="rssCreateButton" type="button" value="___COMMON_SAVE_BUTTON___" />
                   			</div>
                   		</div>
                   		<div class="clear"></div>
    				</div>
                  <div id="content_buttons">
                     <div id="crt_actions_area">
                        <input class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="___COMMON_SAVE_BUTTON___" />
                        <input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
                     </div>
                  </div>
               </div>
            </div>
         </div>
         <div class="clear"></div>
      </div>
   </div>
</div>
