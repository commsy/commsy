define([	"dojo/_base/declare",
        	"commsy/ClickPopupHandler",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on"], function(declare, ClickPopupHandler, query, dom_class, lang, domConstruct, domAttr, On) {
	return declare(ClickPopupHandler, {
		constructor: function() {
			
		},
		
		init: function(triggerNode, customObject) {
			this.triggerNode = triggerNode;
			this.item_id = customObject.iid;
			this.user_id = customObject.user_id;
			this.content_id = customObject.context_id;
			this.action = customObject.action;
			this.description_user = customObject.description_user;
			this.module = "userContextJoin";
			
			
			this.features = [];
			
			// register click for node
			this.registerPopupClick();
		},
		
		setupSpecific: function() {
		},
		
		onPopupSubmit: function(customObject) {
			var part = customObject.part;
			var user_id = customObject.user_id;
			var context_id = customObject.context_id;
			var action = customObject.action;
			var description_user = customObject.description_user;
			var agb = customObject.agb;
			
			// setup data to send via ajax
			var search = {
				tabs: [],
				nodeLists: [
				   { query: query("textarea[name='form_data[description_user]']", this.contentNode) },
				   { query: query("input[name='form_data[agb]']", this.contentNode) },
				   { query: query("input[name='form_data[code]']", this.contentNode) }
				]
			};
			
			this.submit(search,  { part: part, user_id: user_id, context_id: context_id, action: action, description_user: description_user, agb: agb });
		},
		
		onPopupSubmitSuccess: function(data) {
			location.href = "commsy.php?cid=" + data.cid + "&mod=" + data.mod + "&fct=detail&iid=" + data.item_id;
		},
		
		onPopupSubmitError: function(response){
			require(["dojo/dom-style", "dojo/query", "dojo/NodeList-dom"], function(domStyle, query){
				if(response.code === "111"){
					domStyle.set("error_wrong_code", 'display', 'block');
				} else if(response.code === "115"){
					domStyle.set("error_agb_accept",'display','block');
				}
				
			});
			//query("input[name='form_data[code]']", this.contentNode).value = '';
		}
	});
});