var dojoConfig = {
	has: {
		"dojo-firebug":			true,
		"dojo-debug-messages":	true
	},
	baseUrl:					"/javascript/commsy8_dojo/",
	tlmSiblingOfDojo:			false,
	packages: [
	           					{ name: "dojo", location: "libs/dojo" },
	           					{ name: "dijit", location: "libs/dijit" },
	           					{ name: "dojox", location: "libs/dojox" },
	           					{ name: "commsy", location: "commsy"}
	]
}

require(["dojo/_base/declare"], function(declare) {
	var Controller = declare(null, {
		constructor: function(args) {
			
		},
		
		init: function() {
			require(["dojo/query", "dojo/domReady!"], function(query, ready) {
				
				// initiate popup handler
				require(["commsy/popups/room_configuration"], function(RoomConfigurationPopup) {
					var handler = new RoomConfigurationPopup(query("a#tm_settings"));
				});
			});
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});