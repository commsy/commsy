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
						
						// register click for edit button
						jQuery('a#edit_roomlist').bind('click', {
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
			var handle = event.data.handle;
			var target = jQuery(event.target);
			
			var content_objects = jQuery('div#tm_dropmenu_breadcrumb div#profile_content_row_three, div#tm_dropmenu_breadcrumb div#profile_content_row_four');
			
			// make hidden rooms visible
			jQuery('div#profile_content_row_four').show();
			
			// setup sortables
			content_objects.find('.breadcrumb_room_area').sortable({
				connectWith:	'.breadcrumb_room_area',
				placeholder:	'ui-state-highlight'
			});
			
			// process each room block
			jQuery('div.room_block').each(function() {
				var room_area_objects = jQuery(this).find('div.breadcrumb_room_area');
				
				// group h3-tags together
				var ref = null;
				jQuery.each(room_area_objects, function(index) {
					// save first room area
					if(index === 0) ref = jQuery(this);
					
					// otherwise move its rooms to first room
					else {
						ref.find('div.clear').before(jQuery(this).find('a.room_change_item'));
						
						// remove room area
						jQuery(this).remove();
					}
				});
				
				// determe number of dummies to add
				/*
				 * holds the beginning position after that only dummies appear
				 * D D D D R D D R D D D D D
				 * 				  /\
				 * 				  ||
				 */
				var earliest_dummy_streak_appearance = -1;
				
				/*
				 * holds the latest appearance of a room
				 * D D D D R D D R D D D D D
				 * 				/\
				 * 				||
				 */
				var latest_room_appearance = -1;
				
				var count = 0;
				ref.find('a.room_change_item, div.room_dummy').each(function(index) {
					// determ type
					if(jQuery(this).hasClass('room_dummy')) {
						// dummy
						// make visible
						jQuery(this).removeClass('room_dummy_no_border');
						
						//if(earliest_dummy_streak_appearance == -1) earliest_dummy_streak_appearance = index;
					} else {
						// room
						// update latest appearance
						latest_room_appearance = index;
						
						//if(index > earliest_dummy_streak_appearance) earliest_dummy_streak_appearance = -1;
					}
					
					count++;
				});
				
				var dummies_to_add = 0;
				
				// not fully filled rows
				if(count % 4 !== 0) dummies_to_add = 4 + 4 - count % 4;		// this is one complete row + filled last one	
				
				// last row contains a room
				else if(latest_room_appearance > count - 3) {
					dummies_to_add = 4;
				}
				
				// add dummies		
				for(var i=0; i < dummies_to_add; i++) {
					ref.find('div.clear').before(jQuery('<div/>', {'class': 'room_dummy'}));
				}
				
				// remove all h3-tags
				jQuery(this).children('h3').remove();
				
				// make h2-tags to inputs
				jQuery(this).children('h2').each(function() {
					// wrap
					jQuery(this).html(jQuery('<input/>', {
						'value':	jQuery(this).html()
					}));
				});
			});
			
			// add new block area
			jQuery('div#tm_dropmenu_breadcrumb div#profile_content_row_three div.room_block:last').after(
			jQuery('<div/>', {
				'class':	'float-left'
			}).append(
				jQuery('<a/>', {
					'id':	'roomlist_append_block',
					'href':	'#',
					'html':	'new block'
				})));
			
			// register new block
			jQuery('a#roomlist_append_block').bind('click', {handle: handle}, handle.appendNewBlock);
			
			// add save
			jQuery('div#tm_dropmenu_breadcrumb div#profile_content_row_three').append(
			jQuery('<div/>', {
				'class':	'float-right'
			}).append(
				jQuery('<a/>', {
					'id':	'roomlist_save',
					'href':	'#',
					'html':	'save'
				}))).append(
			jQuery('<div/>', {
				'class':	'clear'
			}));
			
			// register save
			jQuery('a#roomlist_save').bind('click', {handle: handle}, handle.saveRoomlist);
			
			// unregister handler
			jQuery('a#edit_roomlist').unbind();
			
			return false;
		},
		
		appendNewBlock: function(event) {
			// build main structure
			jQuery('div#profile_content_row_three div.room_block:last').after(
			jQuery('<div/>', {
				'class':	'room_block'
			}).append(
				jQuery('<h2/>').append(
					jQuery('<input/>', {
						'value':	'Neu'
					}))).append(
				jQuery('<div/>', {
					'class':	'breadcrumb_room_area'
				})));
			
			var new_area_object = jQuery('div#profile_content_row_three div.room_block:last div.breadcrumb_room_area');
			
			// make sortable
			new_area_object.sortable({
				connectWith:	'.breadcrumb_room_area',
				placeholder:	'ui-state-highlight'
			});
			
			// append eight dummies
			for(var i=0; i < 8; i++) {
				new_area_object.append(jQuery('<div/>', {
					'class':	'room_dummy'
				}));
			}
			
			// append clearing div
			new_area_object.append(jQuery('<div/>', {
				'class':	'clear'
			}));
		},
		
		saveRoomlist: function(event) {
			var handle = event.data.handle;
			
			var data = {
				module:		'breadcrumb',
				form_data:	[]
			};
			var room_config = [];
			
			// prepare form data
			jQuery('div#profile_content_row_three div.room_block').each(function() {
				// get title from h2
				room_config.push({
					'type':		'title',
					'value':	jQuery(this).children('h2').children('input').attr('value')
				});
				
				// get room and spaces
				jQuery(this).children('div.breadcrumb_room_area').find('a.room_change_item, div.room_dummy').each(function() {
					// determ type
					var type = 'room';
					if(jQuery(this).hasClass('room_dummy')) type = 'dummy';
					
					room_config.push({
						'type':		type,
						'value':	jQuery(this).find('input[name="hidden_item_id"]').attr('value')
					});
				});
			});
			
			data.form_data.push({
				'name':		'room_config',
				'value':	room_config
			});
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=save',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						
					}
				}
			});
			
			// stop processing
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