define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/cookie",
        	"dojo/dom-attr",
        	"dojo/_base/array"], function(declare, BaseClass, Lang, Query, On, Cookie, DomAttr, BaseArray) {
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
			this.inputNodes.forEach(Lang.hitch(this, function(node, index, arr) {
				var name = DomAttr.get(node, "name");
				var id = name.substr(18).substr(0, name.length - 19);
				
				if(BaseArray.indexOf(this.cookieObject.selectedIDs, id) !== -1) {
					DomAttr.set(node, "checked", "checked");
				}
			}));
			
			// restore number of selected entries
			DomAttr.set(this.counterNode, "innerHTML", this.cookieObject.selectedIDs.length);
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
		}
	});
});