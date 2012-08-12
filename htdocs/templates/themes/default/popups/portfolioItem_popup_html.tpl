{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">
			<div id="popup_inner" class="scrollPopup">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
	
					<h2>___CS_BAR_PORTFOLIO_HEADER___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___CS_BAR_PORTFOLIO_NEW___{else}___CS_BAR_PORTFOLIO_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>


					<div id="popup_content">
						<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						
						<div class="input_row">
	                        <label for="portfolioTitle">___COMMON_TITLE___:</label>
	                        <input id="portfolioTitle" type="text" class="size_200" name="form_data[title]" value="{show var=$portfolio.title}"/>
						</div>
						
						<div class="input_row">
	                        <label for="portfolioDescription">___COMMON_DESCRIPTION___:</label>
	                        <textarea cols="80" rows="6" id="portfolioDescription" name="form_data[description]">{show var=$portfolio.description}</textarea>
						</div>
						
						<div class="input_row">
							<label for="portfolioExternal">___EXTERNAL_VIEWER_DESCRIPTION___</label>
							<input type="text" id="portfolioExternal" name="form_data[externalViewer]" value="{show var=$portfolio.externalViewer}"/>
						</div>
						
						<div class="input_row">
							<input id="newPortfolioSubmitButton" type="button" class="submit" data-custom="part: 'all'" value="___COMMON_CREATE___"/>
	           			</div>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>