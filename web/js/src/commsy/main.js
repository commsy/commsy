noBacklink = false;
togglePopups = [];

require([	"dojo/_base/declare",
         	"commsy/base",
         	"dojo/_base/lang",
         	"dojo/_base/xhr"], function(declare, BaseClass, Lang, xhr) {
	var Controller = declare(BaseClass, {
		constructor: function(args) {
			
		},
		
		init: function() {
			require([	"dojo/query",
			         	"dojo/dom-attr",
			         	"dojo/on",
			         	"dojo/NodeList-traverse",
			         	"dojo/domReady!"], Lang.hitch(this, function(query, domAttr, On, ready) {
			    
			    var uri_object = this.uri_object;
				
				this.initCommsyBar();
				
				// register event for handling mouse actions outside content div
				On(document.body, "click", Lang.hitch(this, function(event) {
					if(domAttr.get(event.target, "id") === "popup_wrapper") {
						// TODO: create something like a tooltip here
						alert("Bitte schließen Sie zuerst das Popup-Fenster, bevor Sie sonstige Seitenoperationen ausführen");
					}
				}));

				if (this.from_php.c_media_integration) {
					// MDO on click
					On(query(".mdoLink"), "click", Lang.hitch(this, function(event) {
						// var cid = this.from_php.environment.portal_id;
						var cid = dojo.queryToObject(dojo.doc.location.search.substr((dojo.doc.location.search[0] === "?" ? 1 : 0))).cid;
						var link = domAttr.get(event.target, "href");
						var json_data;
						var regEx = /xplay\.datenbank-bildungsmedien.net\/(\d|\w)*\/(.*)\//;
						var match = link.match(regEx);
						var identifier = match[2];
						if (!identifier) {
							identifier = '';
						}
						
						xhr.post({
							// The URL to request
							url: 'commsy.php?cid=' + cid + '&mod=ajax&fct=mdo_perform_search&action=search&identifier='+identifier,
							// The method that handles the request's successful result
							// Handle the response any way you'd like!
							load: function(message) {
								var result = eval('(' + message + ')');
	            				if(result.status === 'success') {
									window.open(result.data.url);
								} else {
									window.open(link);
								}
							}
						});
						event.preventDefault();
					}));
				}
				
				// setup rubric forms
				query(".open_popup").forEach(Lang.hitch(this, function(node, index, arr) {
					// get custom data object
					var customObject = this.getAttrAsObject(node, "data-custom");
					
					var module = customObject.module;
					
					require(["commsy/popups/Click" + this.ucFirst(module) + "Popup"], function(ClickPopup) {
						var handler = new ClickPopup();
						handler.init(node, customObject);
					});
				}));
				
				// send
				var aSendNodes = query("a.popup_send");
				if (aSendNodes) {
					On(aSendNodes, "click", Lang.hitch(this, function(event) {
						var widgetManager = this.getWidgetManager();
						
						// get custom data object
						var customObject = this.getAttrAsObject(event.target, "data-custom");
						
						widgetManager.GetInstance("commsy/widgets/Send/SendWidget", customObject).then(function(deferred) {
							var widgetInstance = deferred.instance;
							
							// open widget
							widgetInstance.Open();
						});
					}));
				}
				
				// buzzwords and tags expander
				if (this.uri_object.fct === "index") {
					require(["commsy/DivToggle"], function(DivToggle) {
						var handler = new DivToggle();
						handler.setup();
					});
				}
				
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
						handler.setupTree(node, function() {
							// highlight path
							
							var key;
							var a = uri_object;
							
							var path_array = [];
							// 
							for (key in a) {
								// if key fits to seltag_
								if(/^seltag_[1-9]\d*$/.test(key)){
									if(a[key]){
										// get seltag id
										var item_id;
										item_id = /^seltag_([1-9]\d*)$/.exec(key);
										var path = handler.buildPath(item_id[1]);
										path_array.push(path);
										
									}
								} else if(/^seltag$/.test(key)){
									var path = handler.buildPath(a[key]);
									path_array.push(path);
								}
							}
							
							handler.tree.set("paths", path_array);
						}, true);
					});
				});
				
				// sub tree
				// tree
				query("div.subtree").forEach(function(node, index, arr) {
					require(["commsy/tree"], function(Tree) {
						var handler = new Tree();
						handler.setupTree(node, function() {
							// get every tag which is connected to the entry
							// highlight path
							if (uri_object.seltag) {
								var seltag = uri_object.seltag;
								var path = handler.buildPath(seltag);
								handler.tree.set("paths", [path]);
							}
						}, true);
					});
				});
				
				// gallery
				var galleryNode = query("div.gallery")[0];
				if ( galleryNode )
				{
					require(["commsy/Gallery"], function(Gallery)
					{
						var gallery = new Gallery();
						gallery.init(galleryNode, uri_object.cid, uri_object.iid, uri_object.module);
					});
				}
				//Projekktor
				if (this.uri_object.fct == "detail") {
					var videoNodes = query('video');

					if(videoNodes){
						require(["commsy/projekktor"], function(projekktor){
							projekktor.setupProjekktor();
						});
					}
				}

				
				// limesurvey
				var limeSurveyNode = query("div.limesurveyList")[0];
				if ( limeSurveyNode )
				{
					require(["commsy/widgets/LimeSurvey/LimeSurveyUserWidget"], function(LimeSurvey)
					{
						var limeSurvey = new LimeSurvey();
						limeSurvey.startup();
						limeSurvey.placeAt(limeSurveyNode);
					});
				}
				
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
					require(["commsy/DateCalendar"], function(DateCalendar) {
						var handler = new DateCalendar();
						handler.setup();
					});
				}
				
				// search
				if(this.from_php.dev.indexed_search === true) {
					var inputNode = query("input#search_input")[0];
					
					if (inputNode) {
						require(["commsy/Search"], function(Search) {
							var handler = new Search();
							handler.setup(inputNode);
						});
					}
				}
				
				// password expire soon alert to change password
				if(this.from_php.environment.password_expire_soon) {
					require(["dijit/Dialog","dojo/i18n!commsy/nls/tooltipErrors","dojo/cookie"], function(Overlay, ErrorTranslations,Cookie) {
						// create the dialog:
						var cookieName = 'expired_password_shown';
						if(!Cookie(cookieName)){
							var myDialog = new dijit.Dialog({
							    title: "Passwort läuft ab",
							    content: ErrorTranslations.password_expire,
							    style: "width: 300px"
							});
							myDialog.show();
							Cookie(cookieName,true);
						}
						
					});
				}
				
				// overlays
				query("a.new_item_2, a.new_item, a.attachment, span#detail_assessment, div.cal_days_events a, div.cal_days_week_events a, .tooltip_toggle").forEach(function(node, index, arr) {
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
				require(["commsy/lightbox"], function(lightbox) {
					lightbox.addImageGroup(query("a[class^='lightbox']"));
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
										"div.item_actions a.annotations," +
										"div.item_actions a.versions");
						
					require(["commsy/ActionExpander"], function(ActionExpander) {
						var handler = new ActionExpander();
						handler.setup(actors);
					});
					
					var printNode = query("a#printbutton")[0];
					if ( printNode )
					{
						On(printNode, "click", function(event) {
							require(["commsy/PrintDivToggle"], function(PrintDivToggle) {
								var printToggle = new PrintDivToggle();
								printToggle.setup(printNode);
								
							});	
							event.preventDefault();
							return false;
						});
					}
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
		},
		
		initCommsyBar: function() {
			require([	"dojo/query",
			         	"dojo/on",
			         	"dojo/NodeList-traverse",
			         	"dojo/domReady!"], Lang.hitch(this, function(Query, On, ready) {
			    
	         	/*
	         	 * not migrated yet
	         	 */
         		var aStackNode = Query("a#tm_stack")[0];
				var widgetManager = this.getWidgetManager();
				if (aStackNode) {
					require(["commsy/popups/ToggleStack"], function(StackPopup) {
						new StackPopup(aStackNode, Query("div#tm_menus div#tm_dropmenu_stack")[0], widgetManager);
					});
				}
				
         		dojo.forEach(
	    		[
					{
						node:		Query("a#tm_settings")[0],
						widget:		"commsy/popups/ToggleRoomConfiguration",
						actor:		Query("div#tm_menus div#tm_dropmenu_configuration")[0]
					},
					{
						node:		Query("a#tm_user")[0],
						widget:		"commsy/popups/TogglePersonalConfiguration",
						actor:		Query("div#tm_menus div#tm_dropmenu_pers_bar")[0]
					},
					{
						node:		Query("a#tm_bread_crumb")[0],
						widget:		"commsy/popups/ToggleBreadcrumb",
						actor:		Query("div#tm_menus div#tm_dropmenu_breadcrumb")[0]
					},
					{
						node:		Query("a#tm_connection")[0],
						widget:		"commsy/popups/ToggleConnection",
						actor:		Query("div#tm_menus div#tm_dropmenu_connection")[0]
					},
					{
						node:		Query("a#tm_clipboard")[0],
						widget:		"commsy/popups/ToggleClipboard",
						actor:		Query("div#tm_menus div#tm_dropmenu_clipboard")[0]
					}
			    ], Lang.hitch(this, function(object) {
			    	if (object.node && object.actor) {
			    		On.once(object.node, "click", Lang.hitch(this, function(event) {
			    			require([object.widget], function(Widget) {
				    			var handler = new Widget(object.node, object.actor);
				    			handler.open();
			    			});
	    				}));
			    	}
			    }));
			    
			    /*
			     * Widgets managed by WidgetManager
			     */
			    
			    dojo.forEach(
	    		[
					{
						node:		Query("a#tm_mycalendar")[0],
						widget:		"commsy/widgets/Calendar/CalendarWidget",
						mixin:		{}
					},
					{
						node:		Query("a#tm_portfolio")[0],
						widget:		"commsy/widgets/Portfolio/PortfolioWidget",
						mixin:		{}
					},
					{
						node:		Query("a#tm_widgets")[0],
						widget:		"commsy/widgets/MyWidgets/MyWidgetsWidget",
						mixin:		{}
					},
					{
						node:		Query("a#tm_limesurvey")[0],
						widget:		"commsy/widgets/LimeSurvey/LimeSurveyWidget",
						mixin:		{}
					}
			    ], Lang.hitch(this, function(object) {
			    	if (object.node) {
			    		On.once(object.node, "click", Lang.hitch(this, function(event) {
	    					var widgetManager = this.getWidgetManager();
	    					
	    					widgetManager.GetInstance(object.widget, object.mixin).then(function(deferred)
							{
								var widgetInstance = deferred.instance;
								
								// register click event
								widgetManager.RegisterOpenCloseClick(widgetInstance, object.node);
								
								// open widget
								widgetInstance.Open();
							});
	    				}));
			    	}
			    }));
			}));
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});
