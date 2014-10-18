define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/on",
        	"dijit/Tooltip",
        	"dojo/_base/lang",
        	"dojo/i18n!./nls/tooltipErrors"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, request, On, Tooltip, lang, ErrorTranslations) {
	return declare(TogglePopupHandler, {
		sendImages: [],
		
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
				this.closeErrorTooltips();
			}
		},

		setupSpecific: function() {
			var communityRoomInputNode = Query("input#add_community_room", this.contentNode)[0];
			if(communityRoomInputNode) {
				// register click for community room assign button
				On(communityRoomInputNode, "click", lang.hitch(this, function(event) {
					this.onClickAssignCommunityRoom();
				}));
			}
			
			// register click for community room assign checkboxes
			var communityRoomCheckboxNodes = Query("input[name^='form_data[communityroomlist_']");
			if ( communityRoomCheckboxNodes)
			{
				On(communityRoomCheckboxNodes, "click", lang.hitch(this, function(event)
				{
					this.onClickCommunityRoomCheckbox(event);
				}));
			}

			// register click for additional status button
			On(Query("input#add_additional_status", this.contentNode)[0], "click", lang.hitch(this, function(event) {
				this.onClickAdditionalStatus();
			}));

			// update schema preview and set onchange handler
			this.updateConfigurationSchemaPreview();

			On(Query("select#room_color_choice", this.contentNode)[0], "change", lang.hitch(this, function(event) {
				this.updateConfigurationSchemaPreview();
			}));

			// participation code hiding
			dojo.forEach(Query("input[name='form_data[member_check]']", this.contentNode), lang.hitch(this, function(node, index, arr) {
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

			On(moderationRubricNode, "change", lang.hitch(this, function(event) {
				// get active moderation rubric
				var moderationRubricNode = Query("select#moderation_rubric", this.contentNode)[0];
				var activeRubric = DomAttr.get(moderationRubricNode, "value");
				this.updateUsageHints(activeRubric);
			}));

			// handle mail text update
			var mailTextRubricNode = Query("select#mailtext_rubric", this.contentNode)[0];
			var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
			this.updateMailText(DomAttr.get(mailTextRubricChildrenNode, "id"));

			On(mailTextRubricNode, "change", lang.hitch(this, function(event) {
				// get active value
				var mailTextRubricChildrenNode = Query("option:checked", mailTextRubricNode)[0];
				var activeMailtext = DomAttr.get(mailTextRubricChildrenNode, "id");
				this.updateMailText(activeMailtext);
			}));

			// handle usage contract update
			var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
			this.updateUsageContract(DomAttr.get(usageContractNode, "value"));

			On(usageContractNode, "change", lang.hitch(this, function(event) {
				// get active value
				var usageContractNode = Query("select#additional_agb_description_text", this.contentNode)[0];
				var activelang = DomAttr.get(usageContractNode, "value");
				this.updateUsageContract(activelang);
			}));

			dojo.ready(lang.hitch(this, function() {
				// setup callback for single uploads
				this.featureHandles["upload-single"][0].setCallback(lang.hitch(this, function(fileInfo) {
					// room logo upload
					
					// setup preview
					var formNode = this.featureHandles["upload-single"][0].uploader.form;
					var previewNode = Query("div.filePreview", formNode)[0];
					
					DomConstruct.empty(previewNode);
					
					DomConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");
					
					this.sendImages.push({ part: "room_logo", fileInfo: fileInfo });
				}));

				this.featureHandles["upload-single"][1].setCallback(lang.hitch(this, function(fileInfo) {
					// room background
					
					// setup preview
					var formNode = this.featureHandles["upload-single"][1].uploader.form;
					var previewNode = Query("div.filePreview", formNode)[0];
					
					DomConstruct.empty(previewNode);
					
					DomConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");
					
					this.sendImages.push({ part: "room_bg", fileInfo: fileInfo });
				}));
			}));

			// setup accounts tab
			require(["commsy/Accounts"], lang.hitch(this, function(Accounts) {
				var accounts = new Accounts();
				accounts.init(this.cid, this.from_php.template.tpl_path);

				// check for auto load tab
				var autoOpen = this.from_php.autoOpenPopup;
				if (autoOpen) {
					var aNode = Query("a[href='" + autoOpen.tab + "']")[0];
					if (aNode) {
						accounts.setStatus(autoOpen.parameters.filter);
						aNode.click();
					}
				}
			}));
			
			// confirm delete Wordpress
			var deleteWordpressButton = Query("#submit_delete_wordpress", this.contentNode)[0];
			if (deleteWordpressButton) {
				On(deleteWordpressButton, "click", lang.hitch(this, function(event) {
					this.button_delete = new dijit.form.Button({
						label:		"Blog endg&uuml;ltig l&ouml;schen",
						onClick:	lang.hitch(this, function(event) {
							this.onPopupSubmit({
                        part: "external_configuration",
                        action: "delete_wordpress"
                     });
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					this.button_cancel = new dijit.form.Button({
						label:		"Abbrechen",
						onClick:	lang.hitch(this, function(event) {
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					// create and show the dialog
					// TODO: translate
					this.dialog = new dijit.Dialog({
						title:		"Wordpress l&ouml;schen",
						content: 	"<b style='color:#ff0000;'>Achtung: Alle Daten im Blog werden gel&ouml;scht. Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b><br/><br/>"
					});
					dojo.place(this.button_delete.domNode, this.dialog.containerNode);
					dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
					
					this.dialog.show();
				}));
			}
			
		   // confirm delete Wiki
         var deleteWikiButton = Query("#submit_delete_wiki", this.contentNode)[0];
         if (deleteWikiButton) {
            On(deleteWikiButton, "click", lang.hitch(this, function(event) {
               this.button_delete = new dijit.form.Button({
                  label:      "Wiki endg&uuml;ltig l&ouml;schen",
                  onClick: lang.hitch(this, function(event) {
                     this.onPopupSubmit({
                        part: "external_configuration",
                        action: "delete_wiki"
                     });
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               this.button_cancel = new dijit.form.Button({
                  label:      "Abbrechen",
                  onClick: lang.hitch(this, function(event) {
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               // create and show the dialog
               // TODO: translate
               this.dialog = new dijit.Dialog({
                  title:      "Wiki l&ouml;schen",
                  content:	  "<b style='color:#ff0000;'>Ein gel&ouml;schtes Wiki kann nicht wieder rekonstruiert werden. M&ouml;chten Sie dieses Wiki endg&uuml;ltig l&ouml;schen?</b><br/><br/>"
               });
               dojo.place(this.button_delete.domNode, this.dialog.containerNode);
               dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
               
               this.dialog.show();
            }));
         }
         
         	// confirm delete room
			var deleteWordpressButton = Query("#submit_delete_room", this.contentNode)[0];
			if (deleteWordpressButton) {
				On(deleteWordpressButton, "click", lang.hitch(this, function(event) {
					this.button_delete = new dijit.form.Button({
						label:		"Raum endg&uuml;ltig l&ouml;schen",
						onClick:	lang.hitch(this, function(event) {
							this.onPopupSubmit({
			                   part: "room_configuration",
			                   action: "delete_room"
			                });
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					this.button_cancel = new dijit.form.Button({
						label:		"Abbrechen",
						onClick:	lang.hitch(this, function(event) {
							// destroy the dialog
							this.dialog.destroyRecursive();
						})
					});
					
					// create and show the dialog
					// TODO: translate
					this.dialog = new dijit.Dialog({
						title:		"Raum l&ouml;schen",
						content: 	"<b style='color:#ff0000;'>Achtung: Der gesamte Raum und alle Daten im Raum werden gel&ouml;scht. Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b><br/><br/>"
					});
					dojo.place(this.button_delete.domNode, this.dialog.containerNode);
					dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
					
					this.dialog.show();
				}));
			}
		},

		onClickAssignCommunityRoom: function() {
			// get id from selected option
			var selectNode = Query("select#room_communityrooms", this.contentNode)[0];
			var selectedId = DomAttr.get(selectNode, "value");
			// check if id is a number and greater than -1
			if(!isNaN(selectedId) && selectedId > -1) {
				// check if already assigned
				var assigned = false;
				dojo.forEach(Query("input[name^='form_data[communityroomlist_']"), function(node, index, arr) {
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

					var inputNode = DomConstruct.create("input", {
						id:			"room_communityroomlist",
						type:		"checkbox",
						checked:	true,
						value:		selectedId,
						name:		"form_data[communityroomlist_" + selectedId + "]"
					}, divNode, "last");

					var roomName = DomAttr.get(Query("option[value='" + selectedId + "']", selectNode)[0], "innerHTML");
					DomConstruct.create("span", {
						innerHTML:	roomName
					}, divNode, "last");
					
					On(inputNode, "click", lang.hitch(this, function(event)
					{
						this.onClickCommunityRoomCheckbox(event);
					}));
				}
			}
		},
		
		onClickCommunityRoomCheckbox: function(event)
		{
			// if project rooms must be linked to community rooms
			if ( this.from_php.environment.portal_link_status === "mandatory" )
			{
				// get number of all checked checkboxes
				var numChecked = Query("input[name^='form_data[communityroomlist_']:checked").length;
				if ( numChecked === 0 )
				{
					// display info box
					var errorNode = Query("select#room_communityrooms", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.contextRoom1021, errorNode);
					this.errorNodes.push(errorNode);
					
					event.preventDefault();
					event.stopPropagation();
				}
			}
		},

		onClickAdditionalStatus: function() {
			var inputObject = Query("input#status")[0];

			var value = DomAttr.get(inputObject, "value");
			DomAttr.set(inputObject, "value", "");

			if(value !== "") {
				// append new entry
				var divObject = Query("div#additional_status_list")[0];

				// get new value
				var newValue = 5;
				dojo.forEach(Query("input", divObject), function(node, index, arr) {
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

				DomConstruct.create("span", {
					innerHTML:	value
				}, divObject, "last");
			}
		},

		updateConfigurationSchemaPreview: function() {
			// set image path for preview and handle own schema
			var selectedOptionNode = Query("select#room_color_choice option:checked", this.contentNode)[0];
			var selectedValue = DomAttr.get(selectedOptionNode, "value");
			//var selectedText = DomAttr.get(selectedOptionNode, "innerHTML");
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

				//if(selectedValue === "default") selectedText = "default";
				DomAttr.set(imageNode, "src", "templates/themes/" + selectedValue + "/preview.gif");
			}
		},

		updateUsageHints: function(selectedValue) {
			// hide all
			dojo.forEach(Query("input[id^='moderation_title_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node, "hidden");
			});

			dojo.forEach(Query("div[id^='moderation_description_'], textarea[id^='moderation_description_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden");
			});

			// show selected
			DomClass.remove(Query("input#moderation_title_" + selectedValue, this.contentNode)[0], "hidden");
			DomClass.remove(Query("div#moderation_description_" + selectedValue + ", textarea#moderation_description_" + selectedValue, this.contentNode)[0].parentNode, "hidden");
		},

		updateMailText: function(selectedValue) {
			// extract index
			var regex = new RegExp("mail_text_([0-9]*)");
			var results = regex.exec(selectedValue);
			var index = results[1];

			// hide all
			dojo.forEach(Query("textarea[id^='moderation_mail_body_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden");
				DomClass.add(node.parentNode.parentNode, "hidden");
			});

			// show selected
			dojo.forEach(Query("textarea#moderation_mail_body_de_" + index + ", textarea#moderation_mail_body_en_" + index, this.contentNode), function(node, index, arr) {
				DomClass.remove(node.parentNode, "hidden");
				DomClass.remove(node.parentNode.parentNode, "hidden");
			});
		},

		updateUsageContract: function(selectedValue) {
			// hide all
			dojo.forEach(Query("div[id^='agb_text_']", this.contentNode), function(node, index, arr) {
				DomClass.add(node.parentNode, "hidden");
				DomClass.add(node.parentNode.parentNode, "hidden");
			});

			// show selected
			var node = Query("div[id^='agb_text_" + selectedValue + "']")[0];
			DomClass.remove(node.parentNode, "hidden");
			DomClass.remove(node.parentNode.parentNode, "hidden");
		},

		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			var action = customObject.action;

			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
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
			
			this.submit(search, { part: part, action: action });
		},

		onPopupSubmitSuccess: function(item_id) {
			// save images
			if (this.sendImages.length > 0) {
				var data = {
					module:			"configuration",
					additional: {
					    part:		this.sendImages[0].part,
					    fileInfo:	this.sendImages[0].fileInfo
					}
				};
				
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'popup',
						action:	'save'
					},
					data: data
				}).then(
					lang.hitch(this, function(response) {
						if (this.sendImages[1]) {
							var data = {
								module:			"configuration",
								additional: {
								    part:		this.sendImages[1].part,
								    fileInfo:	this.sendImages[1].fileInfo
								}
							};
							
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'popup',
									action:	'save'
								},
								data: data
							}).then(
								lang.hitch(this, function(response) {
									location.reload();
								})
							);
						} else {
							location.reload();
						}
					})
				);
			} else {
				if (!item_id) {
					location.reload();
				} else {
					location.href = "commsy.php?cid=" + item_id;
				}
			}
		}
	});
});