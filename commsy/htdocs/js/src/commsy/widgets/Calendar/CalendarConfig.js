define(
[
	"dojo/_base/declare",
	"dijit/_WidgetBase",
	"commsy/base",
	"dijit/_TemplatedMixin",
	"dojo/text!./templates/CalendarConfig.html",
	"dojo/i18n!./nls/calendarConfig",
	"dojo/_base/lang",
	"dojo/promise/all",
	"dojo/on",
	"dojo/topic",
	"dijit/MenuItem",
	"dijit/CheckedMenuItem",
	"dijit/form/ComboButton",
	"dijit/DropDownMenu",
	"dijit/MenuSeparator",
	"dijit/PopupMenuItem",
	"dijit/registry"
], function
(
	declare,
	WidgetBase,
	BaseClass,
	TemplatedMixin,
	Template,
	CalendarTranslations,
	Lang,
	All,
	On,
	Topic,
	MenuItem,
	CheckedMenuItem,
	ComboButton,
	DropDownMenu,
	MenuSeparator,
	PopupMenuItem,
	Registry
) {
	return declare([BaseClass, WidgetBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"CommSyWidgetBorderless",

		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		/**
		 * \brief	Processing after the DOM fragment is created
		 * 
		 * Called after the DOM fragment has been created, but not necessarily
		 * added to the document.  Do not include any operations which rely on
		 * node dimensions or placement.
		 */
		postCreate: function()
		{
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			All(
			[
			 	this.loadRoomList(),
			 	this.loadConfig()
			]).then(Lang.hitch(this, function() {
				this.createMenu();
				
				var calendarWidget = this.parentWidget.calendar;
				calendarWidget.options = this.config;
				calendarWidget.createCalendar();
			}));
		},
		
		/**
		 * \brief 	Processing after the DOM fragment is added to the document
		 * 
		 * Called after a widget and its children have been created and added to the page,
		 * and all related widgets have finished their create() cycle, up through postCreate().
		 * This is useful for composite widgets that need to control or layout sub-widgets.
		 * Many layout widgets can use this as a wiring phase.
		 */
		startup: function()
		{
			this.inherited(arguments);
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		loadRoomList: function() {
			return this.AJAXRequest("myCalendar", "getRoomList", {}, Lang.hitch(this, function(response) {
				this.roomList = response;
			}));
		},
		
		loadConfig: function() {
			return this.AJAXRequest("myCalendar", "getConfig", {}, Lang.hitch(this, function(response)
			{
				this.config = response;
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
			
			menu.addChild(new CheckedMenuItem({
				id:				"onlyAssignedMenuItem",
				label:			CalendarTranslations.configOnlyAssigned,
				checked:		this.config.assignedToMe,
				onClick:		Lang.hitch(this, this.onClickConfigOnlyAssigned)
			}));
			
			menu.addChild(new MenuSeparator());
			
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
				onClick:		Lang.partial(Lang.hitch(this, this.onClickAllRooms), (checkedVar === "checkedInDates") ? "dates" : "todo", topMenu)
			}));
			topMenu.addChild(new MenuItem({
				label:			CalendarTranslations.configFromNone,
				onClick:		Lang.partial(Lang.hitch(this, this.onClickNoneRooms), (checkedVar === "checkedInDates") ? "dates" : "todo", topMenu)
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
		 * Event Handling
		 ************************************************************************************/
		onClickConfigToDo: function(event) {
			event.preventDefault();
		},
		
		onClickConfigRestrictions: function(event) {
			event.preventDefault();
		},
		
		onClickConfigOnlyAssigned: function(event)
		{
			var widget = Registry.byId("onlyAssignedMenuItem");
			
			// store change
			this.AJAXRequest("myCalendar", "storeConfig", { config: { assignedToMe: widget.checked } }, Lang.hitch(this, function(response)
			{
				// reload calendar
				Topic.publish("updatePrivateCalendar", { setConfig: { assignedToMe: widget.checked } });
			}));
		},
		
		onClickAllRooms: function(type, menuWidget, event) {
			// store changes
			this.AJAXRequest("myCalendar", "storeRoomSelectAll", { type: type },
				Lang.hitch(this, function(response) {
					// reload calendar
					Topic.publish("updatePrivateCalendar", {});
				})
			);
			
			// update menu
			var childWidgets = Registry.findWidgets(menuWidget.domNode);
			dojo.forEach(childWidgets, function(widget, index, arr) {
				if (widget.get("declaredClass") === "dijit.CheckedMenuItem") {
					widget.set("checked", true);
				}
			});
		},
		
		onClickNoneRooms: function(type, menuWidget, event)
		{
			// store changes
			this.AJAXRequest("myCalendar", "storeRoomSelectNone", { type: type },
				Lang.hitch(this, function(response)
				{
					// reload calendar
					Topic.publish("updatePrivateCalendar", {});
				})
			);
			
			// update menu
			var childWidgets = Registry.findWidgets(menuWidget.domNode);
			dojo.forEach(childWidgets, function(widget, index, arr)
			{
				if ( widget.get("declaredClass") === "dijit.CheckedMenuItem")
				{
					widget.set("checked", false);
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