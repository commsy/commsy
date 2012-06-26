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
			
			// setup accounts tab
			require(["commsy/Accounts"], Lang.hitch(this, function(Accounts) {
				var accounts = new Accounts();
				accounts.init(this.cid, this.from_php.template.tpl_path);
			}));
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