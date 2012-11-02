define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "send";
			
			//this.features = [ "editor" ];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [
				],
				nodeLists: [
				    { query: query("input[name='form_data[subject]']", this.contentNode) },
				    { query: query("textarea[name='form_data[body]']", this.contentNode) },
				    { query: query("input[name='form_data[copyToAttendees]']", this.contentNode) },
				    { query: query("input[name^='form_data[group_']", this.contentNode) },
				    { query: query("input[name^='form_data[institution_']", this.contentNode) },
				    { query: query("input[name='form_data[allMembers]']", this.contentNode) },
				    { query: query("input[name='form_data[copyToSender]']", this.contentNode) }
				]
			};
			
			this.submit(search, { itemId: this.item_id });
		},
		
		onPopupSubmitSuccess: function(confirmData) {
			/*
			// prepare mixin data
			var mixin = {
				mailSuccess:	true,
				mail:			confirmData
			};
			
			// get instance of confirm widget
			var widgetManager = this.getWidgetManager();
			widgetManager.GetInstance("commsy/widgets/MailConfirmWidget/MailConfirmWidget", mixin).then(function(deferred)
			{
				var widgetInstance = deferred.instance;
				
				// open widget
				widgetInstance.Open();
			});
			*/
			this.close();
		},
		
		onPopupSubmitError: function(response) {
			
		}
	});
});