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
	
	return {
		title : ckeditor_images_select,
		minWidth : 270,
		minHeight : 120,
		contents : [
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
            			}
            		]
            	}
		],
		buttons : [ CKEDITOR.dialog.okButton ],
		onOk : function()
		{
		    var selected_file = document.getElementsByName('upload')[0].value;
		    
			var image = CKEDITOR.dom.element.createFromHtml('<span>(:image '+selected_file+' alt=\''+selected_file+'\':)</span>');
			
			editor.insertElement(image);
		}
	};

} );