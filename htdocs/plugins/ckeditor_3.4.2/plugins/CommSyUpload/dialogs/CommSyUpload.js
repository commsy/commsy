/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.dialog.add( 'CommSyUpload', function( editor )
{
	var config = editor.config,
		lang = editor.lang.smiley,
		images = config.smiley_images,
		columns = 8,
		i;

	/**
	 * Simulate "this" of a dialog for non-dialog events.
	 * @type {CKEDITOR.dialog}
	 */
	var dialog;
	//var onClick = function( evt )
	//{
	//	var target = evt.data.getTarget(),
	//		targetID = target.getId();
	//
	//	if(targetID != null){
	//		var image = CKEDITOR.dom.element.createFromHtml('<span>(:image '+targetID+':)</span>');
	//		editor.insertElement(image);
	//
	//		dialog.hide();
	//		evt.data.preventDefault();
	//	}
	//};

	var image_types = new Array(
			new Array('jpg', 'picture.png'),
			new Array('jpeg', 'picture.png'),
			new Array('gif', 'picture.png'),
			new Array('tif', 'picture.png'),
			new Array('tiff', 'picture.png'),
			new Array('png', 'picture.png'),
			new Array('qt', 'picture.gif'),
			new Array('pict', 'picture.png'),
			new Array('psd', 'picture.png'),
			new Array('bmp', 'picture.png'),
			new Array('svg', 'picture.png')
	);
	
	var html = '';
	if(typeof(ckeditor_commsy_files) !== 'undefined'){
		if(ckeditor_commsy_files.length > 0){
			//var image_counter = 0;
			var image_array = new Array;
			//html += '<ul style="list-style-type: none">';
			for ( var int = 0; int < ckeditor_commsy_files.length; int++) {
				var temp_file = ckeditor_commsy_files[int];
				var file_extension_array = temp_file.split('.');
				var file_extension = file_extension_array[file_extension_array.length-1];
				file_extension = file_extension.toLowerCase();
				var is_image = false;
				var file_icon = 'unknown.png';
				for ( var int2 = 0; int2 < image_types.length; int2++) {
					if(file_extension == image_types[int2][0]){
						is_image = true;
						file_icon = image_types[int2][1];
					}
				}
				if(is_image){
					//html += '<li><img src="images/'+file_icon+'" /><span id="'+temp_file+'" onmouseover="this.style.cursor=\'pointer\';"> '+temp_file+'</span></li>';
					//image_counter++;
					image_array.push(temp_file);
				}
			}
			//html += '</ul>';
			
			//if(image_counter == 0){
			//	html = ckeditor_images_no_files;
			//	onClick = function( evt ){};
			//}
			
			if(image_array.length == 0){
				html += ckeditor_images_no_files;
				onClick = function( evt ){};
			} else {
				// Bildauswahl
				html += '<form id="commsy_images">';
				html += '<table>';
				html += '<tr>';
				html += '<td class="ckeditor_select_title">';
				html += ckeditor_images_select_file+':';
				html += '</td>';
				html += '<td class="ckeditor_select_content">';
				html += '<select id="select_file" class="ckeditor_select">';
				for ( var int3 = 0; int3 < image_array.length; int3++) {
					html += '<option>'+image_array[int3]+'</option>';
				}
				html += '</select>';
				html += '</td>';
				html += '</tr>';

				// Bildgbreite
				html += '<tr>';
				html += '<td class="ckeditor_select_title">';
				html += ckeditor_images_select_width+':';
				html += '</td>';
				html += '<td class="ckeditor_select_content">';
				html += '<select id="select_width" class="ckeditor_select">';
				html += '<option value="50">'+ckeditor_images_size_small+' (50px)</option>';
				html += '<option value="200">'+ckeditor_images_size_medium+' (200px)</option>';
				html += '<option value="500">'+ckeditor_images_size_large+' (500px)</option>';
				html += '<option value="original">'+ckeditor_images_size_original+'</option>';
				html += '</select>';
				html += '</td>';
				html += '</tr>';

				// Ausrichtung
				html += '<tr>';
				html += '<td class="ckeditor_select_title">';
				html += ckeditor_images_select_alignment+':';
				html += '</td>';
				html += '<td class="ckeditor_select_content">';
				html += '<select id="select_alignment" class="ckeditor_select">';
				html += '<option value="lfloat">'+ckeditor_images_alignment_left+'</option>';
				//html += '<option value="center">'+ckeditor_images_alignment_center+'</option>';
				html += '<option value="rfloat">'+ckeditor_images_alignment_right+'</option>';
				html += '</select>';
				html += '</td>';
				html += '</tr>';
				html += '</table>';
				html += '<form>';
			}
		} else {
			html = ckeditor_images_no_files;
			onClick = function( evt ){};
		}
		//html += '<br/><br/><br/><form><input type="file"><input type="submit" id="ckeditor_commsy_image_upload_button" onClick="image_button()" value="Bild hochladen"></form>';
	} else {
		html = ckeditor_images_no_files;
		onClick = function( evt ){};
	}
	
	var commsyImageSelector =
	{
		type : 'html',
		html : html,
		onLoad : function( event )
		{
			dialog = event.sender;
		},
		style : 'width: 100%; height: 100%; border-collapse: separate;'
	};
	
	var commsyImageUploadFileSelection =
	{
	   type : 'file',
	   id : 'upload',
	   label : editor.lang.image.btnUpload,
	   style: 'height:40px',
	   size : 38
    };
	
	return {
		title : ckeditor_images_select,
		minWidth : 270,
		minHeight : 120,
		contents : [
			/*{
				id : 'tab1',
				label : 'CommSy',
				title : 'CommSy',
				expand : false,
				padding : 0,
				elements : [
			            commsyImageSelector,
					]
			},*/
			{
            	id : 'Upload',
            	hidden : true,
            	filebrowser : 'uploadButton',
            	label : editor.lang.image.upload,
            	elements :
            		[
            			{
            				type : 'file',
            				id : 'upload',
            				label : editor.lang.image.btnUpload,
            				style: 'height:40px',
            				size : 38
            			},
            			{
            				type : 'fileButton',
            				id : 'uploadButton',
            				filebrowser : 'info:txtUrl',
            				label : editor.lang.image.btnUpload,
            				'for' : [ 'Upload', 'upload' ]
            			},
            			commsyImageSelector
            		]
            	}
		],
		
		buttons : [ CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton ],
		onOk : function()
		{
			if(document.getElementById('select_file')){
			    var selected_file = document.getElementById('select_file').value;
			    
			    var selected_width_temp = document.getElementById('select_width').value;
			    if(selected_width_temp != 'original'){
			    	selected_width = 'width='+selected_width_temp;
			    } else {
			    	selected_width = '';
			    }
			    
			    var selected_alignment = document.getElementById('select_alignment').value;
			    
				var image = CKEDITOR.dom.element.createFromHtml('<span>(:image '+selected_file+' '+selected_width+' '+selected_alignment+' alt=\''+selected_file+'\':)</span>');
				
				//var image = CKEDITOR.dom.element.createFromHtml('<img src="commsy.php/Finland.gif?cid=107&amp;mod=material&amp;fct=getfile&amp;iid=28" />');
				
				editor.insertElement(image);
			}
		},
		onCancel : function(){
			
		}
	};

} );

var commsy_upload_callback = CKEDITOR.tools.addFunction(function(){
   alert( 'Hello!');
});