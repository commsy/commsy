define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/topic",
        	"commsy/request"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On, Topic, request) {
	return declare(ClickPopupHandler, {
		constructor: function() {

		},

		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "material";
			this.editType = customObject.editType;
			this.version_id = customObject.vid;
			this.contextId = customObject.contextId;
			this.itemTitle = "";

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			/* setup bibliographic form elements */
			// get value from active bibliographic option
			var selectNode = query("select#bibliographic_select", this.contentNode)[0];

			if (selectNode) {
				// show / hude bibliographic div's
				this.showHideBibliographic(selectNode);

				// register handler for select
				On(selectNode, "change", lang.hitch(this, function(event) {
					this.showHideBibliographic(selectNode);
				}));
			}


			// Set up click handler for buzzwords
			buzzwordAddButton = query("input#popup_button_add_buzzword", this.contentNode)[0];
			if(buzzwordAddButton){
				On(buzzwordAddButton, "click", lang.hitch(this, function(event) {
					this.addNewBuzzword();
				}));
			}
			

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
				    { id: "tags_tab", group: "tags" },
				    { id: "workflow_tab" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("select#bibliographic_select", this.contentNode) }
				]
			};

			// set title to refresh item list
			if(query("input[name='form_data[title]']", this.contentNode)[0]) {
				this.itemTitle = domAttr.get(query("input[name='form_data[title]']", this.contentNode)[0], "value");
			}
			

			// add visible bibliographic div
			// TODO: maybe there is a not-class selector?
			dojo.forEach(query("div#bibliographic div[id^='bib_content_']", this.contentNode), function(node, index, arr) {
				if(!dom_class.contains(node, "hidden")) {

					var nodeId = domAttr.get(node, "id");
					search.nodeLists.push({ query: query("div#" + nodeId, this.contentNode) });

					return false;
				}
			});
			this.submit(search, {part:customObject.part, version_id:this.version_id, contextId: this.contextId });
		},

		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if (this.contextId) {
						this.close();

						Topic.publish("newOwnRoomItem", { itemId: item_id });
					} else {
						this.reload(item_id);
					}
				}));
			} else {
				if (this.contextId) {
					this.close();
					var aNode = query("a#listItem" + item_id)[0];
					if (aNode) {
						aNode.innerHTML = this.itemTitle;
						aNode.click();
					}
				} else {
					if(typeof(this.version_id) != 'undefined'){
						this.reload(item_id+"&version_id="+this.version_id);
					} else {
						this.reload(item_id);
					}
				}
			}
		},

		showHideBibliographic: function(selectNode) {
			var key = domAttr.get(selectNode, "value");

			// go through all bibliographic content div's and show the the one who's id matches "bib_content_" + key
			dojo.forEach(query("div#bibliographic div[id^='bib_content_']", this.contentNode), function(node) {
				if(domAttr.get(node, "id") === "bib_content_" + key) {
					// show
					dom_class.remove(node, "hidden");
				} else {
					// hide
					dom_class.add(node, "hidden");
				}
			});
		}
	});
});