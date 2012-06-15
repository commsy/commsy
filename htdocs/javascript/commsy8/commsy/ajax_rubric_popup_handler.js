/**
 * Ajax Rubric Popup Handler Module
 */

define([	"order!libs/jQuery/jquery-1.7.1.min",
			"order!libs/jQuery_plugins/jquery.viewport.mini",
			"order!libs/jQuery/jquery-ui-1.8.17.custom.min",
			"order!libs/jQuery_plugins/jquery.form",
        	"commsy/commsy_functions_8_0_0"], function() {
	return {
		cid: null,
		tpl_path: '',
		netnavigation: null,
		path: null,
		uploaded: false,

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
				
				// determ ref item id from actor href
				var ref_item_id = '';
				var regex = new RegExp("[\\?&]ref_iid=([^&#]*)");
				var results = regex.exec(jQuery(this).attr('href'));
				if(results !== null && results[1] !== 'NEW') ref_item_id = results[1];

				jQuery(this).bind('click', {
					commsy_functions:	commsy_functions,
					handle:				handle,
					actor:				jQuery(this),
					module:				module,
					item_id:			item_id,
					ref_iid:			ref_item_id}, handle.onClick);
			});
		},

		onClick: function(event) {
			var commsy_functions = event.data.commsy_functions;
			var handle = event.data.handle;

			var data = {
				module: 	event.data.module,
				iid:		event.data.item_id,
				ref_iid:	event.data.ref_iid
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
					if(data.status === 'success') {
						// we recieved html - append it
						jQuery('body').prepend(data.html);

						// reinvoke Uploadify
						var uploadify_handler = commsy_functions.getModuleCallback('commsy/uploadify');
						uploadify_handler.create(null, {
							object:				jQuery('input[id="uploadify"]'),
							handle:				uploadify_handler,
							commsy_functions:	commsy_functions,
							upload_object:		jQuery('a[id="uploadify_doUpload"]'),
							clear_object:		jQuery('a[id="uploadify_clearQuery"]'),
							onAllComplete:	handle.onUploadifyAllComplete
						});

						// reinvoke CKEditor
						var ck_editor_handler = commsy_functions.getModuleCallback('commsy/ck_editor');
						ck_editor_handler.create(null, {
							handle:				ck_editor_handler,
							register_on:		jQuery('div.ckeditor')
						});
						
						// reinvoke Datepicker
						var datepicker_handler = commsy_functions.getModuleCallback('commsy/datepicker');
						datepicker_handler.setup(null, {
							handle:				datepicker_handler,
							register_on:		jQuery('input.datepicker')
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
								if(node.getEventTargetType(event) == null) {
									// toggle bold
									/<span>(.*)<\/span>/.exec(node.data.title);
									var title = RegExp.$1;
									
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

									// prevent default processing
									return false;
								}
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

			// add buzzword data
			var buzzword_objects = jQuery('ul.popup_buzzword_list li');
			var buzzword_ids = [];
			jQuery.each(buzzword_objects, function() {
				// check if input is checked
				var input_object = jQuery(this).find('input[type="checkbox"]');

				if(input_object.attr('checked') === 'checked') {
					// extract buzzword id
					/buzzword_([0-9]*)/.exec(jQuery(this).attr('id'));
					buzzword_ids.push(RegExp.$1);
				}
			});
			data.form_data.push({
				name:	'buzzwords',
				value:	buzzword_ids
			});

			// add tag data
			var dynatree = jQuery('div[id="tag_tree"]').dynatree('getTree');
			var tag_ids  = [];

			if(typeof(dynatree['$tree']) !== 'undefined') {
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
					if(data.status === 'success') {
						// invoke netnavigation - process after item creation actions
						if(item_id == 'NEW') {
							handle.netnavigation.afterItemCreation(data.item_id);
						}
						
						// submit path
						if(handle.path !== null) {
							handle.path.save(data.item_id);
						}
						
						// submit picture
						var form_object = jQuery('form#picture_upload');
						
						if(form_object.find('input[type="file"]').length > 0) {
							if(form_object.find('input[type="file"]').attr('value') !== '') {
								handle.uploadPicture(form_object, data.item_id);
							} else {
								handle.close();
								
								handle.reload(data.item_id);
							}
						} else {
							handle.close();
							
							handle.reload(data.item_id);
						}
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
				}
			});

			return false;
		},
		
		reload: function(item_id) {
			// page reload
			var fct = '';
			var regex = new RegExp("[\\?&]fct=([^&#]*)");
			var results = regex.exec(location.href);
			if(results !== null) fct = results[1];

			var module = '';
			var regex = new RegExp("[\\?&]mod=([^&#]*)");
			var results = regex.exec(location.href);
			if(results !== null && results[1] !== 'NEW') module = results[1];

			location.href = 'commsy.php?cid=' + this.cid + '&mod=' + module + '&fct=detail&iid=' + item_id;
		},
		
		uploadPicture: function(form_object, item_id) {
			var handle = this;
			
			jQuery('input#upload_hidden_iid').val(item_id);
			
			// setup ajax form
			form_object.ajaxForm();
			
			// submit form
			form_object.ajaxSubmit({
				type:		'POST',
				success:	function() {
					handle.reload(item_id);
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
			
			// setup path
			if(jQuery('a#popup_path_tab').length > 0) this.setupPath(handle, item_id);

			// setup netnavigation
			this.setupNetnavigation(handle, item_id, module);
			
			// this will setup some special things for certain modules
			if(module === 'material') handle.setupMaterialPopup();

			// register click for save button
			jQuery('input[id="popup_button_create"]').bind('click', {
				handle:		this,
				module:		module,
				item_id:	item_id}, this.onClickSave);

			// setup tabs
			this.setupTabs();
		},
		
		onClickSave: function(event) {
			// check if uploadify queue is empty
			var queue_length = jQuery('div#uploadifyQueue').children().length;
			
			if(queue_length == 0 || event.data.handle.uploaded == true) {
				event.data.handle.uploaded = false;
				
				// if queue is empty - save item
				event.data.handle.save(event);
			} else {
				var uploadify = jQuery('input#uploadify');
				
				// first upload files - then save
				uploadify.uploadifyUpload();
				
				event.data.handle.uploaded = true;
			}
		},
		
		onUploadifyAllComplete: function() {
			jQuery('input#popup_button_create').click();
		},
		
		setupMaterialPopup: function() {
			var handle = this;
			
			/* setup bibliographic form elements */
			// get value from active bibliographic option
			var select_object = jQuery('select#bibliographic_select');
			
			// show / hide bibliographic div's
			handle.showHideBibliographic(select_object);
			
			// register handler for select
			select_object.change(function() {
				// show / hide bibliographic div's
				handle.showHideBibliographic(select_object);
			});
		},
		
		showHideBibliographic: function(select_object) {
			var key = select_object.children('option:selected').val();
			
			// go through all bibliographic content div's and show the one who's id matches "bib_content_" + key
			jQuery('div#bibliographic div[id^="bib_content_"]').each(function() {
				if(jQuery(this).attr('id') === 'bib_content_' + key) {
					jQuery(this).show();
					
					// go through each input field and change the name, if needed, so they will be submitted again
					jQuery(this).find('input, select').each(function() {
						if(jQuery(this).attr('name').substr(0, 14) === 'do_not_submit_') {
							jQuery(this).attr('name', jQuery(this).attr('name').substr(14));
						}
					});
				} else {
					jQuery(this).hide();
					
					// go through each input field and change the name, if needed, so they won't be submitted
					jQuery(this).find('input, select').each(function() {
						if(jQuery(this).attr('name').substr(0, 14) !== 'do_not_submit_') {
							jQuery(this).attr('name', 'do_not_submit_' + jQuery(this).attr('name'));
						}
					});
				}
			});
		},

		setupNetnavigation: function(handle, item_id, module) {
			// init netnavigation class
			this.netnavigation = new Netnavigation();
			this.netnavigation.init(handle.cid, item_id, module, this.tpl_path);
		},
		
		setupPath: function(handle, item_id) {
			// init path class
			this.path = new Path();
			this.path.init(handle.cid, item_id, this.tpl_path);
		}
	};
});

/* Path Class */
var Path = function() {
	return {
		cid:				null,
		item_id:			null,
		tpl_path:			'',
		
		init: function(cid, item_id, tpl_path) {
			this.cid = cid;
			this.item_id = item_id;
			this.tpl_path = tpl_path;
			
			var handle = this;
			
			// register onclick handler
			jQuery('a#popup_path_tab').click(function() {
				var list = jQuery('ul#popup_path_list');
				
				// get all connected entries for this item
				if(item_id !== 'NEW') {
					handle.ajaxRequest('getConnectedEntries', {item_id: item_id}, function(data) {
						// clear list
						list.children().remove();
						
						// append items to list
						jQuery.each(data, function() {
							list.append(
								jQuery('<li/>', {
									'class':	'netnavigation'
								}).append(
									jQuery('<input/>', {
										type:		'checkbox',
										id:			'path_' + this.linked_id,
										checked:	this.path_active
									})
								).append(
									jQuery('<img/>', {
										src:	handle.tpl_path + 'img/netnavigation/' + this.img
									})
								).append(
									jQuery('<span/>', {
										text:		this.text
									})
								)
							);
						});
					});
				}
				
				// setup sortable
				list.sortable({
					placeholder:	'ui-state-highlight'
				});
			});
		},
		
		save: function(item_id) {
			var request_item_id = this.item_id;
			if(typeof(item_id) !== 'undefined') request_item_id = item_id; 
			
			// collect data
			var ids = [];
			jQuery('ul#popup_path_list input[type="checkbox"]:checked').each(function() {
				// extract item id
				var id = '';
				var regex = new RegExp("path_(.*)");
				var results = regex.exec(jQuery(this).attr('id'));
				if(results !== null) id = results[1];
				
				ids.push(id);
			});
			
			this.ajaxRequest('savePath', {
				item_id:	request_item_id,
				linked_ids:	ids}, function() {
					
				});
		},
		
		ajaxRequest: function(action, data, callback) {
			var handle = this;

			jQuery.ajax({
				type: 'POST',
				url: 'commsy.php?cid=' + handle.cid + '&mod=ajax&fct=path&action=' + action,
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
}

/* Netnavigation Class */
var Netnavigation = function() {
	return {
		cid: 						null,
		item_id: 					null,
		module: 					null,
		tpl_path: 					'',
		initialized: 				false,
		paging: {
			current: 0
		},
		restrictions: {
			search:					'',
			rubric:					'all',
			type:					2,
			only_linked:			false
		},
		store: {
			pages:					1,
			selected:				0,
			after_item_creation:	[]
		},

		init: function(cid, item_id, module, tpl_path) {
			this.cid = cid;
			this.item_id = item_id;
			this.module = module;
			this.tpl_path = tpl_path;

			var handle = this;

			// register onclick handler
			jQuery('#popup_netnavigation_attach_new').click(function() {
				// scroll in/out
				var animate_object = jQuery('#popup_netnavigation');
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
							
							// reset paging
							handle.paging.current = 0;
							handle.performRequest();

							return false;
						});

						// perform first request
						handle.performRequest();

						handle.initialized = true;
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
				handle.restrictions.only_linked = (event.target.checked == true) ? true : false;

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
			
			var module = this.module;

			// send request
			this.ajaxRequest('performRequest', data, function(ret) {
				var content_object = jQuery('#popup_netnavigation #crt_row_area');

				// fill list
				content_object.empty();
				
				jQuery.each(ret.list, function(index) {
					// if current module is of type user, deactivate selection for "All Members"(system label) entries
					var disabled = false;
					if(module === 'user' && this.system_label === true) {
						disabled = true;
					}
					
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
									checked:	this.checked,
									disabled:	disabled
								})
							)
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_270',
								text:		this.title
							})
						).append(
							jQuery('<div/>', {
								'class':	'pop_col_150',
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

				// update selected
				handle.store.selected = ret.num_selected_total;
				jQuery('span#pop_item_entries_selected').text(handle.store.selected);

				// register checkbox events - unregistering is done by jQuery when empty the content object
				content_object.find('input[type="checkbox"]').each(function() {
					var row_object = jQuery(this).parentsUntil('div[class^="pop_row_"]').parent();
					var old_bg_color = row_object.css('background-color');

					jQuery(this).change(function(event) {
						var checked = (jQuery(this).attr('checked') === 'checked') ? true : false;
						var linked_id = jQuery(event.target).attr('id').substr(7);

						var data = {
							item_id:	handle.item_id,
							link_id:	linked_id,
							checked:	checked
						};

						jQuery('span#pop_item_entries_selected').text(handle.store.selected);

						// save old row background color and set new
						row_object.css('background-color', '#D1D1D1');

						handle.ajaxRequest('updateLinkedItem', data, function(ret) {
							// fade back to old row color
							row_object.animate({
								'background-color':	old_bg_color
							});

							if(checked === true) {
								// on check
								handle.store.selected++;

								var text = ret.linked_item.link_text;

								// add related entry to right box list
								jQuery('div#netnavigation_list ul').prepend(
									jQuery('<li/>', {
										'class':	'netnavigation',
										id:			'item_' + linked_id
									}).append(
										jQuery('<a/>', {
											target:	'_self',
											href:	'commsy.php?cid=' + handle.cid + '&mod=' + ret.linked_item.module + '&fct=detail&iid=' + ret.linked_item.linked_iid,
											title:	ret.linked_item.title
										}).append(
											jQuery('<img/>', {
												src:	handle.tpl_path + 'img/netnavigation/' + ret.linked_item.img,
												title:	ret.linked_item.title
											})
										)
									).append(
										jQuery('<a/>', {
											target:	'_self',
											href:	'commsy.php?cid=' + handle.cid + '&mod=' + ret.linked_item.module + '&fct=detail&iid=' + ret.linked_item.linked_iid,
											title:	ret.linked_item.title,
											text:	' ' + text
										})
									)
								);
								
								if(jQuery('a#popup_path_tab').length > 0) {
									// add related entry to path list
									jQuery('ul#popup_path_list').append(
										jQuery('<li/>', {
											'class':	'netnavigation'
										}).append(
											jQuery('<input/>', {
												type:		'checkbox',
												id:			'path_' + ret.linked_item.linked_iid,
												checked:	false
											})
										).append(
											jQuery('<img/>', {
												src:	handle.tpl_path + 'img/netnavigation/' + ret.linked_item.img
											})
										).append(
											jQuery('<span/>', {
												text:		text
											})
										)
									);
								}
							} else {
								// on uncheck
								handle.store.selected--;

								// remove related entry from right box list
								var li_object = jQuery('div#netnavigation_list li#item_' + linked_id);
								li_object.slideUp(1000, function() {
									li_object.remove();
								});
								
								if(jQuery('a#popup_path_tab').length > 0) {
									// remove related entry from path list
									var input_object = jQuery('ul#popup_path_list input#path_' + linked_id);
									input_object.parent().remove();
								}
							}
						});
					});
				});

				// update current page and total number of pages
				jQuery('#pop_item_current_page').text((ret.list.length === 0) ? 0 : handle.paging.current + 1);
				jQuery('#pop_item_pages').text(ret.paging.pages);

				// store pages
				handle.store.pages = ret.paging.pages;
			});
		},

		afterItemCreation: function(item_id) {
			var handle = this;

			// get ids
			var store_after_item_creation = [];
			jQuery('div#netnavigation_list li.netnavigation').each(function() {
				store_after_item_creation.push(jQuery(this).attr('id').substr(5));
			});

			jQuery.each(store_after_item_creation, function(index, value) {
				var data = {
					item_id:	item_id,
					link_id:	value,
					checked:	true
				};

				handle.ajaxRequest('updateLinkedItem', data, null);
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