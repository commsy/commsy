define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.module = "detail";
			this.version_id = customObject.vid || null;
			this.contextId = customObject.contextId;
			
			if (customObject.portfolioId) {
				this.setInitData({
					portfolioId:		customObject.portfolioId,
					portfolioRow:		customObject.portfolioRow,
					portfolioColumn:	customObject.portfolioColumn,
					fromPortfolio:		customObject.fromPortfolio
				});
			} else if(customObject.fromPortfolio) {
				this.setInitData({
					portfolioId:		customObject.portfolioId,
					fromPortfolio:		customObject.fromPortfolio
				});
			}
			
			this.ajaxHTMLSource = "detail_popup";
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// reinvoke ActionExpander
			var actors = Query(	"div.item_actions a.edit," +
								"div.item_actions a.detail," +
								"div.item_actions a.workflow," +
								"div.item_actions a.linked," + 
								"div.item_actions a.annotations," +
								"div.item_actions a.versions");
				
			require(["commsy/ActionExpander"], function(ActionExpander) {
				var handler = new ActionExpander();
				handler.setup(actors);
			});
			
			// reinvoke forms
			Query(".open_popup", this.contentNode).forEach(Lang.hitch(this, function(node, index, arr) {
				// this popup must be closed on click
				On(node, "click", Lang.hitch(this, function(event) {
					this.close();
				}));
				
				// get custom data object
				var customObject = this.getAttrAsObject(node, "data-custom");
				var module = customObject.module;
				
				if ( module === "discarticle" /*&& customObject.answerTo*/ )
				{
					customObject.discussionId = this.item_id;
				}
				
				// insert context id
				customObject.contextId = this.contextId;
				
				// insert portfolio data, if given
				if (this.initData.portfolioId) {
					declare.safeMixin(customObject, {
						portfolioId:		this.initData.portfolioId,
						portfolioRow:		this.initData.portfolioRow,
						portfolioColumn:	this.initData.portfolioColumn
					});
				}
				
				require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
					var handler = new ClickPopup();
					handler.init(node, customObject);
				});
			}));
			
			// discussion tree
			var treeNode = Query("div#discussion_tree")[0];
			
			if (treeNode) {
				require(["commsy/DiscussionTree"], Lang.hitch(this, function(DiscussionTree) {
					var handler = new DiscussionTree({ item_id: this.item_id });
					handler.setupTree(treeNode);
				}));
			}
			
			// ajax actions
			require(["commsy/AjaxActions"], function(AjaxActions) {
				var aNodes = Query("a.ajax_action");
				
				if (aNodes) {
					var handler = new AjaxActions();
					handler.setup(aNodes);
				}
			});
			
			if (typeof jsMath !== 'undefined') {
			   if (typeof jsMath.Process == 'function') {
			      jsMath.Process();
			   }
			}
		},
		
		onPopupSubmit: function(customObject) {
			// setup data to send via ajax
			var search = {
				tabs: [],
				nodeLists: []
			};
			
			
			this.submit(search, { version_id: this.version_id });
		},
		
		onPopupSubmitSuccess: function(response) {
		}
	});
});