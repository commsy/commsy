define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-construct",
        	"commsy/request",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/_base/array",
        	"dijit/Tooltip",
        	"dijit/Dialog",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, lang, Query, On, DomConstruct, request, DomAttr, DomStyle, BaseArray, tooltip, Dialog) {
	return declare(BaseClass, {		
		cid: 						null,
		tpl_path: 					'',
		initialized: 				false,
		paging: {
			current: 0
		},
		restrictions: {
			search:					'',
			status:					7
		},
		store: {
			pages:					1,
			selected_ids:			[]
		},
		translations:				null,
		
		init: function(cid, tpl_path) {
			this.cid = cid;
			this.tpl_path = tpl_path;
			
			// register onclick handler
			On(Query("#popup_account_tab")[0], "click", lang.hitch(this, function(event) {
				// get initial data if this is the first call
				if(this.initialized === false) {
					request.ajax({
						query: {
							cid:	this.uri_object.cid,
							mod:	'ajax',
							fct:	'accounts',
							action:	'getInitialData'
						}
					}).then(
						lang.hitch(this, function(response) {
							this.translations = response.data.translations;
							
							// setup paging
							this.setupPaging();
							
							// setup restrictions
							this.setupRestrictions();
							
							this.performRequest();
							
							// setup form submit
							On(Query("input[name='accounts_submit_restrictions']")[0], "click", lang.hitch(this, function(event) {
								// reset selected ids
								//this.store.selected_ids = [];
								
								// reset paging
								this.paging.current = 0;
								this.performRequest();
								
								event.preventDefault();
							}));
							
							// setup action submit
							On(Query("input#list_action_submit")[0], "click", lang.hitch(this, function(event) {
								this.onActionSubmit();
							}));
						})
					);
				}
			}));
		},
		
		setupRestrictions: function() {
			var contentNode = Query(".pop_item_content")[0];
			
			// status restriction
			On(Query("select[name='accounts_status_restriction']", contentNode)[0], "change", lang.hitch(this, function(event) {
				this.restrictions.status = DomAttr.get(event.target, "value");
				
				event.preventDefault();
			}));
			
			// search restriction
			On(Query("input[name='accounts_search_restriction']", contentNode)[0], "change", lang.hitch(this, function(event) {
				this.restrictions.search = DomAttr.get(event.target, "value");
				
				event.preventDefault();
			}));
		},
		
		setupPaging: function() {
			var navigationNode = Query(".pop_item_navigation")[0];
			
			// first
			On(Query("#first", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current = 0;
				event.preventDefault();
				this.performRequest();
			}));
			
			// previous
			On(Query("#prev", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current--;
				event.preventDefault();
				this.performRequest();
			}));
			
			// next
			On(Query("#next", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current++;
				event.preventDefault();
				this.performRequest();
			}));
			
			// last
			On(Query("#last", navigationNode)[0], "click", lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current = this.store.pages - 1;
				event.preventDefault();
				this.performRequest();
			}));
		},
		
		setStatus: function(status) {
			this.restrictions.status = status;
			
			var contentNode = Query(".pop_item_content")[0];
			var optionNode = Query("select[name='accounts_status_restriction'] option[value='" + status + "']", contentNode)[0];
			DomAttr.set(optionNode, "selected", "selected");
		},
		
		performRequest: function() {
			// create data object for request
			var data = {
				current_page:		this.paging.current,
				restrictions:		this.restrictions
			};
			
			// send request
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'accounts',
					action:	'performRequest'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					var contentObject = Query("#popup_accounts #crt_row_area")[0];
					
					// fill list
					DomConstruct.empty(contentObject);
					
					dojo.forEach(response.data.list, lang.hitch(this, function(entry, index, arr) {
						var rowDivNode = DomConstruct.create("div", {
							className:		(index % 2 === 0) ? 'pop_row_even' : 'pop_row_odd'
						}, contentObject, "last");
						
							var checkboxDivNode = DomConstruct.create("div", {
								className:		"pop_col_25"
							}, rowDivNode, "last");
							
							DomConstruct.create("input", {
								type:		"checkbox",
								id:			"user_" + entry.item_id,
								checked:	(BaseArray.indexOf(this.store.selected_ids, entry.item_id) !== -1) ? true : false
							}, checkboxDivNode, "last");
							
							var accountDivNode = DomConstruct.create("div", {
								className:		"pop_col_270",
								innerHTML:		entry.fullname,
								title:         entry.comment
							}, rowDivNode, "last");
							
							if (entry.comment) {
   							var accountCommentSpan = DomConstruct.create("span", {
      							className:  "account_comment"
							   }, accountDivNode, "last");
   							
   							DomConstruct.create("img", {
								   src:		   "templates/themes/default/img/comment.gif"
							   }, accountCommentSpan, "last");
							}
							
							DomConstruct.create("div", {
								className:		"pop_col_150",
								innerHTML:		entry.status
							}, rowDivNode, "last");
							
							DomConstruct.create("div", {
								className:		"pop_col_150",
								innerHTML:		entry.email
							}, rowDivNode, "last");
							
							DomConstruct.create("div", {
								className:		"clear"
							}, rowDivNode, "last");
							
					}));
					
					// register input event handler and store / remove selected ids
					On(Query("input[id^='user_']", contentObject), "click", lang.hitch(this, function(event) {
						var inputObject = event.target;
						
						// extract id
						var itemId = DomAttr.get(inputObject, "id").substr(5);
						
						var index = BaseArray.indexOf(this.store.selected_ids, itemId);
						if(index === -1) this.store.selected_ids.push(itemId);
						else this.store.selected_ids.splice(index, 1);
					}));
					
					// update current page and total number of pages
					DomAttr.set(Query("#pop_item_current_page")[0], "innerHTML", (response.data.list.length === 0) ? 0 : this.paging.current + 1);
					DomAttr.set(Query("#pop_item_pages")[0], "innerHTML", response.data.paging.pages);
					
					// store pages
					this.store.pages = response.data.paging.pages;
				})
			);
		},
		
		onActionSubmit: function() {
			// get current action
			var action = DomAttr.get(Query("select#list_action")[0], "value");
			
			// send action and id list via ajax
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'accounts',
					action:	'performUserAction'
				},
				data: {
					ids:	this.store.selected_ids,
					action:	action
				}
			}).then(
				lang.hitch(this, function(response) {
					if(response.status == "error" && response.code == "103"){
						// console.log("FEHLER! Das Entfernen des letzten Moderators ist nicht möglich");
						errorDialog = new Dialog({
					        title: "Fehler",
					        content: "Der letzte Moderator des Raums kann nicht gelöscht oder gesperrt werden.",
					        style: "width: 300px"
					    });

					    errorDialog.show();
					} else {

						var selectedIds = this.store.selected_ids;
						this.store.selected_ids = [];
						
						// reload list to get changes
						this.performRequest();
						
						request.ajax({
							query: {
								cid:	this.uri_object.cid,
								mod:	'ajax',
								fct:	'accounts',
								action:	'GetNewUserAccount'
							}
						}).then(
							lang.hitch(this, function(response) {
								var commsyBarAccountNode = Query("span#tm_settings_count_new_accounts")[0];
								var commsyTabAccountNode = Query("a#popup_account_tab > span")[0];
								
								if(commsyBarAccountNode && response.data.count > 0){
									DomAttr.set(commsyBarAccountNode,"innerHTML",response.data.count);
								} else if(commsyBarAccountNode && response.data.count == 0){
									DomConstruct.destroy(commsyBarAccountNode);
								}
								
								if(commsyTabAccountNode && response.data.count > 0){
									DomAttr.set(commsyTabAccountNode,"innerHTML","("+response.data.count+")");
								} else if(commsyTabAccountNode && response.data.count == 0){
									DomConstruct.destroy(commsyTabAccountNode);
								}
							})
						);
						
						// load mail popup information
						if (selectedIds.length > 0) {
							request.ajax({
								query: {
									cid:	this.uri_object.cid,
									mod:	'ajax',
									fct:	'popup',
									action:	'getHTML'
								},
								data: {
									ids:	selectedIds,
									action:	action,
									module:	"configuration_mail"
								}
							}).then(
								lang.hitch(this, function(response) {
									var mailContentNode = Query("div#popup_accounts_mail")[0];
									
									DomConstruct.empty(mailContentNode);
									DomConstruct.place(response.data, mailContentNode, "last");
									
									// create mail send and abort event
									On(Query("input[name='send']", mailContentNode)[0], "click", lang.hitch(this, function(event) {
										this.sendMail(mailContentNode, action, selectedIds);
									}));
									
									On(Query("input[name='abort']", mailContentNode)[0], "click", lang.hitch(this, function(event) {
										var mailContentNode = Query("div#popup_accounts_mail")[0];
										DomConstruct.empty(mailContentNode);
										
									}));
								})
							);
						}
					}
				})
			);
		},
		
		sendMail: function(contentNode, action, selectedIds) {
			var sendMailNode = Query("input[name='form_data[send_mail]']", contentNode)[0];
			
			// collect data
			var data = {
				sendMail:		(sendMailNode) ? sendMailNode.checked : true,
				modCC:			Query("input[name='form_data[copy_mod_cc]']", contentNode)[0].checked,
				modBCC:			Query("input[name='form_data[copy_mod_bcc]']", contentNode)[0].checked,
				authCC:			Query("input[name='form_data[copy_auth_cc]']", contentNode)[0].checked,
				authBCC:		Query("input[name='form_data[copy_auth_bcc]']", contentNode)[0].checked,
				subject:		DomAttr.get(Query("input[name='form_data[subject]']", contentNode)[0], "value"),
				description:	DomAttr.get(Query("textarea[name='form_data[body]']", contentNode)[0], "value"),
				ids:			selectedIds,
				action:			action
			};
			
			// send request
			request.ajax({
				query: {
					cid:	this.uri_object.cid,
					mod:	'ajax',
					fct:	'accounts',
					action:	'sendMail'
				},
				data: data
			}).then(
				lang.hitch(this, function(response) {
					var mailContentNode = Query("div#popup_accounts_mail")[0];
					DomConstruct.empty(mailContentNode);
				})
			);
		}
	});
});