define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "configuration";
			
			this.features = [ "editor", "upload", "colorpicker" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_settings_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_settings_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
			var communityRoomInputNode = Query("input#add_community_room", this.contentNode)[0];
			if(communityRoomInputNode) {
				// register click for community room assign button
				On(communityRoomInputNode, "click", Lang.hitch(this, function(event) {
					this.onClickAssignCommunityRoom();
				}));
			}
			
			// register click for additional status button
			On(Query("input#add_additional_status", this.contentNode)[0], "click", Lang.hitch(this, function(event) {
				this.onClickAdditionalStatus();
			}));
			
			// update schema preview and set onchange handler
			this.updateConfigurationSchemaPreview();
			
			On(Query("select#room_color_choice", this.contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.updateConfigurationSchemaPreview();
			}));
			
			// participation code hiding
			Query("input[name='form_data[member_check]']", this.contentNode).forEach(Lang.hitch(this, function(node, index, arr) {
				if(DomAttr.get(node, "value") === "withcode") {
					// enable
					On(node, "click", function(event) {
						DomAttr.set(Query("input#code", this.contentNode)[0], "disabled", false);
					});
				} else {
					// disable
					On(node, "click", function(event) {
						DomAttr.set(Query("input#code", this.contentNode)[0], "disabled", true);
					});
				}
			}));
			
			// setup moderation support form elements
			var moderationRubricNode = Query("select#moderation_rubric", this.contentNode)[0];
			this.updateUsageHints(DomAttr.get(moderationRubricNode, "value"));
			
			On(moderationRubricNode, "change", Lang.hitch(this, function(event) {
				// get active moderation rubric
				var moderationRubricNode = Query("select#moderation_rubric", this.contentNode)[0];
				var activeRubric = DomAttr.get(moderationRubricNode, "value");
				this.updateUsageHints(activeRubric);
			}));
			
			// handle mail text update
			var mailTextRubricNode = Query("select#mailtext_rubric", this.contentNode)[0];
			var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
			this.updateMailText(DomAttr.get(mailTextRubricChildrenNode, "id"));
			
			On(mailTextRubricNode, "change", Lang.hitch(this, function(event) {
				// get active value
				var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
				var activeMailtext = DomAttr.get(mailTextRubricChildrenNode, "id");
				this.updateMailText(activeMailtext);
			}));
			
			// handle usage contract update
			var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
			this.updateUsageContract(DomAttr.get(usageContractNode, "value"));
			
			On(usageContractNode, "change", Lang.hitch(this, function(event) {
				// get active value
				var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
				var activeLang = DomAttr.get(usageContractNode, "value");
				this.updateUsageContract(activeLang);
			}));
			
			// setup account
			
			/*
			var accounts = new Accounts();
			accounts.init(handle.cid, handle.preconditions.template.tpl_path);
			*/
		},
		
		onClickAssignCommunityRoom: function() {
			// get id from selected option
			var selectNode = Query("select#room_communityrooms", this.contentNode)[0];
			var selectedId = DomAttr.get(selectNode, "value");
			
			// check if id is a number and greater than -1
			if(!isNaN(selectedId) && selectedId > -1) {
				// check if already assigned
				var assigned = false;
				Query("input[name^='form_data[communityroomlist_']").forEach(function(node, index, arr) {
					// extract id
					var regex = new RegExp("form_data\\[communityroomlist_([0-9]*)\\]");
					var results = regex.exec(DomAttr.get(node, "name"));
					var id = results[1];
					
					if(id == selectedId) {
						assigned = true;
						return false;
					}
				});
				
				if(assigned === false) {
					// append new entry
					var divNode = Query("div#assigned_community_rooms", this.contentNode)[0];
					
					DomConstruct.create("input", {
						id:			"room_communityroomlist",
						type:		"checkbox",
						checked:	true,
						value:		selectedId,
						name:		"form_data[communityroomlist_" + selectedId + "]"
					}, divNode, "last");
					
					DomConstruct.create(DomAttr.get(selectNode, "innerHTML"), divNode, "last");
				}
			}
		},
		
		onClickAdditionalStatus: function() {
			var inputObject = Query("input#state")[0];
			
			var value = DomAttr(inputObject, "value");
			DomAttr.set(inputObject, "value", "");
			
			if(value !== "") {
				// append new entry
				var divObject = Query("div#additional_status_list");
				
				// get new value
				var newValue = 5;
				Query("input", divObject).forEach(function(node, index, arr) {
					var regex = new RegExp("form_data\\[additional_status_([0-9]*)\\]");
					var results = regex.exec(DomAttr.get(node, "name"));
					var index = results[1];
					
					if(index >= newValue) newValue = parseInt(index) + 1;
				});
				
				DomConstruct.create("input", {
					type:		"checkbox",
					checked:	true,
					value:		value,
					name:		"form_data[additional_status_" + newValue + "]"
				}, divObject, "last");
				
				DomConstruct.create(value, divObject, "last");
			}
		},
		
		updateConfigurationSchemaPreview: function() {
			// set image path for preview and handle own schema
			var selectedOptionNode = Query("select#room_color_choice option:checked", this.contentNode)[0];
			var selectedValue = DomAttr.get(selectedOptionNode, "value");
			var selectedText = DomAttr.get(selectedOptionNode, "innerHTML");
			var imageNode = Query("div#room_color_preview img", this.contentNode)[0];
			var imageDivNode = Query("div#room_color_preview", this.contentNode)[0];
			var divNode = Query("div#room_color_own", this.contentNode)[0];
			
			if(selectedValue === "individual") {
				// hide image preview, show own
				DomClass.add(imageDivNode, "hidden");
				DomClass.remove(divNode, "hidden");
			} else {
				// show image preview, hide own
				DomClass.remove(imageDivNode, "hidden");
				DomClass.add(divNode, "hidden");
				
				if(selectedValue === "default") selectedText = "default";
				DomAttr.set(imageNode, "src", "templates/themes/" + selectedValue + "/preview.gif");
			}
		},
		
		updateUsageHints: function(selectedValue) {
			// hide all
			Query("input[id^='moderation_title_']", this.contentNode).forEach(function(node, index, arr) {
				DomClass.add(node, "hidden");
			});
			
			Query("div[id^='moderation_description_']", this.contentNode).forEach(function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden");
			});
			
			// show selected
			DomClass.remove(Query("input#moderation_title_" + selectedValue, this.contentNode)[0], "hidden");
			DomClass.remove(Query("div#moderation_description_" + selectedValue, this.contentNode)[0].parentNode, "hidden");
		},
		
		updateMailText: function(selectedValue) {
			// extract index
			var regex = new RegExp("mail_text_([0-9]*)");
			var results = regex.exec(selectedValue);
			var index = results[1];
			
			// hide all
			Query("div[id^='moderation_mail_body_']", this.contentNode).forEach(function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden")
				DomClass.add(node.parentNode.parentNode, "hidden");
			});
			
			// show selected
			Query("div#moderation_mail_body_de_" + index + ", div#moderation_mail_body_en_" + index, this.contentNode).forEach(function(node, index, arr) {
				DomClass.remove(node.parentNode, "hidden")
				DomClass.remove(node.parentNode.parentNode, "hidden");
			});
		},
		
		updateUsageContract: function(selectedValue) {
			// hide all
			Query("div[id^='agb_text_']", this.contentNode).forEach(function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden")
				DomClass.add(node.parentNode.parentNode, "hidden");
			});
			
			// show selected
			var node = Query("div[id^='agb_text_" + selectedValue + "']")[0];
			DomClass.remove(node.parentNode, "hidden");
			DomClass.remove(node.parentNode.parentNode, "hidden");
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			// add ckeditor data to hidden div
			this.featureHandles["editor"].forEach(function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;
				
				DomAttr.set(Query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});
			
			// setup data to send via ajax
			var search = {
				tabs: [
				    { id: part }
				],
				nodeLists: [
				]
			};
			
			this.submit(search, { part: part });
			
			/*
			 * var handle = event.data.handle;
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
		
				thishandle.saveConfiguration(event);
				
				
			}
			
			var index = 0;
			form_objects.each(function() {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					handle.uploadRoomPicture(jQuery(this), index, all, handle.saveConfiguration, event);
					index++;
				}
			});
			 */
		},
		
		onPopupSubmitSuccess: function(item_id) {
			this.close();
		}
	});
});

