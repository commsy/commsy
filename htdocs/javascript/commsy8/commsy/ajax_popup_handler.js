/**
 * Ajax Popup Handler Module
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
				// determ module from actor href
				var module = '';
				var regex = new RegExp("[\\?&]mod=([^&#]*)");
				var results = regex.exec(jQuery(this).attr('href'));
				if(results !== null && results[1] !== 'NEW') module = results[1];
				
				// determ item id from actor href
				var item_id = 'NEW';
				var regex = new RegExp("[\\?&]iid=([^&#]*)");
				var results = regex.exec(jQuery(this).attr('href'));
				if(results !== null && results[1] !== 'NEW') item_id = results[1];
				
				
				jQuery(this).bind('click', {
					commsy_functions:	commsy_functions,
					handle:				handle,
					actor:				jQuery(this),
					module:				module,
					item_id:			item_id}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;
			
			var data = {
				module: event.data.module,
				iid:	event.data.item_id
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
						
						// setup popup
						handle.setupPopup(event.data.module, event.data.item_id);
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		close: function(event) {
			// unregister ck editor
			var editor = jQuery('div[id="popup_ckeditor"]');
			if(editor.length > 0) editor.ckeditorGet().destroy();
			
			// remove popup html from dom
			jQuery('div[id="popup_wrapper"]').remove();
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

				data.form_data.push({
					name:	'iid',
					value:	item_id
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
		},
		
		setupTabs: function() {
			var handle = this;
			
			// register click for tabs
			jQuery('div[class="tab_navigation"] a').each(function(index) {
				jQuery(this).bind('click', {index: index}, handle.onClickTab);
			});
		},
		
		setupBuzzwords: function() {
			// unassigned
			jQuery('div[id="popup"] ul[id="buzzwords_unassigned"]').sortable({
				connectWith:	'ul',
				placeholder:	'ui-state-highlight',
				cursor:			'pointer',
				change:			function(event, ui) {
					if(ui.sender !== null) {
						// adjust
						ui.item.find('img').attr('alt', 'add');
					}
				}
			});
			
			// assigned
			jQuery('div[id="popup"] ul[id="buzzwords_assigned"]').sortable({
				connectWith:	'ul',
				placeholder:	'ui-state-highlight',
				cursor:			'pointer',
				change:			function(event, ui) {
					if(ui.sender !== null) {
						// adjust
						ui.item.find('img').attr('alt', 'remove');
					}
				}
			});
			
			// register add event
			jQuery('ul[id^="buzzwords_"] img').each(function() {
				jQuery(this).click(function() {
					var li = jQuery(this).parent().parent();
					
					// get ul id
					var ul_id = li.parent().attr('id');
					
					// detach
					li = li.detach();
					
					if(ul_id === 'buzzwords_unassigned') {
						// append to assigned
						li.appendTo(jQuery('ul[id="buzzwords_assigned"]'));
						
						// adjust
						li.find('img').attr('alt', 'remove');
					} else {
						// append to unassigned
						li.appendTo(jQuery('ul[id="buzzwords_unassigned"]'));
						
						// adjust
						li.find('img').attr('alt', 'add');
					}
				});
			});
		},
		
		onClickTab: function(event) {
			var target = jQuery(event.currentTarget);
			var index = event.data.index;
			
			// set all tabs inactive
			jQuery('div[class="tab_navigation"] a').each(function() {
				jQuery(this).attr('class', 'pop_tab');
			})
			
			// set target active
			target.attr('class', 'pop_tab_active');
			
			// switch display
			// get divs
			var content_divs = jQuery('div[id="popup_tabcontent"] div[class^="settings_area"]');
			
			// set class for divs
			content_divs.each(function(i) {
				if(index === i) {
					jQuery(this).attr('class', 'settings_area');
				} else {
					jQuery(this).attr('class', 'settings_area hidden');
				}
			});
			
			// fullsize black overlay
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
			
			return false;
		},
		
		setupPopup: function(module, item_id) {
			var handle = this;
			
			// fullsize black overlay
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
			
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
			jQuery('input[id="popup_button_create"]').bind('click', {
				handle:		this,
				module:		module,
				item_id:	item_id}, this.create);
			
			// setup buzzwords
			this.setupBuzzwords();
			
			// setup tabs
			this.setupTabs();
		}
	};
});