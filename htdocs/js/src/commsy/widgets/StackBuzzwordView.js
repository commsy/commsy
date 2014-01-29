define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query",
        	"dojo/topic"], function(declare, WidgetBase, Base, TemplatedMixin, Lang, DomConstruct, DomAttr, On, Query, Topic) {
	
	return declare([Base, WidgetBase, TemplatedMixin], {
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
			
			this.module = "buzzwords";
			this.itemId = this.from_php.ownRoom.id;
			
			Topic.subscribe("newOwnRoomBuzzword", Lang.hitch(this, function(object) {
				this.updateList();
			}));
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.updateList();
			
			require(["commsy/popups/ClickBuzzwordsPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.buzzwordEditNode, { module: "buzzwords", contextId: this.itemId });
			}));
		},
		
		updateList: function() {
			this.AJAXRequest("buzzwords", "getBuzzwords", { roomId: this.itemId },
				Lang.hitch(this, function(response) {
					DomConstruct.empty(this.buzzwordListNode);
					
					dojo.forEach(response, Lang.hitch(this, function(item, index, arr) {
						var buzzwordNode = DomConstruct.create("a", {
							className:		"keywords_s" + item.class_id,
							href:			"#",
							innerHTML:		item.name + " "
						}, this.buzzwordListNode, "last");
						
						On(buzzwordNode, "click", Lang.hitch(this, function(event) {
							this.onClickBuzzword(item.to_item_id, item.name);
						}));
					}));
				})
			);
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickBuzzword: function(buzzwordId, buzzwordName) {
			var listWidget = this.widgetHandler.getWidget("widgets/StackStack");
			
			listWidget.addBuzzwordRestriction(buzzwordId, buzzwordName);
		}
	});
});