define("commsy/bar/CalendarConfig", [	"dojo/_base/declare",
        	"dijit/_WidgetBase",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/query",
        	"dojo/on",
        	"dojo/topic",
        	"dijit/MenuItem",
        	"dijit/CheckedMenuItem",
        	"dijit/form/ComboButton",
        	"dijit/DropDownMenu",
        	"dijit/MenuSeparator",
        	"dijit/PopupMenuItem",
        	"dijit/registry",
        	"dojo/i18n!./nls/calendar"], function(declare, WidgetBase, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, Query, On, Topic, MenuItem, CheckedMenuItem, ComboButton, DropDownMenu, MenuSeparator, PopupMenuItem, Registry, CalendarTranslations) {
	
	return declare([BaseClass, WidgetBase, TemplatedMixin], {
		baseClass:			"CommSyWidgetBorderless",
		widgetHandler:		null,
		
		itemId:				null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.roomList = [];
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			this.itemId = this.from_php.ownRoom.id;
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.loadRoomList().then(Lang.hitch(this, function() {
				this.createMenu();
			}));
		},
		
		afterParse: function() {
			
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		loadRoomList: function() {
			return this.AJAXRequest("myCalendar", "getRoomList", {}, Lang.hitch(this, function(response) {
				this.roomList = response;
			}));
		},
		
		createMenu: function() {
			var menu = new DropDownMenu();
			
			/*
			menu.addChild(new CheckedMenuItem({
				label:			CalendarTranslations.configToDo,
				onClick:		this.onClickConfigToDo
			}));
			
			menu.addChild(new CheckedMenuItem({
				label:			CalendarTranslations.configRestrictions,
				onClick:		this.onClickConfigRestrictions
			}));
			
			menu.addChild(new MenuSeparator());
			*/
			
			/* Date Menu */
			var dateMenu = new DropDownMenu();
			
			this.createRoomMenu(dateMenu, "checkedInDates");
			
			menu.addChild(new PopupMenuItem({
				label:			CalendarTranslations.configDate,
				popup:			dateMenu
			}));
			
			/* ToDo Menu */
			/*
			var todoMenu = new DropDownMenu();
			
			this.createRoomMenu(todoMenu, "checkedInTodo");
			
			menu.addChild(new PopupMenuItem({
				label:			CalendarTranslations.configToDo,
				popup:			todoMenu
			}));
			*/
			
			var button = new ComboButton({
				label:			CalendarTranslations.configHeadline,
				dropDown:		menu
			});
			button.placeAt(this.widgetBodyNode);
		},
		
		createRoomMenu: function(topMenu, checkedVar) {
			topMenu.addChild(new MenuItem({
				label:			CalendarTranslations.configFromAll,
				onClick:		Lang.partial(Lang.hitch(this, this.onClickAllRooms), (checkedVar === "checkedInDates") ? "dates" : "todo")
			}));
			
			topMenu.addChild(new MenuSeparator());
			
			/* add room list */
			dojo.forEach(this.roomList, Lang.hitch(this, function(room, index, arr) {
				topMenu.addChild(new CheckedMenuItem({
					label:		room.title,
					checked:	room[checkedVar],
					onChange:	Lang.partial(Lang.hitch(this, this.onRoomSelectChange), room.id, (checkedVar === "checkedInDates") ? "dates" : "todo")
				}));
			}));
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
		
		onClickAllRooms: function(type, event) {
			// store changes
			this.AJAXRequest("myCalendar", "storeRoomSelectAll", { type: type },
				Lang.hitch(this, function(response) {
					// reload calendar
					Topic.publish("updatePrivateCalendar", {});
				})
			);
			
			// update menu
			var childWidgets = Registry.findWidgets(event.rangeParent);
			dojo.forEach(childWidgets, function(widget, index, arr) {
				if (widget.get("declaredClass") === "dijit.CheckedMenuItem") {
					widget.set("checked", true);
				}
			});
		},
		
		onRoomSelectChange: function(roomId, type, checked) {
			// store change
			this.AJAXRequest("myCalendar", "storeRoomChange", { roomId: roomId, type: type, checked: checked },
				Lang.hitch(this, function(response) {
					// reload calendar
					Topic.publish("updatePrivateCalendar", {});
				})
			);
		}
	});
});