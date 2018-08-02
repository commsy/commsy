{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_edit{if $popup.overflow}_stack{/if}">
		<div id="popup_frame">

			<div id="popup_inner"{if $popup.overflow} class="scrollPopup"{/if}>


				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
	{if $item.edit_type == 'netnavigation'}
						<h2>___COMMON_NETNAVIGATION_ENTRIES___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/netnavigation_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ITEM_ATTACH___{else}___COMMON_ITEM_ATTACH___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>
	{elseif $item.edit_type == 'buzzwords'}
						<h2>___COMMON_BUZZWORDS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_BUZZWORD_ATTACH___{else}___COMMON_BUZZWORD_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/buzzwords_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{elseif $item.edit_type == 'tags'}
						<h2>___COMMON_TAGS___</h2>
							<div class="clear"> </div>
					</div>
					<div id="popup_content_wrapper">
						<div id="popup_title">
							<h2>{if $popup.edit == false}___COMMON_TAG_ATTACH___{else}___COMMON_TAG_ATTACH___{/if}</h2>
							<div class="clear"> </div>
						</div>


						<div id="popup_content">
							{include file="popups/include/tags_tab_include_html.tpl"}
						</div>
						<div id="content_buttons">
							<div id="crt_actions_area">
								<input type="hidden" name="editType" value="{$item.edit_type}"/>
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_ASSIGN___{else}___COMMON_ASSIGN___{/if}" />
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>
					</div>

	{else}
					<h2>___COMMON_MATERIAL___</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="popup_title">
						<h2>{if $popup.edit == false}___COMMON_ENTER_NEW___{else}___COMMON_EDIT___{/if}</h2>
						<div class="clear"> </div>
					</div>



					<div id="popup_content">
						<div id="mandatory_missing" class="input_row hidden">
							___COMMON_MANDATORY_FIELDS_CONTENT___
						</div>
						<div class="input_row">
							<div class="input_label_100">___COMMON_TITLE___<span class="required">*</span>:</div> <input type="text" value="{if isset($item.title)}{$item.title|escape:"html"}{/if}" name="form_data[title]" class="size_400" />
						</div>
						<div class="input_row">
							<span  class="input_label_100">___MATERIAL_BIBLIOGRAPHIC___<span class="required">*</span>:</span>
							<select id="bibliographic_select" name="form_data[bib_kind]" size="1" class="size_200" >
					            <option value="none" {if $item.bib_kind == 'none'} selected="selected" {/if} >* ___MATERIAL_BIB_NOTHING___</option>
					            <option value="common" {if $item.bib_kind == 'common'} selected="selected" {/if}>* ___MATERIAL_BIB_NONE___</option>
					            <option value="book" {if $item.bib_kind == 'book'} selected="selected" {/if}>___MATERIAL_BIB_BOOK___</option>
					            <option value="collection" {if $item.bib_kind == 'collection'} selected="selected" {/if}>___MATERIAL_BIB_COLLECTION___</option>
					            <option value="incollection" {if $item.bib_kind == 'incollection'} selected="selected" {/if}>___MATERIAL_BIB_INCOLLECTION___</option>
					            <option value="article" {if $item.bib_kind == 'article'} selected="selected" {/if}>___MATERIAL_BIB_ARTICLE___</option>
					            <option value="chapter" {if $item.bib_kind == 'chapter'} selected="selected" {/if}>___MATERIAL_BIB_CHAPTER___</option>
					            <option value="inpaper" {if $item.bib_kind == 'inchapter'} selected="selected" {/if}>___MATERIAL_BIB_INPAPER___</option>
					            <option value="thesis" {if $item.bib_kind == 'thesis'} selected="selected" {/if}>___MATERIAL_BIB_THESIS___</option>
					            <option value="manuscript" {if $item.bib_kind == 'manuscript'} selected="selected" {/if}>___MATERIAL_BIB_MANUSCRIPT___</option>
					            <option value="website" {if $item.bib_kind == 'website'} selected="selected" {/if}>___MATERIAL_BIB_WEBSITE___</option>
					            <option value="document" {if $item.bib_kind == 'document'} selected="selected" {/if}>___MATERIAL_BIB_DOCUMENT___</option>
					            <option value="foto" {if $item.bib_kind == 'foto'} selected="selected" {/if}>___MATERIAL_BIB_FOTO___</option>
	         				</select>
	         			</div>
	         			<div class="clear"></div>

	         			{* bibliographic data *}
	         			<div id="bibliographic">
	         				<div id="bib_content_common" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_AUTHORS___:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						___MATERIAL_BIBLIOGRAPHIC___:
	         					</div>

								<div class="editor_content">
									<div id="common" class="ckeditor">{show var=$item.common}</div>
								</div>
	         				</div>

	         				<div id="bib_content_book" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
	         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="{show var=$item.publisher}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
	         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="{show var=$item.edition}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_series">___MATERIAL_SERIES___:</label>
	         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="{show var=$item.series}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
	         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="{show var=$item.volume}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
	         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="{show var=$item.isbn}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_collection" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
	         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="{show var=$item.publisher}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
	         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="{show var=$item.edition}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_series">___MATERIAL_SERIES___:</label>
	         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="{show var=$item.series}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
	         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="{show var=$item.volume}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
	         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="{show var=$item.isbn}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date2">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date2" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_incollection" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_editor">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_editor" type="text" class="size_200" name="form_data[editor]" value="{show var=$item.editor}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_booktitle">___MATERIAL_BOOKTITLE___<span class="required">*</span>:</label>
	         						<input id="bib_booktitle" type="text" class="size_200" name="form_data[booktitle]" value="{show var=$item.booktitle}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
	         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="{show var=$item.publisher}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
	         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="{show var=$item.edition}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_series">___MATERIAL_SERIES___:</label>
	         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="{show var=$item.series}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
	         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="{show var=$item.volume}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
	         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="{show var=$item.isbn}" />
	         					</div>

                                <div class="input_row">
                                    <label for="bib_pages">___MATERIAL_PAGES___:</label>
                                    <input id="bib_pages" type="text" class="size_200" name="form_data[pages]" value="{show var=$item.pages}" />
                                </div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date3">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date3" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_article" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_journal">___MATERIAL_JOURNAL___<span class="required">*</span>:</label>
	         						<input id="bib_journal" type="text" class="size_200" name="form_data[journal]" value="{show var=$item.journal}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
	         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="{show var=$item.volume}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_issue">___MATERIAL_ISSUE___:</label>
	         						<input id="bib_issue" type="text" class="size_200" name="form_data[issue]" value="{show var=$item.issue}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_pages">___MATERIAL_PAGES___<span class="required">*</span>:</label>
	         						<input id="bib_pages" type="text" class="size_200" name="form_data[pages]" value="{show var=$item.pages}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publisher">___MATERIAL_PUBLISHER___:</label>
	         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="{show var=$item.publisher}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_issn">___MATERIAL_ISSN___:</label>
	         						<input id="bib_issn" type="text" class="size_200" name="form_data[issn]" value="{show var=$item.issn}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date4">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date4" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_dat}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_chapter" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
	         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="{show var=$item.edition}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_series">___MATERIAL_SERIES___:</label>
	         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="{show var=$item.series}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
	         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="{show var=$item.volume}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
	         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="{show var=$item.isbn}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date5">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date5" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_inpaper" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_journal">___MATERIAL_JOURNAL___<span class="required">*</span>:</label>
	         						<input id="bib_journal" type="text" class="size_200" name="form_data[journal]" value="{show var=$item.journal}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_issue">___MATERIAL_ISSUE___:</label>
	         						<input id="bib_issue" type="text" class="size_200" name="form_data[issue]" value="{show var=$item.issue}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_pages">___MATERIAL_PAGES___<span class="required">*</span>:</label>
	         						<input id="bib_pages" type="text" class="size_200" name="form_data[pages]" value="{show var=$item.pages}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publisher">___MATERIAL_PUBLISHER___:</label>
	         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="{show var=$item.publisher}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date6">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date6" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_thesis" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_thesis_kind">___MATERIAL_THESIS_KIND___<span class="required">*</span>:</label>
	         						<select id="bib_thesis_kind" class="size_200" name="form_data[thesis_kind]">
	         							<option value="term"{if $item.thesis_kind == 'term'} selected="selected"{/if}>___MATERIAL_THESIS_TERM___</option>
	         							<option value="bachelor"{if $item.thesis_kind == 'bachelor'} selected="selected"{/if}>___MATERIAL_THESIS_BACHELOR___</option>
	         							<option value="master"{if $item.thesis_kind == 'master'} selected="selected"{/if}>___MATERIAL_THESIS_MASTER___</option>
	         							<option value="exam"{if $item.thesis_kind == 'exam'} selected="selected"{/if}>___MATERIAL_THESIS_EXAM___</option>
	         							<option value="diploma"{if $item.thesis_kind == 'diploma'} selected="selected"{/if}>___MATERIAL_THESIS_DIPLOMA___</option>
	         							<option value="dissertation"{if $item.thesis_kind == 'dissertation'} selected="selected"{/if}>___MATERIAL_THESIS_DISSERTATION___</option>
	         							<option value="postdoc"{if $item.thesis_kind == 'postdoc'} selected="selected"{/if}>___MATERIAL_THESIS_POSTDOC___</option>
	         						</select>
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_university">___MATERIAL_UNIVERSITY___<span class="required">*</span>:</label>
	         						<input id="bib_university" type="text" class="size_200" name="form_data[university]" value="{show var=$item.university}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_faculty">___MATERIAL_FACULTY___:</label>
	         						<input id="bib_faculty" type="text" class="size_200" name="form_data[faculty]" value="{show var=$item.faculty}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date7">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date7" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_manuscript" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
	         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="{show var=$item.publishing_date}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
	         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date8">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date8" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_website" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
	         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="{show var=$item.author}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url">___MATERIAL_URL___<span class="required">*</span>:</label>
	         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_url_date9">___MATERIAL_URL_DATE___:</label>
	         						<input id="bib_url_date9" type="text" class="size_200" name="form_data[url_date]" value="{show var=$item.url_date}" />
	         					</div>
	         				</div>

	         				<div id="bib_content_document" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_document_editor">___MATERIAL_BIB_DOCUMENT_EDITOR___:</label>
	         						<input id="bib_document_editor" type="text" class="size_200" name="form_data[document_editor]" value="{show var=$item.document_editor}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_document_maintainer">___MATERIAL_BIB_DOCUMENT_MAINTAINER___:</label>
	         						<input id="bib_document_maintainer" type="text" class="size_200" name="form_data[document_maintainer]" value="{show var=$item.document_maintainer}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_document_release_number">___MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER___:</label>
	         						<input id="bib_document_release_number" type="text" class="size_200" name="form_data[document_release_number]" value="{show var=$item.document_release_number}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_document_release_date">___MATERIAL_BIB_DOCUMENT_RELEASE_DATE___:</label>
	         						<input id="bib_document_release_date" class="size_200 datepicker" type="text" name="form_data[document_release_date]" value="{show var=$item.document_release_date}" />
	         					</div>
	         				</div>
	         				
	         				<div id="bib_content_foto" class="hidden">
	         					<div class="input_row">
	         						<label for="bib_foto_copyright">___MATERIAL_BIB_FOTO_COPYRIGHT___:</label>
	         						<input id="bib_foto_copyright" type="text" class="size_200" name="form_data[foto_copyright]" value="{show var=$item.foto_copyright}" />
	         					</div>

	         					<div class="input_row">
	         						<label for="bib_foto_reason">___MATERIAL_BIB_FOTO_REASON___:</label>
	         						<input id="bib_foto_reason" type="text" class="size_200" name="form_data[foto_reason]" value="{show var=$item.foto_reason}" />
	         					</div>
	         					
	         					<div class="input_row">
	         						<label for="bib_foto_date">___MATERIAL_BIB_FOTO_DATE___:</label>
	         						<input id="bib_foto_date" type="text" class="size_200 datepicker" name="form_data[foto_date]" value="{show var=$item.foto_date}" />
	         					</div>

	         					<div class="input_row">
	         						___MATERIAL_BIBLIOGRAPHIC___:
	         					</div>

								<div class="editor_content">
									<div id="common" class="ckeditor">{show var=$item.common}</div>
								</div>
	         				</div>
	         			</div>

						<div class="editor_content">
							<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						</div>
					</div>



					<div id="popup_tabs">
						<div class="tab_navigation">
							<a href="files_tab" class="pop_tab_active">___MATERIAL_FILES___</a>
							{if $popup.is_owner == true}<a href="rights_tab" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
							{if isset($popup.buzzwords)}<a href="buzzwords_tab" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
							{if isset($popup.tags)}<a href="tags_tab" class="pop_tab">___COMMON_TAGS___</a>{/if}
							{if $item.with_workflow == true}<a href="workflow_tab" class="pop_tab">___COMMON_WORKFLOW___</a>{/if}
							{if !$popup.overflow}<a href="netnavigation_tab" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>{/if}
							<div class="clear"> </div>
						</div>
						<div id="popup_tabcontent">
							{include file="popups/include/files_tab_include_html.tpl"}

							{include file="popups/include/rights_tab_include_html.tpl"}

							{include file="popups/include/buzzwords_tab_include_html.tpl"}

							{include file="popups/include/tags_tab_include_html.tpl"}

							{if $item.with_workflow == true}
								<div class="tab hidden" id="workflow_tab">
									<div class="settings_area">
										{if $item.with_workflow_traffic_light == true}
											<fieldset class="fieldset">
												<legend>___COMMON_WORKFLOW_TRAFFIC_LIGHT___</legend>

												<div class="input_row_100">
													<input id="workflow_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="3_none"{if $item.workflow_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="0_green"{if $item.workflow_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;" src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="1_yellow"{if $item.workflow_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_traffic_light]" value="2_red" {if $item.workflow_traffic_light == '2_red'} checked="checked"{/if}/>
				         							<label for="workflow_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
												<div class="input_row"><hr class="float-left hr_400" /><div class="clear"></div></div>
											</fieldset>
										{/if}


										{if $item.with_workflow_resubmission == true}
											<fieldset>
												<legend>___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE___</legend>

												<div class="input_row_100">
													<label for="workflow_resubmission">___PREFERENCES_CONFIGURATION_WORKFLOW_RESUBMISSION_VALUE___:</label>
													<input id="workflow_resubmission" type="checkbox" style="vertical-align: bottom;" name="form_data[workflow_resubmission]"{if $item.workflow_resubmission == true} checked="checked"{/if} />
													<input id="workflow_resubmission_date" class="datepicker" type="text" name="form_data[workflow_resubmission_date]" value="{show var=$item.workflow_resubmission_date}" />
												</div>


												<div class="input_row">
													___COMMON_WORKFLOW_RESUBMISSION_WHO___:
												</div>

												<div class="input_row">
													<input id="workflow_resubmission_who" class="float-left" type="radio" name="form_data[workflow_resubmission_who]" value="creator"{if $item.workflow_resubmission_who == 'creator'} checked="checked"{/if} />
													<label for="workflow_resubmission_who" class="auto_width">
														___COMMON_WORKFLOW_RESUBMISSION_CREATOR___ (<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.workflow_creator_id}">{$item.workflow_creator_fullname}</a>)
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row">
													<input id="workflow_resubmission_who" class="float-left" type="radio" name="form_data[workflow_resubmission_who]" value="modifier"{if $item.workflow_resubmission_who == 'modifier'} checked="checked"{/if} />
													<label for="workflow_resubmission_who" class="auto_width">
													___COMMON_WORKFLOW_RESUBMISSION_MODIFIER___
													{if !empty($item.workflow_modifier)}
														(
														{foreach $item.workflow_modifier as $modifier}
															{if isset($modifier.id)}
																<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$modifier.id}">{$modifier.name}</a>
															{else}
																{$modifier.name}
															{/if}

															{if !$modifier@last}, {/if}
														{/foreach}
														)
													{/if}
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row" style="margin-bottom:20px;">
													<label for="workflow_resubmission_who_additional">___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL___ (___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR___)</label>
													<input id="workflow_resubmission_who_additional" type="text" name="form_data[workflow_resubmission_who_additional]" value="{show var=$item.workflow_resubmission_who_additional}" />
												</div>

												<div class="input_row">
													___COMMON_WORKFLOW_RESUBMISSION_TRAFFIC_LIGHT___:
												</div>

												<div class="input_row_100">
													<input id="workflow_resubmission_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="3_none"{if $item.workflow_resubmission_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_resubmission_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_resubmission_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="0_green"{if $item.workflow_resubmission_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_resubmission_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_resubmission_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="1_yellow"{if $item.workflow_resubmission_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_resubmission_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_resubmission_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_resubmission_traffic_light]" value="2_red"{if $item.workflow_resubmission_traffic_light == '2_red'} checked="checked"{/if} />
				         							<label for="workflow_resubmission_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
												<div class="input_row"><hr class="float-left hr_400" /><div class="clear"></div></div>
											</fieldset>
										{/if}

										{if $item.with_workflow_validity == true}
											<fieldset>
												<legend>___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_VALUE___</legend>

												<div class="input_row_150">
													<label for="workflow_validity">___PREFERENCES_CONFIGURATION_WORKFLOW_VALIDITY_VALUE___:</label>
													<input id="workflow_validity" style="vertical-align:bottom;" type="checkbox" name="form_data[workflow_validity]"{if $item.workflow_validity_date == true} checked="checked"{/if} />
													<input id="workflow_validity_date" class="datepicker" type="text" name="form_data[workflow_validity_date]" value="{show var=$item.workflow_validity_date}" />
												</div>

												<div class="input_row">
													___COMMON_WORKFLOW_VALIDITY_WHO___:
												</div>

												<div class="input_row_150">
													<input id="workflow_validity_who" class="float-left" type="radio" name="form_data[workflow_validity_who]" value="creator"{if $item.workflow_validity_who == 'creator'} checked="checked"{/if} />
													<label for="workflow_validity_who" class="auto_width">
														___COMMON_WORKFLOW_RESUBMISSION_CREATOR___ (<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$item.workflow_creator_id}">{$item.workflow_creator_fullname}</a>)
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row">
													<input id="workflow_validity_who" class="float-left" type="radio" name="form_data[workflow_validity_who]" value="modifier"{if $item.workflow_validity_who == 'modifier'} checked="checked"{/if} />
													<label for="workflow_validity_who" class="auto_width">
													___COMMON_WORKFLOW_RESUBMISSION_MODIFIER___
													{if !empty($item.workflow_modifier)}
														(
														{foreach $item.workflow_modifier as $modifier}
															{if isset($modifier.id)}
																<a href="commsy.php?cid={$environment.cid}&mod=user&fct=detail&iid={$modifier.id}">{$modifier.name}</a>
															{else}
																{$modifier.name}
															{/if}

															{if !$modifier@last}, {/if}
														{/foreach}
														)
													{/if}
													</label>
													<div class="clear"></div>
												</div>

												<div class="input_row" style="margin-bottom:20px">
													<label for="workflow_validity_who_additional">___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL___ (___COMMON_WORKFLOW_RESUBMISSION_ADDITIONAL_SEPERATOR___)</label>
													<input id="workflow_validity_who_additional" type="text" name="form_data[workflow_validity_who_additional]" value="{show var=$item.workflow_validity_who_additional}" />
												</div>


												<div class="input_row">
													___COMMON_WORKFLOW_VALIDITY_TRAFFIC_LIGHT___:
												</div>

												<div class="input_row_100">
													<input id="workflow_validity_traffic_light_none" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="3_none"{if $item.workflow_validity_traffic_light == '3_none'} checked="checked"{/if} />
													<label for="workflow_validity_traffic_light_none">___COMMON_WORKFLOW_TRAFFIC_LIGHT_TEXT_NONE___</label>
													<div class="clear"></div>
					         					</div>

												<div class="input_row_100">
													<input id="workflow_validity_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="0_green"{if $item.workflow_validity_traffic_light == '0_green'} checked="checked"{/if} />
													<label for="workflow_validity_traffic_light_red">{$item.workflow_traffic_light_description.green}</label>
													<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_green.png" alt="{$item.workflow_traffic_light_description.green}" title="{$item.workflow_traffic_light_description.green}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_validity_traffic_light_yellow" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="1_yellow"{if $item.workflow_validity_traffic_light == '1_yellow'} checked="checked"{/if} />
				         							<label for="workflow_validity_traffic_light_yellow">{$item.workflow_traffic_light_description.yellow}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_yellow.png" alt="{$item.workflow_traffic_light_description.yellow}" title="{$item.workflow_traffic_light_description.yellow}">
													<div class="clear"></div>
					         					</div>

					         					<div class="input_row_100">
				         							<input id="workflow_validity_traffic_light_red" class="float-left" type="radio" name="form_data[workflow_validity_traffic_light]" value="2_red"{if $item.workflow_validity_traffic_light == '2_red'} checked="checked"{/if} />
				         							<label for="workflow_validity_traffic_light_red">{$item.workflow_traffic_light_description.red}</label>
				         							<img style="width:45px;"  src="{$basic.tpl_path}img/workflow_traffic_light_red.png" alt="{$item.workflow_traffic_light_description.red}" title="{$item.workflow_traffic_light_description.red}">
													<div class="clear"></div>
					         					</div>
											</fieldset>
										{/if}
									</div>
								</div>
							{/if}

							{include file="popups/include/netnavigation_tab_include_html.tpl"}

						</div>

						<div id="content_buttons">
							<div id="crt_actions_area">
								<input id="popup_button_create" class="popup_button submit" data-custom="part: 'all'" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
								{if $popup.edit}<input id="popup_button_new_version" class="popup_button submit" data-custom="part: 'version'" type="button" name="" value="___MATERIAL_VERSION_BUTTON___" />{/if}
								<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
							</div>
						</div>



					</div>
				</div>


				{/if}

			</div>


			<div class="clear"></div>
		</div>
	</div>
</div>