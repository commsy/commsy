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
	"commsy/request",
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
	lang,
	All,
	On,
	request,
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
			]).then(lang.hitch(this, function() {
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
			return request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'getRoomList'
				}
			}).then(
				lang.hitch(this, function(response) {
					this.roomList = response.data;
				})
			);
		},
		
		loadConfig: function() {
			return request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'getConfig'
				}
			}).then(
				lang.hitch(this, function(response) {
					this.config = response.data;
				})
			);
		},
		
		createMenu: function() {
			var menu = new DropDownMenu();
			
			menu.addChild(new CheckedMenuItem({
				id:				"onlyAssignedMenuItem",
				label:			CalendarTranslations.configOnlyAssigned,
				checked:		this.config.assignedToMe,
				onClick:		lang.hitch(this, this.onClickConfigOnlyAssigned)
			}));
			
			menu.addChild(new MenuSeparator());
			
			/* Date Menu */
			var dateMenu = new DropDownMenu();
			
			this.createRoomMenu(dateMenu, "checkedInDates");
			
			menu.addChild(new PopupMenuItem({
				label:			CalendarTranslations.configDate,
				popup:			dateMenu
			}));
			
			var button = new ComboButton({
				label:			CalendarTranslations.configHeadline,
				dropDown:		menu
			});
			button.placeAt(this.widgetBodyNode);
		},
		
		createRoomMenu: function(topMenu, checkedVar) {
			topMenu.addChild(new MenuItem({
				label:			CalendarTranslations.configFromAll,
				onClick:		lang.partial(lang.hitch(this, this.onClickAllRooms), (checkedVar === "checkedInDates") ? "dates" : "todo", topMenu)
			}));
			topMenu.addChild(new MenuItem({
				label:			CalendarTranslations.configFromNone,
				onClick:		lang.partial(lang.hitch(this, this.onClickNoneRooms), (checkedVar === "checkedInDates") ? "dates" : "todo", topMenu)
			}));
			
			topMenu.addChild(new MenuSeparator());
			
			/* add room list */
			dojo.forEach(this.roomList, lang.hitch(this, function(room, index, arr) {
				topMenu.addChild(new CheckedMenuItem({
					label:		room.title,
					checked:	room[checkedVar],
					onChange:	lang.partial(lang.hitch(this, this.onRoomSelectChange), room.id, (checkedVar === "checkedInDates") ? "dates" : "todo")
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
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'storeConfig'
				},
				data: {
					config: {
						assignedToMe: widget.checked
					}
				}
			}).then(
				lang.hitch(this, function(response) {
					// reload calendar
					Topic.publish("updatePrivateCalendar", { setConfig: { assignedToMe: widget.checked } });
				})
			);
		},
		
		onClickAllRooms: function(type, menuWidget, event) {
			// store changes
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'storeRoomSelectAll'
				},
				data: {
					type: type
				}
			}).then(
				lang.hitch(this, function(response) {
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
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'storeRoomSelectNone'
				},
				data: {
					type: type
				}
			}).then(
				lang.hitch(this, function(response) {
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
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'myCalendar',
					action:	'storeRoomChange'
				},
				data: {
					roomId:		roomId,
					type:		type,
					checked:	checked
				}
			}).then(
				lang.hitch(this, function(response) {
					// reload calendar
					Topic.publish("updatePrivateCalendar", {});
				})
			);
		}
	});
});