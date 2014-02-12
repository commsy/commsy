define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/fx",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, Lang, Query, On, DomConstruct, DomAttr, DomStyle, FX) {
	return declare(BaseClass, {		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.restrictions = {
				search:					'',
				rubric:					'all',
				type:					2,
				only_linked:			false
			};
			
			this.cid = null;
			this.tpl_path = '';
			this.initialized = false;
			
			this.paging = {
				current: 0
			};
			
			this.store = {
				pages:					1,
				selected:				0,
				after_item_creation:	[]
			};
			
			this.config = null;
			
			this.requestData = {
				item_id: 				null
			};
		},
		
		init: function(cid, tpl_path, config) {
			this.cid = cid;
			this.tpl_path = tpl_path;
			this.config = config;
			
			this.config.contentNode = this.config.contentNode || Query("body")[0];
			
			// register handler
			On(this.config.activatorNode, "click", Lang.hitch(this, function(event) {
				// get inital data if this is the first call
				if(this.initialized === false) {
					this.AJAXRequest(this.config.module, "getInitialData", { }, Lang.hitch(this, function(data) {
						// init rubric select box
						var selectNode = Query("select[name='netnavigation_rubric_restriction']", this.config.contentNode)[0];
						
						dojo.forEach(data.rubrics, function(rubric, index, arr) {
							DomConstruct.create("option", {
								value:		rubric.value,
								innerHTML:	rubric.text,
								disabled:	rubric.disabled
							}, selectNode, "last");
						});
						
						// setup
						this.setupPaging();
						this.setupRestrictions();
						
						// register form submit
						On(Query("input[name='netnavigation_submit_restrictions']", this.config.contentNode)[0], "click", Lang.hitch(this, function(event) {
							// reset paging
							this.paging.current = 0;
							
							this.performRequest();
							
							event.preventDefault();
						}));
						
						if(this.config.OnInitDone) this.config.OnInitDone();
						
						this.initialized = true;
					}));
				}
			}));
		},
		
		setupRestrictions: function() {
			var contentNode = Query("div.pop_item_content", this.config.contentNode)[0];
			
			// type restriction
			var typeRestrictionNode = Query("select[name='netnavigation_type_restriction']", this.config.contentNode)[0];
			if(typeRestrictionNode) {
				On(typeRestrictionNode, "change", Lang.hitch(this, function(event) {
					this.restrictions.type = DomAttr.get(event.target, "value");
					event.preventDefault();
				}));
			}
			
			// rubric restriction
			On(Query("select[name='netnavigation_rubric_restriction']", contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.restrictions.rubric = DomAttr.get(event.target, "value");
				event.preventDefault();
			}));
			
			// search restriction
			On(Query("input[name='netnavigation_search_restriction']", contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.restrictions.search = DomAttr.get(event.target, "value");
				event.preventDefault();
			}));
			
			// linked restriction
			On(Query("input[name='netnavigation_linked_restriction']", contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.restrictions.only_linked = (DomAttr.get(event.target, "checked") === true) ? true : false;
				event.preventDefault();
			}));
		},
		
		setupPaging: function() {
			var navigationNode = Query(".pop_item_navigation", this.config.contentNode)[0];
			
			// first
			On(Query("a", navigationNode)[0], "click", Lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current = 0;
				event.preventDefault();
				this.performRequest();
			}));
			
			// previous
			On(Query("a", navigationNode)[1], "click", Lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current--;
				event.preventDefault();
				this.performRequest();
			}));
			
			// next
			On(Query("a", navigationNode)[2], "click", Lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current++;
				event.preventDefault();
				this.performRequest();
			}));
			
			// last
			On(Query("a", navigationNode)[3], "click", Lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current = this.store.pages - 1;
				event.preventDefault();
				this.performRequest();
			}));
		},
		
		performRequest: function() {
			// create data object for request
			var data = {
				module:			this.module,
				current_page:	this.paging.current,
				restrictions:	this.restrictions,
				roomId:			this.config.roomId
			};
			
			Lang.mixin(data, this.requestData);
			
			// send request
			this.AJAXRequest(this.config.module, "performRequest", data, Lang.hitch(this, function(ret) {
				var contentNode = Query("#crt_row_area, .crt_row_area", this.config.contentNode)[0];
				
				// fill list
				DomConstruct.empty(contentNode);
				
				dojo.forEach(ret.list, function(entry, index, arr) {
					// if current module is of type user, deactivate selection for "All Members"(system label) entries
					var disabled = false;
					if(this.module === "user" && entry.system_label === true) {
						disabled = true;
					}
					
					var rowDiv = DomConstruct.create("div", {
						className:		(index % 2 === 0) ? "pop_row_even" : "pop_row_odd"
					}, contentNode, "last");
					
						var checkboxDiv = DomConstruct.create("div", {
							className:		"pop_col_25"
						}, rowDiv, "last");
						
							DomConstruct.create("input", {
								type:		"checkbox",
								id:			"linked_" + entry.item_id,
								checked:	entry.checked,
								disabled:	disabled
							}, checkboxDiv, "last");
						
						DomConstruct.create("div", {
							className:		"pop_col_270",
							innerHTML:		entry.title
						}, rowDiv, "last");
						
						DomConstruct.create("div", {
							className:		"pop_col_150",
							innerHTML:		entry.modification_date
						}, rowDiv, "last");
						
						DomConstruct.create("div", {
							className:		"pop_col_150",
							innerHTML:		entry.modificator
						}, rowDiv, "last");
						
						DomConstruct.create("div", {
							className:		"clear"
						}, rowDiv, "last");
				});
				
				// update selected
				this.store.selected = ret.num_selected_total;
				//DomAttr.set(Query("span#pop_item_entries_selected")[0], "innerHTML", this.store.selected);
				
				// register checkbox events - unregistering is done when destroying
				dojo.forEach(Query("input[type='checkbox']", contentNode), Lang.hitch(this, function(node, index, arr) {
					var rowNode = new dojo.NodeList(node).parents("div[class^='pop_row_']")[0];
					
					// safe row background color
					var oldBgColor = DomStyle.get(rowNode, "backgroundColor");
					
					On(node, "change", Lang.hitch(this, function(event) {
						var checked = DomAttr.get(node, "checked");
						var linkedId = DomAttr.get(event.target, "id").substr(7);
						
						var data = {
							link_id:	linkedId,
							checked:	checked
						};
						
						Lang.mixin(data, this.requestData);
						
						//DomAttr.set(Query("span#pop_item_entries_selected")[0], "innerHTML", this.store.selected);
						
						// set new row background color
						DomStyle.set(rowNode, "backgroundColor", "#D1D1D1");
						
						// perform request
						this.AJAXRequest(this.config.module, "updateLinkedItem", data, Lang.hitch(this, function(ret) {
							// animate back to old row color
							dojo.anim(rowNode, {
								"backgroundColor":		oldBgColor
							});
							
							if(checked === true) {
								// on check
								this.store.selected++;
							} else {
								// on uncheck
								this.store.selected--;
							}
						}));
					}));
					
				}));
				
				// update current page and total number of pages
				DomAttr.set(Query("#pop_item_current_page, .pop_item_current_page", this.config.contentNode)[0], "innerHTML", (ret.list.length === 0) ? 0 : this.paging.current + 1);
				DomAttr.set(Query("#pop_item_pages, .pop_item_pages", this.config.contentNode)[0], "innerHTML", ret.paging.pages);
				
				// store pages
				this.store.pages = ret.paging.pages;
			}));
		}/*,
		
		afterItemCreation: function(item_id, callback) {
			// get ids
			var storeAfterItemCreation = [];
			
			dojo.forEach(Query("div#netnavigation_list li"), function(node, index, arr) {
				storeAfterItemCreation.push(DomAttr.get(node, "id").substr(5));
			});
			
			var requestsTotal = storeAfterItemCreation.length;
			var requestsCompleted = 0;
			
			if(storeAfterItemCreation.length === 0) callback();
			else {
				dojo.forEach(storeAfterItemCreation, Lang.hitch(this, function(id, index, arr) {
					var data = {
						item_id:	item_id,
						link_id:	id,
						checked:	true
					}
					
					this.AJAXRequest("netnavigation", "updateLinkedItem", data,
						function() {
							requestsCompleted++;
							
							if(requestsCompleted === requestsTotal) callback();
						},
						function() {},
						true
					);
				}));
			}
		}*/
	});
});