define([	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dijit/MenuItem",
        	"dijit/CheckedMenuItem",
        	"dijit/form/ComboButton",
        	"dijit/DropDownMenu",
        	"dijit/MenuSeparator",
        	"dijit/PopupMenuItem",
        	"dojo/i18n!./nls/calendar"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On, MenuItem, CheckedMenuItem, ComboButton, DropDownMenu, MenuSeparator, PopupMenuItem, CalendarTranslations) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidgetBorderless",
		widgetHandler:		null,
		
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			this.itemId = this.from_php.ownRoom.id;
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.createMenu();
		},
		
		afterParse: function() {
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		createMenu: function() {
			var menu = new DropDownMenu();
			
			menu.addChild(new CheckedMenuItem({
				label:			CalendarTranslations.configToDo,
				onClick:		this.onClickConfigToDo
			}));
			
			menu.addChild(new CheckedMenuItem({
				label:			CalendarTranslations.configRestrictions,
				onClick:		this.onClickConfigRestrictions
			}));
			
			menu.addChild(new MenuSeparator());
			
			/* Date Menu */
			var dateMenu = new DropDownMenu();
			
			this.createRoomMenu(dateMenu);
			
			menu.addChild(new PopupMenuItem({
				label:			CalendarTranslations.configDate,
				popup:			dateMenu
			}));
			
			/* ToDo Menu */
			var todoMenu = new DropDownMenu();
			
			this.createRoomMenu(todoMenu);
			
			menu.addChild(new PopupMenuItem({
				label:			CalendarTranslations.configToDo,
				popup:			todoMenu
			}));
			
			var button = new ComboButton({
				label:			CalendarTranslations.configHeadline,
				dropDown:		menu
			});
			button.placeAt(this.widgetBodyNode);
		},
		
		createRoomMenu: function(topMenu) {
			topMenu.addChild(new MenuItem({
				label:			CalendarTranslations.configFromAll
			}));
			
			topMenu.addChild(new MenuSeparator());
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onClickConfigToDo: function(event) {
			console.log("event");
			event.preventDefault();
		},
		
		onClickConfigRestrictions: function(event) {
			console.log("event");
			event.preventDefault();
		},
		
		onExecuteMenu: function(event) {
			
		}
	});
});