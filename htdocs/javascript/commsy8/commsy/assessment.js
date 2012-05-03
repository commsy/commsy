/**
 * Assessment Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
        	"order!libs/jQuery_plugins/jquery.tools.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		init: function(commsy_functions, parameters) {
			parameters.handle = this;
			parameters.commsy_functions = commsy_functions;
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.setup, parameters);
		},
		
		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
				template: ['tpl_path']
			};
			
			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},
		
		setup: function(preconditions, parameters) {
			var handle = parameters.handle;
			var object = parameters.object;
			var commsy_functions = parameters.commsy_functions;
			
			// setup overlay
			object.tooltip({
				effect:		'slide',
				opacity:	0.95
			});
			
			// setup vote function
			if(object.hasClass('rateable')) {
				var stars_objects = object.find('img');
				
				var old_status = [];
				stars_objects.each(function(index) {
					// store old status
					old_status[index] = jQuery(this).attr('src');
					
					// register mouseover
					jQuery(this).mouseover(function() {
						// set all stars up to the hovered one to full stars
						jQuery(this).prevAll().andSelf().attr('src', preconditions.template.tpl_path + 'img/star_selected.gif');
					});
					
					// register click
					jQuery(this).click(function() {
						// perform ajax call to register vote
						var data = {
							item_id:	commsy_functions.getURLParam('iid'),
							vote:		index + 1
						};
						
						jQuery.ajax({
							type: 'POST',
							url: 'commsy.php?cid=' + commsy_functions.getURLParam('cid') + '&mod=ajax&fct=assessment&action=vote',
							data: JSON.stringify(data),
							contentType: 'application/json; charset=utf-8',
							dataType: 'json',
							error: function() {
								console.log("error while sending ajax request - assessment.js");
							},
							success: function(data, status) {
								// reload
								// TODO: implement without
								location.reload();
							}
						});
					});
				});
				
				// register mouseout
				object.mouseout(function() {
					// set all stars to there previous state
					jQuery(this).children().each(function(index) {
						jQuery(this).attr('src', old_status[index]);
					});
				});
			}
			
			// register delete function
			jQuery('a#assessment_delete_own').click(function() {
				// perform ajax call to delete own voting
				var data = {
					item_id:	commsy_functions.getURLParam('iid')
				};
				
				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + commsy_functions.getURLParam('cid') + '&mod=ajax&fct=assessment&action=deleteOwn',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function() {
						console.log("error while sending ajax request - assessment.js");
					},
					success: function(data, status) {
						// reload
						// TODO: implement without
						location.reload();
					}
				});
			});
		}
	};
});