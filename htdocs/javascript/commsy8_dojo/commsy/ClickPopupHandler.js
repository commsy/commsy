define([	"dojo/_base/declare",
        	"commsy/PopupHandler",
        	"dojo/on",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dijit/Tooltip",
        	"dojo/dom-attr",
        	"dojo/dom-construct",
        	"dojo/dom-style"], function(declare, PopupHandler, on, lang, query, dom_class, Tooltip, dom_attr, domConstruct, domStyle) {
	return declare(PopupHandler, {
		triggerNode:			null,
		item_id:				null,
		ref_iid:				null,
		ticks:					0,

		constructor: function(args) {
			this.fct = "rubric_popup";
		},

		registerPopupClick: function() {
			on(this.triggerNode, "click", lang.hitch(this, function(event) {
				if(this.is_open === false) {
					this.is_open = true;

					this.setupLoading();

					// setup ajax request for getting html
					this.AJAXRequest("rubric_popup", "getHTML", { module: this.module, iid: this.item_id, ref_iid: this.ref_iid, editType: this.editType, version_id: this.version_id }, lang.hitch(this, function(html) {
						// append html to body
						domConstruct.place(html, query("body")[0], "first");

						this.contentNode = query("div#popup_wrapper")[0];
						this.scrollToNodeAnimated(this.contentNode);

						this.setupTabs();
						this.setupFeatures();
						this.setupSpecific();
						this.setupAutoSave();

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

						// register event for handling mouse actions outside content div
						on(document.body, "click", lang.hitch(this, function(event) {
							if(dom_attr.get(event.target, "id") === "popup_wrapper") {
								// TODO: create something like a tooltip here
								alert("Bitte schließen Sie zuerst das Popup-Fenster, bevor Sie sonstige Seitenoperationen ausführen");
							}
						}));

						this.is_open = !this.is_open;

						this.destroyLoading();
					}));

					event.preventDefault();
				}
			}));
		},

		setupAutoSave: function() {
			var mode = this.from_php.autosave.mode;
			var limit = this.from_php.autosave.limit;

			if(mode > 0) {
				// autosave is enabled
				require(["dojox/timing", "dojox/string/sprintf"], lang.hitch(this, function() {
					var timer = new dojox.timing.Timer(1000);

					if(mode == 2) {
						// show countdown
						var timerDiv = domConstruct.create("div", {
							className:	"autosave",
							innerHTML:	"00:00:00"
						}, query("div#crt_actions_area", this.contentNode)[0], "first");
					}

					timer.onTick = lang.hitch(this, function() {
						this.ticks++;

						if(this.ticks === limit) {
							// get custom data object
							var customObject = this.getAttrAsObject(query("input.submit", this.contentNode)[0], "data-custom");
							this.onPopupSubmit(customObject);
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

							var display = dojox.string.sprintf("%02u:%02u:%02u", hoursLeft, minutesLeft, secondsLeft);
							dom_attr.set(timerDiv, "innerHTML", display);
						}
					});

					timer.start();
				}));
			}
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

			this.is_open = false;
		},

		reload: function(item_id) {
			// page reload
			var cid = this.uri_object.cid;
			var module = this.uri_object.mod;

			if(module === "home") {
				var module = this.module;
			}

			location.href = "commsy.php?cid=" + cid + "&mod=" + module + "&fct=detail&iid=" + item_id;
		},
	});
});