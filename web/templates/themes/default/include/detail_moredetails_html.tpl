<!-- Start fade_in_ground -->
<div class="fade_in_ground_panel">
	<div class="fi_moredetails">
		<div class="fi_md_info">
			<img src="{$basic.tpl_path}img/fi_item_detail.gif" alt="Detailansicht" />
		</div>

		<div class="fi_md_content">
			<div class="fi_mdc_item_250">
				<h4>___COMMON_REFNUMBER___</h4>
				<p class="fi_mdc_item_250">
					{$data.item_id}
            	</p>
				<h4>___COMMON_LAST_MODIFIED_BY___</h4>
				<p class="fi_mdc_item_250">
					{if !empty($data.last_modificator)}
					   	{build_user_link status=$data.last_modificator_status user_name=$data.last_modificator id=$data.last_modificator_id} {if !empty($data.last_modification_date)}- {$data.last_modification_date}{/if}
					{else}
						 ___COMMON_NO_LAST_MODIFIED_BY___
					{/if}
				</p>


			</div>

			<div class="fi_mdc_item_250">
				<h4>___COMMON_CREATED_BY___</h4>
				<p class="fi_mdc_item_250">
				   {build_user_link status=$data.creator_status user_name=$data.creator id=$data.creator_id} - {$data.creation_date}
				</p>
				<h4>___COMMON_ALL_MODIFIER___</h4>
				<p class="fi_mdc_item_250">
					{if !empty($data.modifier)}
						{foreach $data.modifier as $modifier}
							{build_user_link status=$modifier.status user_name=$modifier.name id=$modifier.id}{if !$modifier@last}, {/if}
						{/foreach}
					{else}
					   ___COMMON_NO_LAST_MODIFIED_BY___
					{/if}
				</p>
			</div>
			<div class="fi_mdc_item_250">
				{if isset($data.read_since_modification_percentage)}
					<h4>___COMMON_READ_SINCE_MODIFICATION_DATE___</h4>
					<div class="progressbar">
						<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
						<span class="percent hidden">{$data.read_since_modification_percentage}</span>
						<span class="value hidden">{$data.read_since_modification_count}</span>
					</div>
				{/if}
			</div>
			<div class="fi_mdc_item_250">
	            {if isset($data.read_percentage)}
	               <h4>___COMMON_READ_SINCE_CREATION_DATE___</h4>
	               <div class="progressbar">
	                  <img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
	                  <span class="percent hidden">{$data.read_percentage}</span>
	                  <span class="value hidden">{$data.read_count}</span>
	               </div>
	            {/if}
			</div>

			{if $data.is_workflow_type && !$data.workflow_reader}
				{*
				<div class="fi_mdc_item_wide">
					<h4>___COMMON_READ_ENTRY_SINCE_MODIFICATION___</h4>
					<div class="progressbar">
						<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
						<span class="percent hidden">{$data.read_since_modification_percentage}</span>
						<span class="value hidden">{$data.read_since_modification_count}</span>
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