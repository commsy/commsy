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
			
			jQuery(parameters.objects).each(function(index) {
				// lightbox
				jQuery(this).lightBox({
					imageLoading:	tpl_path + 'img/lightbox/lightbox-ico-loading.gif',
					imageBtnClose:	tpl_path + 'img/lightbox/lightbox-btn-close.gif',
					imageBtnPrev:	tpl_path + 'img/lightbox/lightbox-btn-prev.gif',
					imageBtnNext:	tpl_path + 'img/lightbox/lightbox-btn-next.gif'
				});
			});
		}
	};
});