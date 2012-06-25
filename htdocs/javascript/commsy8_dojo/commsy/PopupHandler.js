define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, BaseClass, on, lang, query, dom_class, dom_attr, domConstruct, domStyle) {
	return declare(BaseClass, {
		is_open:				false,
		contentNode:			null,
		features:				[],
		featureHandles:			[],
		module:					null,
		fct:					null,
		
		constructor: function(args) {
			
		},
		
		setupTabs: function() {
			var link_nodes = query("div.tab_navigation a", this.popup_content_node);
			var content_nodes = query("div#popup_tabcontent div[class^='tab']");
			
			// register click event for all tabs
			on(link_nodes, "click", lang.hitch(this, function(event) {
				// set all tabs inactive
				link_nodes.forEach(function(node) {
					dom_class.add(node, "pop_tab");
				});
				
				// set clicked active
				dom_class.replace(event.target, "pop_tab_active", "pop_tab");
				
				/* switch content */
				// set classes for divs
				content_nodes.forEach(function(node) {
					if(dom_attr.get(event.target, "href") === dom_attr.get(node, "id")) {
						dom_class.remove(node, "hidden");
					} else {
						dom_class.add(node, "hidden");
					}
				});
				
				event.preventDefault();
			}));
		},
		
		setupFeatures: function() {
			this.features.forEach(lang.hitch(this, function(feature, index, arr) {
				if(feature === "editor") {
					query("div.ckeditor", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
						require(["commsy/ckeditor"], lang.hitch(this, function(CKEditor) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new CKEditor();
							this.featureHandles[feature][index].create(node);
						}));
					}));
				}
				
				if(feature === "tree") {
					query("div.tree", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
						require(["commsy/tree"], lang.hitch(this, function(Tree) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Tree({
								followUrl:		false,
								checkboxes:		true,
								expanded:		true,
								item_id:		this.item_id
							});
							this.featureHandles[feature][index].setupTree(node);
						}));
					}));
				}
				
				if(feature === "upload") {
					query("div.uploader", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
						require(["commsy/Uploader"], lang.hitch(this, function(Uploader) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Uploader();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}
				
				if(feature === "upload-single") {
					query("div.uploader-single", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
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
						this.featureHandles[feature][0].init(this.uri_object.cid, this.item_id, this.module, this.from_php.template.tpl_path);
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
					query("input.datepicker", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
						require(["commsy/Calendar"], lang.hitch(this, function(Calendar) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Calendar();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}
				
				if(feature === "colorpicker") {
					query("input.colorpicker", this.contentNode).forEach(lang.hitch(this, function(node, index, arr) {
						require(["commsy/Colorpicker"], lang.hitch(this, function(Colorpicker) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new Colorpicker();
							this.featureHandles[feature][index].setup(node);
						}));
					}));
				}
			}));
		},
		
		setupLoading: function() {
			// TODO: add invisible screen layer, to prevent closing, before fully loaded
			var loadingScreenDiv = domConstruct.create("div", {
				"id":		"loadingScreen"
			}, document.body, "first")
			
				var loadingScreenInner = domConstruct.create("div", {
					"id":		"loadingScreenInner"
				}, loadingScreenDiv, "last");
				
					domConstruct.create("h2", {
						innerHTML:		"Loading..."
					}, loadingScreenInner, "last");
					
					domConstruct.create("img", {
						src:		this.from_php.template.tpl_path + "img/ajax_loader_big.gif"
					}, loadingScreenInner, "last");
		},
		
		destroyLoading: function() {
			var loadingScreenDiv = query("#loadingScreen")[0];
			
			dojo.fadeOut({
				node:		loadingScreenDiv,
				duration:	1000,
				onEnd:		function() {
					domConstruct.destroy(loadingScreenDiv);
				}
			}).play();
		},
		
		submit: function(search, additional) {
			additional = additional || [];
			
			var form_data = [];
			if(this.fct == "rubric_popup") {
				form_data = [{
					name:	'iid',
					value:	this.item_id
				}];
			}
			
			var data = {
				form_data:	form_data,
				module:		this.module,
				additional:	additional
			}
			
			// collect form data from given search params
			var nodeLists = search.nodeLists;
			
			// add tabs to node lists
			search.tabs.forEach(function(tabObject, index, arr) {
				tabObject.query = query("div#" + tabObject.id);
				delete tabObject.id;
				
				nodeLists = nodeLists.concat(tabObject);
			});
			
			// process node lists
			nodeLists.forEach(function(nodeList, index, arr) {
				var group = nodeList.group || null;
				var nodes = nodeList.query;
				
				nodes.forEach(function(node, index, arr) {
					var formNodes = null;
					
					if(node.tagName === "INPUT" || node.tagName === "SELECT") {
						formNodes = [ node ];
					} else {
						formNodes = query("input[name^='form_data'], select[name^='form_data']", node);
					}
					
					formNodes.forEach(function(formNode, index, arr) {
						var add = false;
						
						var type = dom_attr.get(formNode, "type");
						
						// if form field is a checkbox, only add if checked
						// if form field is a radio button, only add the selected one
						if(type === "checkbox" || type === "radio") {
							if(	formNode.checked === true ||
								dom_attr.get(formNode, "aria-checked") === "true" ||
								dom_attr.get(formNode, "aria-checked") === "mixed") add = true;
						}
						
						// otherwise add
						else {
							add = true;
						}
						
						if(add) {
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
								
								data.form_data[group_index].value.push(dom_attr.get(formNode, "value"));
							} else {
								// extract name
								/form_data\[(.*)\]/.exec(dom_attr.get(formNode, "name"));
								
								data.form_data.push({
									name:	RegExp.$1,
									value:	dom_attr.get(formNode, "value")
								});
							}
						}
					});
				});
			});
			
			// send data
			this.AJAXRequest(this.fct, "save", data,
				lang.hitch(this, function(item_id) {
					this.onPopupSubmitSuccess(item_id);
				}),
				
				lang.hitch(this, function(response) {
					if(response.status === "error" && response.code === 101) {
						var missingFields = response.detail;
						
						// create a red border around the missing fields and scroll to first one
						missingFields.forEach(lang.hitch(this, function(field, index, arr) {
							var fieldNode = query("[name='form_data[" + field + "]']", this.contentNode)[0];
							
							domStyle.set(fieldNode, "border", "1px solid red");
							
							if(index === 0) {
								this.scrollToNodeAnimated(fieldNode);
							}
						}));
					} else {
						console.error("an unhandled error response occurred");
					}
				})
			);
		},
		
		close: function() {
			// destroy uploader
			if(this.featureHandles["upload"]) {
				this.featureHandles["upload"].forEach(function(uploader, index, arr) {
					uploader.destroy();
				});
			}
			if(this.featureHandles["upload-single"]) {
				this.featureHandles["upload-single"].forEach(function(uploader, index, arr) {
					uploader.destroy();
				});
			}
			
			// set closed
			this.is_open = false;
		},
	});
});