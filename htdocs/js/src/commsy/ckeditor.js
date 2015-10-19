define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/_base/lang",
        	"dojo/on",
        	"dojo/query",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, domAttr, domConstruct, Lang, On, Query) {
	return declare(BaseClass, {
		instance:	null,
		node:		null,

		options: {
			skin:						'moono',
			uiColor:					'#eeeeee',
			customConfig:				"",
			startupFocus:				false,
			dialog_startupFocusTab:		false,
			resize_enabled:				true,
			resize_maxWidth:			'100%',
			height:						'150px',
			enterMode:					CKEDITOR.ENTER_BR,
			shiftEnterMode:				CKEDITOR.ENTER_P,
			extraPlugins:				"CommSyAbout,CommSyVideo,CommSyAudio,CommSyDocument,CommSyImage",
			//extraPlugins: 'CommSyImages,CommSyMDO',
			toolbar: [
			    ['Preview', 'Cut', 'Copy', 'Paste', 'PasteFromWord', '-', 'Undo', 'Redo'],
			    ['Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', 'SpecialChar'],
			    ['NumberedList', 'BulletedList', 'Outdent', 'Indent', 'Blockquote'],
			    ['JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock'],
			    ['CommSyAbout'],
			    ['Format', 'Font', 'FontSize'],
			    ['TextColor', 'BGColor', '-', 'RemoveFormat','-','Maximize', 'Source'],
			    ['Link', 'Unlink', '-', 'Table', 'HorizontalRule', 'Smiley', '-', 'Flash'],
			    //CommSy group
			    ['CommSyImage', 'CommSyVideo', 'CommSyAudio', 'CommSyDocument']
			    
			]
		},

		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			this.options.filebrowserUploadUrl		= 'commsy.php?cid='+this.uri_object.cid+'&mod=ajax&fct=ckeditor_image_upload&action=savefile';
			this.options.filebrowserBrowseUrl		= 'commsy.php?cid='+this.uri_object.cid+'&mod=ajax&fct=ckeditor_image_browse&action=getHTML';
			this.options.filebrowserWindowWidth		= '100';
			this.options.filebrowserWindowHeight	= '50';
			this.options.language					= this.from_php.environment.lang;

			// if (this.from_php.mdo_active) {
			// 	this.options.extraPlugins = this.options.extraPlugins + ",CommSyMDO";
			// }
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
			
			CKEDITOR.config.allowedContent = true;
			
			CKEDITOR.plugins.addExternal( "CommSyAbout", "../../src/commsy/ckeditor/plugins/about/", "CommSyAbout.php?cid="+this.uri_object.cid );

			// if (this.from_php.mdo_active) {
				
			// 	CKEDITOR.plugins.addExternal( "CommSyMDO", "../../src/commsy/ckeditor/plugins/CommSyMDO/", "plugin.js");
			// }
			
			CKEDITOR.plugins.addExternal( "CommSyVideo", "../../src/commsy/ckeditor/plugins/video/", "CommSyVideo.js");
			CKEDITOR.plugins.addExternal( "CommSyAudio", "../../src/commsy/ckeditor/plugins/audio/", "CommSyAudio.js");
			CKEDITOR.plugins.addExternal( "CommSyDocument", "../../src/commsy/ckeditor/plugins/document/", "CommSyDocument.js");
			CKEDITOR.plugins.addExternal( "CommSyImage", "../../src/commsy/ckeditor/plugins/image/", "plugin.js");
			
			if ( node.nodeName === "TEXTAREA" )
			{
				this.instance = CKEDITOR.replace(node, this.options);
			}
			else
			{
				var data = node.innerHTML;
				node.innerHTML = "";
				this.instance = CKEDITOR.appendTo(node, this.options, data);
			}

			// get the form this editor belongs to
			var nodeList =  new dojo.NodeList(node);
			var formNode = nodeList.parents("form")[0];

			// on form submit, attach editor content to hidden input
			if(formNode) {
				On(formNode, "submit", Lang.hitch(this, function(event) {
					domAttr.set(hiddenNode, "value", this.instance.getData());
				}));
			}
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