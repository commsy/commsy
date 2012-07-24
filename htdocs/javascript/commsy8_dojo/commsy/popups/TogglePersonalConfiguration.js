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
					// send ajax request
					var data = {
						module:			"profile",
						additional: {
						    part:		"user_picture",
						    fileInfo:	fileInfo
						}
					};

					this.AJAXRequest("popup", "save", data, function(response) {
						// maybe change the picture in-time
					});
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

			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				DomAttr.set(Query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			if(part === "user" || part === "newsletter" || part === "cs_bar") {
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
						{ query: Query("input[name='form_data[auto_save]']", this.contentNode) }
					]
				};
			} else if(part === "account_merge") {
				var search = {
					tabs: [],
					nodeLists: [
						{ query: Query("input[name='form_data[merge_user_id]']", this.contentNode) },
						{ query: Query("input[name='form_data[merge_user_password]']", this.contentNode) }
					]
				};
			} else {
				// account delete
				var search = {
					tabs: [],
					nodeLists: []
				};
			}

			this.submit(search, { part: part });
		},

		onPopupSubmitSuccess: function(item_id) {
			this.close();
		}
	});
});