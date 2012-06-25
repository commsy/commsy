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
			
			this.features = [ "editor", "upload", "upload-single", "colorpicker" ];
			
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
			
			dojo.ready(Lang.hitch(this, function() {
				// setup callback for single upload
				this.featureHandles["upload-single"][0].setCallback(Lang.hitch(this, function(fileInfo) {
					// send ajax request
					var data = {
						module:			"configuration",
						additional: {
						    part:		"room_logo",
						    fileInfo:	fileInfo
						}
					};
					
					this.AJAXRequest("popup", "save", data, function(response) {
						// maybe change the picture in-time
					});
				}));
				
				this.featureHandles["upload-single"][1].setCallback(Lang.hitch(this, function(fileInfo) {
					// send ajax request
					var data = {
						module:			"configuration",
						additional: {
						    part:		"room_bg",
						    fileInfo:	fileInfo
						}
					};
					
					this.AJAXRequest("popup", "save", data, function(response) {
						// maybe change the picture in-time
					});
				}));
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
		},
		
		onPopupSubmitSuccess: function(item_id) {
			this.close();
		}
	});
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