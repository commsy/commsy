define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style",
        	"dojo/has",
        	"dojo/_base/sniff",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, on, lang, query, dom_class, dom_attr, domConstruct, domStyle, Has) {
	return declare(BaseClass, {
		is_open:				false,
		contentNode:			null,
		features:				[],
		featureHandles:			[],
		module:					null,
		fct:					null,
		editType:				null,
		version_id:				null,
		contextId:				null,

		constructor: function(args) {

		},

		setupTabs: function() {
			var link_nodes = query("div.tab_navigation a", this.contentNode);
			var content_nodes = query("div#popup_tabcontent div.tab, div.popup_tabcontent div.tab", this.contentNode);
			
			// register click event for all tabs
			on(link_nodes, "click", lang.hitch(this, function(event) {
				// set all tabs inactive
				dojo.forEach(link_nodes, function(node) {
					dom_class.add(node, "pop_tab");
				});

				// set clicked active
				dom_class.replace(event.target, "pop_tab_active", "pop_tab");

				/* switch content */
				// set classes for divs
				dojo.forEach(content_nodes, function(node) {
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
			dojo.forEach(this.features, lang.hitch(this, function(feature, index, arr) {
				if(feature === "editor") {
					dojo.forEach(query("div.ckeditor", this.contentNode), lang.hitch(this, function(node, index, arr) {
						require(["commsy/ckeditor"], lang.hitch(this, function(CKEditor) {
							this.featureHandles[feature] = this.featureHandles[feature] || [];
							this.featureHandles[feature][index] = new CKEditor();
							this.featureHandles[feature][index].create(node);
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
								expanded:		(Has("ie") <= 8) ? false : true,
								item_id:		this.item_id,
								room_id:		this.contextId
							});
							this.featureHandles[feature][index].setupTree(node);
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
			}

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
					this.destroyLoading();
				}),

				lang.hitch(this, function(response) {
					if(response.status === "error" && response.code === 101) {
						var missingFields = response.detail;

						// show missing mandatory text
						var missingDivNode = query("div#mandatory_missing", this.contentNode)[0];
						dom_class.remove(missingDivNode, "hidden");
						this.scrollToNodeAnimated(missingDivNode);

						/*
						// create a red border around the missing fields and scroll to first one
						dojo.forEach(missingFields, lang.hitch(this, function(field, index, arr) {
							var fieldNode = query("[name='form_data[" + field + "]']", this.contentNode)[0];

							var nodeType = dom_attr.get(fieldNode, "type");
							if(nodeType === "hidden") {
								fieldNode = new dojo.NodeList(fieldNode).prev()[0];
							}

							domStyle.set(fieldNode, "border", "2px solid red !important");

							if(index === 0) {
								this.scrollToNodeAnimated(fieldNode);
							}
						}));
						*/
					} else {
						console.error("an unhandled error response occurred");
					}
					this.destroyLoading();
				})
			);
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

			// set closed
			this.is_open = false;
		},
	});
});