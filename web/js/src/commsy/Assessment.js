define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/dom-attr",
        	"dojo/query",
        	"commsy/request",
        	"dojo/on",
        	"dojo/dom-class",
        	"dojo/dom-style",
        	"dojo/_base/lang",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, DomAttr, Query, request, On, DomClass, DomStyle, lang) {
	return declare(BaseClass, {
		display:	false,
		anim:		null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			// setup vote function
			if(node) {
				if(DomClass.contains(node, "rateable")) {
					var starImageNodes = Query("img", node);
					
					var oldStatus = [];
					dojo.forEach(starImageNodes, lang.hitch(this, function(starImageNode, index, arr) {
						// store old status
						oldStatus[index] = DomAttr.get(starImageNode, "src");
						
						// register mouseover
						On(starImageNode, "mouseover", lang.hitch(this, function(event) {
							// set all stars up to the hovered one to full stars
							DomAttr.set(starImageNode, "src", this.from_php.template.tpl_path + "img/star_selected.gif");
							dojo.forEach(new dojo.NodeList(starImageNode).prevAll(), lang.hitch(this, function(node, index, arr) {
								DomAttr.set(node, "src", this.from_php.template.tpl_path + "img/star_selected.gif");
							}));
						}));
						
						// register click
						On(starImageNode, "click", lang.hitch(this, function(event) {
							// perform ajax call to register vote
							var data = {
								item_id:	this.uri_object.iid,
								vote:		index + 1
							};
							
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'assessment',
									action:	'vote'
								},
								data: data
							}).then(
								lang.hitch(this, function(response) {
									// TODO: implement without reload
									location.reload();
								})
							);
						}));
						
						// register mouseout
						On(starImageNode, "mouseout", lang.hitch(this, function(event) {
							// set all stars to there previous state
							dojo.forEach(new dojo.NodeList(node).children(), function(node, index, arr) {
								DomAttr.set(node, "src", oldStatus[index]);
							});
						}));
					}));
				}
				
				// register delete function
				var deleteNode = Query("a#assessment_delete_own")[0];
				if(deleteNode) {
					On(deleteNode, "click", lang.hitch(this, function(event) {
						var data = {
							item_id:	this.uri_object.iid
						};
						
						// perform request
						request.ajax({
							query: {
								cid:	this.uri_object.cid,
								mod:	'ajax',
								fct:	'assessment',
								action:	'deleteOwn'
							},
							data: data
						}).then(
							lang.hitch(this, function(response) {
								// TODO: implement without reload
								location.reload();
							})
						);
					}));
				}
			}
		}
	});
});