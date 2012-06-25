require([	"dojo/_base/declare",
         	"commsy/base",
         	"dojo/_base/lang"], function(declare, BaseClass, Lang) {
	var Controller = declare(BaseClass, {
		constructor: function(args) {
			
		},
		
		init: function() {
			require([	"dojo/query",
			         	"dojo/dom-attr",
			         	"dojo/NodeList-traverse",
			         	"dojo/domReady!"], Lang.hitch(this, function(query, domAttr, ready) {
				
				// initiate popup handler
				require(["commsy/popups/ToggleRoomConfiguration"], function(RoomConfigurationPopup) {
					var handler = new RoomConfigurationPopup(query("a#tm_settings")[0], query("div#tm_menus div#tm_dropmenu_configuration")[0]);
				});
				require(["commsy/popups/TogglePersonalConfiguration"], function(PersonalConfigurationPopup) {
					var handler = new PersonalConfigurationPopup(query("a#tm_user")[0], query("div#tm_menus div#tm_dropmenu_pers_bar")[0]);
				});
				require(["commsy/popups/ToggleBreadcrumb"], function(BreadcrumbPopup) {
					var handler = new BreadcrumbPopup(query("a#tm_bread_crumb")[0], query("div#tm_menus div#tm_dropmenu_breadcrumb")[0]);
				});
				
				// setup rubric forms
				query("a.open_popup").forEach(Lang.hitch(this, function(node, index, arr) {
					// get custom data object
					var customObject = this.getAttrAsObject(node, "data-custom");
					
					var module = customObject.module;
					
					require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
						var handler = new ClickPopup(node, customObject);
					});
				}));
				
				// ckeditor
				query("div.ckeditor").forEach(function(node, index, arr) {
					require(["commsy/ckeditor"], function(CKEditor) {
						var handler = new CKEditor();
						handler.create(node);
					});
				});
				
				// tree
				query("div.tree").forEach(function(node, index, arr) {
					require(["commsy/tree"], function(Tree) {
						var handler = new Tree();
						handler.setupTree(node);
					});
				});
				
				// search
				require(["commsy/Search"], function(Search) {
					var handler = new Search(query("input#search_input")[0]);
				});
				
				// overlays
				query("a.new_item_2, a.new_item, a.attachment, span#detail_assessment").forEach(function(node, index, arr) {
					require(["commsy/Overlay"], function(Overlay) {
						var handler = Overlay();
						handler.setup(node);
					});
				});
				
				// div expander
				var objects = [];
				query("div.content_item div[class^='list_wrap']").forEach(function(node, index, arr) {					
					objects.push({ div:node, actor:	query("a.open_close", node.parentNode)[0] });
				});
				
				require(["commsy/DivExpander"], function(DivExpander) {
					var handler = DivExpander();
					handler.setup(objects);
				});
				
				// lightbox
				require(["commsy/Lightbox"], function(Lightbox) {
					var handler = Lightbox();
					handler.setup(query("a[class^='lightbox']"));
				});
				
				// progressbar
				query("div.progressbar").forEach(function(node, index, arr) {
					require(["commsy/ProgressBar"], function(ProgressBar) {
						var handler = ProgressBar();
						handler.setup(node);
					});
				});
				
				// on detail context
				if(this.uri_object.fct === 'detail') {
					// action expander
					var actors = query(	"div.item_actions a.edit," +
										"div.item_actions a.detail," +
										"div.item_actions a.workflow," +
										"div.item_actions a.linked," + 
										"div.item_actions a.annotations");
						
					require(["commsy/ActionExpander"], function(ActionExpander) {
						var handler = new ActionExpander();
						handler.setup(actors);
					});
				}
				
				// uploader
				query("div.uploader").forEach(function(node, index, arr) {
					require(["commsy/Uploader"], function(Uploader) {
						var handler = new Uploader();
						handler.setup(node);
					});
				});
				
				// follow anchors
				if(window.location.href.indexOf("#") !== -1) {
					require(["commsy/AnchorFollower"], function(AnchorFollower) {
						var handler = new AnchorFollower();
						
						var anchor = window.location.href.substring(window.location.href.indexOf("#") + 1);
						handler.follow(anchor);
					});
				}
				
				// assessment
				require(["commsy/Assessment"], function(Assessment) {
					var handler = new Assessment();
					handler.setup(query("span#detail_assessment")[0]);
				});
				
				// colorpicker
				query("div.colorpicker").forEach(function(node, index, arr) {
					require(["commsy/Colorpicker"], function(Colorpicker) {
						var handler = new Colorpicker();
						handler.setup(node);
					});
				});
			}));
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});