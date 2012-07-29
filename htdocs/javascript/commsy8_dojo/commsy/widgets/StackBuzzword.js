define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/popups/ClickBuzzwordsPopup",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query",
        	"dojo/has",
        	"dojo/_base/sniff"], function(declare, WidgetBase, BuzzwordsPopup, TemplatedMixin, Lang, DomConstruct, DomAttr, On, Query, Has) {
	
	return declare([BuzzwordsPopup, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		module:				null,
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		/************************************************************************************
		 * Overwrite init-Method of TagsPopup
		 ************************************************************************************/
		init: function(/*triggerNode, customObject*/) {
			this.module = "buzzwords";
			this.itemId = this.from_php.ownRoom.id;
			
			/*
			 * We do not need the main init and setupSpecific routines from ClickTagPopups
			 * 
			 * Instead, we setup up our own html markup and reimplement setupSpecfic, but
			 * still use all those handling functions from ClickTagsPopup
			 */
			
			this.contentNode = this.widgetBodyNode;
			
			this.setupTabs();
			
			// get buzzwords
			this.AJAXRequest("buzzwords", "getBuzzwords", { roomId: this.itemId },
				Lang.hitch(this, function(response) {
					dojo.forEach(response, Lang.hitch(this, function(item, index, arr) {
						DomConstruct.create("li", {
							className:		"ui-state-default popup_buzzword_item",
							innerHTML:		item.name
						}, this.addBuzzwordListNode, "last");
						
						DomConstruct.create("li", {
							className:		"ui-state-default popup_buzzword_item",
							innerHTML:		item.name
						}, this.mergeBuzzwordListNode, "last");
						
						DomConstruct.create("option", {
							value:			item.to_item_id,
							innerHTML:		item.name
						}, this.mergeSelectOne, "last");
						
						DomConstruct.create("option", {
							value:			item.to_item_id,
							innerHTML:		item.name,
							disabled:		(index == 0) ? true : false
						}, this.mergeSelectTwo, "last");
						
						// edit tab
						var rowNode = DomConstruct.create("div", {
							className:		"input_row"
						}, this.editInputNode, "last");
						
							var inputNode = DomConstruct.create("input", {
								"id":		item.to_item_id,
								value:		item.name,
								type:		"text",
								className:	"buzzword_change_name size_200"
							}, rowNode, "last");
							
							DomConstruct.create("input", {
								type:		"button",
								value:		this.changeTranslationNode.innerHTML,
								className:	"popup_button buzzword_change mandatory",
								name:		"form_data[" + item.to_item_id + "]"
							}, rowNode, "last");
							
							DomConstruct.create("input", {
								"id":		item.to_item_id,
								type:		"button",
								value:		this.attachTranslationNode.innerHTML,
								className:	"popup_button buzzword_attach",
								name:		"form_data[" + item.to_item_id + "]"
							}, rowNode, "last");
							
							DomConstruct.create("input", {
								type:		"button",
								value:		this.deleteTranslationNode.innerHTML,
								className:	"popup_button buzzword_delete",
								name:		"form_data[" + item.to_item_id + "]"
							}, rowNode, "last");
							
						if (index == 0) {
							this.listAttachTitleNode.innerHTML = "&bdquo;" + item.name + "&rdquo;"
						}
					}));
					
					DomConstruct.create("div", {
						className:			"clear"
					}, this.addBuzzwordListNode, "last");		
					
					DomConstruct.create("div", {
						className:			"clear"
					}, this.mergeBuzzwordListNode, "last");	
					
					
					// setup list
					require(["commsy/List"], Lang.hitch(this, function(List) {
						this.list = new List();
						this.list.init(this.cid, this.from_php.template.tpl_path, {
							activatorNode:	this.editActivatorNode,
							module:			"buzzwords",
							roomId:			this.itemId,
							OnInitDone:		Lang.hitch(this, function() {
								this.list.performRequest();
							}),
							contentNode:	this.contentNode
						});
						
						// set initial buzzword to first in attach tab
						var firstAttachBuzzwordNode = Query("div#edit_tab input.buzzword_attach")[0];
						if (firstAttachBuzzwordNode) {
							var buzzwordId = DomAttr.get(firstAttachBuzzwordNode, "id");
							this.list.requestData.item_id = buzzwordId;
						}
					}));
					
					// connect all assignment buttons in edit tab
					dojo.forEach(Query("input.buzzword_attach"), Lang.hitch(this, function(inputNode, index, arr) {
						On(inputNode, "click", Lang.hitch(this, function(event) {
							// get name and extract buzzword id
							var nameAttr = DomAttr.get(inputNode, "name");
							var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
							
							// update reference id of list and perform a new request
							this.list.requestData.item_id = buzzwordId;
							this.list.performRequest();
							
							// update header
							var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
							DomAttr.set(this.listAttachTitleNode, "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
						}));
					}));
					
					// connect all change buttons in edit tab
					dojo.forEach(Query("input.buzzword_change"), Lang.hitch(this, function(inputNode, index, arr) {
						On(inputNode, "click", Lang.hitch(this, function(event) {
							// get name and extract buzzword id
							var nameAttr = DomAttr.get(inputNode, "name");
							var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
							
							// get new buzzword name
							var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
							
							// perform ajax request
							this.AJAXRequest("buzzwords", "updateBuzzword", { buzzword_id: buzzwordId, buzzword: buzzwordName },
								Lang.hitch(this, function(response) {
									// update header if the buzzword was set in list
									if(this.list.requestData.item_id === buzzwordId) {
										DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
									}
								}),
								Lang.hitch(this, function(response) {
									
								})
							);
						}));
					}));
					
					// connect all delete buttons in edit tab
					dojo.forEach(Query("input.buzzword_delete"), Lang.hitch(this, function(inputNode, index, arr) {
						On(inputNode, "click", Lang.hitch(this, function(event) {
							// get name and extract buzzword id
							var nameAttr = DomAttr.get(inputNode, "name");
							var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
							
							// get buzzword name
							var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
							
							// perform ajax request
							this.AJAXRequest("buzzwords", "deleteBuzzword", { buzzword_id: buzzwordId },
								Lang.hitch(this, function(response) {
									// remove buzzword from all lists, merge selects and edit tab
									this.removeBuzzwordFromLists(buzzwordName);
									this.removeBuzzwordFromMergeSelects(buzzwordName);
									this.removeBuzzwordFromEditTab(buzzwordName);
								}),
								Lang.hitch(this, function(response) {
									
								})
							);
						}));
					}));
				})
			);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.init();
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onCreateNewBuzzword: function(event) {
			this.OnAddNewBuzzword(this.itemId);
		},
		
		onChangeSelectOne: function(event) {
			this.enableAllOptionsExceptOne(this.mergeSelectTwo, DomAttr.get(event.target, "value"));
		},
		
		onChangeSelectTwo: function(event) {
			this.enableAllOptionsExceptOne(this.mergeSelectOne, DomAttr.get(event.target, "value"));
		},
		
		onClickMerge: function(event) {
			this.OnMergeBuzzwords();
		}
	});
});