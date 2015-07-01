define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, lang, DomConstruct, request, DomAttr, DomStyle, On) {
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
			require(["commsy/EditTree"], lang.hitch(this, function(EditTree) {
				this.tree = new EditTree({
					followUrl:		false,
					checkboxes:		false,
					room_id:		this.contextId,
					expanded:		false,
					item_id:		this.item_id,
					onCreateTagCallback: lang.hitch(this, function(newTag) {
						// merge tab
						this.addTagToMergeSelects(newTag.item_id, newTag.title);

						// attach tab
						var attachTabContentNode = Query("div#attach_tab div#content_row_one")[0];

						var divNode = DomConstruct.create("div", {
							className: "input_row"
						}, attachTabContentNode, "last");

						DomConstruct.create("label", {
							innerHTML: newTag.title,
							'for': newTag.item_id
						}, divNode, "first");

						DomConstruct.create("input", {
							className: "popup_button tag_attach",
							type: "button",
							name: "form_data[" + newTag.item_id + "]",
							id: newTag.item_id,
							value: "Einträge zuordnen"
						}, divNode, "last");

						this.connectAssignmentButtons();
						
						
					}),
					onDeleteTagCallback: lang.hitch(this, function(itemId, tag) {
						// merge tab
						this.removeTagFromMergeSelects(tag);

						// attach tab
						dojo.forEach(Query("div#attach_tab div#content_row_one div.input_row label"), lang.hitch(this, function(inputNode, index, arr) {
							if(DomAttr.get(inputNode, "innerHTML") === tag) {
								DomConstruct.destroy(inputNode.parentNode);
							}
						}));
						
					})
				});
				this.tree.setupTree(Query("div.tree", this.contentNode)[0], lang.hitch(this, function(tree) {					
					On(tree.tree, "open", lang.hitch(this, function(item, node) {
						this.tree.addCreateAndRenameToAllLabels();
					}));
				}));
			}));
			
			// this will handle both select boxes in merge tab
			var selectOneNode = Query("select#tag_merge_one")[0];
			var selectTwoNode = Query("select#tag_merge_two")[0];
			
			On(selectOneNode, "change", lang.hitch(this, function(event) {
				// when changing box one, disable the selected value in box two
				this.enableAllOptionsExceptOne(selectTwoNode, DomAttr.get(event.target, "value"));
			}));
			On(selectTwoNode, "change", lang.hitch(this, function(event) {
				// when changing box two, disable the selected value in box one
				this.enableAllOptionsExceptOne(selectOneNode, DomAttr.get(event.target, "value"));
			}));
			
			// setup list
			require(["commsy/List"], lang.hitch(this, function(List) {
				this.list = new List();
				this.list.init(this.cid, this.from_php.template.tpl_path, {
					activatorNode:	Query("a.list_activator")[0],
					module:			"tags",
					roomId:			this.contextId,
					OnInitDone:		lang.hitch(this, function() {
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

			this.connectAssignmentButtons();
			
			
		},

		connectAssignmentButtons: function () {
			// connect all assignment buttons in attach tab
			dojo.forEach(Query("input.tag_attach"), lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", lang.hitch(this, function(event) {
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
			dojo.forEach(optionNodes, lang.hitch(this, function(optionNode, index, arr) {
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
				dojo.some(optionNodes, lang.hitch(this, function(optionNode, index, arr) {
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
			dojo.forEach(Query("ul.popup_tag_list"), lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		tag
				}, clearNode, "before");
			}));
		},
		
		removeTagFromLists: function(tag) {
			dojo.forEach(Query("li.popup_tag_item"), lang.hitch(this, function(itemNode, index, arr) {
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
			
			dojo.forEach(OptionNodes, lang.hitch(this, function(optionNode, index, arr) {
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
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'tags',
					action:	'sortABC'
				},
				data: {
					roomId: this.contextId
				}
			}).then(
				lang.hitch(this, function(response) {
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
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'tags',
						action:	'mergeTags'
					},
					data: {
						idOne:	mergeIdOne,
						idTwo:	mergeIdTwo
					}
				}).then(
					lang.hitch(this, function(response) {
						this.close();
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
			location.reload();
		}
	});
});