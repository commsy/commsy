define([	"dojo/_base/declare",
        	"dijit/Dialog",
        	"commsy/base",
        	"dijit/_TemplatedMixin",
        	"dojo/_base/lang",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/on",
        	"dojo/query",
        	"dojox/timing",
        	"dojo/text!./templates/ErrorDialog.html",
        	"dojo/i18n!./nls/common"], function(declare, Dialog, BaseClass, TemplatedMixin, Lang, DomConstruct, DomAttr, On, Query, Timing, Template, CommonTranslations) {
	
	return declare([BaseClass, Dialog, TemplatedMixin], {
		baseClass:			"CommSyErrorWidget",
		widgetHandler:		null,
		secRemaining:		5,
		title:				"Error",
		
		templateString:		Template,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
			
			this.timer = new Timing.Timer(1000);
		},
		
		postMixInProperties: function() {
			this.inherited(arguments);
			
			this.translations = CommonTranslations;
		},
		
		postCreate: function() {
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.secRemainingNode.innerHTML = this.secRemaining;
			
			this.timer.onTick = Lang.hitch(this, function(event) {
				this.onTick(event);
			});
			this.timer.start();
		},
		
		/************************************************************************************
		 * EventHandler
		 ************************************************************************************/
		onTick: function(event) {
			if (this.secRemaining === 1) {
				this.timer.stop();
				this.destroyRecursive(false);
			} else {
				this.secRemaining--;
				this.secRemainingNode.innerHTML = this.secRemaining;
			}
		}
	});
});