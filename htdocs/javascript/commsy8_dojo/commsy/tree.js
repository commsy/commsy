define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dijit/Tree",
        	"commsy/stores/tag_store"], function(declare, BaseClass, lang, Tree, TagStore) {
	return declare(BaseClass, {
		constructor: function(button_node, content_node) {
			
		},
		
		setupTree: function(node) {
			var model = TagStore({
				mayHaveChildren: function(object) {
					return "children" in object;
				},
				
				getChildren: function(object, onComplete, onError) {
					/*
					 * // retrieve the full copy of the object
			        this.get(object.id).then(function(fullObject){
			            // copy to the original object so it has the children array as well.
			            object.children = fullObject.children;
			            // now that full object, we should have an array of children
			            onComplete(fullObject.children);
			        }, function(error){
			            // an error occurred, log it, and indicate no children
			            console.error(error);
			            onComplete([]);
			        });
					 */
					var fullObject = this.get(object.id);
					object.children = fullObject.children;
					
					onComplete(fullObject.children);
				},
				
				/*
				getRoot: function(onItem, onError) {
					/*
					 * // get the root object, we will do a get() and callback the result
        		this.get("root").then(onItem, onError);
					 *//*
					var rootObject = this.get('root');
					onItem(rootObject);
				},
				*/
				getLabel: function(object) {
					return object.title;
				}
			});
			
			model.getAll(function() {
				// create tree
				var tree = new Tree({
					model:	model
				});
				
				tree.placeAt(node);
			});
		},
		
		onClickPopupOpen: function() {
			
		},
		
		setupSpecific: function() {
			
		}
	});
});