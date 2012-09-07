define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "tags";
			this.tree= null;
			
			this.features = [ ];
			
			this.contextId = customObject.contextId;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			require(["commsy/EditTree"], Lang.hitch(this, function(EditTree) {
				this.tree = new EditTree({
					followUrl:		false,
					checkboxes:		false,
					room_id:		this.contextId,
					expanded:		false,
					item_id:		this.item_id
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0], Lang.hitch(this, function(tree) {					
					On(tree.tree, "open", Lang.hitch(this, function(item, node) {
						this.tree.addCreateAndRenameToAllLabels();
					}));
				}));
			}));
			
			// this will handle both select boxes in merge tab
			var selectOneNode = Query("select#tag_merge_one")[0];
			var selectTwoNode = Query("select#tag_merge_two")[0];
			
			On(selectOneNode, "change", Lang.hitch(this, function(event) {
				// when changing box one, disable the selected value in box two
				this.enableAllOptionsExceptOne(selectTwoNode, DomAttr.get(event.target, "value"));
			}));
			On(selectTwoNode, "change", Lang.hitch(this, function(event) {
				// when changing box two, disable the selected value in box one
				this.enableAllOptionsExceptOne(selectOneNode, DomAttr.get(event.target, "value"));
			}));
			
			// setup list
			require(["commsy/List"], Lang.hitch(this, function(List) {
				this.list = new List();
				this.list.init(this.cid, this.from_php.template.tpl_path, {
					activatorNode:	Query("a.list_activator")[0],
					module:			"tags",
					roomId:			this.contextId,
					OnInitDone:		Lang.hitch(this, function() {
						this.list.performRequest();
					})
				});
				
				// set initial buzzword to first in attach tab
				var firstAttachTagNode = Query("div#attach_tab input.tag_attach")[0];
				if (firstAttachTagNode) {
					var tagId = DomAttr.get(firstAttachTagNode, "id");
					this.list.requestData.item_id = tagId;
					this.list.requestData.contextId = this.contextId;
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
					this.list.requestData.contextId = this.contextId;
					this.list.performRequest();
					
					// update header
					var tagName = DomAttr.get(new dojo.NodeList(inputNode).siblings("label")[0], "innerHTML");
					DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + tagName + "&rdquo;");
				}));
			}));
		},
		
		enableAllOptionsExceptOne: function(selectNode, exception) {
			var optionNodes = Query("option", selectNode);
			
			// handle disabled state
			dojo.forEach(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "value") === exception) {
					DomAttr.set(optionNode, "disabled", "disabled");
				} else {
					if(DomAttr.has(optionNode, "disabled")) {
						DomAttr.remove(optionNode, "disabled");
					}
				}
			}));
			
			// try to find a value, that is not the excepted one - this happens when it was selected before
			if(DomAttr.get(selectNode, "value") === exception) {
				var skip = false;
				dojo.some(optionNodes, Lang.hitch(this, function(optionNode, index, arr) {
					if(skip) return false;
					
					var optionValue = DomAttr.get(optionNode, "value");
					if(optionValue !== exception) {
						DomAttr.set(selectNode, "value", optionValue);
						skip = true;
					}
				}));
			}
		},
		
		addTagToLists: function(tag) {
			dojo.forEach(Query("ul.popup_tag_list"), Lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		tag
				}, clearNode, "before");
			}));
		},
		
		removeTagFromLists: function(tag) {
			dojo.forEach(Query("li.popup_tag_item"), Lang.hitch(this, function(itemNode, index, arr) {
				if(DomAttr.get(itemNode, "innerHTML") === tag) {
					DomConstruct.destroy(itemNode);
				}
			}));
		},
		
		addTagToMergeSelects: function(id, tag) {
			var selectOneNode = Query("select#tag_merge_one")[0];
			var selectTwoNode = Query("select#tag_merge_two")[0];
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	tag
			}, selectOneNode, "last");
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	tag
			}, selectTwoNode, "last");
		},
		
		removeTagFromMergeSelects: function(tag) {
			var OptionNodes = Query("select#tag_merge_one option, select#tag_merge_two option");
			
			dojo.forEach(OptionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "innerHTML") === tag) {
					DomConstruct.destroy(optionNode);
				}
			}));
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "sort_abc") {
				this.onSortABC();
			} else if(part == "merge") {
				this.onMergeTags();
			}
		},
		
		onSortABC: function() {
			this.AJAXRequest("tags", "sortABC", { roomId: this.contextId },
				Lang.hitch(this, function(response) {
					this.close();
				})
			);
		},
		
		onMergeTags: function() {
			// get the two ids to merge
			var mergeIdOne = DomAttr.get(Query("select#tag_merge_one")[0], "value");
			var mergeIdTwo = DomAttr.get(Query("select#tag_merge_two")[0], "value");
			
			if(mergeIdOne !== mergeIdTwo) {
				// send ajax request
				this.AJAXRequest("tags", "mergeTags", { idOne: mergeIdOne, idTwo: mergeIdTwo },
					Lang.hitch(this, function(response) {
						// remove both tags from all lists and add the new one
						//this.removeTagFromLists(response.tagOne);
						//this.removeTagFromLists(response.tagTwo);
						//this.addTagToLists(response.newTag);
						
						// remove both tags from the merge select boxes and add the new one
						/*
						this.removeTagFromMergeSelects(response.tagOne);
						this.removeTagFromMergeSelects(response.tagTwo);
						this.addTagToMergeSelects(mergeIdOne, response.newTag);
						*/
						
						this.close();
					}),
					
					Lang.hitch(this, function(response) {
						
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
			location.reload();
		}
	});
});