define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "discussion";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
		},

		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;

				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[discussion_type]']", this.contentNode) },
				    { query: query("input[name='form_data[subject]']", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search, { contextId: this.contextId } );
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();
						var aNode = query("a#listItem" + item_id)[0];
						if (aNode) {
							aNode.click();
						}
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.click();
					}
				} else {
					this.reload(item_id);
				}
			}
		},
	});
});