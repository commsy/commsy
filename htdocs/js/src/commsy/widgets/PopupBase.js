define(
[
 	"dojo/_base/declare",
 	"dijit/_WidgetBase",
 	"commsy/base",
 	"dojo/_base/lang",
 	"commsy/request",
 	"dojo/query",
 	"dojo/dom-style",
 	"dojo/_base/Deferred"
], function
(
	declare,
	WidgetBase,
	CommSyBase,
	lang,
	request,
	Query,
	DomStyle,
	Deferred
) {
	return declare([CommSyBase, WidgetBase],
	{
		toggle:				false,						///< Determs if this is a switchable popup
		canOverlay:			false,						///< Determs if popup can overlay other popus
		
		// internal
		widgetManager:		null,						///< widgetManager instance - set up on construction
		
		// attributes
		isOpen:				false,						///< Indicates current display status
		loaded:				false,						///< Indicates if popup is fully initialized
		initData:			null,						///< Initialization data
		
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
		setInitData: function(data)
		{
			this.set("initData", data);
		},
		
		/**
		 * Open Wrapper
		 */
		Open: function()
		{
			this.set("isOpen", true);
		},
		
		/**
		 * Close Wrapper
		 */
		Close: function()
		{
			this.set("isOpen", false);
		},
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/**
		 * \brief	Load Popup
		 * 
		 * Loads a popup by requesting initial data from php
		 * 
		 * @return	Deferred - resolves when loading is done or not needed
		 */
		_LoadPopup: function()
		{
			var loadingDeferred = new Deferred();
			
			// load popup if not already done and init data is given
			if ( !this.loaded && this.initData ) {
				// send ajax request to initiate popup
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	this.initData.module,
						action:	this.initData.action
					},
					data: this.initData.data
				}).then(
					lang.hitch(this, function(response) {
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
						
						this._set("loaded", true);
						loadingDeferred.resolve();
					})
				);
			} else {
				this._set("loaded", true);
				loadingDeferred.resolve();
			}
			
			return loadingDeferred;
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/**
		 * Setter for "isOpen" attribute
		 * 
		 * @param[in]	open			Boolean
		 */
		_setIsOpenAttr: function(open)
		{
			if ( open === true )
			{
				// if not already opened
				if ( this.isOpen === false )
				{
					this.OnOpenPopup();
				}
			}
			else
			{
				// if not already closed
				if ( this.isOpen === true )
				{
					this.OnClosePopup();
				}
			}
			
			this._set("isOpen", open);
		},
		
		/************************************************************************************
		 * Event Handler
		 ************************************************************************************/
		
		/**
		 * \brief	toggle event
		 * 
		 * This will open the popup. It will also load the popup, if requested.
		 * Should be overwritten and called by child classes to specify custom behaviour
		 * 
		 * @return	Deferred - resolves when opening is done
		 */
		OnOpenPopup: function()
		{
			var openDeferred = new Deferred();
			
			this._LoadPopup().then(lang.hitch(this, function(response) {
				noBacklink = true;

				// check if there are other switchable popups open
				if ( /*this.statics.switchableIsOpen &&*/ this.canOverlay === false ) {
					// close all
					this.widgetManager.CloseAllWidgets();
				}
				noBacklink = false;
				
				// place widget
				var widgetNode = Query("body div#" + this.id)[0];
				if ( !widgetNode ) {
					this.placeAt( Query("body")[0], "first" );
				} else {
					// check if hidden and unhide
					if ( DomStyle.get(widgetNode, "display") == "none" ) {
						DomStyle.set(widgetNode, "display", "block");
					}
				}
				
				// if this popup is switchable, we set the "static" flag
				if ( this.toggle ) {
					this.statics.switchableIsOpen = true;
				}
				
				openDeferred.resolve();
			}));
			
			return openDeferred;
		},
		
		/**
		 * \brief	close event
		 * 
		 * Closes the popup. Should be overwritten and called by child classes to specify custom behaviour
		 */
		OnClosePopup: function()
		{
			if (noBacklink) {
				// if this is a switchable popup we just hide it, otherwise we destroy it
				if ( this.toggle ) {
					DomStyle.set(this.domNode, "display", "none");
				} else {
					this.destroy();
				}
			} else {
				if (!this.backlink()) {
					// if this is a switchable popup we just hide it, otherwise we destroy it
					if ( this.toggle ) {
						DomStyle.set(this.domNode, "display", "none");
					} else {
						this.destroy();
					}
				}
			}
		}
	});
});