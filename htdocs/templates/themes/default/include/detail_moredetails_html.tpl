<!-- Start fade_in_ground -->
<div class="fade_in_ground_panel hidden">
	<div class="fi_moredetails">
		<div class="fi_md_info">
			<img src="{$basic.tpl_path}img/fi_item_detail.gif" alt="Detailansicht" />
		</div>
		
		<div class="fi_md_content">
			<div class="fi_mdc_item">
			<h4>___COMMON_LAST_MODIFIED_BY___</h4>
			<a href="">{$data.modificator}</a>{$data.modification_date}
			</div>
			
			{if !empty($data.modifiers)}
				<div class="fi_mdc_item">
					<h4>___COMMON_EDIT_BY___</h4>
					<a href="">
						{foreach $data.modifiers as $modifier}
							{$modifer}{if !$modifier@last}, {/if}
						{/foreach}
					</a>
				</div>
			{/if}
			
			<div class="fi_mdc_item">
				<h4>___COMMON_CREATED_BY___</h4>
				<a href="">{$data.creator}</a>
			</div>
			
			<div class="fi_mdc_item">
				<h4>___COMMON_REFNUMBER___</h4>
				<a href="">{$data.reference_number}</a>
			</div>
			
			<div class="clear"> </div>
		</div>
		
		<div class="clear"> </div>
	</div>
</div>
<!-- Ende fade_in_ground -->