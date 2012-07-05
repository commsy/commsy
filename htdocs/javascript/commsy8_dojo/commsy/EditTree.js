define([	"dojo/_base/declare",
        	"dojo/dom-construct",
        	"dojo/io-query",
        	"commsy/tree",
        	"dojo/_base/lang",
        	"cbtree/Tree",
        	"dojo/query",
        	"cbtree/models/ForestStoreModel",
        	"dojo/data/ItemFileWriteStore",
        	"cbtree/CheckBox",
        	"cbtree/models/StoreModel-API"], function(declare, DomConstruct, ioQuery, TreeClass, Lang, Tree, Query, ForestStoreModel, ItemFileWriteStore, CheckBox) {
	return declare(TreeClass, {
		constructor: function(options) {
			// parent constructor is called automatically
		},
		
		setupTree: function(node) {
			// call parent method - overwrite arguments(add a callback function, when loading is done)
			this.inherited(arguments, [ node, Lang.hitch(this, function() {
				// loading is done - now we can safely access this.tree
				
				// add "+" to all node labels
				this.addCreateToAllLabels();
			})]);
		},
		
		addCreateToAllLabels: function() {
			var model = this.tree.model;
			
			console.log(model.fetchItem({ identifier:'*' }));
		}
	});
});