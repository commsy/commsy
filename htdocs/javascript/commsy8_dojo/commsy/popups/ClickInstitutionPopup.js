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
			this.featureHandles["editor"].forEach(function(editor, index, arr) {
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
				    { query: query("input[name='form_data[title]']", this.contentNode) },
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
							this.close();
							this.reload(item_id);
						}));
					} else {
						this.close();
						this.reload(item_id);
					}
				}));
			} else {
				this.close();
				this.reload(item_id);
			}
			
			
			
			/*
			// submit picture
			var form_object = jQuery('form#picture_upload');
			
			if(form_object.find('input[type="file"]').length > 0) {
				if(form_object.find('input[type="file"]').attr('value') !== '') {
					handle.uploadPicture(form_object, data.item_id);
				} else {
					handle.close();
					
					handle.reload(data.item_id);
				}
			} else {
				handle.close();
				
				handle.reload(data.item_id);
			}
			 */
		},
	});
});

/*

		
		
		uploadPicture: function(form_object, item_id) {
			var handle = this;
			
			jQuery('input#upload_hidden_iid').val(item_id);
			
			// setup ajax form
			form_object.ajaxForm();
			
			// submit form
			form_object.ajaxSubmit({
				type:		'POST',
				success:	function() {
					handle.reload(item_id);
				}
			});
			
			return false;
		},

		
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