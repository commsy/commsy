define(
[
	"dojo/_base/declare",
	"commsy/widgets/List/ListWidget",
	"dojo/i18n!./nls/MyWidgetsRoomwideSearch",
	"dojo/_base/lang",
	"dojo/dom-construct",
	"dojo/on",
	"commsy/request",
	"dojo/dom-class",
	"dojo/dom-attr",
	"dojo/query",
	"dijit/MenuItem",
	"dijit/CheckedMenuItem",
	"dijit/form/ComboButton",
	"dijit/DropDownMenu",
	"dijit/PopupMenuItem"
], function
(
	declare,
	ListWidget,
	PopupTranslations,
	lang,
	DomConstruct,
	On,
	request,
	DomClass,
	DomAttr,
	Query,
	MenuItem,
	CheckedMenuItem,
	ComboButton,
	DropDownMenu,
	PopupMenuItem
) {
	return declare([ListWidget],
	{
		hasSearchMask:		true,									///< does this list provide a search mask?
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.searchFilter = null;
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
			this.set("title", PopupTranslations.title);
			
			// configure columns definition
			this.addColumn(0, function(rowNode, rowData)
			{
				// first column
				var firstColumnNode = DomConstruct.create("div",
				{
					className:		"column_280"
				}, rowNode, "last");
				
					var pNode = DomConstruct.create("p", {}, firstColumnNode, "last");

						DomConstruct.create("a",
						{
							"id":		"listItem" + rowData.itemId,
							className:	"stack_link",
							href:		"commsy.php?cid=" + rowData.contextId + "&mod=" + rowData.module + "&fct=detail&iid=" + rowData.itemId,
							innerHTML:	rowData.title
						}, pNode, "last");
			});
			
			this.addColumn(1, function(rowNode, rowData)
			{
				// second column
				var secondColumnNode = DomConstruct.create("div",
				{
					className:		"column_45"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, secondColumnNode, "last");

						if (rowData.fileCount > 0)
						{
							DomConstruct.create("a",
							{
								className:		"attachment",
								href:			"#",
								innerHTML:		rowData.fileCount
							}, pNode, "last");
						}
			});
			
			this.addColumn(2, lang.hitch(this, function(rowNode, rowData)
			{
				// third column
				var thirdColumnNode = DomConstruct.create("div",
				{
					className:		"column_65"
				}, rowNode, "last");

					var pNode = DomConstruct.create("p", {}, thirdColumnNode, "last");

						DomConstruct.create("img",
						{
							src:		this.from_php.template.tpl_path + "img/netnavigation/" + rowData.image.img,
							title:		rowData.image.text
						}, pNode, "last");
			}));
			
			this.addColumn(3, function(rowNode, rowData)
			{
				// fourth column
				var fourthColumnNode = DomConstruct.create("div",
				{
					className:		"column_90"
				}, rowNode, "last");

					DomConstruct.create("p",
					{
						innerHTML:		rowData.modificationDate
					}, fourthColumnNode, "last");
			});
			
			this.addColumn(4, function(rowNode, rowData)
			{
				// fifth column
				var fifthColumnNode = DomConstruct.create("div",
				{
					className:		"column_155"
				}, rowNode, "last");

					DomConstruct.create("p",
					{
						innerHTML:		rowData.roomName
					}, fifthColumnNode, "last");
			});
			
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'widget_roomwide_search',
					action:	'getSearchFilter'
				}
			}).then(
				lang.hitch(this, function(response) {
					this.searchFilter = response.data;
					
					this.createMenu();
					
					// set the store
					this.setStore("widget_roomwide_search");
				})
			);
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
		createMenu: function()
		{
			var mainMenu = new DropDownMenu();
			var rubricMenu = new DropDownMenu();
			var roomMenu = new DropDownMenu();
			
			/* Rubric Menu */
			dojo.forEach(this.searchFilter.rubrics, lang.hitch(this, function(rubric)
			{
				rubricMenu.addChild(new CheckedMenuItem(
				{
					label:		rubric.text,
					checked:	true,
					onChange:	lang.partial(lang.hitch(this, this.onRubricSelectChange), rubric)
				}));
			}));
			
			mainMenu.addChild(new PopupMenuItem(
			{
				label:			PopupTranslations.searchRubrics,
				popup:			rubricMenu
			}));
			
			/* Room Menu */
			dojo.forEach(this.searchFilter.rooms, lang.hitch(this, function(room)
			{
				roomMenu.addChild(new CheckedMenuItem(
				{
					label:		room.title,
					checked:	true,
					onChange:	lang.partial(lang.hitch(this, this.onRoomSelectChange), room)
				}));
			}));
			
			mainMenu.addChild(new PopupMenuItem(
			{
				label:			PopupTranslations.searchRooms,
				popup:			roomMenu
			}));
			
			var menuButton = new ComboButton(
			{
				label:			PopupTranslations.searchExtended,
				dropDown:		mainMenu
			});
			menuButton.placeAt(this.searchContainerNode, "last");
		},
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onRubricSelectChange: function(rubric, checked)
		{
			// update search filter
			dojo.forEach(this.searchFilter.rubrics, lang.hitch(this, function(rubricElement, index)
			{
				if ( rubric.type == rubricElement.type )
				{
					this.searchFilter.rubrics[index].active = checked;
				}
			}));
			
			this.queryOptions.filter = this.searchFilter;
			this.store.query(this.query, this.queryOptions, lang.hitch(this, this.updateList));
		},
		
		onRoomSelectChange: function(room, checked)
		{
			// update search filter
			dojo.forEach(this.searchFilter.rooms, lang.hitch(this, function(roomElement, index)
			{
				if ( room.id == roomElement.id )
				{
					this.searchFilter.rooms[index].active = checked;
				}
			}));
			
			this.queryOptions.filter = this.searchFilter;
			this.store.query(this.query, this.queryOptions, lang.hitch(this, this.updateList));
		}
	});
});