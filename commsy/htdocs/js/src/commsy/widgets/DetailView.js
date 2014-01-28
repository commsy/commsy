define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"commsy/request",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query"], function(declare, WidgetBase, BaseClass, TemplatedMixin, lang, request, DomConstruct, DomAttr, On, Query) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		module:				null,
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			
			// load detail content
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'widget_detail_view',
					action:	'getDetailContent'
				},
				data: {
					module:	this.module,
					itemId:	this.itemId
				}
			}).then(
				lang.hitch(this, function(response) {
					this.detailContentNode.innerHTML = response.data;
					
					// take the title and set it also as widget header title
					var titleH2Node = Query("div.content_item h2", this.detailContentNode)[0];
					if (titleH2Node) {
						var title = DomAttr.get(titleH2Node, "innerHTML");
						this.detailTitleNode.innerHTML = title;
					}
					
					/* we need to reinvoke all those JS modules, that handles detail view interaction */
					// setup rubric forms
					Query(".open_popup").forEach(lang.hitch(this, function(node, index, arr) {
						// get custom data object
						var customObject = this.getAttrAsObject(node, "data-custom");
						
						var module = customObject.module;
						
						require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
							var handler = new ClickPopup();
							handler.init(node, customObject);
						});
					}));
				})
			);
		}
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
	});
});