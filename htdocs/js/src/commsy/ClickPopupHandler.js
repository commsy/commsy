define([	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/_base/lang",
        	"commsy/request",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, PopupHandler, on, lang, request, query, dom_class, dom_attr, domConstruct, domStyle) {
	return declare(PopupHandler, {
		constructor: function(args) {
			this.fct			= "rubric_popup";
			this.initData		= {};
			
			this.triggerNode	= null;
			this.item_id		= null;
			this.ref_iid		= null;
			this.ticks			= 0;
			this.timer			= null;
			this.userAction		= false;
			this.lockListener	= null;
			this.ajaxHTMLSource	= "rubric_popup";
		},
		
		setInitData: function(object) {
			this.initData = object;
		},

		registerPopupClick: function() {
			on(this.triggerNode, "click", lang.hitch(this, function(event) {
				this.open();
				event.preventDefault();
			}));
		},
		
		open: function() {
			if(this.is_open === false) {
				this.is_open = true;

				this.setupLoading();
				
				var data = { module: this.module, iid: this.item_id, ref_iid: this.ref_iid, editType: this.editType, version_id: this.version_id, contextId: this.contextId, date_new: this.date_new };
				declare.safeMixin(data, this.initData);

				// setup ajax request for getting html
				request.ajax({
					query: {
						cid:	this.uri_object.cid,
						mod:	'ajax',
						fct:	this.ajaxHTMLSource,
						action:	'getHTML'
					},
					data: data
				}).then(
					lang.hitch(this, function(response) {
						// append html to body
						domConstruct.place(response.data, query("body")[0], "first");

						this.contentNode = query("div#popup_wrapper")[0];
						this.scrollToNodeAnimated(this.contentNode);

						this.setupTabs();
						this.setupFeatures();
						this.setupSpecific();
						this.setupAutoSave();
						this.onCreate();

						if (this.from_php.environment.item_locking) {
							// if we are editing an item, setup the locking mechanism
							if (this.item_id !== "NEW") {
								this.setupLocking();
							}
						}

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

						this.is_open = !this.is_open;

						this.destroyLoading();
					})
				);
			}
		},

		setupAutoSave: function() {
			var mode = this.from_php.autosave.mode;
			var limit = this.from_php.autosave.limit * 60;

			if(mode > 0) {
				// autosave is enabled
				require(["dojox/timing", "dojox/string/sprintf"], lang.hitch(this, function() {
					this.timer = new dojox.timing.Timer(1000);
					var timerDiv = false;

					if(mode == 2) {
						// show countdown
						timerDiv = domConstruct.create("div", {
							className:	"autosave",
							innerHTML:	"00:00:00"
						}, query("div#crt_actions_area", this.contentNode)[0], "first");
					}

					this.timer.onTick = lang.hitch(this, function() {
						this.ticks++;

						if(this.ticks === limit) {
							// get custom data object
							var customObject = this.getAttrAsObject(query("input.submit", this.contentNode)[0], "data-custom");
							this.onPopupSubmit(customObject);
							
							// reset ticks
							this.ticks = 0;
						}

						if(mode == 2) {
							// update countdown
							var timeLeft = limit - this.ticks;

							// hours
							var hoursLeft = Math.floor(timeLeft / 3600);
							timeLeft -= hoursLeft * 3600;

							// minutes
							var minutesLeft = Math.floor(timeLeft / 60);
							timeLeft -= minutesLeft * 60;

							// seconds
							var secondsLeft = timeLeft;
							
							if ( timerDiv )
							{
								var display = dojox.string.sprintf("%02u:%02u:%02u", hoursLeft, minutesLeft, secondsLeft);
								dom_attr.set(timerDiv, "innerHTML", display);
							}
						}
					});

					this.timer.start();
				}));
			}
		},

		setupLocking: function() {
			// update the locking date, so that the item is initially marked as changing
			this.updateLockingDate();

			// listen for changes
			this.lockListener = on(this.contentNode, "click, keypress, change", lang.hitch(this, function(event) {
				// if we are not already aware of a user action
				if (!this.userAction) {
					// don't care about submits
					if (!dom_class.contains(event.target, "submit")) {
						this.userAction = true;

						// update the editing date
						this.updateLockingDate();
					}
				}
			}));

			var cooldown = 20 * 60 * 1000;
			cooldown = 5 * 1000;

			// setup an interval after that we reset the user action flag
			window.setInterval(lang.hitch(this, function() {
				this.userAction = false;
			}), cooldown);
		},

		updateLockingDate: function() {
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'locking',
					action:	'update'
				},
				data: {
					id: this.item_id
				}
			});
		},

		clearLockingDate: function() {
			this.lockListener.remove();

			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'locking',
					action:	'clear'
				},
				data: {
					id: this.item_id
				}
			});
		},

		close: function() {
			this.inherited(arguments);
			
			// destroy editors
			if(this.featureHandles["editor"]) {
				dojo.forEach(this.featureHandles["editor"], function(editor, index, arr) {
					editor.destroy();
				});
			}

			// destroy datepicker
			if(this.featureHandles["calendar"]) {
				dojo.forEach(this.featureHandles["calendar"], function(calendar, index, arr) {
					calendar.destroy();
				});
			}

			// remove from dom
			domConstruct.destroy(this.contentNode);

			// destroy Loading
			this.destroyLoading();
			
			// destroy timer
			if ( this.timer )
			{
				this.timer.stop();
				this.ticks = 0;
			}

			this.is_open = false;

			if (this.from_php.environment.item_locking) {
				this.clearLockingDate();
			}
		}
	});
});