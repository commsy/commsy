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
			this.module = "date";
			this.editType = customObject.editType;
			this.contextId = customObject.contextId;
			this.date_new =  customObject.date_new;
			this.itemTitle = "";

			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];

			// register click for node
			this.registerPopupClick();
		},

		setupSpecific: function() {
			// recurring dates
			var selectNode = query("select[name='form_data[recurring_select]']")[0];
			var recurringDetailNodes = query("div[id^='recurring_details_']");

			if (selectNode) {
				On(selectNode, "change", lang.hitch(this, function(event) {
					var value = domAttr.get(selectNode, "value");

					// hide all
					dojo.forEach(recurringDetailNodes, lang.hitch(this, function(node, index, arr) {
						dom_class.add(node, "hidden");
					}));

					// display specific
					dom_class.remove(query("div#recurring_details_" + value)[0], "hidden");
				}));
			}

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
			if (this.featureHandles['calendar']) {
                if (this.featureHandles['calendar'][0]) {
                    var calendar = this.featureHandles['calendar'][0].calendar;
                    var displayedValue = calendar.get('displayedValue');

                    var hiddenNode = query("input[name='form_data[dayStart]']")[0];
                    if (hiddenNode) {
                        domAttr.set(hiddenNode, 'value', displayedValue);
                    }
                }
			}
			if (this.featureHandles['calendar']) {
    			if (this.featureHandles['calendar'][1]) {
    				var calendar = this.featureHandles['calendar'][1].calendar;
    				var displayedValue = calendar.get('displayedValue');
    				
    				var hiddenNode = query("input[name='form_data[dayEnd]']")[0];
    				if (hiddenNode) {
    					domAttr.set(hiddenNode, 'value', displayedValue);
    				}
    			}
            }

			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "rights_tab" },
					{ id: "addon_tab" },
					{ id: "buzzwords_tab", group: "buzzwords" },
					{ id: "tags_tab", group: "tags" }
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[dayStart]']", this.contentNode) },
				    { query: query("input[name='form_data[timeStart]']", this.contentNode) },
				    { query: query("input[name='form_data[dayEnd]']", this.contentNode) },
				    { query: query("input[name='form_data[timeEnd]']", this.contentNode) },
				    { query: query("input[name='form_data[place]']", this.contentNode) },
				    { query: query("input.tabStatus", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) }
				]
			};

			// set title to refresh item list
            if(query("input[name='form_data[title]']", this.contentNode)[0]) {
                this.itemTitle = domAttr.get(query("input[name='form_data[title]']", this.contentNode)[0], "value");
            }

			this.submit(search, {part:customObject.part, contextId: this.contextId });
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
					this.reload(item_id);
				}
			}
		}
	});
});