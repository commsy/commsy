{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_edit">
		<div id="popup_frame">
			<div id="popup_inner">


				<div id="popup_title">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>{if $popup.edit == false}___COMMON_ENTER_NEW_MATERIAL___{else}___COMMON_MATERIAL_EDIT___{/if}</h2>
					<div class="clear"> </div>
				</div>


				<div id="popup_content">
					<div class="input_row">
						<div class="input_label_100">___COMMON_TITLE___<span class="required">*</span>:</div> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400" />
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
         				</select>
         			</div>
         			
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="{show var=$item.url}" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="{show var=$item.address}" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_dat}" />
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" class="size_200 datepicker" type="text" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="{show var=$item.url_date}" />
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
         			</div>
         			
					<div class="editor_content">
						<div id="description" class="ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
					</div>
				</div>



				<div id="popup_tabs">
					<div class="tab_navigation">
						<a href="" class="pop_tab_active">___MATERIAL_FILES___</a>
						{if $popup.is_owner == true}<a href="" class="pop_tab">___COMMON_RIGHTS___</a>{/if}
						{if isset($popup.buzzwords)}<a href="" class="pop_tab">___COMMON_BUZZWORDS___</a>{/if}
						{if isset($popup.tags)}<a href="" class="pop_tab">___COMMON_TAGS___</a>{/if}
						<a href="" id="popup_netnavigation_attach_new" class="pop_tab">___COMMON_ATTACHED_ENTRIES___</a>
						<div class="clear"> </div>
					</div>
					<div id="popup_tabcontent">
						<div class="settings_area">
							<div class="sa_col_left">
								<div id="file_finished"></div>
								<input id="uploadify" name="uploadify" type="file" />

								<div>
									<a id="uploadify_doUpload">
										<img src="{$basic.tpl_path}img/uploadify/button_upload_{$environment.lang}.png" />
									</a>
									<a id="uploadify_clearQuery">
										<img src="{$basic.tpl_path}img/uploadify/button_abort_{$environment.lang}.png" />
									</a>
								</div>
							</div>

							<div class="sa_col_right">
								<p class="info_notice">
								<img src="{$basic.tpl_path}img/file_info_icon.gif" alt="Info"/>
								{i18n tag=MATERIAL_MAX_FILE_SIZE param1=$popup.general.max_upload_size}
								</p>
							</div>

							<div class="clear"> </div>
						</div>
						{if $popup.is_owner == true}
							<div class="settings_area hidden">
								{if $popup.config.with_activating}
									<input type="checkbox" name="form_data[private_editing]" value="1"{if $item.private_editing == true} checked="checked"{/if}/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}<br/>
									<input type="checkbox" name="form_data[hide]" value="1"{if $item.is_not_activated} checked="checked"{/if}>___COMMON_HIDE___
									___DATES_HIDING_DAY___ <input class="datepicker" type="text" name="form_data[dayStart]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
									___DATES_HIDING_TIME___ <input type="text" name="form_data[timeStart]" value="{if isset($item.activating_time)}{$item.activating_time}{/if}"/>

								{else}
									<input type="radio" name="form_data[public]" value="1" checked="checked"/>___RUBRIC_PUBLIC_YES___<br/>
									<input type="radio" name="form_data[public]" value="0"/>{i18n tag=RUBRIC_PUBLIC_NO param1=$popup.user.fullname}
								{/if}
							</div>
						{/if}

						{if isset($popup.buzzwords)}
							<div class="settings_area hidden">
								<ul class="popup_buzzword_list">
									{foreach $popup.buzzwords as $buzzword}
										<li id="buzzword_{$buzzword.item_id}" class="ui-state-default popup_buzzword_item">
											<input type="checkbox"{if $buzzword.assigned == true} checked="checked"{/if}/>{$buzzword.name}
										</li>
									{/foreach}
									<div class="clear"></div>
								</ul>
								<div class="clear"></div>
							</div>
						{/if}

						{if isset($popup.tags)}
							<div class="settings_area hidden">
								<div id="tag_tree">
									{block name=sidebar_tagbox_treefunction}
										{* Tags Function *}
										{function name=tag_tree level=0}
											<ul>
											{foreach $nodes as $node}
												<li	id="node_{$node.item_id}"
													{if $node.children|count > 0}class="folder"{/if}>
													{if $node.match == true}<b>{$node.title}</b>
													{else}{$node.title}
													{/if}
												{if $node.children|count > 0}	{* recursive call *}
													{tag_tree nodes=$node.children level=$level+1}
												{/if}
											{/foreach}
											</ul>
										{/function}
									{/block}

									{* call function *}
									{tag_tree nodes=$popup.tags}
								</div>
							</div>
						{/if}

						{include file="popups/include/edit_attach_items_include_html.tpl"}

					</div>



					<div id="content_buttons">
						<div id="crt_actions_area">
							<input id="popup_button_create" class="popup_button" type="button" name="" value="{if $popup.edit == false}___COMMON_SAVE_BUTTON___{else}___COMMON_CHANGE_BUTTON___{/if}" />
							<input id="popup_button_abort" class="popup_button" type="button" name="" value="___COMMON_CANCEL_BUTTON___" />
						</div>
					</div>



				</div>
			</div>


			<div class="clear"></div>
		</div>
	</div>
</div>