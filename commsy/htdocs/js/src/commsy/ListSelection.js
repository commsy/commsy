define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/cookie",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/_base/array"], function(declare, BaseClass, Lang, Query, On, Cookie, DomAttr, DomConstruct, BaseArray) {
	return declare(BaseClass, {
		cookieName:		"commsy_list_selection",
		cookieObject: {
			lastRubric:		null,
			selectedIDs:	[]
		},
		currentRubric:	null,
		inputNodes:		null,
		counterNode:	null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(inputNodes, counterNode) {
			this.inputNodes = inputNodes;
			this.counterNode = counterNode;
			
			// set current rubric
			this.currentRubric = this.uri_object.mod;
			
			// set values from cookie - if cookie exists
			var cookieJSON = Cookie(this.cookieName);
			var cookieObject = {};
			if(cookieJSON) {
				cookieObject = dojo.fromJson(cookieJSON);
			}
			
			// last rubric comes either from cookie or - if the cookie was not set - is the current rubric
			this.cookieObject.lastRubric = cookieObject.lastRubric || this.currentRubric;
			this.cookieObject.selectedIDs = cookieObject.selectedIDs || [];
			
			// setup handler for checkboxes
			On(this.inputNodes, "click", Lang.hitch(this, function(event) {
				this.onClickCheckbox(event.target);
			}));
			
			// setup handler for list action submit button
			var inputSubmitNode = Query("input#delete_confirmselect_option")[0];
			if (inputSubmitNode) {
				On(inputSubmitNode, "click", Lang.hitch(this, function(event) {
					this.onClickListActionSubmit(event.target);
				}));
			}
			
			// setup select all handler
			var inputSelectAllNode = Query("input#selectAll")[0];
			if (inputSelectAllNode) {
				On(inputSelectAllNode, "click", Lang.hitch(this, function(event) {
					this.onSelectAll(event.target);
				}));
			}
			
			// if current rubric equals last rubric - restore selection from cookie,
			// otherwise reset selection
			if(this.currentRubric === this.cookieObject.lastRubric) {
				this.restoreSelection();
			} else {
				this.cookieObject.selectedIDs = [];
			}
			
			// save current rubric as last rubric and save cookie
			this.cookieObject.lastRubric = this.currentRubric;
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
		},
		
		restoreSelection: function() {
			// restore checkbox status
			dojo.forEach(this.inputNodes, Lang.hitch(this, function(node, index, arr) {
				var name = DomAttr.get(node, "name");
				var id = name.substr(18).substr(0, name.length - 19);
				
				if(BaseArray.indexOf(this.cookieObject.selectedIDs, id) !== -1) {
					DomAttr.set(node, "checked", true);
				}
			}));
			
			// if all checkboxes on this page are checked, set the "select all" checkbox enabled
			// otherwise deselect it
			var checkboxNodes = Query("div.row_even input[type='checkbox'], div.row_odd input[type='checkbox']");
			var checkedCheckboxNodes = Query("div.row_even input[type='checkbox']:checked, div.row_odd input[type='checkbox']:checked");
			
			var inputSelectAllNode = Query("input#selectAll")[0];
			if ( checkboxNodes && checkedCheckboxNodes && inputSelectAllNode )
			{
				if ( checkboxNodes.length == checkedCheckboxNodes.length )
				{
					DomAttr.set(inputSelectAllNode, "checked", true);
				}
				else
				{
					DomAttr.set(inputSelectAllNode, "checked", false);
				}
			}
			
			// restore number of selected entries
			if(this.counterNode) DomAttr.set(this.counterNode, "innerHTML", this.cookieObject.selectedIDs.length);
		},
		
		onClickCheckbox: function(checkboxNode) {
			var name = DomAttr.get(checkboxNode, "name");
			var id = name.substr(18).substr(0, name.length - 19);
			
			// get current checkbox status
			var isChecked = (DomAttr.get(checkboxNode, "checked"));
			
			if(isChecked) {
				// add id to selected
				this.cookieObject.selectedIDs.push(id);
			} else {
				// remove id from selected
				this.cookieObject.selectedIDs.splice(BaseArray.indexOf(this.cookieObject.selectedIDs, id), 1);
			}
			
			// save cookie
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
			
			// restore selection
			this.restoreSelection();
		},
		
		performListOption: function(option) {
			if (option == "listoption_download") {
				// bad hack... :(
				// create a link and simulate click - like in detail view
				dojo.forEach(this.cookieObject.selectedIDs, Lang.hitch(this, function(id, index, arr) {
					var downloadLinkNode = DomConstruct.create("a", {
						href:	"commsy.php?cid=" + this.uri_object.cid + "&mod=download&fct=action&iid=" + id,//this.cookieObject.selectedIDs.join(";"),
						target:	"_blank"
					}, Query("body")[0], "last");
					
					downloadLinkNode.click();
					
					DomConstruct.destroy(downloadLinkNode);
				}));
			}
		},
		
		onClickListActionSubmit: function(inputNode) {
			// perform list action
			var value = DomAttr.get(Query("select[name='form_data[option][list]']")[0], "value");
			this.performListOption(value);
			
			// empty selected ids
			this.cookieObject.selectedIDs = [];
			
			// save cookie
			Cookie(this.cookieName, dojo.toJson(this.cookieObject));
		},
		
		onSelectAll: function(inputNode)
		{
			var checkboxNodes = Query("div.row_even input[type='checkbox'], div.row_odd input[type='checkbox']");
			var checkedCheckboxNodes = Query("div.row_even input[type='checkbox']:checked, div.row_odd input[type='checkbox']:checked");
			
			if ( checkboxNodes && checkedCheckboxNodes )
			{
				/*
				 * If the number of current checked checkboxes is lower than the total number of checkboxes, select all.
				 * Otherwise deselect all
				 */
				if ( checkedCheckboxNodes.length < checkboxNodes.length )
				{
					dojo.forEach(checkboxNodes, function(checkboxNode, index, arr)
					{
						DomAttr.set(checkboxNode, "checked", true);
					});
					
					var inputSelectAllNode = Query("input#selectAll")[0];
					if (inputSelectAllNode)
					{
						DomAttr.set(inputSelectAllNode, "checked", true);
					}
				}
				else
				{
					dojo.forEach(checkboxNodes, function(checkboxNode, index, arr)
					{
						DomAttr.set(checkboxNode, "checked", false);
					});
					
					var inputSelectAllNode = Query("input#selectAll")[0];
					if (inputSelectAllNode)
					{
						DomAttr.set(inputSelectAllNode, "checked", false);
					}
				}
				
				// update cookie
				dojo.forEach(this.inputNodes, Lang.hitch(this, function(node, index, arr)
				{
					var name = DomAttr.get(node, "name");
					var id = name.substr(18).substr(0, name.length - 19);
					
					// get current checkbox status
					var isChecked = (DomAttr.get(node, "checked"));
					
					if ( isChecked )
					{
						// add id to selected
						this.cookieObject.selectedIDs.push(id);
					}
					else
					{
						// remove id from selected
						this.cookieObject.selectedIDs.splice(BaseArray.indexOf(this.cookieObject.selectedIDs, id), 1);
					}
					
					
					if(BaseArray.indexOf(this.cookieObject.selectedIDs, id) !== -1) {
						DomAttr.set(node, "checked", true);
					}
				}));
				
				// save cookie
				Cookie(this.cookieName, dojo.toJson(this.cookieObject));
				
				// restore selection
				this.restoreSelection();
			}
		}
	});
});