/*


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
				 *//*
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
				 *//*
				var earliest_dummy_streak_appearance = -1;

				/*
				 * holds the latest appearance of a room
				 * D D D D R D D R D D D D D
				 * 				/\
				 * 				||
				 *//*
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

/* Accounts Class *//*
var Accounts = function() {
	return {
		cid: 						null,
		tpl_path: 					'',
		initialized: 				false,
		paging: {
			current: 0
		},
		restrictions: {
			search:					'',
			status:					7
		},
		store: {
			pages:					1,
			selected_ids:			[]
		},
		translations:				null,

		init: function(cid, tpl_path) {
			this.cid = cid;
			this.tpl_path = tpl_path;

			var handle = this;

			// register onclick handler
			jQuery('#popup_account_tab').click(function() {
				// get inital data if this is the first call
				if(handle.initialized === false) {
					handle.ajaxRequest('getInitialData', {}, function(data) {
						handle.translations = data.translations;
						
						// setup paging
						handle.setupPaging();

						// setup restrictions
						handle.setupRestrictions();

						// setup form submit
						jQuery('input[name="accounts_submit_restrictions"]').click(function() {
							handle.performRequest();
							
							// reset selected ids
							handle.store.selected_ids = [];
							
							// reset paging
							handle.paging.current = 0;
							handle.performRequest();

							return false;
						});
						
						// setup action submit
						jQuery('input[id="list_action_submit"]').bind('click', { handle: handle }, handle.onActionSubmit);

						// perform first request
						handle.performRequest();

						handle.initialized = true;
					});
				}
			});
		},

		setupRestrictions: function() {
			var content_object = jQuery('.pop_item_content');
			var handle = this;

			// status restriction
			content_object.find('select[name="accounts_status_restriction"]').change(function(event) {
				handle.restrictions.status = jQuery(event.target).val();

				return false;
			});

			// search restriction
			content_object.find('input[name="accounts_search_restriction"]').change(function(event) {
				handle.restrictions.search = jQuery(event.target).val();

				return false;
			});
		},

		setupPaging: function() {
			var navigation_object = jQuery('.pop_item_navigation');
			var handle = this;

			// first
			navigation_object.children('#first').click(function() {
				if(handle.paging.current > 0) {
					handle.paging.current = 0;
					handle.performRequest();
				}

				return false;
			});

			// previous
			navigation_object.children('#prev').click(function() {
				if(handle.paging.current > 0) {
					handle.paging.current -= 1;
					handle.performRequest();
				}

				return false;
			});

			// next
			navigation_object.children('#next').click(function() {
				if(handle.paging.current + 1 < handle.store.pages) {
					handle.paging.current += 1;
					handle.performRequest();
				}

				return false;
			})

			// last
			navigation_object.children('#last').click(function() {
				if(handle.paging.current + 1 < handle.store.pages) {
					handle.paging.current = handle.store.pages - 1;
					handle.performRequest();
				}

				return false;
			})
		},

		performRequest: function() {
			var handle = this;

			// create data object for request
			var data = {
				current_page:	this.paging.current,
				restrictions:	this.restrictions
			};

			// send request
			this.ajaxRequest('performRequest', data, function(ret) {
				var content_object = jQuery('#popup_accounts #crt_row_area');

				// fill list
				content_object.empty();
				
				jQuery.each(ret.list, function(index) {
					content_object.append(
						jQuery('<div/>', {
							'class':	(index % 2 === 0) ? 'pop_row_even' : 'pop_row_odd'
						}).append(
							jQuery('<div/>', {
								'class':	'pop_col_25'
							}).append(
								jQuery('<input/>', {
									type:		'checkbox',
									id:			'user_' + this.item_id,
									checked:	(jQuery.inArray(this.item_id, handle.store.selected_ids) !== -1) ? true : false
								})
							)				
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_270',
								text:		this.fullname
							})
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_150',
								text:		this.status
							})
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_150',
								text:		this.email
							})
						).append(
							jQuery('<div/>', {
								'class':	'clear'
							})
						)
					);
				});
				
				// register input event handler and store / remove selected ids
				content_object.find('input[id^="user_"]').click(function(event) {
					var input_object = jQuery(event.target);
					
					// extract id
					var item_id = input_object.attr('id').substr(5);
					
					var index = jQuery.inArray(item_id, handle.store.selected_ids);
					if(index === -1) handle.store.selected_ids.push(item_id);
					else handle.store.selected_ids.splice(index, 1);
				});

				// update current page and total number of pages
				jQuery('#pop_item_current_page').text((ret.list.length === 0) ? 0 : handle.paging.current + 1);
				jQuery('#pop_item_pages').text(ret.paging.pages);

				// store pages
				handle.store.pages = ret.paging.pages;
			});
		},
		
		onActionSubmit: function(event) {
			var handle = event.data.handle;
			
			// get current action
			var action = jQuery('select#list_action').attr('value');
			
			// send action and id list via ajax
			handle.ajaxRequest('performUserAction', { ids: handle.store.selected_ids, action: action }, function() {
				// load mail popup information
				handle.ajaxRequestHTML({ ids: handle.store.selected_ids, action: action, module: 'configuration_mail' }, function(html) {
					jQuery('div#popup_accounts_mail').html(html);
				});
			});
		},

		ajaxRequest: function(action, data, callback) {
			var handle = this;

			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=accounts&action=' + action,
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				async: false,
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(ret, status) {
					if(ret.status === 'success') {
						if(callback !== null) {
							callback(ret.data);
						}
					}
				}
			});
		},
		
		ajaxRequestHTML: function(data, callback) {
			var handle = this;

			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				async: false,
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(ret, status) {
					if(ret.status === 'success') {
						if(callback !== null) {
							callback(ret.html);
						}
					}
				}
			});
		}
	}
};*/