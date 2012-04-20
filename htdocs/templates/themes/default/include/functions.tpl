{* Params Function *}
{function name=params}{foreach $params as $param}&{$param@key}={$param}{/foreach}{/function}

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