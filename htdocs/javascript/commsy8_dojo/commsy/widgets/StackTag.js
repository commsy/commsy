define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/popups/ClickTagsPopup",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query",
        	"dojo/has",
        	"dojo/_base/sniff"], function(declare, WidgetBase, TagsPopup, TemplatedMixin, Lang, DomConstruct, DomAttr, On, Query, Has) {
	
	return declare([TagsPopup, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidget",
		widgetHandler:		null,
		
		module:				null,
		itemId:				null,
		tree:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		/************************************************************************************
		 * Overwrite init-Method of TagsPopup
		 ************************************************************************************/
		init: function(/*triggerNode, customObject*/) {
			this.module = "tags";
			this.itemId = this.from_php.ownRoom.id;
			
			/*
			 * We do not need the main init and setupSpecific routines from ClickTagPopups
			 * 
			 * Instead, we setup up our own html markup and reimplement setupSpecfic, but
			 * still use all those handling functions from ClickTagsPopup
			 */
			
			this.contentNode = this.widgetBodyNode;
			
			this.setupTabs();
			
			require(["commsy/EditTree"], Lang.hitch(this, function(EditTree) {
				this.tree = new EditTree({
					followUrl:		false,
					checkboxes:		false,
					expanded:		(Has("ie") <= 8) ? false : true,
					room_id:		this.itemId
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0], Lang.hitch(this, function() {
					// tag the edit tree model and fill the rest of the template instead of generating a new request
					var count = 0;
					this.tree.iterateCallback(this.tree.tree.rootNode.item, Lang.hitch(this, function(item) {
						if (item.id !== "$root$") {
							var optionNode = DomConstruct.create("option", {
								value:		item.item_id,
								innerHTML:	item.title
							});
							DomConstruct.place(optionNode, this.mergeSelectNodeOne, "last");
							
							var optionNode = DomConstruct.create("option", {
								value:		item.item_id,
								innerHTML:	item.title,
								disabled:	(count == 0) ? true : false
							});
							DomConstruct.place(optionNode, this.mergeSelectNodeTwo, "last");
							
							// attach tab
							var rowNode = DomConstruct.create("div", {
								className:		"input_row"
							});
							
								DomConstruct.create("label", {
									"for":		item.item_id,
									innerHTML:	item.title
								}, rowNode, "last");
								
								DomConstruct.create("input", {
									className:	"popup_button, tag_attach",
									type:		"button",
									name:		"form_data[" + item.item_id + "]",
									value:		this.attachTranslationNode.innerHTML
								}, rowNode, "last");
							DomConstruct.place(rowNode, this.listAttachNode, "last");
							
							if (count == 0) {
								this.listAttachTitleNode.innerHTML = "&bdquo;" + item.title + "&rdquo;"
							}
							
							count++;
						}
					}));
					
					// setup list
					require(["commsy/List"], Lang.hitch(this, function(List) {
						this.list = new List();
						this.list.init(this.cid, this.from_php.template.tpl_path, {
							activatorNode:	this.attachActivatorNode,
							module:			"tags",
							roomId:			this.itemId,
							OnInitDone:		Lang.hitch(this, function() {
								this.list.performRequest();
							}),
							contentNode:	this.contentNode
						});
						
						// set initial tag to first in attach tab
						var firstAttachTagNode = Query("div#attach_tab input.tag_attach")[0];
						if (firstAttachTagNode) {
							var tagId = DomAttr.get(firstAttachTagNode, "id");
							this.list.requestData.item_id = tagId;
						}
					}));
					
					// connect all assignment buttons in attach tab
					dojo.forEach(Query("input.tag_attach"), Lang.hitch(this, function(inputNode, index, arr) {
						On(inputNode, "click", Lang.hitch(this, function(event) {
							// get name and extract buzzword id
							var nameAttr = DomAttr.get(inputNode, "name");
							var tagId = nameAttr.substr(10, nameAttr.length-11);
							
							// update reference id of list and perform a new request
							this.list.requestData.item_id = tagId;
							this.list.performRequest();
							
							// update header
							var tagName = DomAttr.get(new dojo.NodeList(inputNode).siblings("label")[0], "innerHTML");
							DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + tagName + "&rdquo;");
						}));
					}));
				}));
			}));
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			
			// init ClickTagsPopup here
			this.init();
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onChangeSelectNodeOne: function(event) {
			// when changing box one, disable the selected value in box two
			this.enableAllOptionsExceptOne(this.mergeSelectNodeTwo, DomAttr.get(event.target, "value"));
		},
		
		onChangeSelectNodeTwo: function(event) {
			// when changing box two, disable the selected value in box one
			this.enableAllOptionsExceptOne(this.mergeSelectNodeOne, DomAttr.get(event.target, "value"));
		},
		
		onClickSortABC: function(event) {
			// TODO
		},
		
		onClickCombine: function(event) {
			var mergeIdOne = DomAttr.get(this.mergeSelectNodeOne, "value");
			var mergeIdTwo = DomAttr.get(this.mergeSelectNodeTwo, "value");
			
			this.AJAXRequest("tags", "mergeTags", { idOne: mergeIdOne, idTwo: mergeIdTwo },
				Lang.hitch(this, function(response) {
					
				})
			);
		}
	});
});