/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.dialog.add( 'CommSyFiles', function( editor )
{
	var config = editor.config,
		lang = editor.lang.smiley,
		images = config.smiley_images,
		columns = 8,
		i;
	
	var dialog;
	var onClick = function( evt )
	{
		var target = evt.data.getTarget(),
			targetID = target.getId();

		if(targetID != null){
			var image = CKEDITOR.dom.element.createFromHtml('<span>(:file '+targetID+':)</span>');
			editor.insertElement(image);
	
			dialog.hide();
			evt.data.preventDefault();
		}
	};

	//var image_types = new Array(
	//		new Array('jpg', 'picture.png'),
	//		new Array('jpeg', 'picture.png'),
	//		new Array('gif', 'picture.png'),
	//		new Array('tif', 'picture.png'),
	//		new Array('tiff', 'picture.png'),
	//		new Array('png', 'picture.png'),
	//		new Array('qt', 'picture.gif'),
	//		new Array('pict', 'picture.png'),
	//		new Array('psd', 'picture.png'),
	//		new Array('bmp', 'picture.png'),
	//		new Array('svg', 'picture.png')
	//);
	
	var file_types = new Array(
			new Array('htm', 'text.png'),
			new Array('html', 'text.png'),
			new Array('txt', 'text.png'),
			new Array('text', 'text.png'),
			new Array('xml', 'text.png'),
			new Array('xsl', 'text.png'),
			
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
			new Array('svg', 'picture.png'),
			
			new Array('zip', 'archive.png'),
			new Array('tar', 'archive.png'),
			new Array('gz', 'archive.png'),
			new Array('tgz', 'archive.png'),
			new Array('z', 'archive.png'),
			new Array('hqx', 'archive.png'),
			new Array('sit', 'archive.png'),
			
			new Array('au', 'sound.png'),
			new Array('wav', 'sound.png'),
			new Array('mp3', 'sound.png'),
			new Array('aif', 'sound.png'),
			new Array('aiff', 'sound.png'),
			
			new Array('mp4', 'movie.png'),
			new Array('avi', 'movie.png'),
			new Array('mov', 'movie.png'),
			new Array('moov', 'movie.png'),
			new Array('mpg', 'movie.png'),
			new Array('mpeg', 'movie.png'),
			new Array('dif', 'movie.png'),
			new Array('dv', 'movie.png'),
			new Array('flv', 'movie.png'),
			
			new Array('pdf', 'pdf.png'),
			new Array('fdf', 'pdf.png'),
			new Array('doc', 'doc.png'),
			new Array('docx', 'doc.png'),
			new Array('dot', 'doc.png'),
			new Array('rtf', 'doc.png'),
			new Array('ppt', 'ppt.png'),
			new Array('pot', 'ppt.png'),
			new Array('pptx', 'ppt.png'),
			new Array('lsi', 'lassi_commsy.png'),
			new Array('odf', 'ooo_formula_commsy.png'),
			new Array('odg', 'ooo_draw_commsy.png'),
			new Array('ods', 'ooo_calc_commsy.png'),
			new Array('odp', 'ooo_impress_commsy.png'),
			new Array('odt', 'ooo_writer_commsy.png'),
			new Array('xls', 'xls.png'),
			new Array('xlsx', 'xls.png'),
			new Array('swf', 'movie.png')
	);
	
	var html = '';
	if(typeof(ckeditor_commsy_files) !== 'undefined'){
		if(ckeditor_commsy_files.length > 0){
			var file_counter = 0;
			html += '<ul style="list-style-type: none">';
			for ( var int = 0; int < ckeditor_commsy_files.length; int++) {
				var temp_file = ckeditor_commsy_files[int];
				var file_extension_array = temp_file.split('.');
				var file_extension = file_extension_array[file_extension_array.length-1];
				file_extension = file_extension.toLowerCase();
				//var is_file = false;
				var is_image = false;
				var file_icon = 'unknown.png';
				for ( var int2 = 0; int2 < file_types.length; int2++) {
					if(file_extension == file_types[int2][0]){
						//is_file = true;
						file_icon = file_types[int2][1];
					}
				}
				//if(!is_file){
				//	for ( var int2 = 0; int2 < image_types.length; int2++) {
				//		if(file_extension == image_types[int2][0]){
				//			is_image = true;
				//		}
				//	}
				//}
				//if(!is_image){
					html += '<li><img src="images/'+file_icon+'" /><span id="'+temp_file+'" onmouseover="this.style.cursor=\'pointer\';"> '+temp_file+'</span></li>';
					file_counter++;
				//}
			}
			html += '</ul>';
			if(file_counter == 0){
				html = ckeditor_files_no_files;
				onClick = function( evt ){};
			}
		} else {
			html = ckeditor_files_no_files;
			onClick = function( evt ){};
		}
	} else {
		html = ckeditor_files_no_files;
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
		onClick : onClick,
		style : 'width: 100%; height: 100%; border-collapse: separate;'
	};
	
	return {
		title : ckeditor_files_select,
		minWidth : 270,
		minHeight : 120,
		contents : [
			{
				id : 'tab1',
				label : '',
				title : '',
				expand : false,
				padding : 0,
				elements : [
			            commsyImageSelector
					]
			}
		],
		buttons : [ CKEDITOR.dialog.okButton, CKEDITOR.dialog.cancelButton ]
	};
} );
