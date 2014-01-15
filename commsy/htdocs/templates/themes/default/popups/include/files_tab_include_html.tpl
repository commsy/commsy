<div class="tab" id="files_tab">
	<div class="settings_area">
		<div class="sa_col_left">
			<div id="files_finished"></div>
			
			<div id="files_attached">
				{foreach $item.files as $file}
					<div style="float:left;"><input type="checkbox" checked="checked" name="form_data[file_{$file@index}]" value="{$file.file_id}" /></div><div id="file_name" name="file_name">{$file.file_name}</div><br/>
				{/foreach}
			</div>
			
			<div class="uploader">
				<form method="post" action="UploadFile.php" id="myForm" enctype="multipart/form-data" >
				   <input class="fileSelector"></input>
				   {*<input type="button" class="upload popup_button" value="Upload"/>*}
				   
				   <div class="fileList"></div>
				</form>
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
</div>