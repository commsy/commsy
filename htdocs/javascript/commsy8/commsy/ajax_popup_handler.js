/**
 * Ajax Popup Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cid: null,
		tpl_path: '',

		init: function(commsy_functions, parameters) {
			this.cid = commsy_functions.getURLParam('cid');

			// set preconditions
			this.setPreconditions(commsy_functions, this.loadPopup, {handle: this, commsy_functions: commsy_functions, handling: parameters});
		},

		setPreconditions: function(commsy_functions, callback, parameters) {
			var preconditions = {
				template: ['tpl_path']
			};

			// register preconditions
			commsy_functions.registerPreconditions(preconditions, callback, parameters);
		},

		loadPopup: function(preconditions, parameters) {
			var commsy_functions = parameters.commsy_functions;
			var actors = parameters.handling.objects;
			var handle = parameters.handle;

			handle.tpl_path = preconditions.template.tpl_path;
			jQuery.each(actors, function() {
				// determ module from actor href
				var module = '';
				var regex = new RegExp("[\\?&]mod=([^&#]*)");
				var results = regex.exec(jQuery(this).attr('href'));
				if(results !== null && results[1] !== 'NEW') module = results[1];

				// determ item id from actor href
				var item_id = 'NEW';
				var regex = new RegExp("[\\?&]iid=([^&#]*)");
				var results = regex.exec(jQuery(this).attr('href'));
				if(results !== null && results[1] !== 'NEW') item_id = results[1];

				jQuery(this).bind('click', {
					commsy_functions:	commsy_functions,
					handle:				handle,
					actor:				jQuery(this),
					module:				module,
					item_id:			item_id}, handle.onClick);
			});
		},

		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;

			var data = {
				module: event.data.module,
				iid:	event.data.item_id
			};
			
			handle.ajaxRequest('getHTML', data, function(data) {
				if(data.status === 'success') {
					// we recieved html - append it
					jQuery('body').prepend(data.html);

					// reinvoke CKEditor
					var ck_editor_handler = commsy_functions.getModuleCallback('commsy/ck_editor');
					ck_editor_handler.create(null, {
						handle:				ck_editor_handler,
						register_on:		jQuery('div.ckeditor')
					});

					// setup popup
					handle.setupPopup(event.data.module, event.data.item_id);
				}
			});

			// stop processing
			return false;
		},

		close: function(event) {
			// unregister ck editor instances
			var editors = jQuery('div#popup_frame div.ckeditor');
			if(editors.length > 0) {
				editors.each(function() {
					jQuery(this).ckeditorGet().destroy();
				});
			}

			// remove popup html from dom
			jQuery('div[id="popup_wrapper"]').remove();
		},

		save: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			var item_id = event.data.item_id;
			
			// add ckeditor data to hidden div
			jQuery('div.ckeditor').each(function() {
				var editor = jQuery(this).ckeditorGet();
				jQuery(this).parent().children('input[type="hidden"]').attr('value', editor.getData());
			});
			
			// collect form data
			var form_objects = jQuery('div[id="popup_wrapper"] input[name^="form_data"],div[id="popup_wrapper"] select[name^="form_data"]');
			
			// build object
			var data = {
				form_data: [],
				module: module
			};
			jQuery.each(form_objects, function() {
				var add = false;

				// if form field is a checkbox, only add if checked
				if(jQuery(this).attr('type') === 'checkbox') {
					if(jQuery(this).attr('checked') === 'checked') {
						add = true;
					}
				}

				// if form fiel is a radio button, only add the selected one
				else if(jQuery(this).attr('type') === 'radio') {
					if(jQuery(this).attr('checked')	 === 'checked') {
						add = true;
					}
				}

				else {
					add = true;
				}

				if(add === true) {
					// extract name
					/form_data\[(.*)\]/.exec(jQuery(this).attr('name'));

					data.form_data.push({
						name:	RegExp.$1,
						value:	jQuery(this).attr('value')
					});
				}
			});

			// set item id
			data.form_data.push({
				name:	'iid',
				value:	item_id
			});
			
			// ajax request
			handle.ajaxRequest('save', data, function(data) {
				if(data.status === 'success') {
					handle.close();
				} else if(data.status === 'error' && data.code === 101) {
					// mandatory error
					var missing_fields = data.detail;
					
					// create a red border around the missing fields and scroll to first one
					jQuery.each(missing_fields, function(index, field_name) {
						jQuery.each(form_objects, function() {
							if(jQuery(this).attr('name') === 'form_data[' + field_name + ']') {
								jQuery(this).css('border', '1px solid red');
								
								if(index === 0 && !jQuery.inviewport(jQuery(this), {threshold: 0})) {
									jQuery('html, body').animate({scrollTop: jQuery(this).offset().top}, 500);
								}
							}
						});
					});
				} else {
					// unhandled error
					console.log('unhandled error');
				}
			});

			return false;
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
			var content_divs = jQuery('div[id="popup_tabcontent"] div[class^="settings_area"]');

			// set class for divs
			content_divs.each(function(i) {
				if(index === i) {
					jQuery(this).attr('class', 'settings_area');
				} else {
					jQuery(this).attr('class', 'settings_area hidden');
				}
			});

			return false;
		},

		setupPopup: function(module, item_id) {
			var handle = this;

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

			// register click for save button
			jQuery('input[id="popup_button_create"]').bind('click', {
				handle:		this,
				module:		module,
				item_id:	item_id}, this.save);

			// setup tabs
			this.setupTabs();
		},
		
		ajaxRequest: function(action, data, callback) {
			var handle = this;

			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=popup&action=' + action,
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				async: false,
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(callback !== null) {
						callback(data);
					}
				}
			});
		}
	};
});