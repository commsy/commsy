/**
 * Buzzword Popup Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cid: null,
		
		init: function(commsy_functions, parameters) {
			this.cid = commsy_functions.getURLParam('cid');
			
			// set preconditions
			this.setPreconditions(commsy_functions, this.loadPopup, {handle: this, commsy_functions: commsy_functions, handling: parameters});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		loadPopup: function(preconditions, parameters) {
			var commsy_functions = parameters.commsy_functions;
			var actors = parameters.handling.objects;
			var handle = parameters.handle;
			
			jQuery.each(actors, function() {
				jQuery(this).bind('click', {
					commsy_functions:	commsy_functions,
					handle:				handle,
					actor:				jQuery(this)}, handle.onClick);
			});
		},
		
		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;
			
			var data = {
				module:		'buzzwords',
				act_module:	commsy_functions.getURLParam('mod'),
				iid:		'NEW'
			};
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=getHTML',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// we recieved html - append it
						jQuery('body').prepend(data);
						
						// setup popup
						handle.setupPopup(event.data.module, event.data.item_id);
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		setupPopup: function(module, item_id) {
			var handle = this;
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
			// register click for close button
			jQuery('a[id="popup_close"]').click(function() {
				handle.close();
				return false;
			});
			
			// register click for abort button
			jQuery('input[id="popup_button_abort"]').click(function() {
				handle.close();
				return false;
			});
			
			// register click for buzzword create button
			jQuery('input#buzzword_create').bind('click', {
				handle:		this,
				module:		module}, this.newBuzzword);
			
			// register click for buzzword delete button
			jQuery('input#.buzzword_delete').bind('click', {
				handle:		this,
				module:		module}, this.deleteBuzzword);
			
			// register click for buzzword delete button
			jQuery('input#.buzzword_change').bind('click', {
				handle:		this,
				module:		module}, this.changeBuzzword);
			
			// setup tabs
			this.setupTabs();
		},
		
		fullSizeOverlay: function() {
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
		},
		
		close: function(event) {
			// unregister ck editor
			//var editor = jQuery('div[id="popup_ckeditor"]');
			//if(editor.length > 0) editor.ckeditorGet().destroy();
			
			// remove popup html from dom
			jQuery('div[id="popup_wrapper"]').remove();
		},
		
		setupTabs: function() {
			var handle = this;
			
			// register click for tabs
			jQuery('div[class="tab_navigation"] a').each(function(index) {
				jQuery(this).bind('click', {
					index:	index,
					handle:	handle}, handle.onClickTab);
			});
		},
		
		onClickTab: function(event) {
			var target = jQuery(event.currentTarget);
			var index = event.data.index;
			var handle = event.data.handle;
			
			// set all tabs inactive
			jQuery('div[class="tab_navigation"] a').each(function() {
				jQuery(this).attr('class', 'pop_tab');
			})
			
			// set target active
			target.attr('class', 'pop_tab_active');
			
			// switch display
			// get divs
			var content_divs = jQuery('div[id="popup_tabcontent"] div[class^="tab"]');
			
			// set class for divs
			content_divs.each(function(i) {
				if(index === i) {
					// remove hidden
					jQuery(this).removeClass('hidden');
				} else {
					// add hidden
					jQuery(this).addClass('hidden');
				}
			});
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
			return false;
		},
		
		newBuzzword: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			
			// check mandatory	
			if(handle.checkMandatory(jQuery('div[class="tab"] input[class~="mandatory"]'))) {
				// prepare data to send
				var data = {
					form_data: {
						buzzword:	jQuery('input#buzzword_create_name').attr('value')
					},
					module: module
				};

				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=buzzwords&action=create',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function(jqXHR, textStatus, errorThrown) {
						console.log("error while getting popup");
					},
					success: function(data, status) {
						if(status === 'success') {
							
						}
					}
				});
			}
		},
		
		deleteBuzzword: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			
			// get sender
			var sender = jQuery(event.target);
			
			// extract buzzword id
			var buzzword_id = sender.attr('name').substr(10, sender.attr('name').length - 11);
			
			// prepare data to send
			var data = {
				form_data: {
					buzzword_id:	buzzword_id
				},
				module: module
			};

			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=buzzwords&action=delete',
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						// animate away
						sender.parent().slideUp(function() {
							// remove from dom
							sender.parent().remove();
						});
					}
				}
			});
		},
		
		changeBuzzword: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			
			// get sender
			var sender = jQuery(event.target);
			
			// check mandatory			
			if(handle.checkMandatory(sender.parent().find('input[class~="mandatory"]'))) {
				// extract buzzword id
				var buzzword_id = sender.attr('name').substr(10, sender.attr('name').length - 11);
				
				// prepare data to send
				var data = {
					form_data: {
						buzzword_id:	buzzword_id,
						buzzword:		sender.parent().find('input.buzzword_change_name').attr('value')
					},
					module: module
				};

				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=buzzwords&action=change',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function(jqXHR, textStatus, errorThrown) {
						console.log("error while getting popup");
					},
					success: function(data, status) {
						if(status === 'success') {
							
						}
					}
				});
			}
		},
		
		checkMandatory: function(elements) {
			var check_passed = true;
			
			elements.each(function() {
				if(jQuery(this).val() === '') {
					if(check_passed === true) {
						// this is the first error
						// check if content is outside screen
						if(!jQuery.inviewport(jQuery(this), {threshold: 0})) {
							// scroll to target
							jQuery('html, body').animate({scrollTop: jQuery(this).offset().top}, 500);
						}
					}
					
					jQuery(this).css('border', '1px solid red');
					check_passed = false;
				}
			});
			
			return check_passed;
		}
	};
});