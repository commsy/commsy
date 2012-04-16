/**
 * Tag Popup Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cid: null,
		
		init: function(commsy_functions, parameters) {
			this.cid = commsy_functions.getURLParam('cid');
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.loadPopup, {handle: this, commsy_functions: commsy_functions, handling: parameters});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		loadPopup: function(preconditions, parameters) {
			var commsy_functions = parameters.commsy_functions;
			var actors = parameters.handling.objects;
			var handle = parameters.handle;
			
			jQuery.each(actors, function() {
				jQuery(this).bind('click', {
					commsy_functions:	commsy_functions,
					handle:				handle,
					actor:				jQuery(this)}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;
			
			var data = {
				module: 'tag',
				iid:	'NEW'
			};
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// we recieved html - append it
						jQuery('body').prepend(data);
						
						
						/*
						
						// reinvoke Uploadify
						var uploadify_handler = commsy_functions.getModuleCallback('commsy/uploadify');
						uploadify_handler.create(null, {
							object:				jQuery('input[id="uploadify"]'),
							handle:				uploadify_handler,
							commsy_functions:	commsy_functions,
							upload_object:		jQuery('a[id="uploadify_doUpload"]'),
							clear_object:		jQuery('a[id="uploadify_clearQuery"]')
						});
						
						// reinvoke CKEditor
						var ck_editor_handler = commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div[id="popup_ckeditor"]'),
							input_object:		jQuery('input[id="popup_ckeditor_content"]')
						});
						
						// reinvoke TagTree
						var tag_tree_handler = commsy_functions.getModuleCallback('commsy/tag_tree');
						tag_tree_handler.buildTree(null, {
							object:				jQuery('div[id="tag_tree"]')
						});
						
						var dynatree = jQuery('div[id="tag_tree"]').dynatree('getTree');
						
						// expand all nodes
						dynatree.visit(function(node) {
							node.expand(true);
						});
						
						// override onclick
						dynatree.options.onClick = function(node, event) {
							// toggle bold
							var title = node.data.title;
							
							// check bold
							if(title.substr(0, 3) === '<b>') {
								// remove
								node.data.title = title.substr(3, title.length - 7);
							} else {
								// add
								node.data.title = '<b>' + title + '</b>';
							}
							
							// re-render
							node.render();
							
							return false;
						}
						*/
						
						
						
						
						// setup popup
						handle.setupPopup(event.data.module, event.data.item_id);
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		setupPopup: function(module, item_id) {
			var handle = this;
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
			// register click for close button
			jQuery('a[id="popup_close"]').click(function() {
				handle.close();
				return false;
			});
			
			// register click for abort button
			jQuery('input[id="popup_button_abort"]').click(function() {
				handle.close();
				return false;
			});
			
			// register click for create button
			/*
			jQuery('input[id="popup_button_create"]').bind('click', {
				handle:		this,
				module:		module,
				item_id:	item_id}, this.create);
			*/
			
			// setup tabs
			this.setupTabs();
		},
		
		fullSizeOverlay: function() {
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
		},
		
		close: function(event) {
			// unregister ck editor
			//var editor = jQuery('div[id="popup_ckeditor"]');
			//if(editor.length > 0) editor.ckeditorGet().destroy();
			
			// remove popup html from dom
			jQuery('div[id="popup_wrapper"]').remove();
		},
		
		setupTabs: function() {
			var handle = this;
			
			// register click for tabs
			jQuery('div[class="tab_navigation"] a').each(function(index) {
				jQuery(this).bind('click', {
					index:	index,
					handle:	handle}, handle.onClickTab);
			});
		},
		
		onClickTab: function(event) {
			var target = jQuery(event.currentTarget);
			var index = event.data.index;
			var handle = event.data.handle;
			
			// set all tabs inactive
			jQuery('div[class="tab_navigation"] a').each(function() {
				jQuery(this).attr('class', 'pop_tab');
			})
			
			// set target active
			target.attr('class', 'pop_tab_active');
			
			// switch display
			// get divs
			var content_divs = jQuery('div[id="popup_tabcontent"] div[class^="tab"]');
			
			// set class for divs
			content_divs.each(function(i) {
				if(index === i) {
					// remove hidden
					jQuery(this).removeClass('hidden');
				} else {
					// add hidden
					jQuery(this).addClass('hidden');
				}
			});
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
			return false;
		},
		
		
		
		
		
		
		
		
		
		
		
		
		
		create: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			var item_id = event.data.item_id;
			
			// check mandatory
			var check_passed = true;
			jQuery('input[class~="mandatory"]').each(function() {
				if(jQuery(this).val() === '') {
					if(check_passed === true) {
						// this is the first error
						// check if content is outside screen
						if(!jQuery.inviewport(jQuery(this), {threshold: 0})) {
							// scroll to target
							jQuery('html, body').animate({scrollTop: jQuery(this).offset().top}, 500);
						}
					}
					
					jQuery(this).css('border', '1px solid red');
					check_passed = false;
				}
			});
			
			if(check_passed) {
				// collect form data
				var form_objects = jQuery('div[id="popup_wrapper"] input[name^="form_data"]');

				// set description data
				var editor = jQuery('div[id="popup_ckeditor"]');
				if(editor.length > 0) {
					jQuery('input[name="form_data[description]"]').val(editor.ckeditorGet().getData());
				}

				// build object
				var data = {
					form_data: [],
					module: module
				};
				jQuery.each(form_objects, function() {
					var add = false;
					
					// if form field is a checkbox, only add if checked
					if(jQuery(this).attr('type') === 'checkbox') {
						if(jQuery(this).attr('checked') === 'checked') {
							add = true;
						}
					}
					
					// if form fiel is a radio button, only add the selected one
					else if(jQuery(this).attr('type') === 'radio') {
						if(jQuery(this).attr('checked')	 === 'checked') {
							add = true;
						}
					}
					
					else {
						add = true;
					}
					
					if(add === true) {
						// extract name
						/form_data\[(.*)\]/.exec(jQuery(this).attr('name'));

						data.form_data.push({
							name:	RegExp.$1,
							value:	jQuery(this).attr('value')
						});
					}
				});
				
				// add buzzword data
				var buzzword_objects = jQuery('ul[id="buzzwords_assigned"] li[id^="buzzword_"]');
				var buzzword_ids = [];
				jQuery.each(buzzword_objects, function() {
					// extract buzzword id
					/buzzword_([0-9]*)/.exec(jQuery(this).attr('id'));
					buzzword_ids.push(RegExp.$1);
				});
				data.form_data.push({
					name:	'buzzwords',
					value:	buzzword_ids
				});
				
				// add tag data
				var dynatree = jQuery('div[id="tag_tree"]').dynatree('getTree');
				var tag_ids  = [];
				dynatree.visit(function(node) {
					// check if bold
					if(node.data.title.substr(0, 3) === '<b>') {
						// separte tag id
						var tag_id = node.data.key.substr(5);

						tag_ids.push(tag_id);
					}
				});
				data.form_data.push({
					name:	'tags',
					value:	tag_ids
				});
				
				
				// set item id
				data.form_data.push({
					name:	'iid',
					value:	item_id
				});
				
				// add files data
				var file_objects = jQuery('div[id="popup_wrapper"] input[name="filelist[]"]');
				var file_ids = [];
				jQuery.each(file_objects, function() {
					file_ids.push(jQuery(this).attr('value'));
				})
				data.form_data.push({
					name:	'files',
					value:	file_ids
				});
				
				// ajax request
				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=create',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function() {
						console.log("error while processing popup action");
					},
					success: function(data, status) {
						console.log(data);
						
						handle.close();
						
						// page reload
						location.reload();
						//handle.preconditionsSuccess(data);
					}
				});
			}
			
			return false;
		}
	};
});