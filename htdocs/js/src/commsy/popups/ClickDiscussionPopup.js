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
			this.module = "discussion";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {

			// Set up click handler for buzzwords
			buzzwordAddButton = query("input#popup_button_add_buzzword", this.contentNode)[0];
			if(buzzwordAddButton) {
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
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[discussion_type]']", this.contentNode) },
				    { query: query("input[name='form_data[subject]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			this.submit(search, { contextId: this.contextId } );
		},

		addNewBuzzword: function () {

			buzzword = domAttr.get(query("input#new_buzzword_input")[0], "value");

			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'buzzwords',
					action:	'createNewBuzzword'
				},
				data: {
					buzzword:	buzzword,
					roomId:		this.contextId
				}
			}).then(
				lang.hitch(this, function(response) {

					buzzwordList = query("ul.popup_buzzword_list")[0];

					var listNode = domConstruct.create("li", {
						className:		"ui-state-default popup_buzzword_item",
						innerHTML: 		buzzword
					}, buzzwordList, "first");

					domConstruct.create("input", {
						className:		"ui-state-default popup_buzzword_item",
						type:			"checkbox",
						value:			response.id,
						name:			"form_data[buzzwords]",
						checked: 		"checked" 
					}, listNode, "first");
				}));
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
						aNode.click();
					}
				} else {
					this.reload(item_id);
				}
			}
		}
	});
});