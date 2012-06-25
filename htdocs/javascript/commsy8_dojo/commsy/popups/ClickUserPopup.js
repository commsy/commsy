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
			this.module = "user";
			
			this.features = [ "editor", "netnavigation", "calendar" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			// add ckeditor data to hidden div
			this.featureHandles["editor"].forEach(function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;
				
				domAttr.set(query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});
			
			// setup data to send via ajax
			var search = {
				tabs: [
					{ id: "account_tab" }
				],
				nodeLists: [
				    { query: query("div#popup_content", this.contentNode) }
				]
			};
			
			this.submit(search);
		},
		
		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					this.close();
					this.reload(item_id);
				}));
			} else {
				this.close();
				this.reload(item_id);
			}
		},
	});
});

/*
		
		onClickSave: function(event) {
			// check if uploadify queue is empty
			var queue_length = jQuery('div#uploadifyQueue').children().length;
			
			if(queue_length == 0 || event.data.handle.uploaded == true) {
				event.data.handle.uploaded = false;
				
				// if queue is empty - save item
				event.data.handle.save(event);
			} else {
				var uploadify = jQuery('input#uploadify');
				
				// first upload files - then save
				uploadify.uploadifyUpload();
				
				event.data.handle.uploaded = true;
			}
		},
		
		onUploadifyAllComplete: function() {
			jQuery('input#popup_button_create').click();
		},
	};
});

*/