/**
 * Ajax Popup Handler Module
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
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=rubric_popup&action=getHTML',
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
						
						// reinvoke Uploadify
						var uploadify_handler = commsy_functions.getModuleCallback('commsy/uploadify');
						uploadify_handler.create(null, {
							object:				jQuery('input[id="uploadify"]'),
							handle:				uploadify_handler,
							commsy_functions:	commsy_functions,
							upload_object:		jQuery('a[id="uploadify_doUpload"]'),
							clear_object:		jQuery('a[id="uploadify_clearQuery"]')
						});
						
						// reinvoke CKEditor
						var ck_editor_handler = commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div[id="popup_ckeditor"]'),
							input_object:		jQuery('input[id="popup_ckeditor_content"]')
						});
						
						// reinvoke TagTree
						var tag_tree_handler = commsy_functions.getModuleCallback('commsy/tag_tree');
						tag_tree_handler.buildTree(null, {
							object:				jQuery('div[id="tag_tree"]')
						});
						
						var dynatree = jQuery('div[id="tag_tree"]').dynatree('getTree');
						
						if(typeof(dynatree['$tree']) !== 'undefined') {
							// expand all nodes and add checkbox inputs
							dynatree.visit(function(node) {
								node.expand(true);
								
								// check bold
								if(node.data.title.substr(0, 3) === '<b>') {
									node.data.title = '<input type="checkbox" checked="checked"/><span>' + node.data.title + '</span>';
								} else {
									node.data.title = '<input type="checkbox"/><span>' + node.data.title + '</span>';
								}
								
								// re-render
								node.render();
							});
							
							// override onclick
							dynatree.options.onClick = function(node, event) {
								// toggle bold
								/<span>(.*)<\/span>/.exec(node.data.title);
								var title = RegExp.$1;
								console.log('title');
								// check bold
								if(node.data.title.substr(0, 3) === '<b>') {
									// remove
									node.data.title = '<input type="checkbox"/><span>' + title + '</span>';
								} else {
									// add
									node.data.title = '<b><input type="checkbox" checked="checked"/><span>' + title + '</span></b>';
								}
								
								// re-render
								node.render();
								
								return false;
							}
						}
						
						// setup popup
						handle.setupPopup(event.data.module, event.data.item_id);
					}
				}
			});
			
			// stop processing
			return false;
		},
		
		close: function(event) {
			// unregister ck editor
			var editor = jQuery('div[id="popup_ckeditor"]');
			if(editor.length > 0) editor.ckeditorGet().destroy();
			
			// remove popup html from dom
			jQuery('div[id="popup_wrapper"]').remove();
		},
		
		save: function(event) {
			var handle = event.data.handle;
			var module = event.data.module;
			var item_id = event.data.item_id;
			
			// check mandatory
			var check_passed = true;
			jQuery('input[class~="mandatory"]').each(function() {
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
			
			if(check_passed) {
				// collect form data
				var form_objects = jQuery('div[id="popup_wrapper"] input[name^="form_data"]');

				// set description data
				var editor = jQuery('div[id="popup_ckeditor"]');
				if(editor.length > 0) {
					jQuery('input[name="form_data[description]"]').val(editor.ckeditorGet().getData());
				}

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
				
				// add buzzword data
				var buzzword_objects = jQuery('ul[id="buzzwords_assigned"] li[id^="buzzword_"]');
				var buzzword_ids = [];
				jQuery.each(buzzword_objects, function() {
					// extract buzzword id
					/buzzword_([0-9]*)/.exec(jQuery(this).attr('id'));
					buzzword_ids.push(RegExp.$1);
				});
				data.form_data.push({
					name:	'buzzwords',
					value:	buzzword_ids
				});
				
				// add tag data
				var dynatree = jQuery('div[id="tag_tree"]').dynatree('getTree');
				var tag_ids  = [];
				
				if(dynatree['$tree'] !== 'undefined') {
					dynatree.visit(function(node) {
						// check if bold
						if(node.data.title.substr(0, 3) === '<b>') {
							// separte tag id
							var tag_id = node.data.key.substr(5);

							tag_ids.push(tag_id);
						}
					});
				}
				
				data.form_data.push({
					name:	'tags',
					value:	tag_ids
				});
				
				
				// set item id
				data.form_data.push({
					name:	'iid',
					value:	item_id
				});
				
				// add files data
				var file_objects = jQuery('div[id="popup_wrapper"] input[name="filelist[]"]');
				var file_ids = [];
				jQuery.each(file_objects, function() {
					file_ids.push(jQuery(this).attr('value'));
				})
				data.form_data.push({
					name:	'files',
					value:	file_ids
				});
				
				// ajax request
				jQuery.ajax({
					type: 'POST',
					url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=rubric_popup&action=save',
					data: JSON.stringify(data),
					contentType: 'application/json; charset=utf-8',
					dataType: 'json',
					error: function() {
						console.log("error while processing popup action");
					},
					success: function(data, status) {
						handle.close();
						
						// page reload
						location.reload();
						//handle.preconditionsSuccess(data);
					}
				});
			}
			
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
		
		setupBuzzwords: function() {
			// unassigned
			jQuery('div[id="popup"] ul[id="buzzwords_unassigned"]').sortable({
				connectWith:	'ul',
				placeholder:	'ui-state-highlight',
				cursor:			'pointer',
				change:			function(event, ui) {
					if(ui.sender !== null) {
						// adjust
						ui.item.find('img').attr('alt', 'add');
					}
				}
			});
			
			// assigned
			jQuery('div[id="popup"] ul[id="buzzwords_assigned"]').sortable({
				connectWith:	'ul',
				placeholder:	'ui-state-highlight',
				cursor:			'pointer',
				change:			function(event, ui) {
					if(ui.sender !== null) {
						// adjust
						ui.item.find('img').attr('alt', 'remove');
					}
				}
			});
			
			// register add event
			jQuery('ul[id^="buzzwords_"] img').each(function() {
				jQuery(this).click(function() {
					var li = jQuery(this).parent().parent();
					
					// get ul id
					var ul_id = li.parent().attr('id');
					
					// detach
					li = li.detach();
					
					if(ul_id === 'buzzwords_unassigned') {
						// append to assigned
						li.appendTo(jQuery('ul[id="buzzwords_assigned"]'));
						
						// adjust
						li.find('img').attr('alt', 'remove');
					} else {
						// append to unassigned
						li.appendTo(jQuery('ul[id="buzzwords_unassigned"]'));
						
						// adjust
						li.find('img').attr('alt', 'add');
					}
				});
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
			
			// fullsize black overlay
			handle.fullSizeOverlay();
			
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
			
			// register click for save button
			jQuery('input[id="popup_button_create"]').bind('click', {
				handle:		this,
				module:		module,
				item_id:	item_id}, this.save);
			
			// setup buzzwords
			this.setupBuzzwords();
			
			// setup netnavigation
			this.setupNetnavigation(handle, item_id, module);
			
			// setup tabs
			this.setupTabs();
		},
		
		setupNetnavigation: function(handle, item_id, module) {
			// init netnavigation class
			var netnavigation = new Netnavigation();
			netnavigation.init(handle.cid, item_id, module);
		},
		
		fullSizeOverlay: function() {
			var overlay = jQuery('div[id="popup_background"]');
			overlay.css('height', jQuery(document).height());
			overlay.css('width', jQuery(document).width());
		}
	};
});

/* Netnavigation Class */
var Netnavigation = function() {
	return {
		cid: null,
		item_id: null,
		module: null,
		initialized: false,
		paging: {
			current: 0
		},
		restrictions: {
			search:			'',
			rubric:			'all',
			type:			2,
			only_linked:	false
		},
		store: {
			pages:			1
		},
		
		init: function(cid, item_id, module) {
			this.cid = cid;
			this.item_id = item_id;
			this.module = module;
			
			var handle = this;
			
			// register onclick handler
			jQuery('#popup_netnavigation_attach_new').click(function() {
				// scroll in/out
				var animate_object = jQuery('#popup_netnavigation');
				
				if(animate_object.css('width') !== '0px') {
					// scroll in
					jQuery('#popup_netnavigation').animate({
						width:			'0px',
						'margin-left':	'-19px'
					});
				} else {
					// get inital data if this is the first call
					if(handle.initialized === false) {
						handle.ajaxRequest('getInitialData', {module: this.module}, function(data) {
							// init rubric select box
							var select_object = jQuery('select[name="netnavigation_rubric_restriction"]');
							jQuery.each(data.rubrics, function() {
								select_object.append(jQuery('<option/>', {
									value:		this.value,
									text:		this.text,
									disabled:	this.disabled
								}));
							});
							
							// setup paging
							handle.setupPaging();
							
							// setup restrictions
							handle.setupRestrictions();
							
							// setup form submit
							jQuery('input[name="netnavigation_submit_restrictions"]').click(function() {
								handle.performRequest();
								
								return false;
							});
							
							// perform first request
							handle.performRequest();
							
							handle.initialized = true;
						});
					}
					
					// scroll out
					jQuery('#popup_netnavigation').animate({
						width:			'739px',
						'margin-left':	'-758px'
					});
				}
			});
		},
		
		setupRestrictions: function() {
			var content_object = jQuery('.pop_item_content');
			var handle = this;
			
			// type restriction
			content_object.find('select[name="netnavigation_type_restriction"]').change(function(event) {
				handle.restrictions.type = jQuery(event.target).val();
				
				return false;
			});
			
			// rubric restriction
			content_object.find('select[name="netnavigation_rubric_restriction"]').change(function(event) {
				handle.restrictions.rubric = jQuery(event.target).val();
				
				return false;
			});
			
			// search restriction
			content_object.find('input[name="netnavigation_search_restriction"]').change(function(event) {
				handle.restrictions.search = jQuery(event.target).val();
				
				return false;
			});
			
			// linked restriction
			content_object.find('input[name="netnavigation_linked_restriction"]').change(function(event) {
				handle.restrictions.only_linked = (jQuery(event.target).val() == 'true') ? true : false;
				
				return false;
			});
		},
		
		setupPaging: function() {
			var navigation_object = jQuery('.pop_item_navigation');
			var handle = this;
			
			// first
			navigation_object.children('#first').click(function() {
				if(handle.paging.current > 0) {
					handle.paging.current = 0;
					handle.performRequest();
				}
				
				return false;
			});
			
			// previous
			navigation_object.children('#prev').click(function() {
				if(handle.paging.current > 0) {
					handle.paging.current -= 1;
					handle.performRequest();
				}
				
				return false;
			});
			
			// next
			navigation_object.children('#next').click(function() {
				if(handle.paging.current + 1 < handle.store.pages) {
					handle.paging.current += 1;
					handle.performRequest();
				}
				
				return false;
			})
			
			// last
			navigation_object.children('#last').click(function() {
				if(handle.paging.current + 1 < handle.store.pages) {
					handle.paging.current = handle.store.pages - 1;
					handle.performRequest();
				}
				
				return false;
			})
		},
		
		performRequest: function() {			
			var handle = this;
			
			// create data object for request
			var data = {
				item_id:		this.item_id,
				module:			this.module,
				current_page:	this.paging.current,
				restrictions:	this.restrictions
			};
			
			// send request
			this.ajaxRequest('performRequest', data, function(ret) {
				var content_object = jQuery('#popup_netnavigation #crt_row_area');
				
				// fill list
				content_object.empty();
				
				jQuery.each(ret.list, function(index) {
					content_object.append(
						jQuery('<div/>', {
							'class':	(index % 2 === 0) ? 'pop_row_even' : 'pop_row_odd'
						}).append(
							jQuery('<div/>', {
								'class':	'pop_col_25'
							}).append(
								jQuery('<input/>', {
									type:		'checkbox',
									id:			'linked_' + this.item_id,
									checked:	this.checked
								})
							)
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_220',
								text:		this.title
							})
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_90',
								text:		this.modification_date
							})
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_150',
								text:		this.modificator
							})
						).append(
							jQuery('<div/>', {
								'class':	'clear'
							})
						)
					);
				});
				
				// register checkbox events - unregistering is done by jQuery when empty the content object
				content_object.find('input[type="checkbox"]').each(function() {
					var row_object = jQuery(this).parentsUntil('div[class^="pop_row_"]').parent();
					var old_bg_color = row_object.css('background-color');
					
					jQuery(this).change(function(event) {
						var data = {
							item_id:	handle.item_id,
							link_id:	jQuery(event.target).attr('id').substr(7),
							checked:	(jQuery(this).attr('checked') === 'checked') ? true : false
						};
						
						// save old row background color and set new
						row_object.css('background-color', '#66CC00');
						
						handle.ajaxRequest('updateLinkedItem', data, function() {
							// fade back to old row color
							row_object.animate({
								'background-color':	old_bg_color
							});
						});
					});
				});
				
				// update current page and total number of pages
				jQuery('#pop_item_current_page').text(handle.paging.current + 1);
				jQuery('#pop_item_pages').text(ret.paging.pages);
				
				// store pages
				handle.store.pages = ret.paging.pages;
			});
		},
		
		ajaxRequest: function(action, data, callback) {
			var handle = this;
			
			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=netnavigation&action=' + action,
				data: JSON.stringify(data),
				contentType: 'application/json; charset=utf-8',
				dataType: 'json',
				async: false,
				error: function(jqXHR, textStatus, errorThrown) {
					console.log("error while getting popup");
				},
				success: function(data, status) {
					if(status === 'success') {
						if(callback !== null) {
							callback(data);
						}
						
						return data;
					}
				}
			});
		}
	}
};