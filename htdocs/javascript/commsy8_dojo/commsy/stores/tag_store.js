define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/store/util/QueryResults",
        	"dojo/_base/lang"], function(declare, BaseClass, QueryResults, lang) {
	return declare(BaseClass, {
		idProperty:		"item_id",
		data:			null,
		index:			null,
		
		constructor: function(options) {
			declare.safeMixin(this, options);
			
			this.data = [];
			this.index = [];
		},
		
		get: function(id, options) {
			return this.data[this.index[id]];
		},
		
		getIdentity: function(object) {
			return object[this.idProperty];
		},
		
		put: function(object, options) {
			
		},
		
		add: function(object, options) {
			
		},
		
		remove: function(id) {
			
		},
		
		getAll: function(callback) {
			// get results from ajax call
			this.AJAXRequest('tagtree', 'getTreeData', {}, lang.hitch(this, function(results) {
				this.data = results;
				
				for(var i = 0, l = this.data.length; i < l; i++) {
					this.index[this.data[i][this.idProperty]] = i;
				}
			}));
		},
		
		query: function(query, options) {
			options = options || {};
			
			return QueryResults(this.data);
		}
	});
});