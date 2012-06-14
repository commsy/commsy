require(["dojo/_base/declare"], function(declare) {
	var Controller = declare(null, {
		constructor: function(args) {
			
		},
		
		init: function() {
			require([	"dojo/query",
			         	"dojo/domReady!"], function(query, ready) {
				
				// initiate popup handler
				require(["commsy/popups/room_configuration"], function(RoomConfigurationPopup) {
					var handler = new RoomConfigurationPopup(query("a#tm_settings")[0], query("div#tm_menus div#tm_dropmenu_configuration")[0]);
				});
				
				// tree
				query("div.tree").forEach(function(node, index, arr) {
					require(["commsy/tree"], function(Tree) {
						var handler = new Tree();
						handler.setupTree(node);
					});
				});
			});
		}
	});
	
	var ctrl = new Controller;
	ctrl.init();
});