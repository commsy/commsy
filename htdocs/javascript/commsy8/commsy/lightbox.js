/**
 * Lightbox Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.lightbox-0.5.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, parameters) {
			// set preconditions
			this.setPreconditions(commsy_functions, this.registerEvent, {objects: parameters});
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
				template: ['tpl_path']
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		registerEvent: function(preconditions, parameters) {
			var tpl_path = preconditions.template.tpl_path;
			
			var lightbox_objects = jQuery(parameters.objects);
			
			// group by item_id - class is lightbox_itemid
			var lightbox_ids= [];
			lightbox_objects.each(function() {
				var item_id = jQuery(this).attr('class').substr(9);
				
				if(jQuery.inArray(item_id, lightbox_ids) === -1) lightbox_ids.push(item_id);
			});
			
			// create lightbox instances for each group
			jQuery.each(lightbox_ids, function() {
				jQuery('a.lightbox_' + this).lightBox({
					fixedNavigation:	true,
					imageLoading:		tpl_path + 'img/lightbox/lightbox-ico-loading.gif',
					imageBtnClose:		tpl_path + 'img/lightbox/lightbox-btn-close.gif',
					imageBtnPrev:		tpl_path + 'img/lightbox/lightbox-btn-prev.gif',
					imageBtnNext:		tpl_path + 'img/lightbox/lightbox-btn-next.gif',
					imageBlank:			tpl_path + 'img/lightbox/lightbox-blank.gif'
				});
			});
		}
	};
});