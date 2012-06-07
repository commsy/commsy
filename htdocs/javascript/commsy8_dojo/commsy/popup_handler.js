define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/on",
        	"dojo/topic",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/dom-attr"], function(declare, BaseClass, on, topic, lang, query, dom_class, dom_attr) {
	return declare(BaseClass, {
		is_loaded:				false,
		is_open:				false,
		popup_button_node:		null,
		popup_content_node:		null,
		
		constructor: function(args) {
			
		},
		
		registerPopupClick: function(publish_topic, data) {
			on(this.popup_button_node, "click", lang.hitch(this, function(event) {
				if(this.is_loaded === false) {
					// setup ajax request for getting html
					this.getHTMLFromAJAX("popup", "getHTML", data, lang.hitch(this, function(html) {
						// append html to node
						this.popup_content_node.innerHTML = html;
						
						this.setupTabs();
						
						// register close
						on(query("a#popup_close", this.popup_content_node), "click", lang.hitch(this, function(event) {
							this.close(publish_topic);
							
							event.preventDefault();
						}));
					}));
					
					this.is_loaded = true;
				}
				
				this.is_open = !this.is_open;
				
				// publish
				topic.publish(publish_topic);
				
				event.preventDefault();
			}));
		},
		
		setupTabs: function() {
			var link_nodes = query("div.tab_navigation a", this.popup_content_node);
			var content_nodes = query("div#popup_tabcontent div[class^='tab']");
			
			// register click event for all tabs
			on(link_nodes, "click", lang.hitch(this, function(event) {
				// set all tabs inactive
				link_nodes.forEach(function(node) {
					dom_class.add(node, "pop_tab");
				});
				
				// set clicked active
				dom_class.replace(event.target, "pop_tab_active", "pop_tab");
				
				/* switch content */
				// set classes for divs
				content_nodes.forEach(function(node) {
					if(dom_attr.get(event.target, "href") === dom_attr.get(node, "id")) {
						dom_class.remove(node, "hidden");
					} else {
						dom_class.add(node, "hidden");
					}
				});
				
				event.preventDefault();
			}));
		},
		
		close: function(publish_topic) {
			// set closed
			this.is_open = false;
			
			// publish event to child class
			topic.publish(publish_topic);
		}
	});
});