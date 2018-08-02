define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"commsy/request",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/fx",
            "dijit/Dialog",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, lang, Query, On, request, DomConstruct, DomAttr, DomStyle, FX, Dialog) {
	return declare(BaseClass, {		
		cid: 						null,
		item_id: 					null,
		module: 					null,
		tpl_path: 					'',
		initialized: 				false,
		paging: {
			current: 0
		},
		restrictions: {
			search:					'',
			rubric:					'all',
			type:					2,
			only_linked:			false
		},
		store: {
			pages:					1,
			selected:				0,
			after_item_creation:	[]
		},
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		init: function(cid, item_id, module, tpl_path, autoInit) {
			this.cid = cid;
			this.item_id = item_id;
			this.module = module;
			this.tpl_path = tpl_path;
			
			// register handler
			if (!autoInit) {
				var triggerNode = Query("#popup_netnavigation_attach_new")[0];
				
				if (triggerNode) {
					On(triggerNode, "click", lang.hitch(this, function(event) {
						this.initDo();
					}));
				}
			} else {
				this.initDo();
			}
		},
		
		initDo: function() {
			// get inital data if this is the first call
			if(this.initialized === false) {
				
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	'netnavigation',
						action:	'getInitialData'
					},
					data: {
						module: this.module
					}
				}).then(
					lang.hitch(this, function(response) {
						// init rubric select box
						var selectNode = Query("select[name='netnavigation_rubric_restriction']")[0];
						
						dojo.forEach(response.data.rubrics, function(rubric, index, arr) {
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
						On(Query("input[name='netnavigation_submit_restrictions']")[0], "click", lang.hitch(this, function(event) {
							// reset paging
							this.paging.current = 0;
							
							this.performRequest();
							
							event.preventDefault();
						}));
						
						// perform first request
						this.performRequest();
						
						this.initialized = true;
					})
				);
			}
		},
		
		setupRestrictions: function() {
			var contentNode = Query("div.pop_item_content")[0];
			
			// type restriction
			var typeRestrictionNode = Query("select[name='netnavigation_type_restriction']")[0];
			if(typeRestrictionNode) {
				On(typeRestrictionNode, "change", lang.hitch(this, function(event) {
					this.restrictions.type = DomAttr.get(event.target, "value");
					event.preventDefault();
				}));
			}
			
			// rubric restriction
			On(Query("select[name='netnavigation_rubric_restriction']", contentNode)[0], "change", lang.hitch(this, function(event) {
				this.restrictions.rubric = DomAttr.get(event.target, "value");
				event.preventDefault();
			}));
			
			// search restriction
			On(Query("input[name='netnavigation_search_restriction']", contentNode)[0], "change", lang.hitch(this, function(event) {
				this.restrictions.search = DomAttr.get(event.target, "value");
				event.preventDefault();
			}));
			
			// linked restriction
			On(Query("input[name='netnavigation_linked_restriction']", contentNode)[0], "change", lang.hitch(this, function(event) {
				this.restrictions.only_linked = (DomAttr.get(event.target, "checked") === true) ? true : false;
				event.preventDefault();
			}));
		},
		
		setupPaging: function() {
			var navigationNode = Query(".pop_item_navigation")[0];
			
			// first
			On(Query("#first", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current = 0;
				event.preventDefault();
				this.performRequest();
			}));
			
			// previous
			On(Query("#prev", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current--;
				event.preventDefault();
				this.performRequest();
			}));
			
			// next
			On(Query("#next", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current++;
				event.preventDefault();
				this.performRequest();
			}));
			
			// last
			On(Query("#last", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current = this.store.pages - 1;
				event.preventDefault();
				this.performRequest();
			}));
		},
		
		performRequest: function() {
			// create data object for request
			var data = {
				item_id:		this.item_id,
				module:			this.module,
				current_page:	this.paging.current,
				restrictions:	this.restrictions
			};
			
			// send request
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'netnavigation',
					action:	'performRequest'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					var contentNode = Query("#popup_netnavigation #crt_row_area")[0];
					
					// fill list
					DomConstruct.empty(contentNode);
					
					dojo.forEach(response.data.list, function(entry, index, arr) {
						// if current module is of type user, deactivate selection for "All Members"(system label) entries
						var disabled = false;
						if(this.module === "user" && entry.system_label === true) {
							disabled = true;
						}
						
						// if current entry is "All Members" disable checkbox
						if(entry.system_label === true){
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
					this.store.selected = response.num_selected_total;
					//DomAttr.set(Query("span#pop_item_entries_selected")[0], "innerHTML", this.store.selected);
					
					// register checkbox events - unregistering is done when destroying
					dojo.forEach(Query("input[type='checkbox']", contentNode), lang.hitch(this, function(node, index, arr) {
						var rowNode = new dojo.NodeList(node).parents("div[class^='pop_row_']")[0];
						
						// safe row background color
						var oldBgColor = DomStyle.get(rowNode, "backgroundColor");
						
						On(node, "change", lang.hitch(this, function(event) {
							var checked = DomAttr.get(node, "checked");
							var linkedId = DomAttr.get(event.target, "id").substr(7);
							
							var data = {
								item_id:	this.item_id,
								link_id:	linkedId,
								checked:	checked
							};
							
							//DomAttr.set(Query("span#pop_item_entries_selected")[0], "innerHTML", this.store.selected);
							
							// set new row background color
							DomStyle.set(rowNode, "backgroundColor", "#D1D1D1");
							
							// perform request
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'netnavigation',
									action:	'updateLinkedItem'
								},
								data: data
							}).then(
								lang.hitch(this, function(response) {
									// animate back to old row color
									dojo.anim(rowNode, {
										"backgroundColor":		oldBgColor
									});

									if(checked === true) {
										// on check
										this.store.selected++;
										
										var text = response.data.linked_item.link_text;
										
										// add related entry to entry list
										var listEntryNode = DomConstruct.create("li", {
											id:				"item_" + linkedId
										}, Query("div#netnavigation_list ul")[0], "first");
										
											var divNode = DomConstruct.create("div", {
												className:		"netnavigation"
											}, listEntryNode, "last");
											
												var linkNode = DomConstruct.create("a", {
													target:		"_self",
													href:		"commsy.php?cid=" + this.cid + "&mod=" + response.data.linked_item.module + "&fct=detail&iid=" + response.data.linked_item.linked_iid,
													title:		response.data.linked_item.title
												}, divNode, "last");
												
													DomConstruct.create("img", {
														src:		this.tpl_path + "img/netnavigation/" + response.data.linked_item.img,
														title:		response.data.linked_item.title
													}, linkNode, "last");
												
												DomConstruct.create("a", {
													target:		"_self",
													href:		"commsy.php?cid=" + this.cid + "&mod=" + response.data.linked_item.module + "&fct=detail&iid=" + response.data.linked_item.linked_iid,
													title:		response.data.linked_item.title,
													innerHTML:	" " + text
												}, divNode, "last");
										
										if(Query("a#popup_path_tab")[0]) {
											// add related entry to path list
											var pathListEntryNode = DomConstruct.create("li", {
												className:		"netnavigation"
											}, Query("ul#popup_path_list")[0], "last");
											
												DomConstruct.create("input", {
													type:		"checkbox",
													id:			"path_" + response.data.linked_item.linked_iid,
													checked:	false
												}, pathListEntryNode, "last");
												
												DomConstruct.create("img", {
													src:		this.tpl_path + "img/netnavigation/" + response.data.linked_item.img
												}, pathListEntryNode, "last");
												
												DomConstruct.create("span", {
													innerHTML:	text
												}, pathListEntryNode, "last");
										}
									} else {
										if (!response.data.last_moderator) {
											// on uncheck
											this.store.selected--;
											
											// remove related entry from entry list
											var liNode = Query("div#netnavigation_list li#item_" + linkedId)[0];
											FX.wipeOut({
												node:	liNode,
												onEnd:	function() {
													DomConstruct.destroy(liNode);
												}
											}).play();
											
											if(Query("a#popup_path_tab")[0]) {
												// remove related entry from path list
												var liPathNode = Query("ul#popup_path_list input#path_" + linkedId)[0];
												DomConstruct.destroy(liPathNode.parentNode);
											}
										} else {
                                            var inputNode = Query("div#popup_netnavigation input#linked_" + linkedId)[0];
                                            $(inputNode).prop('checked', true);

                                            errorDialog = new Dialog({
                                                title: "Fehler",
                                                content: "Der letzte Moderator des Raums kann nicht gelöscht oder gesperrt werden.",
                                                style: "width: 300px"
                                            });

                                            errorDialog.show();
                                        }
										
									}
								})
							);
						}));
						
					}));
					
					// update current page and total number of pages
					DomAttr.set(Query("#pop_item_current_page")[0], "innerHTML", (response.data.list.length === 0) ? 0 : this.paging.current + 1);
					DomAttr.set(Query("#pop_item_pages")[0], "innerHTML", response.data.paging.pages);
					
					// store pages
					this.store.pages = response.data.paging.pages;
				})
			);
		},
		
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
				dojo.forEach(storeAfterItemCreation, lang.hitch(this, function(id, index, arr) {
					var data = {
						item_id:	item_id,
						link_id:	id,
						checked:	true
					};
					
					request.ajax({
						sync: true,
						query: {
							cid:	this.uri_object.cid,
							mod:	'ajax',
							fct:	'netnavigation',
							action:	'updateLinkedItem'
						},
						data: data
					}).then(
						lang.hitch(this, function(response) {
							requestsCompleted++;
							
							if(requestsCompleted === requestsTotal) callback();
						})
					);
				}));
			}
		}
	});
});