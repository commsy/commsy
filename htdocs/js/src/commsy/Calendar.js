define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dijit/form/DateTextBox",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, DateTextBox, DomAttr) {
	return declare(BaseClass, {
		calendar: null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			var dateFormat = '';
			if (this.from_php.environment.lang == "de") {
				dateFormat = "dd.MM.yyyy";
			} else if (this.from_php.environment.lang == "en") {
				dateFormat = "MM/dd/yyyy";
			}
			
			var value = DomAttr.get(node, "value");
			if (value === "") {
				value = null;
			}
			
			this.calendar = new DateTextBox({
				value:			value,
				name:			DomAttr.get(node, "name"),
				constraints: {
					datePattern:	dateFormat
				}
			}, node);
			this.calendar.startup();
		},
		
		destroy: function() {
			this.calendar.destroyRecursive(false);
		}
	});
});