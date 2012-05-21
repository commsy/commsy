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
						<div class="input_label_100">___COMMON_TITLE___<span class="required">*</span>:</div> <input type="text" value="{if isset($item.title)}{$item.title}{/if}" name="form_data[title]" class="size_400 mandatory" />
					</div>
					<div class="input_row">
						<span  class="input_label_100">___MATERIAL_BIBLIOGRAPHIC___<span class="required">*</span>:</span>
						<select name="form_data[bib_kind]" size="1" class="size_200 mandatory" >
				            <option value="form_data[none]" {if $item.bib_kind == ___MATERIAL_BIB_NOTHING___} selected="selected" {/if} >* ___MATERIAL_BIB_NOTHING___</option>
				            <option value="form_data[common]" {if $item.bib_kind == ___MATERIAL_BIB_NONE___} selected="selected" {/if}>* ___MATERIAL_BIB_NONE___</option>
				            <option value="form_data[book]" {if $item.bib_kind == ___MATERIAL_BIB_BOOK___} selected="selected" {/if}>___MATERIAL_BIB_BOOK___</option>
				            <option value="form_data[collection]" {if $item.bib_kind == ___MATERIAL_BIB_COLLECTION___} selected="selected" {/if}>___MATERIAL_BIB_COLLECTION___</option>
				            <option value="form_data[incollection]" {if $item.bib_kind == ___MATERIAL_BIB_INCOLLECTION___} selected="selected" {/if}>___MATERIAL_BIB_INCOLLECTION___</option>
				            <option value="form_data[article]" {if $item.bib_kind == ___MATERIAL_BIB_ARTICLE___} selected="selected" {/if}>___MATERIAL_BIB_ARTICLE___</option>
				            <option value="form_data[chapter]" {if $item.bib_kind == ___MATERIAL_BIB_CHAPTER___} selected="selected" {/if}>___MATERIAL_BIB_CHAPTER___</option>
				            <option value="form_data[inpaper]" {if $item.bib_kind == ___MATERIAL_BIB_INPAPER___} selected="selected" {/if}>___MATERIAL_BIB_INPAPER___</option>
				            <option value="form_data[thesis]" {if $item.bib_kind == ___MATERIAL_BIB_THESIS___} selected="selected" {/if}>___MATERIAL_BIB_THESIS___</option>
				            <option value="form_data[manuscript]" {if $item.bib_kind == ___MATERIAL_BIB_MANUSCRIPT___} selected="selected" {/if}>___MATERIAL_BIB_MANUSCRIPT___</option>
				            <option value="form_data[website]" {if $item.bib_kind == ___MATERIAL_BIB_WEBSITE___} selected="selected" {/if}>___MATERIAL_BIB_WEBSITE___</option>
				            <option value="form_data[document]" {if $item.bib_kind == ___MATERIAL_BIB_DOCUMENT___} selected="selected" {/if}>___MATERIAL_BIB_DOCUMENT___</option>
         				</select>
         			</div>
         			
         			{* bibliographic data *}
         			<div id="bibliographic">
         				<div id="bib_content_common">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_AUTHORS___:</label>
         						<input id="bib_author" type="text" class="size_200" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___:</label>
         						<input id="bib_publishing_date" type="text" class="size_200" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_common">___MATERIAL_BIBLIOGRAPHIC___:</label>
         						<input id="bib_common" type="text" class="size_200" name="form_data[common]" value="" />
         					</div>
         					
         					<div class="editor_content">
								<div id="popup_ckeditor">{*{if isset($item.description)}{$item.description}{/if}*}</div>
								<input type="hidden" id="popup_ckeditor_content" name="form_data[common]" value=""/>
							</div>
         				</div>
         				
         				<div id="bib_content_book">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
         						<input id="bib_publisher" type="text" class="size_200 mandatory" name="form_data[publisher]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200 mandatory" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_series">___MATERIAL_SERIES___:</label>
         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_collection">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
         						<input id="bib_publisher" type="text" class="size_200 mandatory" name="form_data[publisher]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200 mandatory" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_series">___MATERIAL_SERIES___:</label>
         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_incollection">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_editor">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
         						<input id="bib_editor" type="text" class="size_200 mandatory" name="form_data[editor]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_booktitle">___MATERIAL_BOOKTITLE___<span class="required">*</span>:</label>
         						<input id="bib_booktitle" type="text" class="size_200 mandatory" name="form_data[booktitle]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200 mandatory" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
         						<input id="bib_publisher" type="text" class="size_200 mandatory" name="form_data[publisher]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_series">___MATERIAL_SERIES___:</label>
         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_article">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_AUTHORS___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_journal">___MATERIAL_JOURNAL___<span class="required">*</span>:</label>
         						<input id="bib_journal" type="text" class="size_200 mandatory" name="form_data[journal]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_issue">___MATERIAL_ISSUE___:</label>
         						<input id="bib_issue" type="text" class="size_200" name="form_data[issue]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_pages">___MATERIAL_PAGES___<span class="required">*</span>:</label>
         						<input id="bib_pages" type="text" class="size_200 mandatory" name="form_data[pages]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200 mandatory" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___<span class="required">*</span>:</label>
         						<input id="bib_publisher" type="text" class="size_200 mandatory" name="form_data[publisher]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_issn">___MATERIAL_ISSN___:</label>
         						<input id="bib_issn" type="text" class="size_200" name="form_data[issn]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_chapter">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___<span class="required">*</span>:</label>
         						<input id="bib_address" type="text" class="size_200 mandatory" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_edition">___MATERIAL_EDITION___:</label>
         						<input id="bib_edition" type="text" class="size_200" name="form_data[edition]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_series">___MATERIAL_SERIES___:</label>
         						<input id="bib_series" type="text" class="size_200" name="form_data[series]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_volume">___MATERIAL_VOLUME___:</label>
         						<input id="bib_volume" type="text" class="size_200" name="form_data[volume]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_isbn">___MATERIAL_ISBN___:</label>
         						<input id="bib_isbn" type="text" class="size_200" name="form_data[isbn]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_inpaper">
         					<div class="input_row">
         						<label for="bib_author">___MATERIAL_EDITOR___<span class="required">*</span>:</label>
         						<input id="bib_author" type="text" class="size_200 mandatory" name="form_data[author]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publishing_date">___MATERIAL_YEAR___<span class="required">*</span>:</label>
         						<input id="bib_publishing_date" type="text" class="size_200 mandatory" name="form_data[publishing_date]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_journal">___MATERIAL_JOURNAL___<span class="required">*</span>:</label>
         						<input id="bib_journal" type="text" class="size_200 mandatory" name="form_data[journal]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_issue">___MATERIAL_ISSUE___:</label>
         						<input id="bib_issue" type="text" class="size_200" name="form_data[issue]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_pages">___MATERIAL_PAGES___<span class="required">*</span>:</label>
         						<input id="bib_pages" type="text" class="size_200 mandatory" name="form_data[pages]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_address">___MATERIAL_ADDRESS___:</label>
         						<input id="bib_address" type="text" class="size_200" name="form_data[address]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_publisher">___MATERIAL_PUBLISHER___:</label>
         						<input id="bib_publisher" type="text" class="size_200" name="form_data[publisher]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url">___MATERIAL_URL___:</label>
         						<input id="bib_url" type="text" class="size_200" name="form_data[url]" value="" />
         					</div>
         					
         					<div class="input_row">
         						<label for="bib_url_date">___MATERIAL_URL_DATE___:</label>
         						<input id="bib_url_date" type="text" class="size_200" name="form_data[url_date]" value="" />
         					</div>
         				</div>
         				
         				<div id="bib_content_thesis">{*
         					 $thesis_kinds = array();
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_TERM'),
				                                       'value' => 'term');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_BACHELOR'),
				                                       'value' => 'bachelor');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_MASTER'),
				                                       'value' => 'master');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_EXAM'),
				                                       'value' => 'exam');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_DIPLOMA'),
				                                       'value' => 'diploma');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_DISSERTATION'),
				                                       'value' => 'dissertation');
				               $thesis_kinds[] = array('text'  => $this->_translator->getMessage('MATERIAL_THESIS_POSTDOC'),
				                                       'value' => 'postdoc');
				               $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
				               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,5,true);
				               $this->_form->addSelect('thesis_kind',$thesis_kinds,'',$this->_translator->getMessage('MATERIAL_THESIS_KIND'),$this->_translator->getMessage('MATERIAL_THESIS_KIND_DESC'), 1, false,true,false,'','','','',24.8);
				               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
				               $this->_form->addTextField('university','',$this->_translator->getMessage('MATERIAL_UNIVERSITY'),$this->_translator->getMessage('MATERIAL_UNIVERSITY_DESC'),200,35,true);
				               $this->_form->addTextField('faculty','',$this->_translator->getMessage('MATERIAL_FACULTY'),$this->_translator->getMessage('MATERIAL_FACULTY_DESC'),200,35,false);
				            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
				            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
         				*}</div>
         				
         				<div id="bib_content_manuscript">{*
         					$this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
				               $this->_form->addTextField('publishing_date','',$this->_translator->getMessage('MATERIAL_YEAR'),$this->_translator->getMessage('MATERIAL_YEAR'),4,5,true);
				               $this->_form->addTextField('address','',$this->_translator->getMessage('MATERIAL_ADDRESS'),$this->_translator->getMessage('MATERIAL_ADDRESS_DESC'),200,35,true);
				            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35);
				            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
         				*}</div>
         				
         				<div id="bib_content_website">{*
         					 $this->_form->addTextField('author','',$this->_translator->getMessage('MATERIAL_AUTHORS'),$this->_translator->getMessage('MATERIAL_AUTHORS_DESC'),200,35,true);
				            $this->_form->addTextField('url','',$this->_translator->getMessage('MATERIAL_URL'),'',200,35,true);
				            $this->_form->addTextField('url_date','',$this->_translator->getMessage('MATERIAL_URL_DATE'),'',10,20);
         				*}</div>
         				
         				<div id="bib_content_document">{*
         					$this->_form->addTextField('document_editor','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_EDITOR'),200,35,false);
			               $this->_form->addTextField('document_maintainer','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_MAINTAINER'),200,35,false);
			               $this->_form->addTextField('document_release_number','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_NUMBER'),200,35,false);
			               $this->_form->addTextField('document_release_date','',$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE'),$this->_translator->getMessage('MATERIAL_BIB_DOCUMENT_RELEASE_DATE'),200,35,false);
         				*}</div>
         			</div>
         			
         			{*
         			{if $item.bib_kind == }
         			{elsif}
         			{else}
         			{/if}
         			*}

					<div class="editor_content">
						<div id="popup_ckeditor">{if isset($item.description)}{$item.description}{/if}</div>
						<input type="hidden" id="popup_ckeditor_content" name="form_data[description]" value=""/>
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
									___DATES_HIDING_DAY___ <input type="text" name="form_data[dayStart]" value="{if isset($item.activating_date)}{$item.activating_date}{/if}"/>
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