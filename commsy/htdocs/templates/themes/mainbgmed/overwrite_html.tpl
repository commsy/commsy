{extends file="layout_html.tpl"}

{block name=logo_area}
<div id="logo_area">
    {if !empty($environment.logo)}
    	<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$environment.logo}" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
    {else}
    	<img src="templates/themes/mainbgmed/img/logo_rehazentrum.gif" alt="Logo" /> <!-- Logo-Hoehe 60 Pixel -->
	{/if}
	{if $environment.show_room_title}
		<span>{$environment.room_title|truncate:50:"...":true}</span>
	{/if}
</div>
{/block}

{block name=room_search}
	<div id="search_area">
	    <div id="search_navigation">
	        <div id="commsy_search">
	        	{if $environment.module != 'search'}
			        {if $environment.module === 'home'}
			        	{assign var="defaultValue" value="___CAMPUS_SEARCH_INDEX___"}
			        {else}
			        	{assign var="defaultValue" value="___COMMON_SEARCHFIELD___"}
			        {/if}
			        {assign var="systemValue" value=true}
			    {else}
			    	{assign var="defaultValue" value=$search.parameters.search}
		        {/if}
		        
				<form id="indexedsearch" action="commsy.php?cid={$environment.cid}&mod=search&fct=index" method="post">
					<!-- hidden -->
					{if $environment.module != 'home' && $environment.module != 'search'}
		    			<input type="hidden" name="form_data[selrubric]" value="{$environment.module}"/>
		    		{elseif isset($environment.post.form_data.selrubric) && !empty($environment.post.form_data.selrubric)}
		    			<input type="hidden" name="form_data[selrubric]" value="{$environment.post.form_data.selrubric}"/>
		    		{/if}
					
					<div id="searchbox_main">
						<!-- search suggestions -->
			        	{if $environment.with_indexed_search}
			        		<input id="search_suggestion" type="text"/>
			        	{/if}
			        	
						<!-- main search input -->
			            <input id="search_input" class="searchbox-sword" name="form_data[keywords]" type="text" onblur="if(this.value === '') this.value = '{show var=$defaultValue}';" onfocus="if(this.value === '___CAMPUS_SEARCH_INDEX___' || this.value === '___COMMON_SEARCHFIELD___') this.value = '';" value="{show var=$defaultValue}" />
			        	
			        	<!-- submit input -->
			        	<input id="search_submit" type="submit" class="search_button" value="___COMMON_GO_BUTTON2___!" />
					</div>
		            
		            <div class="clear"> </div>
		        </form>
		        <div class="clear"> </div>
	        </div>
	    </div>
	</div>
{/block}

{block name=body_begin}
	<body class="tundra">
	  <div id="reha_main">
{/block}

{block name=body_end}
	</div>
	</body>
{/block}


{block name="css"}
	<link rel="stylesheet" type="text/css" media="screen" href="{$basic.tpl_path}styles.css" />
    <link rel="stylesheet" type="text/css" media="screen" href="templates/themes/mainbgmed/schema.css" />
{/block}