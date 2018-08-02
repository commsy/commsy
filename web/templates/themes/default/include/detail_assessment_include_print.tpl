<span id="detail_assessment"{if !isset($detail.assessment.user_voted) || $detail.assessment.user_voted === false} class="rateable"{/if}>
	{* average assessment *}
	{display_assessment assessment=$detail.assessment.average}
</span>
