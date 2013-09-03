define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dijit/Tooltip",
        	"dojo/_base/lang",
        	"dojo/i18n!./nls/tooltipErrors"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Tooltip, Lang, ErrorTranslations) {
	return declare(TogglePopupHandler, {
		sendImages: [],
		
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "profile";
			this.dialog = null;
			this.button = null;

			this.features = [ "editor", "upload-single" ];

			// register click for node
			this.registerPopupClick();
		},

		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_user_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_user_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},

		setupSpecific: function() {
			dojo.ready(Lang.hitch(this, function() {
				// setup callback for single upload
				this.featureHandles["upload-single"][0].setCallback(Lang.hitch(this, function(fileInfo) {
					// setup preview
					var formNode = this.featureHandles["upload-single"][0].uploader.form;
					var previewNode = Query("div.filePreview", formNode)[0];
					
					DomConstruct.empty(previewNode);
					
					DomConstruct.create("img", {
						src:		"commsy.php?cid=" + this.uri_object.cid + "&mod=picture&fct=getTemp&fileName=" + fileInfo.file
					}, previewNode, "last");
					
					this.sendImages.push({ part: "user_picture", fileInfo: fileInfo });
				}));

				// setup account delete handling
				On(Query("input#delete", this.contentNode)[0], "click", Lang.hitch(this, function() {
					DomClass.remove(Query("div#delete_options", this.contentNode)[0], "hidden");

					// register handler
					On(Query("input#lock_room", this.contentNode)[0], "click", Lang.hitch(this, function() {
						this.onPopupSubmit({ part: "account_lock_room" });
					}));
					On(Query("input#delete_room", this.contentNode)[0], "click", Lang.hitch(this, function() {
						this.onPopupSubmit({ part: "account_delete_room" });
					}));
					On(Query("input#lock_portal", this.contentNode)[0], "click", Lang.hitch(this, function() {
						this.onPopupSubmit({ part: "account_lock_portal" });
					}));
					On(Query("input#delete_portal", this.contentNode)[0], "click", Lang.hitch(this, function() {
						this.onPopupSubmit({ part: "account_delete_portal" });
					}));
				}));
			}));

			console.log(this.from_php);
			if(this.from_php.password.length || this.from_php.password.big || this.from_php.password.small || this.from_php.password.special || this.from_php.password.number){
				var ulNode = DomConstruct.create('ul',{
					
				});
				if(this.from_php.password.length){
					DomConstruct.create('li',{
						innerHTML: this.from_php.password.length
					},ulNode,'last');
				}
				if(this.from_php.password.big){
					DomConstruct.create('li',{
						innerHTML: this.from_php.password.big
					},ulNode,'last');
				}
				if(this.from_php.password.small){
					DomConstruct.create('li',{
						innerHTML: this.from_php.password.small
					},ulNode,'last');
				}
				if(this.from_php.password.special){
					DomConstruct.create('li',{
						innerHTML: this.from_php.password.special
					},ulNode,'last');
				}
				if(this.from_php.password.number){
					DomConstruct.create('li',{
						innerHTML: this.from_php.password.number
					},ulNode,'last');
				}
				new Tooltip({
			        connectId: Query("input[name='form_data[new_password]']", this.contentNode),
			        label: ulNode.outerHTML
			    });
			}

			// check for auto load tab
			var autoOpen = this.from_php.autoOpenPopup;
			if (autoOpen) {
				var aNode = Query("a[href='" + autoOpen.tab + "']")[0];
				if (aNode) {
					aNode.click();
				}
			}
			
		   // confirm delete Wordpress
         var deleteWordpressButton = Query("#submit_delete_wordpress", this.contentNode)[0];
         if (deleteWordpressButton) {
            On(deleteWordpressButton, "click", Lang.hitch(this, function(event) {
               this.button_delete = new dijit.form.Button({
                  label:      "Blog endg&uuml;ltig l&ouml;schen",
                  onClick: Lang.hitch(this, function(event) {
                     this.onPopupSubmit({
                        part: "cs_bar",
                        action: "delete_wordpress"
                     });
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               this.button_cancel = new dijit.form.Button({
                  label:      "Abbrechen",
                  onClick: Lang.hitch(this, function(event) {
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               // create and show the dialog
               // TODO: translate
               this.dialog = new dijit.Dialog({
                  title:      "Wordpress l&ouml;schen",
                  content:    "<b style='color:#ff0000;'>Achtung: Alle Daten im Blog werden gel&ouml;scht. Dieser Vorgang kann nicht r&uuml;ckg&auml;ngig gemacht werden!</b><br/><br/>"
               });
               dojo.place(this.button_delete.domNode, this.dialog.containerNode);
               dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
               
               this.dialog.show();
            }));
         }
         
         // confirm delete Wiki
         var deleteWikiButton = Query("#submit_delete_wiki", this.contentNode)[0];
         if (deleteWikiButton) {
            On(deleteWikiButton, "click", Lang.hitch(this, function(event) {
               this.button_delete = new dijit.form.Button({
                  label:      "Wiki endg&uuml;ltig l&ouml;schen",
                  onClick: Lang.hitch(this, function(event) {
                     this.onPopupSubmit({
                        part: "cs_bar",
                        action: "delete_wiki"
                     });
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               this.button_cancel = new dijit.form.Button({
                  label:      "Abbrechen",
                  onClick: Lang.hitch(this, function(event) {
                     // destroy the dialog
                     this.dialog.destroyRecursive();
                  })
               });
               
               // create and show the dialog
               // TODO: translate
               this.dialog = new dijit.Dialog({
                  title:      "Wiki l&ouml;schen",
                  content:   "<b style='color:#ff0000;'>Ein gel&ouml;schtes Wiki kann nicht wieder rekonstruiert werden. M&ouml;chten Sie dieses Wiki endg&uuml;ltig l&ouml;schen?</b><br/><br/>"
               });
               dojo.place(this.button_delete.domNode, this.dialog.containerNode);
               dojo.place(this.button_cancel.domNode, this.dialog.containerNode);
               
               this.dialog.show();
            }));
         }
		},

		createConfirmBox: function() {
			// create button
			this.button = new dijit.form.Button({
				label: "delete",
				onClick:	Lang.hitch(this, function(event) {
					// process submit
					this.onPopupSubmit({ part: "account_delete" });

					// destroy the dialog
					this.dialog.destroyRecursive();
				})
			});

			// create and show the dialog
			this.dialog = new dijit.Dialog({
				title:		""
			});
			dojo.place(this.button.domNode, this.dialog.containerNode, "last");

			this.dialog.show();
		},

		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			var action = customObject.action;

			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				DomAttr.set(Query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});
			
			console.log(part);

			// setup data to send via ajax
			if(part === "user" || part === "newsletter" || part === "cs_bar" || part === "addon_configuration" ) {
				var search = {
					tabs: [
					    { id: part }
					],
					nodeLists: []
				};
			} else if(part === "account") {
				var search = {
					tabs: [],
					nodeLists: [
						{ query: Query("input[name='form_data[forname]']", this.contentNode) },
						{ query: Query("input[name='form_data[surname]']", this.contentNode) },
						{ query: Query("input[name='form_data[user_id]']", this.contentNode) },
						{ query: Query("input[name='form_data[old_password]']", this.contentNode) },
						{ query: Query("input[name='form_data[new_password]']", this.contentNode) },
						{ query: Query("input[name='form_data[new_password_confirm]']", this.contentNode) },
						{ query: Query("select[name='form_data[language]']", this.contentNode) },
						{ query: Query("input[name='form_data[upload]']", this.contentNode) },
						{ query: Query("input[name='form_data[auto_save]']", this.contentNode) },
						{ query: Query("input[name='form_data[mail_account]']", this.contentNode) },
						{ query: Query("input[name='form_data[mail_room]']", this.contentNode) }
					]
				};
				
			} else if(part === "account_merge") {
				var search = {
					tabs: [],
					nodeLists: [
						{ query: Query("input[name='form_data[merge_user_id]']", this.contentNode) },
						{ query: Query("input[name='form_data[merge_user_password]']", this.contentNode) },
						{ query: Query("select[name='form_data[auth_source]']", this.contentNode) }
					]
				};
			} else {
				// account delete
				var search = {
					tabs: [],
					nodeLists: []
				};
			}
			
			if (this.sendImages.length > 0) {
				// send ajax request
				var data = {
					module:			"profile",
					additional: {
					    part:		this.sendImages[0].part,
					    fileInfo:	this.sendImages[0].fileInfo
					}
				};
				
				this.AJAXRequest("popup", "save", data, Lang.hitch(this, function(response) {
					this.submit(search, { part: part, action: action });
				}));
			} else {
				this.submit(search, { part: part, action: action });
			}
		},
		
		/************************************************************************************
		 * Success Handling
		 ************************************************************************************/

		onPopupSubmitSuccess: function(item_id) {
			location.reload();
		},
		
		/************************************************************************************
		 * Error Handling
		 ************************************************************************************/
		onPopupSubmitError: function(response) {
			// process parent error handling
			this.inherited(arguments);
			
			switch (response.code) {
				case "1022":
					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
					//console.log(response.reason);
					var ulNode = DomConstruct.create('ul',{
					});
					for(var i=0; i<response.reason.length; i++){
						DomConstruct.create('li',{
							innerHTML: response.reason[i]
						},ulNode,'last');
						  //console.debug(response.reason[i], "at index", i);
					}
					console.log(response.reason.length);
					Tooltip.show(ulNode.outerHTML, errorNode);
					this.errorNodes.push(errorNode);
					
					break;	
//				case "1023":
//					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
//					Tooltip.show(ErrorTranslations.personalPopup1023, errorNode);
//					this.errorNodes.push(errorNode);
//					
//					break;	
//				case "1024":
//					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
//					Tooltip.show(ErrorTranslations.personalPopup1024, errorNode);
//					this.errorNodes.push(errorNode);
//					
//					break;	
				case "1025":
					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.personalPopup1025, errorNode);
					this.errorNodes.push(errorNode);
//					
//					break;
//				case "1026":
//					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
//					Tooltip.show(ErrorTranslations.personalPopup1026, errorNode);
//					this.errorNodes.push(errorNode);
//					
//					break;	
//				case "1027":
//					var errorNode = Query("input[name='form_data[new_password_confirm]']", this.contentNode)[0];
//					Tooltip.show(ErrorTranslations.personalPopup1027, errorNode);
//					this.errorNodes.push(errorNode);
					
					break;	
				case "1011":			/* user id already registered */
					var errorNode = Query("input[name='form_data[user_id]']", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.personalPopup1011, errorNode);
					this.errorNodes.push(errorNode);
					
					break;
				
				case "1012":			/* user id contains umlaute */
					break;
				
				case "1013":			/* error in auth source */
					break;
				
				case "1014":			/* anonymous account */
					var errorNode = Query("input[name='form_data[merge_user_id]']", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.personalPopup1014, errorNode);
					this.errorNodes.push(errorNode);
					
					break;
				
				case "1015":			/* invalid account */
					var errorNode = Query("input[name='form_data[merge_user_id]']", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.personalPopup1015, errorNode);
					this.errorNodes.push(errorNode);
					
					break;
				
				case "1016":			/* authentication errror */
					var errorNode = Query("input[name='form_data[merge_user_id]']", this.contentNode)[0];
					Tooltip.show(ErrorTranslations.personalPopup1016, errorNode);
					this.errorNodes.push(errorNode);
					
					break;
			}
		}
	});
});