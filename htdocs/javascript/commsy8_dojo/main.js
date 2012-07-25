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
			         	"dojo/on",
			         	"dojo/domReady!"], Lang.hitch(this, function(query, domAttr, On, ready) {
				
				// initiate popup handler
				require(["commsy/popups/ToggleRoomConfiguration"], function(RoomConfigurationPopup) {
					var aNode = query("a#tm_settings")[0];
					
					if(aNode) var handler = new RoomConfigurationPopup(aNode, query("div#tm_menus div#tm_dropmenu_configuration")[0]);
				});
				require(["commsy/popups/TogglePersonalConfiguration"], function(PersonalConfigurationPopup) {
					var handler = new PersonalConfigurationPopup(query("a#tm_user")[0], query("div#tm_menus div#tm_dropmenu_pers_bar")[0]);
				});
				require(["commsy/popups/ToggleBreadcrumb"], function(BreadcrumbPopup) {
					var handler = new BreadcrumbPopup(query("a#tm_bread_crumb")[0], query("div#tm_menus div#tm_dropmenu_breadcrumb")[0]);
				});
				require(["commsy/popups/ToggleClipboard"], function(ClipboardPopup) {
					var handler = new ClipboardPopup(query("a#tm_clipboard")[0], query("div#tm_menus div#tm_dropmenu_clipboard")[0]);
				});
				
				// widget popups
				var aStackNode = query("a#tm_stack")[0];
				if (aStackNode) {
					require(["commsy/popups/ToggleStack"], function(StackPopup) {
						var handler = new StackPopup(aStackNode, query("div#tm_menus div#tm_dropmenu_stack")[0]);
					});
				}
				
				/*
				require(["commsy/popups/ToggleWidgets"], function(WidgetsPopup) {
					var handler = newWidgetsPopup(query("a#tm_widgets")[0], query("div#tm_menus div#tm_dropmenu_widget_bar")[0]);
				});
				require(["commsy/popups/ToggleMyCalendar"], function(MyCalendarPopup) {
					var handler = newMyCalendarPopup(query("a#tm_mycalendar")[0], query("div#tm_menus div#tm_dropmenu_mycalendar")[0]);
				});
				*/
				
				// setup rubric forms
				query(".open_popup").forEach(Lang.hitch(this, function(node, index, arr) {
					// get custom data object
					var customObject = this.getAttrAsObject(node, "data-custom");
					
					var module = customObject.module;
					
					require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
						var handler = new ClickPopup(node, customObject);
					});
				}));
				
				// ajax actions
				require(["commsy/AjaxActions"], function(AjaxActions) {
					var aNodes = query("a.ajax_action");
					
					if (aNodes) {
						var handler = new AjaxActions();
						handler.setup(aNodes);
					}
				});
				
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
				
				// threaded discussion tree
				if (this.uri_object.mod == "discussion" && this.uri_object.fct == "detail") {
					var treeNode = query("div#discussion_tree")[0];
					
					if (treeNode) {
						require(["commsy/DiscussionTree"], function(DiscussionTree) {
							var handler = new DiscussionTree();
							handler.setupTree(treeNode);
						});
					}
				}
				
				// calendar scroll bar position and select auto submit - process directly here
				if (this.uri_object.fct == "index" && this.uri_object.mod == "date") {
					var scrollDivNode = query("div.cal_table_scroll")[0];
					
					if (scrollDivNode) {
						scrollDivNode.scrollTop = 250;
					}
					
					var selectNode = query("select.calendar_switch")[0];
					if (selectNode) {
						On(selectNode, "change", Lang.hitch(this, function(event) {
							location.reload();
						}));
					}
				}
				
				// search
				if(this.from_php.dev.indexed_search === true) {
					require(["commsy/Search"], function(Search) {
						var handler = new Search();
						handler.setup(query("input#search_input")[0]);
					});
				}
				
				// overlays
				query("a.new_item_2, a.new_item, a.attachment, span#detail_assessment, div.cal_days_events a, div.cal_days_week_events a").forEach(function(node, index, arr) {
					require(["commsy/Overlay"], function(Overlay) {
						var handler = Overlay();
						handler.setup(node);
					});
				});
			    
				// div expander
				if(this.uri_object.mod === "home") {
					var objects = [];
					query("div.content_item div[class^='list_wrap']").forEach(function(node, index, arr) {					
						objects.push({ div: node, actor:	query("a.open_close", node.parentNode)[0] });
					});
					
					require(["commsy/DivExpander"], function(DivExpander) {
						var handler = DivExpander();
						handler.setup(objects);
					});
				}
				
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
				if(this.uri_object.fct === "detail") {
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
				
				// on list context
				if(this.uri_object.fct === "index") {
					// list selection
					var inputNodes = query("input[type='checkbox'][name^='form_data[attach]']");
					var counterNode = query("div.ii_right span#selected_items")[0];
					
					require(["commsy/ListSelection"], function(ListSelection) {
						var handler = new ListSelection();
						handler.setup(inputNodes, counterNode);
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
				
				// automatic popup opener
				// should be loaded at the very last
				require(["commsy/popups/TogglePersonalConfiguration", "commsy/AutoOpenPopup"], function(Configuration, AutoOpenPopup) {
					var handler = new AutoOpenPopup();
					handler.setup();
				});
			}));
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});