define([	"dojo/_base/declare",
        	"commsy/popup_handler",
        	"dojo/topic",
        	"dojo/query",
        	"dojo/fx"], function(declare, PopupHandler, topic, query, fx) {
	return declare(PopupHandler, {
		constructor: function(node) {
			// register click for node
			this.registerPopupClick(node, "popup_room_configuration_click", { module: "configuration" } , function(html) {
				var node = query("div#tm_dropmenu_configuration")[0];
				
				// set html
				node.innerHTML = html;
				
				// wipe out
				fx.wipeIn({ node: query("div#popup_wrapper", node)[0] }).play();
			});
			
			// subscribe to event
			topic.subscribe("popup_room_configuration_click", this.onPopupOpenClick);
		},
		
		onPopupOpenClick: function() {
			console.log('event recieved');
		}
	});
});