define([	"dojo/_base/declare",
        	"commsy/popup_handler",
        	"dojo/topic",
        	"dojo/query",
        	"dojo/dom-class",
        	"dojo/_base/lang"], function(declare, PopupHandler, topic, query, dom_class, lang) {
	return declare(PopupHandler, {
		constructor: function(button_node, content_node) {
			this.popup_button_node = button_node;
			this.popup_content_node = content_node;
			
			// register click for node
			this.registerPopupClick("popup_room_configuration_click", { module: "configuration" });
			
			// subscribe to event
			topic.subscribe("popup_room_configuration_click", lang.hitch(this, this.onClickPopupOpen));
			
			this.setupSpecific();
		},
		
		onClickPopupOpen: function() {
			if(this.is_open === true) {
				dom_class.add(this.popup_button_node, "tm_settings_hover");
				dom_class.remove(this.popup_content_node, "hidden");
			} else {
				dom_class.remove(this.popup_button_node, "tm_settings_hover");
				dom_class.add(this.popup_content_node, "hidden");
			}
		},
		
		setupSpecific: function() {
			/*
			 * // reinvoke CKEditor
						var ck_editor_handler = handle.commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div.ckeditor')
						});
						
						// reinvoke Colorpicker
						var colorpicker_handler = handle.commsy_functions.getModuleCallback('commsy/colorpicker');
						colorpicker_handler.setup(null, {
							handle:				colorpicker_handler,
							register_on:		jQuery('input.colorpicker')
						});
						
						// register click for community room assign button
						jQuery('div#tm_dropmenu_configuration input#add_community_room').bind('click', {
							handle:		handle}, handle.onClickAssignCommunityRoom);
						
						// register click for additional status button
						jQuery('div#tm_dropmenu_configuration input#add_additional_status').bind('click', {
							handle:		handle}, handle.onClickAdditionalStatus);
						
						// register click for save buttons
						jQuery('div#tm_dropmenu_configuration input#submit').bind('click', {
							handle:		handle}, handle.onSaveConfiguration);
						
						// setup configuration popup
						handle.setupConfigurationPopup();
			 */
		}
	});
});