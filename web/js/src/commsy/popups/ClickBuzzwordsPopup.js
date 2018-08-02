define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"commsy/request",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/NodeList-traverse"], function(declare, ClickPopupHandler, Query, DomClass, lang, request, DomConstruct, DomAttr, DomStyle, On, Topic) {
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
					module:			"buzzwords",
					roomId:			this.contextId,
					OnInitDone:		lang.hitch(this, function() {
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
			dojo.forEach(Query("input.buzzword_attach"), lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", lang.hitch(this, function(event) {
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
			dojo.forEach(Query("input.buzzword_change"), lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
					
					// get new buzzword name
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					
					// perform ajax request
					request.ajax({
						query: {
							cid:	this.uri_object.cid,
							mod:	'ajax',
							fct:	'buzzwords',
							action:	'updateBuzzword'
						},
						data: {
							buzzword_id:	buzzwordId,
							buzzword:		buzzwordName
						}
					}).then(
						lang.hitch(this, function(response) {
							
							// update header if the buzzword was set in list
							if(this.list.requestData.item_id === buzzwordId) {
								DomAttr.set(Query("div.open_close_head span.text_important")[0], "innerHTML", "&bdquo;" + buzzwordName + "&rdquo;");
								
								if (this.contextId) {
									Topic.publish("newOwnRoomBuzzword", {});
								}
							}

							// update buzzwords
							DomAttr.set(Query("li#popup_buzzword_item_add_" + buzzwordId)[0], "innerHTML", buzzwordName);
							DomAttr.set(Query("li#popup_buzzword_item_edit_" + buzzwordId)[0], "innerHTML", buzzwordName);
							DomAttr.set(Query("option#popup_buzzword_item_merge_one_" + buzzwordId)[0], "innerHTML", buzzwordName);
							DomAttr.set(Query("option#popup_buzzword_item_merge_two_" + buzzwordId)[0], "innerHTML", buzzwordName);
						})
					);
				}));
			}));
			
			// connect all delete buttons in edit tab
			dojo.forEach(Query("input.buzzword_delete"), lang.hitch(this, function(inputNode, index, arr) {
				On(inputNode, "click", lang.hitch(this, function(event) {
					// get name and extract buzzword id
					var nameAttr = DomAttr.get(inputNode, "name");
					var buzzwordId = nameAttr.substr(10, nameAttr.length-11);
					
					// get buzzword name
					var buzzwordName = DomAttr.get(new dojo.NodeList(inputNode).siblings("input.buzzword_change_name")[0], "value");
					
					// perform ajax request
					request.ajax({
						query: {
							cid:	this.uri_object.cid,
							mod:	'ajax',
							fct:	'buzzwords',
							action:	'deleteBuzzword'
						},
						data: {
							buzzword_id: buzzwordId
						}
					}).then(
						lang.hitch(this, function(response) {
							// remove buzzword from all lists, merge selects and edit tab
							this.removeBuzzwordFromLists(buzzwordName);
							this.removeBuzzwordFromMergeSelects(buzzwordName);
							this.removeBuzzwordFromEditTab(buzzwordName);
							
							if (this.contextId) {
								Topic.publish("newOwnRoomBuzzword", {});
							}
						})
					);
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
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			
			if(part === "add") {
				this.OnAddNewBuzzword();
			} else if(part == "merge") {
				this.OnMergeBuzzwords();
			}
		},
		
		addBuzzwordToLists: function(buzzword) {
			dojo.forEach(Query("ul.popup_buzzword_list"), lang.hitch(this, function(listNode, index, arr) {
				var clearNode = Query("div.clear", listNode)[0];
				
				DomConstruct.create("li", {
					className:		"ui-state-default popup_buzzword_item",
					innerHTML:		buzzword
				}, clearNode, "before");
			}));
		},
		
		removeBuzzwordFromLists: function(buzzword) {
			dojo.forEach(Query("li.popup_buzzword_item"), lang.hitch(this, function(itemNode, index, arr) {
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
			
			dojo.forEach(OptionNodes, lang.hitch(this, function(optionNode, index, arr) {
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
				}, rowDivNode, "last");
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_attach",
					type:			"button",
					value:			"Einträge zuordnen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last");
				
				DomConstruct.create("input", {
					className:		"popup_button buzzword_delete",
					type:			"button",
					value:			"Löschen",
					name:			"form_data[" + id + "]"
				}, rowDivNode, "last");

				this.setupSpecific();
		},
		
		removeBuzzwordFromEditTab: function(buzzword) {
			dojo.forEach(Query("input.buzzword_change_name"), lang.hitch(this, function(inputNode, index, arr) {
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
			var buzzword = lang.trim(DomAttr.get(Query("input#buzzword_create_name")[0], "value"));
			
			if(buzzword !== "") {
				// send ajax request
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'buzzwords',
						action:	'createNewBuzzword'
					},
					data: {
						buzzword:	buzzword,
						roomId:		this.contextId
					}
				}).then(
					lang.hitch(this, function(response) {
						if(response.code != "107") {
							// add the new buzzword to all lists
							this.addBuzzwordToLists(buzzword);
							
							// add the new buzzwords to the merge select boxes
							this.addBuzzwordToMergeSelects(response.data.id, buzzword);
							
							// add the new buzzword to the edit tab
							this.addBuzzwordToEditTab(response.data.id, buzzword);
							
							this.destroyLoading();
							
							if (this.contextId) {
								Topic.publish("newOwnRoomBuzzword", {});
							}
						} else {
							dojo.forEach(Query("li.popup_buzzword_item"), lang.hitch(this, function(buzzwordNode, index, arr) {
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
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'buzzwords',
						action:	'mergeBuzzwords'
					},
					data: {
						idOne:	mergeIdOne,
						idTwo:	mergeIdTwo
					}
				}).then(
					lang.hitch(this, function(response) {
						// remove both buzzwords from all lists and add the new one
						this.removeBuzzwordFromLists(response.data.buzzwordOne);
						this.removeBuzzwordFromLists(response.data.buzzwordTwo);
						this.addBuzzwordToLists(response.data.newBuzzword);
						
						// remove both buzzwords from the merge select boxes and add the new one
						this.removeBuzzwordFromMergeSelects(response.data.buzzwordOne);
						this.removeBuzzwordFromMergeSelects(response.data.buzzwordTwo);
						this.addBuzzwordToMergeSelects(mergeIdOne, response.data.newBuzzword);
						
						// remove both buzzwords from edit tab
						this.removeBuzzwordFromEditTab(response.data.buzzwordOne);
						this.removeBuzzwordFromEditTab(response.data.buzzwordTwo);
						this.addBuzzwordToEditTab(mergeIdOne, response.data.newBuzzword);
						
						this.destroyLoading();
						
						if (this.contextId) {
							Topic.publish("newOwnRoomBuzzword", {});
						}
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		}
	});
});