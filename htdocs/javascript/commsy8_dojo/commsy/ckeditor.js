define([	"dojo/_base/declare",
        	"commsy/base",
        	"ckeditor/ckeditor",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/query",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, CKEditor, domAttr, domConstruct, Query) {
	return declare(BaseClass, {
		instance:	null,
		node:		null,
		
		// TODO: multilanguage support
		options: {
			language: 'de',
			skin: 'kama',
			uiColor: '#eeeeee',
			startupFocus: false,
			dialog_startupFocusTab: false,
			resize_enabled: true,
			resize_maxWidth: '100%',
			enterMode: CKEDITOR.ENTER_BR,
			shiftEnterMode: CKEDITOR.ENTER_P,
			//extraPlugins: 'CommSyImages,CommSyMDO',
			toolbar: [
			    ['Cut', 'Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo', '-', 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'SpecialChar', '-', 'NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote', '-', 'TextColor', 'BGColor', '-', 'RemoveFormat']
			    ,'/',
			    ['Format', 'Font', 'FontSize', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', '-', 'Link', 'Unlink', '-', 'Table', 'HorizontalRule', 'Smiley'],
			    ,'/',
			    ['Maximize', 'Preview', 'About', '-', 'Image']
			]
		},
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		create: function(node) {
			this.node = node;
			
			/* create instance for node */
			// get id of this object and create a hidden input field beside
			// the id determs the form_data[]-key
			// this will later on get the editors content, when the form is submited
			var id = domAttr.get(node, "id");
			
			var hiddenNode = domConstruct.create('input');
			domAttr.set(hiddenNode, "type", "hidden");
			domAttr.set(hiddenNode, "name", "form_data[" + id + "]");
			domConstruct.place(hiddenNode, node, "after");
			
			var data = node.innerHTML;
			node.innerHTML = "";
			
			this.instance = CKEDITOR.appendTo(node, this.options, data);
			
			var nodeList =  new dojo.NodeList(node);
			// get the form this editor belongs to
			var formNode = nodeList.parents("form")[0];
			
			// on form submit, attach editor content to hidden input
			
		},
		
		getInstance: function() {
			return this.instance;
		},
		
		getNode: function() {
			return this.node;
		},
		
		destroy: function() {
			if(this.instance) this.instance.destroy();
		}
	});
});
/*
		create: function(preconditions, parameters) {

			
			// create ckeditor instances for all register_on objects
			register_on.each(function() {
				// get the form this editor belongs to
				var form_object = jQuery(this).parentsUntil('form').parent();
				
				// on form submit, attach editor content to hidden input
				handle.append_content(form_object, jQuery('input[name="form_data[' + id + ']"]'), jQuery(this).ckeditorGet());
			});
		},
		
		append_content: function(form_object, hidden_input_object, editor) {
			form_object.bind('submit', {hidden_input_object: hidden_input_object, editor: editor}, this.onSubmit);
		},
		
		onSubmit: function(event) {
			event.data.hidden_input_object.attr('value', event.data.editor.getData());
		}
	};
});
*/