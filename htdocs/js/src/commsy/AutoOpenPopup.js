define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/query"], function(declare, BaseClass, Query) {
	return declare(BaseClass, {
		constructor: function(options) {
			options = options || {};
			declare.safeMixin(this, options);
		},
		
		setup: function() {
			// check for entry in from_php
			var openPopup = this.from_php.autoOpenPopup;
			if (openPopup) {
				if (openPopup.popup == "tm_settings") {
					this.openRoomConfiguration(openPopup.parameters);
				}
			    // check for entry in from_php_portal
				if (openPopup.popup == "tm_user") {
					this.openUserConfiguration(openPopup.parameters);
				}
			}

			if (typeof this.uri_object.commsy_bar_open != "undefined") {
				var popup = this.uri_object.commsy_bar_open;
				var nodeElement = Query("a#" + popup)[0];
				if (nodeElement) {
					nodeElement.click();
				}
			}	
		},
		
		openRoomConfiguration: function(parameters) {
			// open popup
			Query("a#tm_settings")[0].click();
		},
		
		openUserConfiguration: function(parameters) {
			// open popup
			Query("a#tm_user")[0].click();
		}
	});
});