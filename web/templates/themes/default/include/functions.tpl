{* Params Function *}
{function name=params}{foreach $params as $param}&{$param@key}={$param}{/foreach}{/function}

{function name=search_params}
{foreach $params as $param}{if $param@key == $key}&{$key}={$value}{else}&{$param@key}={$param}{/if}{/foreach}
{/function}

{function name=params_without_key}
{foreach $params as $param}{if $param@key != $key}&{$param@key}={$param}{/if}{/foreach}
{/function}

{function name=restriction_params}
{$add=true}{foreach $params as $param}{if $param@key == $key}&{$key}={$value}{$add=false}{else}&{$param@key}={$param}{/if}{/foreach}
{if $add == true}&{$key}={$value}{/if}
{/function}

{function name=build_user_link}
	{if $status == 'user_is_root'}
		{$user_name}{elseif $status == 'user_disabled'}
		<span class="disabled">{$user_name}</span>{elseif $status == 'user_has_link'}
		<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$id}">{$user_name}</a>{elseif $status == 'user_is_deleted'}
		<span class="disabled">___COMMON_DELETED_USER___</span>{elseif $status == 'user_not_visible'}
		___COMMON_USER_NOT_VISIBLE___{else}
		{$user_name}
	{/if}
{/function}

{function name=show}{if isset($var) && !empty($var)}{$var}{/if}{/function}

{function name=display_assessment}
	{if $assessment > 0}
		{for $i = 1 to $assessment}
			<img src="{$basic.tpl_path}img/star_active.gif" alt="*" />
		{/for}
	{else}
		{$i = 1}
	{/if}
	
	{for $j = $i to 5}
		<img src="{$basic.tpl_path}img/star_non_active.gif" alt="" />
	{/for}
{/function}