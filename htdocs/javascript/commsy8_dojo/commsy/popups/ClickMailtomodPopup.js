define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "mailtomod";
			
			this.features = [ "editor" ];
			
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
				    { id: "rights_tab" }
				],
				nodeLists: [
				    { query: query("div#reciever", this.contentNode), group: "reciever"},
				    
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("input[name='form_data[body]']", this.contentNode) },
				    { query: query("input[name='form_data[subject]']", this.contentNode) },
				]
			};
			
			this.submit(search);
		},
		
		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation / path - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					this.featureHandles["path"][0].save(item_id, lang.hitch(this, function() {
						//this.close();
						this.reload(item_id);
					}));
				}));
			} else {
				this.featureHandles["path"][0].save(item_id, lang.hitch(this, function() {
					//this.close();
					this.reload(item_id);
				}));
			}
		},
	});
});