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
			
			this.module = "tags";
			this.itemId = this.from_php.ownRoom.id;
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			require(["commsy/tree"], Lang.hitch(this, function(Tree) {
				var handler = new Tree({
					room_id:	this.itemId,
					followUrl:	false
				});
				handler.setupTree(this.treeNode, Lang.hitch(this, function() {
					On(handler.tree.tree, "click", Lang.hitch(this, function(item, node, event) {
						this.onClickTag(item.item_id[0], item.title[0]);
						
						event.preventDefault();
					}));
				}), true);
			}));
			
			require(["commsy/popups/ClickTagsPopup"], Lang.hitch(this, function(ClickPopup) {
				var handler = new ClickPopup();
				handler.init(this.tagEditNode, { module: "tags", contextId: this.itemId });
			}));
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickTag: function(tagId, tagName) {
			var listWidget = this.widgetHandler.getWidget("widgets/StackStack");
			
			listWidget.addTagRestriction(tagId, tagName);
		}
	});
});