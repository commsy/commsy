define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dnd/Source",
        	"dojo/on",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/fx",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, Lang, Query, Source, On, DomConstruct, DomAttr, DomStyle, FX) {
	return declare(BaseClass, {		
		cid: 						null,
		item_id: 					null,
		tpl_path: 					'',
		sortable:					null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		init: function(cid, item_id, module, tpl_path) {
			this.cid = cid;
			this.item_id = item_id;
			this.tpl_path = tpl_path;
			
			// register handler
			var triggerNode = Query("#popup_path_tab")[0];
			
			if (triggerNode) {
				// TODO: Hotfix: If topic is saved and path tab was not clicked,
				// DOM for checkboxes is not generated and status will not be saved.
				// For now, load always
				//On(triggerNode, "click", Lang.hitch(this, function(event) {
					var list = Query("ul#popup_path_list")[0];
					
					// setup sortable
					this.sortable = this.sortable || new Source(list, {
						singular: true
					});
					
					// get all connected entries for this item
					if(this.item_id !== "NEW") {
						this.AJAXRequest("path", "getConnectedEntries", { item_id: item_id }, Lang.hitch(this, function(data) {
							// clear list
							dojo.forEach(Query(">", list), function(liNode, index, arr) {
								DomConstruct.destroy(liNode);
							});
							
							// append items to list
							dojo.forEach(data, Lang.hitch(this, function(entry, index, arr) {
								var liNode = DomConstruct.create("li", {
									className:	"netnavigation"
								}, list, "last");
								
									DomConstruct.create("input", {
										type:		"checkbox",
										id:			"path_" + entry.linked_id,
										checked:	entry.path_active
									}, liNode, "last");
									
									DomConstruct.create("img", {
										src:		this.from_php.template.tpl_path + "img/netnavigation/" + entry.img
									}, liNode, "last");
									
									DomConstruct.create("span", {
										innerHTML:	entry.text
									}, liNode, "last");
							}));
							
							this.sortable.insertNodes(false, Query(">", list));
						}));
					}
				//}));
			}
		},
		
		save: function(item_id, callback) {
			var request_item_id = this.item_id;
			if(item_id) request_item_id = item_id;
			
			// check if path tab is set up
			var pathListNode = Query("ul#popup_path_list")[0];
			
			if ( pathListNode )
			{
				// collect data
				var ids = new Array();
				dojo.forEach(Query("input[type='checkbox']:checked", pathListNode), function(checkbox, index, arr) {
					// extract item id
					var regex = new RegExp("path_(.*)");
					var results = regex.exec(DomAttr.get(checkbox, "id"));
					id = results[1];
					
					ids.push(id);
				});
				
				this.AJAXRequest("path", "savePath", { item_id: request_item_id, linked_ids: ids }, callback);
			}
			else
			{
				this.AJAXRequest("path", "savePath", { item_id: request_item_id, onlyUpdate: true }, callback);
			}
		}
	});
});