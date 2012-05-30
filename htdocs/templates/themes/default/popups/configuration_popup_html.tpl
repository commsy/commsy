{* include template functions *}
{include file="include/functions.tpl" inline}

<div id="popup_wrapper">
	<div id="popup_my_area">
		<div id="popup_frame_my_area">
			<div id="popup_inner_my_area">

				<div id="popup_pagetitle">
					<a id="popup_close" href="" title="___COMMON_CLOSE___"><img src="{$basic.tpl_path}img/popup_close.gif" alt="___COMMON_CLOSE___" /></a>
					<h2>
						___COMMON_PAGETITLE_CONFIGURATION___
					</h2>
					<div class="clear"> </div>
				</div>
				<div id="popup_content_wrapper">
					<div id="profile_content_row_three">
						<div class="tab_navigation">
							<a href="" class="pop_tab_active">___CONFIG_META_TITLE___</a>
							<a href="" class="pop_tab">___COMMON_ACCOUNTS___</a>
							<a href="" class="pop_tab">___CONFIG_MODERATION_TITLE___</a>
							<a href="" class="pop_tab">___CONFIGURATION_PLUGIN_LINK___</a>
							<a href="" class="pop_tab">___HOME_EXTRA_TOOLS___</a>

							<div class="clear"> </div>
						</div>

						<div id="popup_tabcontent">
							<div class="tab" id="account">
								<div id="content_row_three">
									<fieldset>
								<div class="input_row_100">
									<label for="room_name">___COMMON_ROOM_NAME___<span class="required">*</span>:</label>
									<input id="room_name" type="text" class="size_200" name="form_data[room_name]" value="{show var=$popup.room.room_name}"/>
									<div class="input_container_180" style="margin-left:96px; margin-bottom:10px;">
										<input id="room_show_name" type="checkbox" name="form_data[room_show_name]"{if $popup.room.room_show_name == true} checked="checked"{/if} />
										<span for="room_show_name">___PREFERENCES_SHOW_TITLE_OPTION___</span>
									</div>
								</div>

								<div class="input_row_100">
									<label for="room_language">___CONTEXT_LANGUAGE___<span class="required">*</span>:</label>
									<select class="size_200" id="room_language" name="form_data['language]">
										{foreach $popup.room.languages as $language}
											<option value="{$language.value}"{if $language.value == $popup.room.language} selected="selected"{/if}{if isset($language.disabled) && $language.disabled == true} disabled="disabled"{/if}>
												{$language.text}
											</option>
										{/foreach}
									</select>
								</div>

								<div class="input_row_100">
									<label for="room_logo">___LOGO_UPLOAD___:</label>
									<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
										<input type="hidden" name="module" value="configuration" />
										<input type="hidden" name="additional[tab]" value="room" />
										<input id="room_logo" type="file" class="size_150 float-left" name="form_data[picture]" accept="image/*" />
									</form>
									<div class="clear"></div>
								</div>

								{if isset($popup.room.logo)}
									<div class="input_row">
										<div class="input_container_180">
											<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.room.logo}" alt="___USER_PICTURE_UPLOADFILE___" />
										</div>
									</div>

									<div class="input_row">
										<div class="input_container_180">
											<input id="delete_logo" class="float-left" type="checkbox" name="form_data[delete_logo]" value="1"/>
											<label for="delete_logo" class="float-left">___LOGO_DELETE_OPTION___</label>
											<div class="clear"></div>
										</div>
									</div>
								{/if}
								
								{if isset($popup.room.time_array)}
									<div class="input_row_100">
										{i18n tag=COMMON_TIME_NAME context=portal}
										
										{foreach $popup.room.time_array as $time}
											<div class="input_container_180">
												<input id="room_time_{$time.value}" type="checkbox" name="form_data[room_time][{$time.value}]" value="1"{if $time.checked == true} checked="checked"{/if} />
												<label for="room_time_{$time.value}">{$time.text}</label>
												<div class="clear"></div>
											</div>
										{/foreach}					
									</div>
								{/if}
								
								{* assignment *}
								{if $popup.room.in_project_room == true}
									{if $popup.room.link_status == 'optional'}
										
									
									
									
									{*
									if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,false,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }	
               						*}
									
									
									{else}
									
									
									{*
									
									if ( !empty ($this->_shown_community_room_array) ) {
                  $this->_form->addCheckBoxGroup('communityroomlist',$this->_shown_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'',false,false);
                  $this->_form->combine();
               }
               if(count($this->_community_room_array) > 2){
                  $this->_form->addSelect('communityrooms',$this->_community_room_array,'',$this->_translator->getMessage('PREFERENCES_COMMUNITY_ROOMS'),'', 1, false,true,false,'','','','',16);
                  $this->_form->combine('horizontal');
                  $this->_form->addButton('option',$this->_translator->getMessage('PREFERENCES_ADD_COMMUNITY_ROOMS_BUTTON'),'','',100);
               }
               						*}
									{/if}
								
								{elseif $popup.room.in_community_room == true}
									<div class="input_row_100">
										___PREFERENCES_ROOM_ASSIGMENT___
										
										<div class="input_container_180">
											<input id="room_assignment_open" type="radio" name="form_data[room_assignment]" value="1"{if $time.checked == true} checked="checked"{/if} />
											<label for="room_assignment_open">___COMMON_ASSIGMENT_ON___</label>
											<div class="clear"></div>
										</div>
										<div class="input_container_180">
											<input id="room_assignment_closed" type="radio" name="form_data[room_assignment]" value="1"{if $time.checked == true} checked="checked"{/if} />
											<label for="room_assignment_closed">___COMMON_ASSIGMENT_OFF___</label>
											<div class="clear"></div>
										</div>
									</div>
								{/if}
								</fieldset>

								{*

      
      
      
      
      /***************Farben************/
      if ( !empty($this->_form_post['color_choice']) and $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_OWN' ) {
          $this->_form->addEmptyLine();
      }
      $this->_form->addSelect( 'color_choice',
                               $this->_array_info_text,
                               '',
                               $this->_translator->getMessage('CONFIGURATION_COLOR_FORM_CHOOSE_TEXT'),
                               '',
                               '',
                               '',
                               '',
                               true,
                               $this->_translator->getMessage('COMMON_CHOOSE_BUTTON'),
                               'option',
                               '',
                               '',
                               '16',
                               true);
      if ( !empty($this->_form_post['color_choice']) ) {
         if ( $this->_form_post['color_choice']== 'COMMON_COLOR_DEFAULT' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_default.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_DEFAULT').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']== 'COMMON_COLOR_SCHEMA_1' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_1.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_1').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_3' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_3.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_3').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_2' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_2.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_2').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_4' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_4.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_4').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_5' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_5.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_5').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_6' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_6.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_6').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_7' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_7.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_7').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_8' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_8.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_8').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_9' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_9.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_9').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_10' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_10.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_10').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_11' ) {
            $this->_form->combine();
            $desc = '<img src="images/commsyicons/color_themes/color_schema_11.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_11').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_12'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_12.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_12').'" style=" border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_13'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_13.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_13').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_14'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_14.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_14').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_15'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_15.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_15').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_16'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_16.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_16').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_17'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_17.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_17').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_18'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_18.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_18').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_19'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_19.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_19').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_20'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_20.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_20').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_21'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_21.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_21').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_22'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_22.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_22').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_23'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_23.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_23').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_24'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_24.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_24').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_25'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_25.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_25').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_26'  ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_26.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_26').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $this->_form_post['color_choice']=='COMMON_COLOR_SCHEMA_OWN' ) {
            $this->_form->addTextField('color_1','',$this->_translator->getMessage('COMMON_COLOR_101'),'','',10);
            $this->_form->addTextField('color_2','',$this->_translator->getMessage('COMMON_COLOR_102'),'','',10);
            $this->_form->addTextField('color_3','',$this->_translator->getMessage('COMMON_COLOR_103'),'','',10);
            $this->_form->addTextField('color_31','',$this->_translator->getMessage('COMMON_COLOR_1031'),'','',10);
            $this->_form->addTextField('color_32','',$this->_translator->getMessage('COMMON_COLOR_1032'),'','',10);
            $this->_form->addTextField('color_4','',$this->_translator->getMessage('COMMON_COLOR_104'),'','',10);
            $this->_form->addTextField('color_5','',$this->_translator->getMessage('COMMON_COLOR_105'),'','',10);
            $this->_form->addTextField('color_6','',$this->_translator->getMessage('COMMON_COLOR_106'),'','',10);
            $this->_form->addTextField('color_7','',$this->_translator->getMessage('COMMON_COLOR_107'),'','',10);
            $this->_form->addRoomLogo('bgimage',
                             '',
                             $this->_translator->getMessage('BG_IMAGE_UPLOAD'),
                             $this->_translator->getMessage('BG_IMAGE_UPLOAD_DESC'),
                             '',
                             false,
                             '4em'
                             );
            $this->_form->combine();
            $this->_form->addCheckbox('bg_image_repeat',1,'',$this->_translator->getMessage('CONFIGURATION_BGIMAGE_REPEAT'),$this->_translator->getMessage('CONFIGURATION_BGIMAGE_REPEAT'));
            $this->_form->addHidden('bgimage_hidden','');
            $this->_form->addHidden('with_bgimage',$this->_with_logo);
            $this->_form->addText('colorpicker','','<br/><br/><INPUT class=color value=#45D7DD>');
            $this->_form->addEmptyLine();
         }
      } else{
         $this->_form->combine();
         $context_item = $this->_environment->getCurrentContextItem();
         $color = $context_item->getColorArray();
         if ( $color['schema']== 'DEFAULT' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_default.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_DEFAULT').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ($color['schema']== 'SCHEMA_1' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_1.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_1').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_3' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_3.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_3').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_2' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_2.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_2').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_4' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_4.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_4').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_5' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_5.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_5').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_6' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_6.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_6').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_7' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_7.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_7').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_8' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_8.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_8').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_9' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_9.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_9').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_10' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_10.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_10').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_11' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_11.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_11').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_12' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_12.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_12').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_13' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_13.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_13').'" style=" border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_14' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_14.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_14').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_15' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_15.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_15').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_16' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_16.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_16').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_17' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_17.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_17').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_18' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_18.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_18').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_19' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_19.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_19').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_20' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_20.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_20').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_21' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_21.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_21').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_22' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_22.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_22').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_23' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_23.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_23').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_24' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_24.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_24').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_25' ) {
            $desc = '<img src="images/commsyicons/color_themes/color_schema_25.gif" alt="'.$this->_translator->getMessage('COMMON_COLOR_SCHEMA_25').'" style="border:1px solid black; vertical-align: middle;"/>';
            $this->_form->addText('example',$this->_translator->getMessage('COMMON_COLOR_EXAMPLE'),$desc);
         }elseif ( $color['schema']=='SCHEMA_OWN' ) {
            $this->_form->addTextField('color_1','',$this->_translator->getMessage('COMMON_COLOR_101'),'','',10);
            $this->_form->addTextField('color_2','',$this->_translator->getMessage('COMMON_COLOR_102'),'','',10);
            $this->_form->addTextField('color_3','',$this->_translator->getMessage('COMMON_COLOR_103'),'','',10);
            $this->_form->addTextField('color_31','',$this->_translator->getMessage('COMMON_COLOR_1031'),'','',10);
            $this->_form->addTextField('color_32','',$this->_translator->getMessage('COMMON_COLOR_1032'),'','',10);
            $this->_form->addTextField('color_4','',$this->_translator->getMessage('COMMON_COLOR_104'),'','',10);
            $this->_form->addTextField('color_5','',$this->_translator->getMessage('COMMON_COLOR_105'),'','',10);
            $this->_form->addTextField('color_6','',$this->_translator->getMessage('COMMON_COLOR_106'),'','',10);
            $this->_form->addTextField('color_7','',$this->_translator->getMessage('COMMON_COLOR_107'),'','',10);
            $this->_form->addRoomLogo('bgimage',
                             '',
                             $this->_translator->getMessage('BG_IMAGE_UPLOAD'),
                             $this->_translator->getMessage('BG_IMAGE_UPLOAD_DESC'),
                             '',
                             false,
                             '4em'
                             );
            $this->_form->combine();
            $this->_form->addCheckbox('bg_image_repeat',1,'',$this->_translator->getMessage('CONFIGURATION_BGIMAGE_REPEAT'),$this->_translator->getMessage('CONFIGURATION_BGIMAGE_REPEAT'));
            $this->_form->addHidden('bgimage_hidden','');
            $this->_form->addHidden('with_bgimage',$this->_with_logo);
            $this->_form->addText('colorpicker','','<br/><br/><INPUT class=color value=#45D7DD>');
            $this->_form->addEmptyLine();
         }
      }

      $languageArray = array();
      $tmpArray = $this->_environment->getAvailableLanguageArray();
      $zaehler = 0;
      foreach ($tmpArray as $item){
         switch ( mb_strtoupper($item, 'UTF-8') ){
            case 'DE':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('DE');
               break;
            case 'EN':
               $languageArray[$zaehler]['text']= $this->_translator->getMessage('EN');
               break;
            default:
               break;
         }
         $languageArray[$zaehler]['value']= $item;
         $zaehler++;
      }
      $context_item = $this->_environment->getCurrentContextItem();
      $html_status = $context_item->getHtmlTextAreaStatus();
      if ($html_status =='1'){
          $html_status ='2';
      }
      $this->_form->addTextArea('description','',$this->_translator->getMessage('CONFIGURATION_ROOM_DESCRIPTION'),'','','5','virtual',false,false,true,$html_status);

      $radio_values = array();
      $radio_values[0]['text'] = $this->_translator->getMessage('CONFIGURATION_RSS_YES');
      $radio_values[0]['value'] = 'yes';
      $radio_values[1]['text'] = $this->_translator->getMessage('CONFIGURATION_RSS_NO');
      $radio_values[1]['value'] = 'no';
      $this->_form->addRadioGroup('rss',$this->_translator->getMessage('CONFIGURATION_RSS'),'',$radio_values,'',true,false);

      // specials in private room - E-Mail to CommSy
      global $c_email_upload;
      if ($c_email_upload && $this->_environment->inPrivateRoom()) {
      	global $c_email_upload_email_account;
         $this->_form->addCheckbox('email_to_commsy',
                                   'value',
                                   false,
                                   $this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY'),
                                   $this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_CHECKBOX', $c_email_upload_email_account)
                                   );
         $this->_form->combine();
         $this->_form->addTextField('email_to_commsy_secret','',$this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_SECRET'),'',60,48);
         $this->_form->combine();
         $this->_form->addText('email_to_commsy_text','',$this->_translator->getMessage('PRIVATE_ROOM_EMAIL_TO_COMMSY_TEXT', $this->_translator->getMessage('EMAIL_TO_COMMSY_PASSWORD'), $this->_translator->getMessage('EMAIL_TO_COMMSY_ACCOUNT')));
      }

      /******** buttons***********/
      $this->_form->addButtonBar('option',$this->_translator->getMessage('PREFERENCES_SAVE_BUTTON'),'',$this->_translator->getMessage('COMMON_DELETE_ROOM'));

								*}







								
								</div>
							</div>

							<div class="tab hidden" id="user">
								<div id="content_row_three">
									<fieldset>
										<legend>Allgemein</legend>

										<div class="input_row">
											<label for="data_title">___USER_TITLE___:</label>
											<input id="data_title" type="text" class="size_200 float-left" name="form_data[title]" value="{show var=$popup.form.user.title}" />
											<input id="data_title_all" type="checkbox" class="float-left" name="form_data[title_all]" />
											<label for="data_title_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_birthday">___USER_BIRTHDAY___:</label>
											<input id="data_birthday" type="text" class="size_200 float-left datepicker" name="form_data[birthday]" value="{show var=$popup.form.user.birthday}" />
											<input id="data_birthday_all" type="checkbox" class="float-left" name="form_data[birthday_all]" />
											<label for="data_birthday_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_picture">___USER_PICTURE_UPLOADFILE___:</label>
											<form id="picture_upload" action="commsy.php?cid={$environment.cid}&mod=ajax&fct=popup&action=save" method="post">
												<input type="hidden" name="module" value="profile" />
												<input type="hidden" name="additional[tab]" value="user_picture" />
												<input id="data_picture" type="file" class="size_200 float-left" name="form_data[picture]" accept="image/*" />
											</form>
											<div class="clear"></div>
										</div>

										{if !empty($popup.form.user.picture)}
											<div class="input_row">
												<div class="input_container_180">
													<img src="commsy.php?cid={$environment.cid}&mod=picture&fct=getfile&picture={$popup.form.user.picture}" alt="___USER_PICTURE_UPLOADFILE___" />
												</div>
											</div>

											<div class="input_row">
												<div class="input_container_180">
													<input id="delete_picture" class="float-left" type="checkbox" name="form_data[delete_picture]" value="1"/>
													<label for="delete_picture" class="float-left">___USER_DEL_PIC_BUTTON___:</label>
													<div class="clear"></div>
												</div>
											</div>
										{/if}

										<div class="input_row">
											<div class="input_container_180">
												<input id="data_picture_all" type="checkbox" class="float-left" name="form_data[picture_all]" />
												<label for="data_picture_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
												<div class="clear"></div>
											</div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Kontakt</legend>

										<div class="input_row">
											<label for="data_mail">___USER_EMAIL___</label>
											<input id="data_mail" type="text" class="mandatory size_200 float-left" name="form_data[mail]" value="{show var=$popup.form.user.mail}" />
											<input id="data_mail_all" type="checkbox" class="float-left" name="form_data[mail_all]" />
											<label for="data_mail_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_telephone">___USER_TELEPHONE___:</label>
											<input id="data_telephone" type="text" class="size_200 float-left" name="form_data[telephone]" value="{show var=$popup.form.user.telephone}" />
											<input id="data_telephone_all" type="checkbox" class="float-left" name="form_data[telephone_all]" />
											<label for="data_telephone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_cellularphone">___USER_CELLULARPHONE___:</label>
											<input id="data_cellularphone" type="text" class="size_200 float-left" name="form_data[cellularphone]" value="{show var=$popup.form.user.cellularphone}" />
											<input id="data_cellularphone_all" type="checkbox" class="float-left" name="form_data[cellularphone_all]" />
											<label for="data_cellularphone_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Adresse</legend>

										<div class="input_row">
											<label for="data_street">___USER_STREET___:</label>
											<input id="data_street" type="text" class="size_200 float-left" name="form_data[street]" value="{show var=$popup.form.user.street}" />
											<input id="data_street_all" type="checkbox" class="float-left" name="form_data[street_all]" />
											<label for="data_street_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_zipcode">___USER_ZIPCODE___:</label>
											<input id="data_zipcode" type="text" class="size_200 float-left" name="form_data[zipcode]" value="{show var=$popup.form.user.zipcode}" />
											<input id="data_zipcode_all" type="checkbox" class="float-left" name="form_data[zipcode_all]" />
											<label for="data_zipcode_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_city">___USER_CITY___:</label>
											<input id="data_city" type="text" class="size_200 float-left" name="form_data[city]" value="{show var=$popup.form.user.city}" />
											<input id="data_city_all" type="checkbox" class="float-left" name="form_data[city_all]" />
											<label for="data_city_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_room">___USER_ROOM___:</label>
											<input id="data_room" type="text" class="size_200 float-left" name="form_data[room]" value="{show var=$popup.form.user.room}" />
											<input id="data_room_all" type="checkbox" class="float-left" name="form_data[room_all]" />
											<label for="data_room_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Organisation</legend>

										<div class="input_row">
											<label for="data_organisation">___USER_ORGANISATION___:</label>
											<input id="data_organisation" type="text" class="size_200 float-left" name="form_data[organisation]" value="{show var=$popup.form.user.organisation}" />
											<input id="data_organisation_all" type="checkbox" class="float-left" name="form_data['organisation_all]"/>
											<label for="data_organisation_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_position">___USER_POSITION___:</label>
											<input id="data_position" type="text" class="size_200 float-left" name="form_data[position]" value="{show var=$popup.form.user.position}" />
											<input id="data_position_all" type="checkbox" class="float-left" name="form_data[position_all]" />
											<label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Messenger</legend>

										<div class="input_row">
											<div>
												___USER_MESSENGER_NUMBERS_TEXT___
											</div>
										</div>

										<div class="input_row">
											<label for="data_icq">___USER_ICQ___:</label>
											<input id="data_icq" type="text" class="size_200 float-left" name="form_data[icq]" value="{show var=$popup.form.user.icq}" />
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_msn">___USER_MSN___:</label>
											<input id="data_msn" type="text" class="size_200 float-left" name="form_data[msn]" value="{show var=$popup.form.user.msn}" />
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_skype">___USER_SKYPE___:</label>
											<input id="data_skype" type="text" class="size_200 float-left" name="form_data[sykpe]" value="{show var=$popup.form.user.skype}" />
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_yahoo">___USER_YAHOO___:</label>
											<input id="data_yahoo" type="text" class="size_200 float-left" name="form_data[yahoo]" value="{show var=$popup.form.user.yahoo}" />
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="data_jabber">___USER_JABBER___:</label>
											<input id="data_jabber" type="text" class="size_200 float-left" name="form_data[jabber]" value="{show var=$popup.form.user.jabber}" />
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<div class="input_container_180">
												<input id="data_messenger_all" type="checkbox" class="float-left" name="form_data[messenger_all]" />
												<label for="data_messenger_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
												<div class="clear"></div>
											</div>
										</div>
									</fieldset>

									<fieldset>
										<legend>Sonstiges</legend>

										<div class="input_row">
											<label for="data_homepage">___USER_HOMEPAGE___:</label>
											<input id="data_homepage" type="text" class="size_200 float-left" name="form_data[homepage]" value="{show var=$popup.form.user.homepage}" />
											<input id="data_homepage_all" type="checkbox" class="float-left" name="form_data[homepage_all]" />
											<label for="data_homepage_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<label for="description">___USER_DESCRIPTION___:</label>
											<div class="clear"></div>
										</div>

										<div class="input_row">
											<div class="editor_content">
												<div id="description" class="ckeditor">{if isset($popup.form.user.description)}{$popup.form.user.description}{/if}</div>
											</div>
										</div>

										<div class="input_row">
											<input id="data_position_all" type="checkbox" class="float-left" name="form_data[description_all]" />
											<label for="data_position_all" class="float-left">___USER_CHANGE_IN_ALL_ROOMS___</label>
											<div class="clear"></div>
										</div>
									</fieldset>

									<div class="input_row">
										<div class="input_container_180">
											<input id="submit" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
										</div>
									</div>
								</div>
							</div>

							<div class="tab hidden" id="newsletter">
								<div id="content_row_three">
									<div class="input_row">
										<label for="newsletter">___USER_STATUS___:</label>

										<div class="input_container_180">
											<input id="newsletter" type="radio" value="2" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '2'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_NONE___<br/>
											<input type="radio" value="3" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '3'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_WEEKLY___<br/>
											<input type="radio" value="1" name="form_data[newsletter]"{if $popup.form.newsletter.newsletter == '1'} checked="checked"{/if} /> ___CONFIGURATION_NEWSLETTER_DAILY___
										</div>
									</div>

									<div class="input_row">
										___CONFIGURATION_NEWSLETTER_NOTE___
									</div>

									<div class="input_row">
										<div class="input_container_180">
											<input id="submit" type="button" name="save" value="___PREFERENCES_SAVE_BUTTON___"/>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="clear"></div>
		</div>
	</div>
</div>