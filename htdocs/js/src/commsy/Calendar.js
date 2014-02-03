define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dijit/form/DateTextBox",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, DateTextBox, DomAttr) {
	return declare(BaseClass, {
		calendar: null,
		
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			if (this.from_php.environment.lang == "de") {
				var dateFormat = "dd.MM.yyyy";
				var loc = "de-de";
			} else if (this.from_php.environment.lang == "en") {
				var dateFormat = "MM/dd/yyyy";
				var loc = "en-en";
			}
			
			/*
			dojo.declare("CustomDateTextBox", DateTextBox, {
		        Customformat: {selector: 'date', datePattern: dateFormat, locale: loc},
		        //value: "", // prevent parser from trying to convert to Date object
		        postMixInProperties: function(){ // change value string to Date object
		            this.inherited(arguments);
		            // convert value to Date object
		            this.value = dojo.date.locale.parse(this.value, this.Customformat);
		        },
		        // To write back to the server in own format, override the serialize method:
		        serialize: function(dateObject, options){
		            return dojo.date.locale.format(dateObject, this.Customformat).toUpperCase();
		        }
		    });
			*/
			
			var value = DomAttr.get(node, "value");
			if (value === "") {
				value = null;
			}
			
			this.calendar = new /*Custom*/DateTextBox({
				value:			value,
				name:			DomAttr.get(node, "name"),
				constraints: {
					datePattern:	dateFormat
				}
			}, node);
		},
		
		destroy: function() {
			this.calendar.destroyRecursive(false);
		}
	});
});