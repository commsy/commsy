<span id="detail_assessment"{if !isset($detail.assessment.user_voted) || $detail.assessment.user_voted === false} class="rateable"{/if}>
	{* average assessment *}
	{display_assessment assessment=$detail.assessment.average}
</span>
<div class="tooltip tooltip_with_400" style="z-index:100">
	<div class="tooltip_inner tooltip_inner_with_400">
		<div class="tooltip_title">
			<div class="header">___COMMON_ASSESSMENT_OVERLAY_DESCRIPTION___</div>
		</div>
		<div class="scrollable">
			<div class="tooltip_content">
				{* detailed assessment information *}
				{for $i = 1 to 5}
						<span class="content">{display_assessment assessment=$i}
						{if !isset($detail.assessment.detail.$i)}
							0 ___COMMON_ASSESSMENT___
						{else}
							{$detail.assessment.detail.$i} {if $detail.assessment.detail.$i == 1}___COMMON_ASSESSMENT_INDEX___{else}___COMMON_ASSESSMENT___{/if}
						{/if}
					</span>
				{/for}

				{* own voting *}
				{if !isset($detail.assessment.user_voted) || $detail.assessment.user_voted === false}
					{* not voted now *}
					<span class="content">___COMMON_ASSESSMENT_OWN_NO___</span>
				{else}
					{* display own voting *}
					<span class="content">
						___COMMON_ASSESSMENT_OWN___
						{$detail.assessment.own_vote}
						&nbsp;(<a href="#" id="assessment_delete_own">{*<img src="{$basic.tpl_path}img/cross.gif" alt="___COMMON_DELETE_BUTTON___" />*}___COMMON_ASSESSMENT_DELETE___</a>)
					</span>
				{/if}
			</div>
		</div>
	</div>
</div>