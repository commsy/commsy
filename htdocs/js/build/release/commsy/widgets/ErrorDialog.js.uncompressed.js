require({cache:{
'url:commsy/widgets/templates/ErrorDialog.html':"<div class=\"${baseClass} dijitDialog\" role=\"dialog\" aria-labelledby=\"${id}_title\">\n\t<div data-dojo-attach-point=\"titleBar\" class=\"dijitDialogTitleBar\">\n\t\t<span data-dojo-attach-point=\"titleNode\" class=\"dijitDialogTitle\" id=\"${id}_title\"></span>\n\t\t<span data-dojo-attach-point=\"closeButtonNode\" class=\"dijitDialogCloseIcon\" data-dojo-attach-event=\"ondijitclick: onCancel\" title=\"${buttonCancel}\" role=\"button\" tabIndex=\"-1\">\n\t\t\t<span data-dojo-attach-point=\"closeText\" class=\"closeText\" title=\"${buttonCancel}\">x</span>\n\t\t</span>\n\t</div>\n\t\n\t<div data-dojo-attach-point=\"containerNode\" class=\"dijitDialogPaneContent\">\n\t\t<img src=\"templates/themes/default/img/error5.png\">\n\t\t<div class=\"CommSyErrorDescription\">${!translations.ajaxErrorDescription}</div>\n\t\t<div class=\"clear\"></div>\n\t\t\n\t\t<div class=\"CommSyErrorMarginText\">${!translations.ajaxErrorAutoClose}(<span data-dojo-attach-point=\"secRemainingNode\"></span>)</div>\n\t</div>\n</div>"}});
define("commsy/widgets/ErrorDialog", [	"dojo/_base/declare",
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