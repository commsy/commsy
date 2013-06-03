{extends file="layout_html.tpl"}

{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    {*<link rel="stylesheet" type="text/css" media="screen" href="templates/themes/individual/styles_{$environment.cid}.css" />*}
    <link rel="stylesheet" type="text/css" media="screen" href="commsy.php?cid={$environment.cid}&mod=individual&fct=getfile&iid={$environment.cid}" />
{/block}