<!-- Start fade_in_ground -->
<div id="workflow_expand" class="hidden">
	<div class="fade_in_ground_workflow">
		<div class="fi_workflow">
			<div class="fi_md_info">
				<img src="{$basic.tpl_path}img/fi_item_workflow.gif" alt="Workflow" />
			</div>
	
			<div class="fi_md_content">
				<div class="fi_mdc_item_150">
					<h4>___COMMON_STATUS___</h4>
					<p class="fi_mdc_item_150">
						{if !empty($data.light)}
							<img class="workflow" src="{$basic.tpl_path}img/workflow_traffic_light_{$data.light}.png" alt="{$data.title}" title="{$data.title}"> {$data.title}
	            		{/if}
	            	</p>
					<h4>___MATERIAL_WORKFLOW_VALID_UNTIL___</h4>
					<p class="fi_mdc_item_380">
					   	{if $data.validity_date == ''}
					    	___COMMON_NO_ENTRY___
					   	{else}
					   		{$data.validity_date}
					   	{/if}
					</p>
				</div>
	
				<div class="fi_mdc_item_380">
					<h4>___MATERIAL_WORKFLOW_RESUBMISSION_UNTIL___</h4>
					<p class="fi_mdc_item_380">
					   	{if $data.resubmission_date == ''}
					    	___COMMON_NO_ENTRY___
					   	{else}
					   		{$data.resubmission_date}
					   	{/if}
					</p>
					<h4>___COMMON_MARK_READ_SINCE_MODIFICATION___</h4>
					<p class="fi_mdc_item_380">
					{if $data.read_since_modification_count_text}
					    {$data.read_since_modification_count_text}
					{else}
					   	___COMMON_NO_ENTRY___
					{/if}
					</p>
				</div>
				<div class="clear"> </div>
			</div>
	
			<div class="clear"> </div>
		</div>
	</div>
</div>
<!-- Ende fade_in_ground -->