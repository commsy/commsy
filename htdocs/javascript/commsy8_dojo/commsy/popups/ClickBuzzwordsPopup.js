define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/on"], function(declare, ClickPopupHandler, Query, DomClass, Lang, DomConstruct, DomAttr, DomStyle, On) {
	return declare(ClickPopupHandler, {
		constructor: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			//this.item_id = customObject.iid;
			this.module = "buzzwords";
			
			this.features = [ ];
			
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
		
		addBuzzwordToMergeSelects: function(buzzword) {
			
		},
		
		removeBuzzwordFromMergeSelects: function(buzzword) {
			
		},
		
		OnAddNewBuzzword: function() {
			// get buzzword
			var buzzword = DomAttr.get(Query("input#buzzword_create_name")[0], "value").trim();
			
			if(buzzword !== "") {
				// send ajax request
				this.AJAXRequest("buzzwords", "createNewBuzzword", { buzzword: buzzword },
					Lang.hitch(this, function(response) {
						// add the new buzzword to all lists
						this.addBuzzwordToLists(buzzword);
						
						// add the new buzzwords to the merge select boxes
						var selectOneNode = Query("select#buzzword_merge_one")[0];
						var selectTwoNode = Query("select#buzzword_merge_two")[0];
						
						DomConstruct.create("option", {
							value:		response.id,
							innerHTML:	buzzword
						}, selectOneNode, "last");
						
						DomConstruct.create("option", {
							value:		response.id,
							innerHTML:	buzzword
						}, selectTwoNode, "last");
					}),
					
					Lang.hitch(this, function(response) {
						// an error means the buzzwords is empty or already exists, so highlight things a little bit
						if(response.code == "107") {
							dojo.forEach(Query("li.popup_buzzword_item"), Lang.hitch(this, function(buzzwordNode, index, arr) {
								var buzzName = DomAttr.get(buzzwordNode, "innerHTML");
								
								DomStyle.set(buzzwordNode, "color", (buzzName === buzzword) ? "red" : "#393939");
							}));
						}
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
						this.addBuzzwordToMergeSelects(response.buzzwordTwo);
					}),
					
					Lang.hitch(this, function(response) {
						
					})
				);
			}
		},
		
		onPopupSubmitSuccess: function(item_id) {
		},
	});
});