define(
[
 	"dojo/_base/declare",
 	"dojo/_base/Deferred",
 	"dojo/promise/all",
 	"dojo/_base/lang",
 	"dojo/on"
], function
(
	declare,
	Deferred,
	All,
	Lang,
	On
) {
	return declare([],
	{
		loadingAnimation:	true,						///< Toggles loading animation
		
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
		GetInstance: function(widgetModule, mixin, silent)
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
			
			require([widgetModule], Lang.hitch(this, function(widgetModule)
			{
				// init widget
				var instance = new widgetModule(mixin);
				instance.startup();
				
				// store instance
				this.widgets.push({ widget: widgetModule, instance: instance });
				
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
		 * \brief	Closes all widgets
		 * 
		 * This Method closes all widgets stored in the manager
		 */
		CloseAllWidgets: function()
		{
			dojo.forEach(this.widgets, function(widget, index, arr)
			{
				widget.instance.Close();
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