define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On, Topic) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "buzzwords";
			this.list = null;
			
			this.features = [ ];
			
			this.contextId = customObject.contextId;
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
			// this will handle both select boxes in merge tab
			var selectOneNode = Query("select#buzzword_merge_one")[0];
			var selectTwoNode = Query("select#buzzword_merge_two")[0];
			
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
					module:			"buzzwords",
					roomId:			this.contextId,
					OnInitDone:		Lang.hitch(this, function() {
						this.list.performRequest();
					})
				});
				
				// set initial buzzword to first in edit tab
				var firstEditBuzzwordNode = Query("div#edit_tab input.buzzword_change_name:first-child")[0];
				if(firstEditBuzzwordNode) {
					var buzzwordId = DomAttr.get(firstEditBuzzwordNode, "id");
					this.list.requestData.item_id = buzzwordId;
					this.list.requestData.contextId = this.contextId;
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
					this.list.requestData.contextId = this.contextId;
					this.list.performRequest();
					
					// update header
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
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
								
								if (this.contextId) {
									Topic.publish("newOwnRoomBuzzword", {});
								}
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
							
							if (this.contextId) {
								Topic.publish("newOwnRoomBuzzword", {});
							}
						}),
						Lang.hitch(this, function(response) {
							
						})
					);
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
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "add") {
				this.OnAddNewBuzzword();
			} else if(part == "merge") {
				this.OnMergeBuzzwords();
			}
		},
		
		addBuzzwordToLists: function(buzzword) {
			dojo.forEach(Query("ul.popup_buzzword_list"), Lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		buzzword
				}, clearNode, "before");
			}));
		},
		
		removeBuzzwordFromLists: function(buzzword) {
			dojo.forEach(Query("li.popup_buzzword_item"), Lang.hitch(this, function(itemNode, index, arr) {
				if(DomAttr.get(itemNode, "innerHTML") === buzzword) {
					DomConstruct.destroy(itemNode);
				}
			}));
		},
		
		addBuzzwordToMergeSelects: function(id, buzzword) {
			var selectOneNode = Query("select#buzzword_merge_one")[0];
			var selectTwoNode = Query("select#buzzword_merge_two")[0];
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	buzzword
			}, selectOneNode, "last");
			
			DomConstruct.create("option", {
				value:		id,
				innerHTML:	buzzword
			}, selectTwoNode, "last");
		},
		
		removeBuzzwordFromMergeSelects: function(buzzword) {
			var OptionNodes = Query("select#buzzword_merge_one option, select#buzzword_merge_two option");
			
			dojo.forEach(OptionNodes, Lang.hitch(this, function(optionNode, index, arr) {
				if(DomAttr.get(optionNode, "innerHTML") === buzzword) {
					DomConstruct.destroy(optionNode);
				}
			}));
		},
		
		addBuzzwordToEditTab: function(id, buzzword) {
			var divNode = Query("div#edit_tab div#content_row_one")[0];
			
			var rowDivNode = DomConstruct.create("div", {
				className:		"input_row"
			}, divNode, "last");
			
				DomConstruct.create("input", {
					className:		"buzzword_change_name size_200",
					type:			"text",
					value:			buzzword
				}, rowDivNode, "last");
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_change mandatory",
					type:			"button",
					value:			"Ändern",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_attach",
					type:			"button",
					value:			"Einträge zuordnen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_delete",
					type:			"button",
					value:			"Löschen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last")
		},
		
		removeBuzzwordFromEditTab: function(buzzword) {
			dojo.forEach(Query("input.buzzword_change_name"), Lang.hitch(this, function(inputNode, index, arr) {
				if (inputNode.value == buzzword) {
					var rowDivNode = new dojo.NodeList(inputNode).parents("div.input_row")[0];
					DomConstruct.destroy(rowDivNode);
					return true;
				}
			}));
		},
		
		OnAddNewBuzzword: function(roomId) {
			roomId = roomId || null;
			
			// get buzzword
			var buzzword = Lang.trim(DomAttr.get(Query("input#buzzword_create_name")[0], "value"));
			
			console.log(buzzword);
			
			
			if(buzzword !== "") {
				// send ajax request
				this.AJAXRequest("buzzwords", "createNewBuzzword", { buzzword: buzzword, roomId: this.contextId },
					Lang.hitch(this, function(response) {
						// add the new buzzword to all lists
						this.addBuzzwordToLists(buzzword);
						
						// add the new buzzwords to the merge select boxes
						this.addBuzzwordToMergeSelects(response.id, buzzword);
						
						// add the new buzzword to the edit tab
						this.addBuzzwordToEditTab(response.id, buzzword);
						
						this.destroyLoading();
						
						if (this.contextId) {
							Topic.publish("newOwnRoomBuzzword", {});
						}
					}),
					
					Lang.hitch(this, function(response) {
						// an error means the buzzwords is empty or already exists, so highlight things a little bit
						if(response.code == "107") {
							dojo.forEach(Query("li.popup_buzzword_item"), Lang.hitch(this, function(buzzwordNode, index, arr) {
								var buzzName = DomAttr.get(buzzwordNode, "innerHTML");
								
								DomStyle.set(buzzwordNode, "color", (buzzName === buzzword) ? "red" : "#393939");
							}));
						}
						
						this.destroyLoading();
					})
				);
			}
		},
		
		OnMergeBuzzwords: function() {
			// get the two ids to merge
			var mergeIdOne = DomAttr.get(Query("select#buzzword_merge_one")[0], "value");
			var mergeIdTwo = DomAttr.get(Query("select#buzzword_merge_two")[0], "value");
			
			if(mergeIdOne !== mergeIdTwo) {
				// send ajax request
				this.AJAXRequest("buzzwords", "mergeBuzzwords", { idOne: mergeIdOne, idTwo: mergeIdTwo },
					Lang.hitch(this, function(response) {
						// remove both buzzwords from all lists and add the new one
						this.removeBuzzwordFromLists(response.buzzwordOne);
						this.removeBuzzwordFromLists(response.buzzwordTwo);
						this.addBuzzwordToLists(response.newBuzzword);
						
						// remove both buzzwords from the merge select boxes and add the new one
						this.removeBuzzwordFromMergeSelects(response.buzzwordOne);
						this.removeBuzzwordFromMergeSelects(response.buzzwordTwo);
						this.addBuzzwordToMergeSelects(mergeIdOne, response.newBuzzword);
						
						this.destroyLoading();
						
						if (this.contextId) {
							Topic.publish("newOwnRoomBuzzword", {});
						}
					}),
					
					Lang.hitch(this, function(response) {
						this.destroyLoading();
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});