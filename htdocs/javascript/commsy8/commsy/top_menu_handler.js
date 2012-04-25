/**
 * Top Menu Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		isExpanded: false,
		commsy_function: null,
		cid: null,
		
		init: function(commsy_functions, parameters) {
			this.commsy_functions = commsy_functions;
			this.cid = commsy_functions.getURLParam('cid');
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setupMenus, {handle: this, objects: parameters.objects});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		setupMenus: function(preconditions, parameters) {
			var handle = parameters.handle;
			var objects = parameters.objects;
			
			// register all trigger
			jQuery.each(objects, function() {
				// determe trigger offset
				//var offset = this.trigger.offset();
				
				// reposition menu
				//this.menu.offset({top: offset.top + this.trigger.outerHeight(), left: offset.left - this.trigger.css('padding-left').substr(0, 2)});
				
				this.trigger.bind('click', {
					handle: handle,
					object:		this,
					objects:	objects}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var handle = event.data.handle;
			var menu = event.data.object.menu;
			var trigger = event.data.object.trigger;
			var active_class = event.data.object.active_class;
			var callback = event.data.object.callback;
			var objects = event.data.objects;
			
			if(menu.css('display') === 'none') {
				// check if another menu is already expanded
				if(handle.isExpanded === true) {
					jQuery.each(objects, function() {						
						if(this.menu.css('display') !== 'none') {
							this.trigger.removeClass(this.active_class)
							
							// hide
							this.menu.hide();
						}
					});
				}
				
				// show
				menu.show();
				
				trigger.addClass(active_class);
				
				handle.isExpanded = true;
				
				// callback
				if(callback !== '') handle[callback].apply(handle, []);
			} else {
				// hide
				menu.slideUp(100);
				
				// remove content
				menu.html('');
				
				trigger.removeClass(active_class);
				
				handle.isExpanded = false;
			}
			
			return false;
		},
		
		onClickPersBar: function() {
			var data = {
				module: 'profile',
				iid:	'NEW'
			};
			
			var handle = this;
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + this.cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// we recieved html - append it
						jQuery('div#tm_dropmenu_pers_bar').html(data);
						
						// show
						jQuery('div#tm_dropmenu_pers_bar div.tm_dropmenu').slideDown(100);
						
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
						*/
						
						// setup popup
						handle.setupPopup();
						
						// setup tabs
						handle.setupTabs(jQuery('div#tm_dropmenu_pers_bar'));
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		onClickBreadcrumb: function() {
			var data = {
				module: 'breadcrumb',
				iid:	'NEW'
			};
			
			var handle = this;
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + this.cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// we recieved html - append it
						jQuery('div#tm_dropmenu_breadcrumb').html(data);
						
						// show
						jQuery('div#tm_dropmenu_breadcrumb div.tm_dropmenu').slideDown(100);
						
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
						*/
						
						// register click for edit button
						jQuery('a#edit_roomlist').click(function() {
							handle:		handle}, handle.setupEditMode);
						
						// setup popup
						handle.setupPopup();
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		setupEditMode: function(event) {
			var commsy_functions = event.data.handle;
			var target = jQuery(event.target);
			
			var content_object = jQuery('div#tm_dropmenu_breadcrumb div#profile_content_row_three');
			
			// setup sortables
			content_object.find('.breadcrumb_column').sortable({
				connectWith:	'.breadcrumb_column',
				placeholder:	'ui-state-highlight'
			});
			
			var column_objects = content_object.find('div.breadcrumb_column');
			
			jQuery.each(column_objects, function() {
				jQuery('<div/>', {'class': 'room_dummy'}).appendTo(this);
			});
			
			return false;
		},
		
		setupPopup: function() {
			var handle = this;
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
			/*
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
			*/
		},
		
		setupTabs: function(parent_object) {
			var handle = this;
			
			// register click for tabs
			parent_object.find('div[class="tab_navigation"] a').each(function(index) {
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
		
		fullSizeOverlay: function() {
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
		}
	};
});