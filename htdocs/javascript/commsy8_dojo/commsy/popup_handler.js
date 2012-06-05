define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/_base/lang"], function(declare, BaseClass, on, topic, lang) {
	return declare(BaseClass, {
		constructor: function(args) {
			
		},
		
		registerPopupClick: function(node, publish_topic, data, callback) {
			on(node, "click", lang.hitch(this, function(event) {
				// setup ajax request for getting html
				this.getHTMLFromAJAX("popup", "getHTML", data, callback);
				
				// publish
				topic.publish(publish_topic);
				
				event.preventDefault();
			}));
		}
	});
});