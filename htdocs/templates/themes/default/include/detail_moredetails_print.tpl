<!-- Start fade_in_ground -->
<div class="fade_in_ground_panel_2" style="background-color: #D7D7D9">
	<div class="fi_moredetails">
		<div class="fi_md_info">
			<img src="{$basic.tpl_path}img/fi_item_detail.gif" alt="Detailansicht" />
		</div>

		<div class="fi_md_content_print">
			<div class="fi_mdc_item_150">
				<h4>___COMMON_REFNUMBER___</h4>
				<p class="fi_mdc_item_150">
					{$data.item_id}
            	</p>
				
				{if isset($data.read_since_modification_percentage)}
					<h4>___COMMON_READ_SINCE_MODIFICATION___</h4>
					<div class="progressbar">
						<!--  <img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" /> -->
						
						<span class="value">{$data.read_since_modification_count}</span>
						<span> - </span>
						<span class="percent">{$data.read_since_modification_percentage}%</span>					
					</div>
				{/if}
			</div>

			<div class="fi_mdc_item_380">
				<h4>___COMMON_CREATED_BY___</h4>
				<p class="fi_mdc_item_380">
				   {build_user_link status=$data.creator_status user_name=$data.creator id=$data.creator_id} - {$data.creation_date}
				</p>
			{if !empty($data.modifier)}
					<h4>___COMMON_EDIT_BY___</h4>
					<p class="fi_mdc_item_380">
					{foreach $data.modifier as $modifier}
						{build_user_link status=$modifier.status user_name=$modifier.name id=$modifier.id}{if !$modifier@last}, {/if}
					{/foreach}
					</p>
			{/if}
			</div>
			
			{if $data.is_workflow_type && !$data.workflow_reader}
				{*
				<div class="fi_mdc_item_wide">
					<h4>___COMMON_READ_ENTRY_SINCE_MODIFICATION___</h4>
					<div class="progressbar">
						<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
						<span class="percent">{$data.read_since_modification_percentage}</span>
						<span class="value">{$data.read_since_modification_count}</span>
					</div>
				</div>
				*}
			{elseif $data.workflow_reader}
				{* TODO *}
				{* display workflow reader *}
				
				{* persons / groups *}
			{/if}
			<div class="clear"> </div>
		</div>

		<div class="clear"> </div>
	</div>
</div>
<!-- Ende fade_in_ground -->