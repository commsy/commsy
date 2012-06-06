/**
 * Top Menu Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
			"order!libs/jQuery_plugins/jquery.form",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		isExpanded: false,
		commsy_function: null,
		cid: null,
		objects: null,
		preconditions: null,

		init: function(commsy_functions, parameters) {
			this.commsy_functions = commsy_functions;
			this.cid = commsy_functions.getURLParam('cid');

			// set preconditions
			this.setPreconditions(commsy_functions, this.setupMenus, {handle: this, objects: parameters.objects});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
					'i18n':	['COMMON_NEW_BLOCK', 'COMMON_SAVE_BUTTON']
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		setupMenus: function(preconditions, parameters) {
			var handle = parameters.handle;
			var objects = parameters.objects;

			handle.preconditions = preconditions;
			handle.objects = objects;

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

				// unregister ck editor instances
				var editors = jQuery('div#popup_wrapper div.ckeditor');
				if(editors.length > 0) {
					editors.each(function() {
						jQuery(this).ckeditorGet().destroy();
					});
				}

				// remove content
				menu.html('');

				trigger.removeClass(active_class);

				handle.isExpanded = false;
			}

			return false;
		},

		close: function() {
			var handle = this;

			// unregister ck editor instances
			var editors = jQuery('div#popup_wrapper div.ckeditor');
			if(editors.length > 0) {
				editors.each(function() {
					jQuery(this).ckeditorGet().destroy();
				});
			}

			// remove popup html from dom
			jQuery('div#popup_wrapper').remove();

			jQuery.each(handle.objects, function() {
				this.trigger.removeClass(this.active_class);
				this.menu.css('display', 'none');
			});

			handle.isExpanded = false;

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
					if(data.status === 'success') {
						// we recieved html - append it
						jQuery('div#tm_dropmenu_pers_bar').html(data.html);

						// reinvoke Datepicker
						var datepicker_handler = handle.commsy_functions.getModuleCallback('commsy/datepicker');
						datepicker_handler.setup(null, {
							handle:				datepicker_handler,
							register_on:		jQuery('input.datepicker')
						});

						// reinvoke CKEditor
						var ck_editor_handler = handle.commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div.ckeditor')
						});

						// show
						jQuery('div#tm_dropmenu_pers_bar div.tm_dropmenu').slideDown(100);

						// register click for save buttons
						jQuery('div#tm_dropmenu_pers_bar input#submit').bind('click', {
							handle:		handle}, handle.onSavePersBar);

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

		onSavePersBar: function(event) {
			var handle = event.data.handle;
			var target = jQuery(event.target);

			// get all form information from current tab
			var col_object = target.parentsUntil('div.tab');
			var form_objects = col_object.find('input[name^="form_data"]');

			// build object
			var data = {
				form_data: [],
				module: 'profile',
				additional: {
					tab: col_object.parent().attr('id')
				}
			};

			// add ckeditor data to hidden div
			jQuery('div.ckeditor').each(function() {
				var editor = jQuery(this).ckeditorGet();
				jQuery(this).parent().children('input[type="hidden"]').attr('value', editor.getData());
			});

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

			// ajax request
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=save',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function() {
					console.log("error while processing popup action");
				},
				success: function(data, status) {
					if(data.status === 'success') {
						// submit picture
						var form_object = jQuery('form#picture_upload');

						if(form_object.find('input[type="file"]').attr('value') !== '') {
							handle.uploadUserPicture(form_object);
						} else {
							handle.close();
						}
					} else if(data.status === 'error' && data.code === 101) {
						// mandatory error
						var missing_fields = data.detail;

						// create a red border around the missing fields and scroll to first one
						jQuery.each(missing_fields, function(index, field_name) {
							jQuery.each(form_objects, function() {
								if(jQuery(this).attr('name') === 'form_data[' + field_name + ']') {
									jQuery(this).css('border', '1px solid red');

									if(index === 0 && !jQuery.inviewport(jQuery(this), {threshold: 0})) {
										jQuery('html, body').animate({scrollTop: jQuery(this).offset().top}, 500);
									}
								}
							});
						});
					} else {
						// unhandled error
						console.log('unhandled error');
					}
				}
			});
		},

		uploadUserPicture: function(form_object) {
			var handle = this;

			// setup ajax form
			form_object.ajaxForm();

			// submit form
			form_object.ajaxSubmit({
				type:		'POST',
				success:	function() {
					handle.close();
				}
			});

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
					if(data.status === 'success') {
						// we recieved html - append it
						jQuery('div#tm_dropmenu_breadcrumb').html(data.html);

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

		sortableOnStop: function(event, ui) {
			// process each room area
	    	jQuery('div.breadcrumb_room_area').each(function() {
	    		// get number of elements in this area
	    		var num_elements = jQuery(this).children('a.room_change_item, div.room_dummy').length;

	    		// fill with dummies if elements missing
	    		if(num_elements % 4 !== 0) {
	    			for(var i = 0; i < 4 - (num_elements % 4); i++) {
	    				jQuery(this).find('div.clear').before(jQuery('<div/>', {'class': 'room_dummy'}));
	    			}
	    		}

	    		// ensure one empty row below the last room in area
	    		/*
				 * holds the latest appearance of a room
				 * D D D D R D D R D D D D D
				 * 				/\
				 * 				||
				 */
				var latest_room_appearance = -1;

				jQuery(this).find('a.room_change_item, div.room_dummy').each(function(index) {
					// determ type
					if(jQuery(this).hasClass('room_change_item')) {
						// room
						// update latest appearance
						latest_room_appearance = index;
					}
				});

				if(latest_room_appearance > -1) {
					var num_dummies_after_last_room = num_elements - latest_room_appearance - 1;

					if(num_dummies_after_last_room <= 3) {
						// add a row of dummies
						for(var i = 0; i < 4; i++) {
		    				jQuery(this).find('div.clear').before(jQuery('<div/>', {'class': 'room_dummy'}));
		    			}
					} else if(num_dummies_after_last_room >= 5) {
						// get new latest room appearance
						var new_latest_room_appearance = -1;
						jQuery(this).find('a.room_change_item, div.room_dummy').each(function(index) {
							// determ type
							if(jQuery(this).hasClass('room_change_item')) {
								// room
								// update latest appearance
								new_latest_room_appearance = index;
							}
						});

						// determe number to delete
						var num_delete = num_elements - new_latest_room_appearance - 1 - 4 - ((num_elements - new_latest_room_appearance - 1 - 4) % 4);

						// remove a row of dummies
						for(var i = 0; i < num_delete; i++) {
		    				jQuery(this).find('div.clear').prev().remove();
		    			}
					}
				}
	    	});
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
				placeholder:	'ui-state-highlight',
				start:			function(event, ui) {
					jQuery(this).sortable('refreshPositions');
			    },
			    stop:			handle.sortableOnStop
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
				'class':	'roomlist_append_block'
			}).append(
				jQuery('<a/>', {
					'id':	'roomlist_append_block',
					'href':	'#',
					'html':	handle.preconditions.i18n['COMMON_NEW_BLOCK']
				})));

			// register new block
			jQuery('a#roomlist_append_block').bind('click', {handle: handle}, handle.appendNewBlock);

			// add save
			jQuery('div#tm_dropmenu_breadcrumb div#profile_content_row_three').append(
			jQuery('<div/>', {
				'class':	'roomlist_save'
			}).append(
				jQuery('<a/>', {
					'id':	'roomlist_save',
					'href':	'#',
					'html':	handle.preconditions.i18n['COMMON_SAVE_BUTTON']
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
				placeholder:	'ui-state-highlight',
				start:			function(event, ui) {
					jQuery(this).sortable('refreshPositions');
			    },
			    stop:			event.data.handle.sortableOnStop
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
					if(data.status === 'success') {
						handle.close();
					}
				}
			});

			// stop processing
			return false;
		},

		setupPopup: function() {
			var handle = this;

			// register click for close button
			jQuery('a#popup_close').click(function() {
				handle.close();

				return false;
			})
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

			return false;
		},

		onClickConfiguration: function() {
			var data = {
				module: 'configuration'
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
					if(data.status === 'success') {
						// we recieved html - append it
						jQuery('div#tm_dropmenu_configuration').html(data.html);

						// show
						jQuery('div#tm_dropmenu_configuration div.tm_dropmenu').slideDown(100);

						// reinvoke CKEditor
						var ck_editor_handler = handle.commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div.ckeditor')
						});

						// reinvoke Colorpicker
						var colorpicker_handler = handle.commsy_functions.getModuleCallback('commsy/colorpicker');
						colorpicker_handler.setup(null, {
							handle:				colorpicker_handler,
							register_on:		jQuery('input.colorpicker')
						});

						// register click for community room assign button
						jQuery('div#tm_dropmenu_configuration input#add_community_room').bind('click', {
							handle:		handle}, handle.onClickAssignCommunityRoom);

						// register click for additional status button
						jQuery('div#tm_dropmenu_configuration input#add_additional_status').bind('click', {
							handle:		handle}, handle.onClickAdditionalStatus);

						// register click for save buttons
						jQuery('div#tm_dropmenu_configuration input#submit').bind('click', {
							handle:		handle}, handle.onSaveConfiguration);

						// setup popup
						handle.setupPopup();

						// setup tabs
						handle.setupTabs(jQuery('div#tm_dropmenu_configuration'));

						// setup configuration popup
						handle.setupConfigurationPopup();
					}
				}
			});

			// stop processing
			return false;
		},

		setupConfigurationPopup: function() {
			var handle = this;

			// update schema preview and set onchange handler
			handle.updateConfigurationSchemaPreview();

			jQuery('select#room_color_choice').change(function() {
				handle.updateConfigurationSchemaPreview();
			});

			// setup color picker
			jQuery('input.colorpicker').colorpicker().on('colorselect', function(e, c) {
				// do something if color
				//console.log("selecte");
			});

			// participation code hiding
			jQuery('input[name="form_data[member_check]"]').each(function() {
				if(jQuery(this).attr('value') == 'withcode') {
					// enable
					jQuery(this).click(function() {
						jQuery('input#code').attr('disabled', false);
					});
				} else {
					// disable
					jQuery(this).click(function() {
						jQuery('input#code').attr('disabled', true);
					});
				}
			});

			/* setup moderation support form elements */
			handle.updateUsageHints(jQuery('select#moderation_rubric').children(':selected').attr('value'));

			jQuery('select#moderation_rubric').change(function(event) {
				// get active moderation rubric
				var active_rubric = jQuery('select#moderation_rubric').children(':selected').attr('value');
				handle.updateUsageHints(active_rubric);
			});

			handle.updateMailText(jQuery('select#mailtext_rubric').children(':selected').attr('id'));

			jQuery('select#mailtext_rubric').change(function(event) {
				// get active value
				var active_mailtext = jQuery('select#mailtext_rubric').children(':selected').attr('id');
				handle.updateMailText(active_mailtext);
			});

			/* setup additional form elements */
			handle.updateUsageContract(jQuery('select#additional_agb_description_text').children(':selected').attr('value'));

			jQuery('select#additional_agb_description_text').change(function(event) {
				// get active value
				var active_lang = jQuery('select#additional_agb_description_text').children(':selected').attr('value');
				handle.updateUsageContract(active_lang);
			});





			/*


			// get value from active bibliographic option
			var select_object = jQuery('select#bibliographic_select');

			// show / hide bibliographic div's
			handle.showHideBibliographic(select_object);

			// register handler for select
			select_object.change(function() {
				// show / hide bibliographic div's
				handle.showHideBibliographic(select_object);
			});
			*/
		},

		updateUsageHints: function(selected_value) {
			// hide all
			jQuery('input[id^="moderation_title_"]').each(function() {
				jQuery(this).addClass('hidden');
			});

			jQuery('div[id^="moderation_description_"]').each(function() {
				jQuery(this).parent().addClass('hidden');
			});

			// show selected
			jQuery('input#moderation_title_' + selected_value).removeClass('hidden');
			jQuery('div#moderation_description_' + selected_value).parent().removeClass('hidden');
		},

		updateUsageContract: function(selected_value) {
			// hide all
			jQuery('div[id^=agb_text_]').each(function() {
				jQuery(this).parent().addClass('hidden').parent().addClass('hidden');
			});

			// show selected
			jQuery('div[id^=agb_text_' + selected_value + ']').parent().removeClass('hidden').parent().removeClass('hidden');
		},

		updateMailText: function(selected_value) {
			// extract index
			var index= '';
			var regex = new RegExp("mail_text_([0-9]*)");
			var results = regex.exec(selected_value);
			if(results !== null && results[1] !== 'NEW') index = results[1];

			// hide all
			jQuery('div[id^=moderation_mail_body_]').each(function() {
				jQuery(this).parent().addClass('hidden').parent().addClass('hidden');
			});

			// show selected
			jQuery('div#moderation_mail_body_de_' + index + ', div#moderation_mail_body_en_' + index).each(function() {
				jQuery(this).parent().removeClass('hidden').parent().removeClass('hidden');
			});
		},

		/*
		 * showHideBibliographic: function(select_object) {
			var key = select_object.children('option:selected').val();

			// go through all bibliographic content div's and show the one who's id matches "bib_content_" + key
			jQuery('div#bibliographic div[id^="bib_content_"]').each(function() {
				if(jQuery(this).attr('id') === 'bib_content_' + key) {
					jQuery(this).show();

					// go through each input field and change the name, if needed, so they will be submitted again
					jQuery(this).find('input, select').each(function() {
						if(jQuery(this).attr('name').substr(0, 14) === 'do_not_submit_') {
							jQuery(this).attr('name', jQuery(this).attr('name').substr(14));
						}
					});
				} else {
					jQuery(this).hide();

					// go through each input field and change the name, if needed, so they won't be submitted
					jQuery(this).find('input, select').each(function() {
						if(jQuery(this).attr('name').substr(0, 14) !== 'do_not_submit_') {
							jQuery(this).attr('name', 'do_not_submit_' + jQuery(this).attr('name'));
						}
					});
				}
			});
		},
		 */

		updateConfigurationSchemaPreview: function() {
			// set image path for preview and handle own schema
			var selected_option_object = jQuery('select#room_color_choice option:selected');
			var selected_value = selected_option_object.val();
			var selected_text = selected_option_object.text();
			var image_object = jQuery('div#room_color_preview img');
			var image_div_object = jQuery('div#room_color_preview');
			var div_object = jQuery('div#room_color_own');

			if(selected_value == 'individual') {
				// hide image preview, show own
				image_div_object.hide();
				div_object.show();
			} else {
				// show image preview, hide own
				image_div_object.show();
				div_object.hide();

				if(selected_value == 'default') selected_text = 'default';

				image_object.attr('src', 'templates/themes/' + selected_text + '/preview.gif');
			}
		},

		onClickAssignCommunityRoom: function(event) {
			var handle = event.data.handle;

			// get id from selected option
			var selected_object = jQuery('select#room_communityrooms option:selected');
			var selected_id = selected_object.attr('value');

			// check if id is a number and greater than -1
			if(!isNaN(selected_id) && selected_id > -1) {
				// check if already assigned
				var assigned = false;
				jQuery('input[name^="form_data[communityroomlist_"]').each(function() {
					// extract id
					var id = '';
					var regex = new RegExp("form_data\\[communityroomlist_([0-9]*)\\]");
					var results = regex.exec(jQuery(this).attr('name'));
					if(results !== null && results[1] !== 'NEW') id = results[1];

					if(id == selected_id) {
						assigned = true;
						return true;
					}
				});

				if(assigned === false) {
					// append new entry
					var div_object = jQuery('div#assigned_community_rooms');

					div_object.append(
						jQuery('<input/>', {
							'id':		'room_communityroomlist',
							type:		'checkbox',
							checked:	true,
							value:		selected_id,
							name:		'form_data[communityroomlist_' + selected_id + ']'
						})
					).append(selected_object.text());
				}
			}
		},

		onClickAdditionalStatus: function(event) {
			var handle = event.data.handle;

			var input_object = jQuery('input#status');

			var value = input_object.attr('value');
			input_object.attr('value', '');

			if(value !== '') {
				// append new entry
				var div_object = jQuery('div#additional_status_list');

				// get new value
				var new_value = 5;
				div_object.children('input').each(function() {
					var iindex = '';
					var regex = new RegExp("form_data\\[additional_status_([0-9]*)\\]");
					var results = regex.exec(jQuery(this).attr('name'));
					if(results !== null && results[1] !== 'NEW') index = results[1];

					if(index >= new_value) new_value = parseInt(index) + 1;
				});


				div_object.append(
					jQuery('<input/>', {
						type:		'checkbox',
						checked:	true,
						value:		value,
						name:		'form_data[additional_status_' + new_value + ']'
					})
				).append(value);
			}
		},

		onSaveConfiguration: function(event) {
			var handle = event.data.handle;
			var target = jQuery(event.target);

			// submit picture
			var form_objects = jQuery('form#logo_upload, form#bg_upload');

			var all = 0;
			form_objects.each(function(index) {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					all++;
				}
			});

			if(all == 0) {
				handle.saveConfiguration(event);
			}

			var index = 0;
			form_objects.each(function() {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					handle.uploadRoomPicture(jQuery(this), index, all, handle.saveConfiguration, event);
					index++;
				}
			});
		},

		saveConfiguration: function(event) {
			var handle = event.data.handle;
			var target = jQuery(event.target);

			// get all form information from current tab
			var col_object = target.parentsUntil('div.tab');
			var form_objects = col_object.find('input[name^="form_data"], select[name^="form_data"]');

			// build object
			var data = {
				form_data: [],
				module: 'configuration',
				additional: {
					tab: col_object.parent().attr('id')
				}
			};

			// add ckeditor data to hidden div
			jQuery('div.ckeditor').each(function() {
				var editor = jQuery(this).ckeditorGet();
				jQuery(this).parent().children('input[type="hidden"]').attr('value', editor.getData());
			});

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

			// ajax request
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=save',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function() {
					console.log("error while processing popup action");
				},
				success: function(data, status) {
					if(data.status === 'success') {
						handle.close();
					} else if(data.status === 'error' && data.code === 101) {
						// mandatory error
						var missing_fields = data.detail;

						// create a red border around the missing fields and scroll to first one
						jQuery.each(missing_fields, function(index, field_name) {
							jQuery.each(form_objects, function() {
								if(jQuery(this).attr('name') === 'form_data[' + field_name + ']') {
									jQuery(this).css('border', '1px solid red');

									if(index === 0 && !jQuery.inviewport(jQuery(this), {threshold: 0})) {
										jQuery('html, body').animate({scrollTop: jQuery(this).offset().top}, 500);
									}
								}
							});
						});
					} else {
						// unhandled error
						console.log('unhandled error');
					}
				}
			});
		},

		uploadRoomPicture: function(form_object, index, all, callback, event) {
			var handle = this;

			// setup ajax form
			form_object.ajaxForm();

			// submit form
			form_object.ajaxSubmit({
				type:		'POST',
				success:	function() {
					if(index+1 == all) callback(event);
				}
			});

			return false;
		},
	};
});