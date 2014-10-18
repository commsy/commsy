define(
[
 	"dojo/_base/declare",
 	"dojo/_base/Deferred",
 	"dojo/promise/all",
 	"dojo/_base/lang",
 	"dojo/on",
 	"dijit/Tooltip"
], function
(
	declare,
	Deferred,
	All,
	Lang,
	On,
	Tooltip
) {
	return declare(null,
	{
		loadingAnimation:	true,						///< Toggles loading animation
		errorNodes:			[],							///< Contains all tooltip error nodes
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.widgets = null;
		},
		
		/**
		 * \brief	Initialization
		 * 
		 * Initialization of WidgetManager
		 */
		Init: function()
		{
			this.widgets = [];
		},
		
		/**
		 * \brief	Returns a new Widget instance
		 * 
		 * Returns a new Widget instance and loads the widget, if not already loaded
		 * 
		 * @param[in]	widgetModule	The module path of the widget
		 * @param[in]	mixin			Optional Mixin Object for Widget creation
		 * @param[in]	silent			Optional flag for skipping loading animation etc.
		 * 
		 * @return	Dojo deferred, resolving when instancing is done, containing the widget instance
		 */
		GetInstance: function(widgetModuleString, mixin, silent)
		{
			silent = silent || false;
			
			mixin = mixin || {};
			declare.safeMixin(mixin, {
				widgetManager:	this
			});
			
			var deferred = new Deferred();
			
			if ( this.loadingAnimation && !silent )
			{
				this._SetupLoading();
			}
			
			require([widgetModuleString], Lang.hitch(this, function(widgetModule)
			{
				// init widget
				var instance = new widgetModule(mixin);
				instance.startup();
				
				// store instance
				this.widgets.push({ widget: widgetModuleString, instance: instance });
				
				deferred.resolve({
					instance:	instance
				});
				
				if ( this.loadingAnimation && !silent )
				{
					this._DestroyLoading();
				}
			}));
			
			return deferred;
		},
		
		/**
		 * \brief		Loads and initialises multiple widgets
		 * 
		 * This will load multiple widgets, just like "GetInstance".
		 * 
		 * @param[in]	widgetDescriptionArray		Array of param-array [ [ widgetModule, mixin, silient ], ... ]
		 * 
		 * @return	Dojo deferred, resolving when instancing is done completly, containing the widget instances
		 */
		GetInstances: function(widgetDescriptionArray)
		{
			var instanceArray = [];
			dojo.forEach(widgetDescriptionArray, Lang.hitch(this, function(widgetDescription, index, arr)
			{
				instanceArray.push(this.GetInstance(widgetDescription[0], widgetDescription[1], widgetDescription[2]));
			}));
			
			return All(instanceArray);
		},
		
		/**
		 * \brief		Removes all widgets matching the String
		 * 
		 * This will remove all internaly stored widgets, that matches with the widgetString
		 * 
		 * @param[in]	widgetString	String of widget
		 */
		removeInstances: function(widgetString)
		{
			var matches = dojo.filter(this.widgets, function(widget)
			{
				return widget.widget === widgetString;
			});
			
			dojo.forEach(matches, function(widget)
			{
				var instance = widget.instance;
				
				instance.destroy();
			});
			
			var tmp = [];
			dojo.forEach(this.widgets, function(widget)
			{
				if ( widget.widget !== widgetString )
				{
					tmp.push(widget);
				}
			});
			this.widgets = tmp;
		},
		
		/**
		 * \brief	Closes all widgets
		 * 
		 * This Method closes all widgets stored in the manager
		 */
		CloseAllWidgets: function(currentPopup)
		{
			currentPopup = currentPopup || {};
			dojo.forEach(togglePopups, Lang.hitch(this, function(popup, index, arr) {
				if (popup !== currentPopup) {
					popup.close();
					popup.is_open = false;
				}
			}));

			dojo.forEach(this.widgets, function(widget, index, arr)
			{
				if ( widget.instance.Close )				// this is false, if the widget is not deferred from PopupBase
				{
					widget.instance.Close();
				}
			});
		},
		
		/**
		 * \brief	Registers open/close click action
		 * 
		 * This will register a open/close click action for a widget instance
		 * 
		 * @param[in]	widgetInstance	The instance of the widget
		 * @param[in]	domNode			The node, the click is registered on
		 */
		RegisterOpenCloseClick: function(widgetInstance, domNode)
		{
			On(domNode, "click", Lang.hitch(this, function(event)
			{
				var isOpen = widgetInstance.get("isOpen");
				
				if ( isOpen )
				{
					widgetInstance.Close();
				}
				else
				{
					widgetInstance.Open();
				}
			}));
		},
		
		createErrorTooltip: function(node, message, position)
		{
			// ensure optional parameter
			position = position || ["left", "right"];
			
			// set tooltip position
			dijit.Tooltip.defaultPosition = position;
			
			// show tooltip
			Tooltip.show(message, node);
			
			// restore default position
			dijit.Tooltip.defaultPosition = ["left", "right"];
			
			// store node
			this.errorNodes.push(node);
		},
		
		closeErrorTooltips: function()
		{
			dojo.forEach(this.errorNodes, Lang.hitch(function(node, index, arr) {
				Tooltip.hide(node);
			}));
			
			this.errorNodes = [];
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/**
		 * \brief	Set up loading
		 * 
		 * Set up a loading animation widget
		 */
		_SetupLoading: function()
		{
			this.base.setupLoading();
		},
		
		/**
		 * \brief	Destroy loading
		 * Destroys loading animation
		 */
		_DestroyLoading: function()
		{
			this.base.destroyLoading();
		},
		
		/**
		 * \brief	Loads a Widget
		 * 
		 * Load a widget, if is not already loaded yet
		 */
		_LoadWidget: function(widgetName)
		{
			
		}
	});
});