define(
[
 	"dojo/_base/declare",
 	"dijit/_WidgetBase",
 	"commsy/base",
 	"dojo/_base/lang",
 	"dojo/query",
 	"dojo/dom-style"
], function
(
	declare,
	WidgetBase,
	CommSyBase,
	Lang,
	Query,
	DomStyle
) {
	return declare([CommSyBase, WidgetBase],
	{
		toggle:				false,						///< Determs if this is a switchable popup
		initData:			null,						///< Initialization data
		loadingAnimation:	true,						///< Toggles loading animation
		
		// internal
		isOpen:				false,						///< Indicates current display status
		isLoaded:			false,						///< Inidcates if popup is fully initialized
		widgetManager:		null,						///< widgetManager instance - set up on construction
		
		// static
		statics: {
			switchableIsOpen:	false
		},
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			if ( !this.widgetManager ) console.error("widgetManager instance not set");
			
			this.templatePath = this.from_php.template.tpl_path;
		},
		
		/**
		 * \brief	Set Init Data
		 * 
		 * Sets initial Data, this will be send to the appropriate php controller
		 * 
		 * @param[in]	data	The init data object
		 */
		SetInitData: function(data)
		{
			this._set("initData", data);
		},
		
		/**
		 * \brief	Opens popup
		 * 
		 * This will open the popup, if currently not shown.
		 * It will also load the popup, if requested.
		 */
		Open: function()
		{
			// if not already open
			if ( !this.isOpen )
			{
				if ( this.loadingAnimation ) this._SetupLoading();
				
				// load popup if not already done and init data is given
				if ( !this.isLoaded && this.initData )
				{
					this._LoadPopup();
				}
				
				this.OnOpenPopup();
				
				// popup is opened now
				this._set("isOpen", true);
				
				if (this.loadingAnimation ) this._DestroyLoading();
			}
		},
		
		/**
		 * \brief	Closes popup
		 * 
		 * Closes the popup i, if currently shown.
		 */
		Close: function()
		{
			this.OnClosePopup();
			
			// popup is closed now
			this._set("isOpen", false);
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/**
		 * \brief	Load Popup
		 * 
		 * Loads a popup be requesting initial data from php
		 */
		_LoadPopup: function()
		{
			// send ajax request to initiate popup
			this.AJAXRequest(
				this.initData.module,
				this.initData.action,
				this.initData.data,
				Lang.hitch(this, function(reponse)
				{
					/*
					 * TooglePopupHandler
					 * 
					 * 
					 * 
					 * // append html to node
				DomConstruct.place(html, this.contentNode, "last");
				
				this.setupTabs();
				this.setupFeatures();
				this.setupSpecific();
				this.onCreate();
				
				// register close
				on(query("a", this.contentNode)[0], "click", lang.hitch(this, function(event) {
					this.close();
					
					event.preventDefault();
				}));
				
				// register submit click
				on(query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
					// get custom data object
					var customObject = this.getAttrAsObject(event.target, "data-custom");
					this.onPopupSubmit(customObject);
					
					event.preventDefault();
				}));
				
				this.destroyLoading();
					 */
					
					
					/*
					 * ClickPopupHandler
					 * 
					 * 
					 * // append html to body
				domConstruct.place(html, query("body")[0], "first");

				this.contentNode = query("div#popup_wrapper")[0];
				this.scrollToNodeAnimated(this.contentNode);

				this.setupTabs();
				this.setupFeatures();
				this.setupSpecific();
				this.setupAutoSave();
				this.onCreate();

				// register close
				on(query("a#popup_close, input#popup_button_abort", this.contentNode), "click", lang.hitch(this, function(event) {
					this.close();

					event.preventDefault();
				}));

				// register submit clicks
				on(query("input.submit", this.contentNode), "click", lang.hitch(this, function(event) {
					// setup loading
					this.setupLoading();

					// get custom data object
					var customObject = this.getAttrAsObject(event.target, "data-custom");
					this.onPopupSubmit(customObject);

					event.preventDefault();
				}));
					 */
					
					this._set("isLoaded", true);
				})
			);
		},
		
		/**
		 * \brief	Set up loading
		 * 
		 * Set up a loading animation widget
		 */
		_SetupLoading: function()
		{
			
		},
		
		/**
		 * \brief	Destroy loading
		 * Destroys loading animation
		 */
		_DestroyLoading: function()
		{
			
		},
		
		/************************************************************************************
		 * Event Handler
		 ************************************************************************************/
		
		/**
		 * \brief	toggle event
		 * 
		 * Called when the popup is opened. Should be overwritten and called by child classes to specify custom behaviour
		 */
		OnOpenPopup: function()
		{
			// check if there are other switchable popups open
			if ( this.statics.switchableIsOpen )
			{
				// close all
				this.widgetManager.CloseAllWidgets();
			}
			
			// place widget
			var widgetNode = Query("body div#" + this.id)[0];
			if ( !widgetNode )
			{
				this.placeAt( Query("body")[0], "first" );
			}
			else
			{
				// check if hidden and unhide
				if ( DomStyle.get(widgetNode, "display") == "none" )
				{
					DomStyle.set(widgetNode, "display", "block");
				}
			}
			
			// if this popup is switchable, we set the "static" flag
			if ( this.toggle )
			{
				this.statics.switchableIsOpen = true;
			}
		},
		
		/**
		 * \brief	close event
		 * 
		 * Called when the popup is close. Should be overwritten and called by child classes to specify custom behaviour
		 */
		OnClosePopup: function()
		{
			// if this is a switchable popup we just hide it, otherwise we destroy it
			if ( this.toggle )
			{
				DomStyle.set(this.domNode, "display", "none");
			}
			else
			{
				this.destroy();
			}
		}
	});
});