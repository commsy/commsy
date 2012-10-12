define(
[
 	"dojo/_base/declare",
 	"dojo/_base/Deferred",
 	"dojo/_base/lang"
], function
(
	declare,
	Deferred,
	Lang
) {
	return declare([],
	{
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
		 * @return	Dojo deferred, resolving when instancing is done, containing the widget instance
		 */
		GetInstance: function(widgetModule, mixin)
		{
			mixin = mixin || {};
			declare.safeMixin(mixin, {
				widgetManager:	this
			});
			
			var deferred = new Deferred();
			
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
			}));
			
			return deferred;
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
				widget.Close();
			});
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
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