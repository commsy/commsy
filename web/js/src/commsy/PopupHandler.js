define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/query",
        	"commsy/request",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style",
        	"dijit/Tooltip",
        	"dojo/i18n!./nls/tooltipErrors",
        	"dojo/parser",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, On, lang, query, request, dom_class, dom_attr, domConstruct, domStyle, Tooltip, ErrorTranslations, parser) {
	return declare(BaseClass, {
		constructor: function(args) {
			this.errorNodes			= [];
			
			this.featureHandles		= [];
			this.is_open			= false;
			this.contentNode		= null;
			this.features			= [];
			this.module				= null;
			this.fct				= null;
			this.editType			= null;
			this.version_id			= null;
			this.contextId			= null;
			this.currentNode		= null;
		},
		
		onCreate: function() {
			On(this.contentNode, "click", lang.hitch(this, function(event) {
				this.closeErrorTooltips();
			}));
			//exec jsMath inside a popup
			var nodes = dojo.query('div[class^="has_math_"]');
			if(typeof(jsMath) != 'undefined'){
				if(jsMath){
					if(nodes){
						dojo.forEach(nodes, function(nodes) {
							jsMath.ProcessBeforeShowing(nodes.className);
						});
					}
				}
			}
			
		},

		setupTabs: function() {
			var link_nodes = query("div.tab_navigation a", this.contentNode);
			var content_nodes = query("div#popup_tabcontent div.tab, div.popup_tabcontent div.tab", this.contentNode);

			// register click event for all tabs
			On(link_nodes, "click", lang.hitch(this, function(event) {
				// set all tabs inactive
				dojo.forEach(link_nodes, function(node) {
					dom_class.add(node, "pop_tab");
				});
				
				// set clicked active
				if(dom_attr.get(event.target, "id") == "count_new_accounts") {
					dom_class.replace(query(event.target).parent()[0], "pop_tab_active", "pop_tab");
				} else {
					dom_class.replace(event.target, "pop_tab_active", "pop_tab");
				}
				

				/* switch content */
				// set classes for divs
				dojo.forEach(content_nodes, lang.hitch(this, function(node, index, arr) {
					var tabName = dom_attr.get(event.target, "href");
					if(tabName === dom_attr.get(node, "id")) {
						
						// CONNECTION TO OTHER PORTALS
						if ( this.module === 'connection' ) {
							this.loadContent(tabName,dom_attr.get(node, "class"));
						}
						
						// show node (= tab)
						dom_class.remove(node, "hidden");

						var hiddenNode = query("input[name='form_data[" + tabName + "]']", this.contentNode)[0];
						if (!hiddenNode) {
							// add a hidden input to mark this tab content as opened
							domConstruct.create("input", {
								type:		"hidden",
								className:	"tabStatus",
								name:		"form_data[" + tabName  + "]",
								value:		true
							}, this.contentNode, "last");
						}
					} else {
						dom_class.add(node, "hidden");
					}
				}));

				event.preventDefault();
			}));
		},

		setupFeatures: function() {
			dojo.forEach(this.features, lang.hitch(this, function(feature, index, arr) {
				if(feature === "editor") {
					dojo.forEach(query("div.ckeditor", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/ckeditor"], lang.hitch(this, function(CKEditor) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new CKEditor();
							this.featureHandles[feature][index].create(node);

							// listen for ckeditor changes and emit a change event to bubble them to the container div
							// this is needed for the locking mechanism to detect user activities
							this.featureHandles[feature][index].instance.on('change', lang.hitch(this, function() {
								On.emit(node, "change", {
									bubbles: true,
									cancelable: true
								});
							}));
						}));
					}));
				}

				if(feature === "tree") {
					dojo.forEach(query("div.tree", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/tree"], lang.hitch(this, function(Tree) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Tree({
								followUrl:		false,
								checkboxes:		true,
								expanded:		false,
								item_id:		this.item_id,
								room_id:		this.contextId
							});
							this.featureHandles[feature][index].setupTree(node, lang.hitch(this, function(tree) {
								tree.model.fetchItemsWithChecked( { match: true}, lang.hitch(this, function(checkedNodes) {
									
									dojo.forEach(checkedNodes, lang.hitch(this, function(item, index, arr) {
										
										var itemId = tree.model.getItemAttr(item, "item_id");
										var createdNode = query("input[name='form_data[tags]'][value='" + itemId + "']", this.contentNode)[0];
										
										if (!createdNode) {
											domConstruct.create("input", {
												type:		"hidden",
												name:		"form_data[tags]",
												value:		itemId
											}, query("div#tags_tab")[0], "last");
										}
									}));
								}));
								
								On(tree.tree, "open", lang.hitch(this, function(item, node) {
									dojo.forEach(item.children, lang.hitch(this, function(children, index, arr) {
										var isChecked = tree.model.getItemAttr(children, "match");
										if (isChecked) {
											var itemId = tree.model.getItemAttr(children, "item_id");
											
											var hiddenNode = query("input[type='hidden'][value='" + itemId + "']", query("div#tags_tab")[0])[0];
											if (hiddenNode) {
												domConstruct.destroy(hiddenNode);
											}
										}
									}));
								}));
							}), (this.editType == "tags"));
						}));
					}));
				}

				if(feature === "upload") {
					dojo.forEach(query("div.uploader", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/Uploader"], lang.hitch(this, function(Uploader) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Uploader();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}

				if(feature === "upload-single") {
					dojo.forEach(query("div.uploader-single", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/Uploader"], lang.hitch(this, function(Uploader) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Uploader({single: true});
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}

				if(feature === "netnavigation") {
					require(["commsy/Netnavigation"], lang.hitch(this, function(Netnavigation) {
						this.featureHandles[feature] = this.featureHandles[feature] || [];
						this.featureHandles[feature][0] = new Netnavigation();
						this.featureHandles[feature][0].init(this.uri_object.cid, this.item_id, this.module, this.from_php.template.tpl_path, (this.editType == "netnavigation"));
					}));
				}

				if(feature === "path") {
					require(["commsy/Path"], lang.hitch(this, function(Path) {
						this.featureHandles[feature] = this.featureHandles[feature] || [];
						this.featureHandles[feature][0] = new Path();
						this.featureHandles[feature][0].init(this.uri_object.cid, this.item_id, this.from_php.template.tpl_path);
					}));
				}

				if(feature === "calendar") {
					dojo.forEach(query("input.datepicker", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/Calendar"], lang.hitch(this, function(Calendar) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Calendar();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}

				if(feature === "colorpicker") {
					dojo.forEach(query("input.colorpicker", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/Colorpicker"], lang.hitch(this, function(Colorpicker) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Colorpicker();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}
			}));
		},

		submit: function(search, additional) {
			additional = additional || [];

			this.setupLoading();
			this.closeErrorTooltips();

			var form_data = [];
			if(this.fct == "rubric_popup") {
				form_data = [{
					name:	'iid',
					value:	this.item_id
				},{
					name:	'editType',
					value:	this.editType
				}];
			}

			var data = {
				form_data:	form_data,
				module:		this.module,
				additional:	additional
			};

			// collect form data from given search params
			var nodeLists = search.nodeLists;

			// add tabs to node lists
			dojo.forEach(search.tabs, function(tabObject, index, arr) {
				tabObject.query = query("div#" + tabObject.id);
				delete tabObject.id;

				nodeLists = nodeLists.concat(tabObject);
			});

			// process node lists
			dojo.forEach(nodeLists, function(nodeList, index, arr) {
				var group = nodeList.group || null;
				var nodes = nodeList.query;

				dojo.forEach(nodes, function(node, index, arr) {
					var formNodes = null;

					if(node.tagName === "INPUT" || node.tagName === "SELECT" || node.tagName === "TEXTAREA") {
						formNodes = [ node ];
					} else {
						formNodes = query("input[name^='form_data'], select[name^='form_data'], textarea[name^='form_data']", node);
					}

					dojo.forEach(formNodes, function(formNode, index, arr) {
						var add = false;

						var type = dom_attr.get(formNode, "type");

						// if form field is a checkbox, only add if checked
						// if form field is a radio button, only add the selected one
						if(type === "checkbox" || type === "radio") {
							if(	(formNode.checked === true && dom_attr.get(formNode, "aria-checked") !== "false") ||
								dom_attr.get(formNode, "aria-checked") === "true" ||
								dom_attr.get(formNode, "aria-checked") === "mixed") {

								add = true;
							}
						}

						// otherwise add
						else {
							add = true;
						}

						if(add) {
							var value = dom_attr.get(formNode, "value");
							if (value === "") value = ""; /* ie8 bogus value fix */
							
							if(group) {
								// create group entry if not defined and get index of it
								var group_index = null;
								if(!dojo.some(data.form_data, function(item, index) {
									group_index = index;
									return item.name === group;
								})) {
									data.form_data.push({
										name:	group,
										value:	[]
									});
									group_index = data.form_data.length - 1;
								}

								data.form_data[group_index].value.push(value);
							} else {
								// extract name
								/form_data\[(.*)\]/.exec(dom_attr.get(formNode, "name"));

								data.form_data.push({
									name:	RegExp.$1,
									value:	value
								});
							}
						}
					});
				});
			});

			// send data
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	this.fct,
					action:	'save'
				},
				data: data
			}).then(lang.hitch(this, function(response) {
				if (response.status === "success") {
					this.onPopupSubmitSuccess(response.data);

					if (typeof this.uri_object.commsy_bar_backlink != "undefined") {
						var backlink = decodeURIComponent(this.uri_object.commsy_bar_backlink);
						window.location = backlink;
					} else {
						this.destroyLoading();
					}
				} else {
					/************************************************************************************
					 * We recieved a failure, maybe a mandatory field is missing or the user entered
					 * a wrong format. This can also be caused by any special controller checks. See
					 * AJAX error return codes.
					 * 
					 * Special error handling is directed to the corresponding popup handler.
					************************************************************************************/
					
					if(response.status === "error" && response.code === "101") {
						// show missing mandatory text
						var missingDivNode = query("div#mandatory_missing", this.contentNode)[0];
						dom_class.remove(missingDivNode, "hidden");
						this.scrollToNodeAnimated(missingDivNode);
						
					} else if(response.status === "error" && response.code === "111") {
						this.onPopupSubmitError(response);
					} else if(response.status === "error" && response.code === "115"){
						this.onPopupSubmitError(response);
					}
					
					// call the popups error handling or in case it is not implemented, the default handling defined in this class
					this.onPopupSubmitError(response);
					// destroy loading after showing error
					this.destroyLoading();
				}
			}));
		},
		
		/**
		 * Abstract implementation for submit errors - can be overwritten
		 */
		onPopupSubmitError: function(response) {
			// remove loading screen
			this.destroyLoading();
			
			switch (response.code) {
				case "113":				/* 	tags are mandatory and not given */
					var errorNode = query("a[href='tags_tab']", this.contentNode)[0];
					dijit.Tooltip.defaultPosition = ["above"];
					Tooltip.show(ErrorTranslations.generalTags113, errorNode);
					dijit.Tooltip.defaultPosition = ["left", "right"];
					this.errorNodes.push(errorNode);
					
					break;
				
				case "114":				/* 	buzzwords are mandatory and not given */
					var errorNode = query("a[href='buzzwords_tab']", this.contentNode)[0];
					dijit.Tooltip.defaultPosition = ["above"];
					Tooltip.show(ErrorTranslations.generalBuzzwords114, errorNode);
					dijit.Tooltip.defaultPosition = ["left", "right"];
					this.errorNodes.push(errorNode);
					
					break;
			}
		},
		
		closeErrorTooltips: function() {
			dojo.forEach(this.errorNodes, lang.hitch(function(node, index, arr) {
				Tooltip.hide(node);
			}));
			
			this.errorNodes = [];
		},

		close: function() {
			// destroy uploader
			if(this.featureHandles["upload"]) {
				dojo.forEach(this.featureHandles["upload"], function(uploader, index, arr) {
					uploader.destroy();
				});
			}
			if(this.featureHandles["upload-single"]) {
				dojo.forEach(this.featureHandles["upload-single"], function(uploader, index, arr) {
					uploader.destroy();
				});
			}
			
			this.closeErrorTooltips();

			// set closed
			this.is_open = false;

			if (!noBacklink) {
				this.backlink();
			}
		}
	});
});