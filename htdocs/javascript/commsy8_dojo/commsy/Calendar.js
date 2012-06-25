define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dijit/form/DateTextBox",
        	"dojo/dom-attr"], function(declare, BaseClass, lang, Query, DateTextBox, DomAttr) {
	return declare(BaseClass, {
		calendar: null,
		
		constructor: function(options) {
			declare.safeMixin(this, options);
		},
		
		setup: function(node) {
			dojo.declare("CustomDateTextBox", DateTextBox, {
		        Customformat: {selector: 'date', datePattern: 'dd.MM.yyyy', locale: 'de-de'},
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
			
			this.calendar = new CustomDateTextBox({
				value:			DomAttr.get(node, "value"),
				name:			DomAttr.get(node, "name"),
				constraints: {
					datePattern:	"dd.MM.yyyy",
				}
			}, node);
		},
		
		destroy: function() {
			this.calendar.destroyRecursive(false);
		}
	});
});