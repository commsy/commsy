define([	"dojo/_base/declare",
        	"commsy/TogglePopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/on",
        	"dojo/_base/lang"], function(declare, TogglePopupHandler, Query, DomClass, DomAttr, DomConstruct, On, Lang) {
	return declare(TogglePopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.contentNode = content_node;
			this.module = "profile";
			
			this.features = [ "editor", "upload" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		onTogglePopup: function() {
			if(this.is_open === true) {
				DomClass.add(this.popup_button_node, "tm_user_hover");
				DomClass.remove(this.contentNode, "hidden");
			} else {
				DomClass.remove(this.popup_button_node, "tm_user_hover");
				DomClass.add(this.contentNode, "hidden");
			}
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			// add ckeditor data to hidden div
			this.featureHandles["editor"].forEach(function(editor, index, arr) {
				var instance = editor.getInstance();
				var node = editor.getNode().parentNode;
				
				DomAttr.set(Query("input[type='hidden']", node)[0], 'value', editor.getInstance().getData());
			});
			
			// setup data to send via ajax
			if(part === "user" || part === "newsletter") {
				var search = {
					tabs: [
					    { id: part }
					],
					nodeLists: []
				};
			} else if(part === "account") {
				var search = {
					tabs: [],
					nodeLists: [
						{ query: Query("input[name='form_data[forname]']", this.contentNode) },
						{ query: Query("input[name='form_data[surname]']", this.contentNode) },
						{ query: Query("input[name='form_data[user_id]']", this.contentNode) },
						{ query: Query("input[name='form_data[old_password]']", this.contentNode) },
						{ query: Query("input[name='form_data[new_password]']", this.contentNode) },
						{ query: Query("input[name='form_data[new_password_confirm]']", this.contentNode) },
						{ query: Query("input[name='form_data[language]']", this.contentNode) },
						{ query: Query("input[name='form_data[upload]']", this.contentNode) },
						{ query: Query("input[name='form_data[auto_save]']", this.contentNode) }
					]
				};
			} else if(part === "account_merge") {
				var search = {
					tabs: [],
					nodeLists: [
						{ query: Query("input[name='form_data[merge_user_id]']", this.contentNode) },
						{ query: Query("input[name='form_data[merge_user_password]']", this.contentNode) }
					]
				};
			} else {
				// account delete
				var search = {
					tabs: [],
					nodeLists: []
				};
			}
			
			
			
			
			this.submit(search, { part: part });
			
			/*
			 * var handle = event.data.handle;
			var target = jQuery(event.target);
			
			// submit picture
			var form_objects = jQuery('form#logo_upload, form#bg_upload');
			
			var all = 0;
			form_objects.each(function(index) {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					all++;
				}
			});
			
			if(all == 0) {
		
				thishandle.saveConfiguration(event);
				
				
			}
			
			var index = 0;
			form_objects.each(function() {
				if(jQuery(this).find('input[type="file"]').attr('value') !== '') {
					handle.uploadRoomPicture(jQuery(this), index, all, handle.saveConfiguration, event);
					index++;
				}
			});
			 */
		},
		
		onPopupSubmitSuccess: function(item_id) {
			this.close();
		}
	});
});

/*



		uploadUserPicture: function(form_object) {
			var handle = this;
			
			// setup ajax form
			form_object.ajaxForm();

			// submit form
			form_object.ajaxSubmit({
				type:		'POST',
				success:	function() {
					handle.close();
				}
			});

			return false;
		},*/