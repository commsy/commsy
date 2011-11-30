		<div class="content_item"> <!-- Start content_item -->
			<div class="item_info">
				<div class="ii_left">
					<p>___COMMON_PAGE_ENTRIES___
						{if $list.list_entries_parameter.20 == 'disabled'}
							<a href=""><strong>20</strong></a>
						{else}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.20}">20</a>
						{/if}
						|
						{if $list.list_entries_parameter.50 == 'disabled'}
							<a href=""><strong>50</strong></a>
						{else}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.50}">50</a>
						{/if}
						|
						{if $list.list_entries_parameter.all == 'disabled'}
							<a href=""><strong>___COMMON_ALL_ENTRIES___</strong></a>
						{else}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.list_entries_parameter.all}">___COMMON_ALL_ENTRIES___</a>
						{/if}
					</p>
				</div>

				<div class="ii_right">
					<div id="item_navigation">
					    {if $list.browsing_parameters.browse_start != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_start}"><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
						{else}
						   <a><img src="{$basic.tpl_path}img/btn_ar_start.gif" alt="Start" /></a>
						{/if}
					    {if $list.browsing_parameters.browse_left != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_left}"><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
						{else}
						   <a><img src="{$basic.tpl_path}img/btn_ar_left.gif" alt="zur&uuml;ck" /></a>
						{/if}
						___COMMON_PAGE___ {$list.browsing_parameters.actual_page_number} / {$list.browsing_parameters.page_numbers}
					    {if $list.browsing_parameters.browse_right != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_right}"><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
						{else}
						   <a><img src="{$basic.tpl_path}img/btn_ar_right.gif" alt="weiter" /></a>
						{/if}
					    {if $list.browsing_parameters.browse_end != "disabled"}
						   <a href="commsy.php?cid={$environment.cid}&mod={$environment.module}&fct={$environment.function}&{$list.browsing_parameters.browse_end}"><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
						{else}
						   <a><img src="{$basic.tpl_path}img/btn_ar_end.gif" alt="Ende" /></a>
						{/if}
					</div>
				</div>

				<div class="clear"> </div>
			</div>

			<div class="clear"> </div>
		</div> <!-- Ende content_item -->