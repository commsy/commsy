define([	"dojo/_base/declare",
        	"commsy/base",
        	"dojo/_base/lang",
        	"dojo/query",
        	"dojo/on",
        	"dojo/dom-construct",
        	"dojo/dom-attr",
        	"dojo/dom-style",
        	"dojo/_base/array",
        	"dijit/Tooltip",
        	"dojo/NodeList-traverse"], function(declare, BaseClass, Lang, Query, On, DomConstruct, DomAttr, DomStyle, BaseArray, tooltip) {
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
			On(Query("#popup_account_tab")[0], "click", Lang.hitch(this, function(event) {
				// get initial data if this is the first call
				if(this.initialized === false) {
					this.AJAXRequest("accounts", "getInitialData", {}, Lang.hitch(this, function(data) {
						this.translations = data.translations;
						
						// setup paging
						this.setupPaging();
						
						// setup restrictions
						this.setupRestrictions();
						
						this.performRequest();
						
						// setup form submit
						On(Query("input[name='accounts_submit_restrictions']")[0], "click", Lang.hitch(this, function(event) {
							// reset selected ids
							//this.store.selected_ids = [];
							
							// reset paging
							this.paging.current = 0;
							this.performRequest();
							
							event.preventDefault();
						}));
						
						// setup action submit
						On(Query("input#list_action_submit")[0], "click", Lang.hitch(this, function(event) {
							this.onActionSubmit();
						}));
					}));
				}
			}));
		},
		
		setupRestrictions: function() {
			var contentNode = Query(".pop_item_content")[0];
			
			// status restriction
			On(Query("select[name='accounts_status_restriction']", contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.restrictions.status = DomAttr.get(event.target, "value");
				
				event.preventDefault();
			}));
			
			// search restriction
			On(Query("input[name='accounts_search_restriction']", contentNode)[0], "change", Lang.hitch(this, function(event) {
				this.restrictions.search = DomAttr.get(event.target, "value");
				
				event.preventDefault();
			}));
		},
		
		setupPaging: function() {
			var navigationNode = Query(".pop_item_navigation")[0];
			
			// first
			On(Query("#first", navigationNode)[0], "click", Lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current = 0;
				event.preventDefault();
				this.performRequest();
			}));
			
			// previous
			On(Query("#prev", navigationNode)[0], "click", Lang.hitch(this, function(event) {
				if(this.paging.current > 0) this.paging.current--;
				event.preventDefault();
				this.performRequest();
			}));
			
			// next
			On(Query("#next", navigationNode)[0], "click", Lang.hitch(this, function(event) {
				if(this.paging.current + 1 < this.store.pages) this.paging.current++;
				event.preventDefault();
				this.performRequest();
			}));
			
			// last
			On(Query("#last", navigationNode)[0], "click", Lang.hitch(this, function(event) {
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
			this.AJAXRequest("accounts", "performRequest", data, Lang.hitch(this, function(response) {
				var contentObject = Query("#popup_accounts #crt_row_area")[0];
				
				// fill list
				DomConstruct.empty(contentObject);
				
				dojo.forEach(response.list, Lang.hitch(this, function(entry, index, arr) {
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
						
						var nameColumnNode = DomConstruct.create("div", {
							className:		"pop_col_270",
							innerHTML:		entry.fullname
						}, rowDivNode, "last");
						
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
				On(Query("input[id^='user_']", contentObject), "click", Lang.hitch(this, function(event) {
					var inputObject = event.target;
					
					// extract id
					var itemId = DomAttr.get(inputObject, "id").substr(5);
					
					var index = BaseArray.indexOf(this.store.selected_ids, itemId);
					if(index === -1) this.store.selected_ids.push(itemId);
					else this.store.selected_ids.splice(index, 1);
				}));
				
				// update current page and total number of pages
				DomAttr.set(Query("#pop_item_current_page")[0], "innerHTML", (response.list.length === 0) ? 0 : this.paging.current + 1);
				DomAttr.set(Query("#pop_item_pages")[0], "innerHTML", response.paging.pages);
				
				// store pages
				this.store.pages = response.paging.pages;
			}));
		},
		
		onActionSubmit: function() {
			// get current action
			var action = DomAttr.get(Query("select#list_action")[0], "value");
			
			// send action and id list via ajax
			this.AJAXRequest("accounts", "performUserAction", { ids: this.store.selected_ids, action: action }, Lang.hitch(this, function(response) {
				
				
				var selectedIds = this.store.selected_ids;
				this.store.selected_ids = [];
				
				// reload list to get changes
				this.performRequest();
				
				
				this.AJAXRequest("accounts", "GetNewUserAccount",{},Lang.hitch(this, function(response){
					var commsyBarAccountNode = Query("span#tm_settings_count_new_accounts")[0];
					var commsyTabAccountNode = Query("a#popup_account_tab > span")[0];
					
					//console.log(commsyTabAccountNode);
					if(commsyBarAccountNode && response.count > 0){
						DomAttr.set(commsyBarAccountNode,"innerHTML",response.count);
					} else if(commsyBarAccountNode && response.count == 0){
						DomConstruct.destroy(commsyBarAccountNode);
					}
					
					if(commsyTabAccountNode && response.count > 0){
						DomAttr.set(commsyTabAccountNode,"innerHTML","("+response.count+")")
					} else if(commsyTabAccountNode && response.count == 0){
						DomConstruct.destroy(commsyTabAccountNode);
					}
				}));
				
				
				
				
				// load mail popup information
				if (selectedIds.length > 0) {
					this.AJAXRequest("popup", "getHTML", { ids: selectedIds, action: action, module: "configuration_mail" }, Lang.hitch(this, function(html) {
						var mailContentNode = Query("div#popup_accounts_mail")[0];
						
						DomConstruct.empty(mailContentNode);
						DomConstruct.place(html, mailContentNode, "last");
						
						// create mail send and abort event
						On(Query("input[name='send']", mailContentNode)[0], "click", Lang.hitch(this, function(event) {
							this.sendMail(mailContentNode, action, selectedIds);
						}));
						
						On(Query("input[name='abort']", mailContentNode)[0], "click", Lang.hitch(this, function(event) {
							var mailContentNode = Query("div#popup_accounts_mail")[0];
							DomConstruct.empty(mailContentNode);
							
						}));
					}));
				}
			}));
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
			this.AJAXRequest("accounts", "sendMail", data,Lang.hitch(this, function(response) {
				// handling errors etc. should be done here
				
				var mailContentNode = Query("div#popup_accounts_mail")[0];
				DomConstruct.empty(mailContentNode);
			}));
		}
	});
});