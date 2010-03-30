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

	/**
	 * Simulate "this" of a dialog for non-dialog events.
	 * @type {CKEDITOR.dialog}
	 */
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

	var types = new Array('jpg', 'gif', 'png');
	
	var html = '';
	if(typeof(ckeditor_commsy_images) !== 'undefined'){
		if(ckeditor_commsy_images.length > 0){
			var file_counter = 0;
			html += '<ul style="list-style-type: none">';
			for ( var int = 0; int < ckeditor_commsy_images.length; int++) {
				var temp_file = ckeditor_commsy_images[int];
				var file_extension_array = temp_file.split('.');
				var file_extension = file_extension_array[file_extension_array.length-1];
				file_extension = file_extension.toLowerCase();
				var is_file = true;
				for ( var int2 = 0; int2 < types.length; int2++) {
					if(file_extension == types[int2]){
						is_file = false;
					}
				}
				if(is_file){
					html += '<li><img src="plugins/ckeditor/plugins/CommSyFiles/images/CommSyFiles.png" /><span id="'+temp_file+'" onmouseover="this.style.cursor=\'pointer\';"> '+temp_file+'</span></li>';
					file_counter++;
				}
			}
			html += '</ul>';
			if(file_counter == 0){
				html = 'Keine Dateien vorhanden';
				onClick = function( evt ){};
			}
		} else {
			html = 'Keine Dateien vorhanden';
			onClick = function( evt ){};
		}
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
		title : 'Datei auswählen',
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
