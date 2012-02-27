{function name=moredetail_build_link}
	{if $status == 'user_is_root'}
		{$user_name}{elseif $status == 'user_disabled'}
		<span class="disabled">{$user_name}</span>{elseif $status == 'user_has_link'}
		<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$id}">{$user_name}</a>{elseif $status == 'user_is_deleted'}
		<span class="disabled">___COMMON_DELETED_USER___</span>{elseif $status == 'user_not_visible'}
		___COMMON_USER_NOT_VISIBLE___{else}
		{$user_name}
	{/if}
{/function}

<!-- Start fade_in_ground -->
<div class="fade_in_ground_panel hidden">
	<div class="fi_moredetails">
		<div class="fi_md_info">
			<img src="{$basic.tpl_path}img/fi_item_detail.gif" alt="Detailansicht" />
		</div>
		
		<div class="fi_md_content">
			<div class="fi_mdc_item">
			<h4>___COMMON_LAST_MODIFIED_BY___</h4>
			{moredetail_build_link status=$data.last_modificator_status user_name=$data.last_modificator id=$data.last_modificator_id} - {$data.last_modification_date}
			</div>
			
			{if !empty($data.modifier)}
				<div class="fi_mdc_item">
					<h4>___COMMON_EDIT_BY___</h4>
					{foreach $data.modifier as $modifier}
						{moredetail_build_link status=$modifier.status user_name=$modifier.name id=$modifier.id}{if !$modifier@last}, {/if}
					{/foreach}
				</div>
			{/if}
			
			<div class="fi_mdc_item">
				<h4>___COMMON_CREATED_BY___</h4>
				{moredetail_build_link status=$data.creator_status user_name=$data.creator id=$data.creator_id}
			</div>
			
			<div class="fi_mdc_item">
				<h4>___COMMON_REFNUMBER___</h4>
				{$data.item_id}
			</div>
			
			<div class="fi_mdc_item_wide">
				<h4>___COMMON_READ___</h4>
				<div class="progressbar">
					<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
					<span class="percent hidden">{$data.read_percentage}</span>
					<span class="value hidden">{$data.read_count}</span>
				</div>
			</div>
			
			<div class="fi_mdc_item_wide">
				<h4>___COMMON_READ_SINCE_MODIFICATION___</h4>
				<div class="progressbar">
					<img src="{$basic.tpl_path}img/ajax_loader.gif" alt="ajax_loader" />
					<span class="percent hidden">{$data.read_since_modification_percentage}</span>
					<span class="value hidden">{$data.read_since_modification_count}</span>
				</div>
			</div>
			
			<div class="clear"> </div>
		</div>
		
		<div class="clear"> </div>
	</div>
</div>
<!-- Ende fade_in_ground -->