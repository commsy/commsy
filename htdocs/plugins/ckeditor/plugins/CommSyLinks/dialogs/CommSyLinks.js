/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.dialog.add( 'CommSyLinks', function( editor )
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
			var image = CKEDITOR.dom.element.createFromHtml('<span>(:item '+targetID+':)</span>');
			editor.insertElement(image);
	
			dialog.hide();
			evt.data.preventDefault();
		}
	};
	
	var item_types = new Array(
			new Array('material', 'material.png'),
			new Array('institution', 'text.png'),
			new Array('topic', 'topic.png'),
			new Array('announcement', 'announcement.png'),
			new Array('annotation', 'announcement.png'),
			new Array('user', 'user.png'),
			new Array('todo', 'todo.png'),
			new Array('step', 'todo.png'),
			new Array('date', 'date.png'),
			new Array('entry', 'text.png'),
			new Array('discussion', 'discussion.png'),
			new Array('group', 'group.png'),
			new Array('section', 'material.png'),
			new Array('discarticle', 'discussion.png'),
			new Array('task', 'todo.png')
	);
	
	var html = '';
	if(typeof(ckeditor_commsy_links) !== 'undefined'){
		if(ckeditor_commsy_links.length > 0){
			var file_counter = 0;
			html += '<ul style="list-style-type: none">';
			for ( var int = 0; int < ckeditor_commsy_links.length; int++) {
				var item_id = ckeditor_commsy_links[int][0];
				var item_text = ckeditor_commsy_links[int][1];
				var item_type = ckeditor_commsy_links[int][2];
				
				for ( var int2 = 0; int2 < item_types.length; int2++) {
					if(item_type == item_types[int2][0]){
						item_icon = item_types[int2][1];
					}
				}
				html += '<li><div class="ckeditor_links_entry"><div class="ckeditor_files_icon"><img src="images/commsyicons/16x16/'+item_icon+'" /></div><div class="ckeditor_files_text"><span id="'+item_id+'" onmouseover="this.style.cursor=\'pointer\';"> '+item_text+'</span></div></div></li>';
				file_counter++;
			}
			html += '</ul>';
			if(file_counter == 0){
				html = ckeditor_links_no_links;
				onClick = function( evt ){};
			}
		} else {
			html = ckeditor_links_no_links;
			onClick = function( evt ){};
		}
	} else {
		html = ckeditor_links_no_links;
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
		title : ckeditor_links_select,
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
