{extends file="room_list_html.tpl"}

{block name=room_navigation_rubric_title}
	___COMMON_SEARCH_RESULTS___:
	<span>{$room.search_content.count_all}</span>
{/block}

{block name=room_list_content}
	<table width="100%" cellpadding="2" cellspacing="0" class="print_table_border">
		<thead>
			<tr>
				<td class="table_head" colspan="4"></td>
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="{if $item@iteration is odd}row_odd{else}row_even{/if} {if $item@iteration is odd}odd_sep_announcement{else}even_sep_announcement{/if}">
					<a href="commsy.php?cid={$environment.cid}&mod={$item.type}&fct=detail&iid={$item.item_id}&search_path=true">{$item.title}</a>
				</td>
				<td>
					<a href="" class="attachment">{$item.num_files}</a>
				</td>
				<td>
					<p><img src="{$basic.tpl_path}img/netnavigation/{$item.type}.png" title="{$item.type}"/></p>
				</td>
				<td>
					<div class="progressbar">
						<span class="percent">{$item.relevanz}</span>
					</div>
				</td>
			</tr>
		</tbody>
{/block}