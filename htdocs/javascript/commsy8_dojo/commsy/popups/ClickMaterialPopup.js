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
			this.module = "material";
			
			this.features = [ "editor", "tree", "upload", "netnavigation", "calendar" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			/* setup bibliographic form elements */
			// get value from active bibliographic option
			var selectNode = query("select#bibliographic_select", this.contentNode)[0];
			
			// show / hude bibliographic div's
			this.showHideBibliographic(selectNode);
			
			// register handler for select
			On(selectNode, "change", lang.hitch(this, function(event) {
				this.showHideBibliographic(selectNode);
			}));
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
				    { query: query("select#bibliographic_select", this.contentNode) }
				]
			};
			
			// add visible bibliographic div
			// TODO: maybe there is a not-class selector?
			query("div#bibliographic div[id^='bib_content_']", this.contentNode).forEach(function(node, index, arr) {
				if(!dom_class.contains(node, "hidden")) {
					
					var nodeId = domAttr.get(node, "id");
					search.nodeLists.push({ query: query("div#" + nodeId, this.contentNode) });
					
					return false;
				}
			});
			
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
		
		showHideBibliographic: function(selectNode) {
			var key = domAttr.get(selectNode, "value");
			
			// go through all bibliographic content div's and show the the one who's id matches "bib_content_" + key
			query("div#bibliographic div[id^='bib_content_']", this.contentNode).forEach(function(node) {
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
		
		setupPath: function(handle, item_id) {
			// init path class
			this.path = new Path();
			this.path.init(handle.cid, item_id, this.tpl_path);
		}
	};
});



*/