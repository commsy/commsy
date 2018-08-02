define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/_base/array",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, lang, Query, On, DomConstruct, request, DomAttr, DomStyle, BaseArray) {
	return declare(BaseClass, {		
		cid: 						null,
		tpl_path: 					'',
		initialized: 				false,
		store: {
			pages:					1,
			selected_ids:			[]
		},
		
		init: function(cid, tpl_path) {
			this.cid = cid;
			this.tpl_path = tpl_path;
			
			this.performRequest();

			// setup select all handler
			var inputSelectAllNode = Query("input#selectAllClipboard")[0];
			if (inputSelectAllNode) {
				On(inputSelectAllNode, "click", lang.hitch(this, function(event) {
					this.onSelectAll();
				}));
			}
			
			// setup action submit
			On(Query("input#list_action_submit")[0], "click", lang.hitch(this, function(event) {
				this.onActionSubmit();
			}));
		},
		
		performRequest: function() {
			// send request
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'clipboard',
					action:	'performRequest'
				}
			}).then(
				lang.hitch(this, function(response) {
					var contentObject = Query("#popup_accounts #crt_row_area")[0];
					
					// fill list
					DomConstruct.empty(contentObject);
					
					// iterate through all rooms
					dojo.forEach(response.data.list, lang.hitch(this, function(room, index, arr) {
						// add headline
						DomConstruct.create("h2", {
							innerHTML:		room.headline
						}, contentObject, "last");
						
						// iterate through all items
						dojo.forEach(room.items, lang.hitch(this, function(entry, index, arr) {
							var rowDivNode = DomConstruct.create("div", {
								className:		(index % 2 === 0) ? 'pop_row_even' : 'pop_row_odd'
							}, contentObject, "last");
							
								var checkboxDivNode = DomConstruct.create("div", {
									className:		"pop_col_25"
								}, rowDivNode, "last");
								
									DomConstruct.create("input", {
										type:		"checkbox",
										id:			"item_" + entry.item_id,
										checked:	(BaseArray.indexOf(this.store.selected_ids.indexOf, entry.item_id) !== -1) ? true : false,
										disabled:	entry.disabled
									}, checkboxDivNode, "last");
								
								DomConstruct.create("div", {
									className:		"pop_col_270",
									innerHTML:		entry.title
								}, rowDivNode, "last");
								
								var rubricDivNode = DomConstruct.create("div", {
									className:		"pop_col_150"
								}, rowDivNode, "last");
								
									DomConstruct.create("img", {
										src:	this.tpl_path + "img/netnavigation/" + entry.rubric.img,
										alt:	entry.rubric.text
									}, rubricDivNode, "last");
								
								DomConstruct.create("div", {
									className:		"pop_col_270",
									innerHTML:		entry.modifier
								}, rowDivNode, "last");
								
								DomConstruct.create("div", {
									className:		"pop_col_150",
									innerHTML:		entry.modification_date
								}, rowDivNode, "last");
								
								DomConstruct.create("div", {
									className:		"clear"
								}, rowDivNode, "last");
						}));
					}));
					
					// register input event handler and store / remove selected ids
					On(Query("input[id^='item_']", contentObject), "click", lang.hitch(this, function(event) {
						var inputObject = event.target;
						
						// extract id
						var itemId = DomAttr.get(inputObject, "id").substr(5);
						
						var index = BaseArray.indexOf(this.store.selected_ids, itemId);
						if(index === -1) this.store.selected_ids.push(itemId);
						else this.store.selected_ids.splice(index, 1);
					}));
				})
			);
		},
		
		onActionSubmit: function() {
			var contentObject = Query("#popup_accounts #crt_row_area")[0];
			
			// get current action
			var action = DomAttr.get(Query("select#list_action")[0], "value");
			
			// setup loading
			this.setupLoading();
			
			// send action and id list via ajax
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'clipboard',
					action:	'performClipboardAction'
				},
				data: {
					ids:	this.store.selected_ids,
					action:	action
				}
			}).then(
				lang.hitch(this, function(response) {
					if(action === "paste") {
						// redirect
						location.href = response.data.url;
					} else if(action === "paste_stack") {
						this.destroyLoading();
					} else if(action === "delete") {
						// remove entries from list
						var numEntries = 0;
						dojo.forEach(Query("input[id^='item_']", contentObject), lang.hitch(this, function(node, index, arr) {
							if(BaseArray.indexOf(this.store.selected_ids, DomAttr.get(node, "id").substr(5)) !== -1) {
								var rowNode = new dojo.NodeList(node).parents("div[class^='pop_row_']")[0];
								DomConstruct.destroy(rowNode);
							} else {
								numEntries++;
							}
						}));
						
						DomAttr.set(Query("span#tm_clipboard_copies")[0], "innerHTML", numEntries);
						this.store.selected_ids = [];
						this.destroyLoading();
					}
				})
			);
		},

		onSelectAll: function() {
			var checkboxNodes = Query("div.pop_row_even input[type='checkbox'], div.pop_row_odd input[type='checkbox']");
			var checkedCheckboxNodes = Query("div.pop_row_even input[type='checkbox']:checked, div.pop_row_odd input[type='checkbox']:checked");
			
			if ( checkboxNodes && checkedCheckboxNodes )
			{
				/*
				 * If the number of current checked checkboxes is lower than the total number of checkboxes, select all.
				 * Otherwise deselect all
				 */
				if ( checkedCheckboxNodes.length < checkboxNodes.length )
				{
					dojo.forEach(checkboxNodes, lang.hitch(this, function(checkboxNode, index, arr)
					{
						DomAttr.set(checkboxNode, "checked", true);
						this.store.selected_ids.push(DomAttr.get(checkboxNode, "id").substr(5));
					}));
					
					var inputSelectAllNode = Query("input#selectAllClipboard")[0];
					if (inputSelectAllNode)
					{
						DomAttr.set(inputSelectAllNode, "checked", true);
					}
				}
				else
				{
					dojo.forEach(checkboxNodes, lang.hitch(this, function(checkboxNode, index, arr)
					{
						DomAttr.set(checkboxNode, "checked", false);
						this.store.selected_ids = [];
					}));
					
					var inputSelectAllNode = Query("input#selectAllClipboard")[0];
					if (inputSelectAllNode)
					{
						DomAttr.set(inputSelectAllNode, "checked", false);
					}
				}
			}
		}
	});
});