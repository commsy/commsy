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
			this.module = "todo";
			
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
					{ id: "tags_tab", group: "tags" },
				],
				nodeLists: [
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("div#files_finished", this.contentNode), group: "files" },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[day_end]']", this.contentNode) },
				    { query: query("input[name='form_data[time_end]']", this.contentNode) },
				    { query: query("input[name='form_data[minutes]']", this.contentNode) },
				    { query: query("input[name='form_data[time_type]']", this.contentNode) },
				    { query: query("select[name='form_data[status]']", this.contentNode) },
				    { query: query("input[name='form_data[title]']", this.contentNode) },
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