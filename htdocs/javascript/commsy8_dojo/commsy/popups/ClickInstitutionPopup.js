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
			this.module = "institution";
			
			this.fileInfo = null;
			
			this.features = [ "editor", "upload", "upload-single", "netnavigation", "calendar" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			dojo.ready(lang.hitch(this, function() {
				// setup callback for single upload
				this.featureHandles["upload-single"][0].setCallback(lang.hitch(this, function(fileInfo) {
					this.fileInfo = fileInfo;
					
					if(this.item_id) uploadPicture(function() {});
				}));
			}));
		},
		
		uploadPicture: function(callback) {
			// send ajax request
			var data = {
				module:			"institution	",
				additional: {
					action:		"upload_picture",
				    fileInfo:	this.fileInfo,
				    iid:		this.item_id
				}
			};
			
			this.AJAXRequest("popup", "save", data, function(response) {
				// maybe change the picture in-time
				
				callback();
			});
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
				    { query: query("div#files_attached", this.contentNode) },
				    { query: query("input[name='form_data[description]']", this.contentNode) },
				    { query: query("input[name='form_data[name]']", this.contentNode) },
				]
			};
			
			this.submit(search);
		},
		
		onPopupSubmitSuccess: function(item_id) {
			// invoke netnavigation / path - process after item creation actions
			if(this.item_id === "NEW") {
				this.featureHandles["netnavigation"][0].afterItemCreation(item_id, lang.hitch(this, function() {
					if(this.fileInfo) {
						this.uploadPicture(lang.hitch(this, function() {
							//this.close();
							this.reload(item_id);
						}));
					} else {
						//this.close();
						this.reload(item_id);
					}
				}));
			} else {
				//this.close();
				this.reload(item_id);
			}
		},
	});
});