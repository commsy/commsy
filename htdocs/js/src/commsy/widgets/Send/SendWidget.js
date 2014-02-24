define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/SendWidget.html",
 	"dojo/i18n!./nls/SendWidget",
 	"dojo/dom-construct",
 	"dojo/on",
 	"dojo/dom-class",
 	"dojo/query",
 	"dijit/registry",
 	"dojo/parser",
 	"dojo/fx",
 	"dojox/form/Manager",
 	"dojo/_base/lang",
 	"dijit/form/ValidationTextBox",
	"dojox/validate/web"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	DomConstruct,
	on,
	domclass,
	query,
	registry,
	parser,
	fx,
	Manager,
	lang
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"sendWidget",
		
		additionalIndex:	1,
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
		body:				"",
		_setBodyAttr:		{ node: "bodyNode", type: "innerHTML" },
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
		},
		
		/**
		 * \brief	Processing after the DOM fragment is created
		 * 
		 * Called after the DOM fragment has been created, but not necessarily
		 * added to the document.  Do not include any operations which rely on
		 * node dimensions or placement.
		 */
		postCreate: function()
		{
			// run parent postCreate processes
			this.inherited(arguments);
			
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.set("title", this.popupTranslations.title);
			
			on(this.formNode, "submit", lang.hitch(this, this.onSubmit));
			on(this.addAdditionalNode, "click", lang.hitch(this, this.onClickAddAdditional));
		},
		
		/**
		 * \brief 	Processing after the DOM fragment is added to the document
		 * 
		 * Called after a widget and its children have been created and added to the page,
		 * and all related widgets have finished their create() cycle, up through postCreate().
		 * This is useful for composite widgets that need to control or layout sub-widgets.
		 * Many layout widgets can use this as a wiring phase.
		 */
		startup: function()
		{
			this.inherited(arguments);
			
			parser.parse(this.widgetNode);
			
			this.AJAXRequest(	"send",
								"init",
								{ itemId: this.iid },
								lang.hitch(this, function(response)
			{
				if (response) {
					// mail body
					if (response.body) {
						this.set("body", response.body);
					}
					
					// attendees
					if (response.showAttendees) {
						this.createAttendeesHTML(response.attendeeType);
					}
					
					// group recipients / institution recipients
					if (response.showGroupRecipients) {
						this.createGroupRecipientsHTML(response.withGroups, response.groups);
					} else if(response.showInstitutionRecipients) {
						this.createInstitutionsRecipientsHTML(response.institutions);
					}
					
					// all members
					if (response.allMembers) {
						this.createAllMembersHTML();
					}
					
					// add additional
					if (response.allowAdditional) {
						domclass.remove(this.addAdditionalWrapperNode, 'hidden');
					}
				}
			}));
		},
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		createAttendeesHTML: function(type)
		{
			var formManager = registry.byId("sendForm");
			
			var rowNode = DomConstruct.create('div', {
				className:	'input_row'
			}, this.lastFormRow, "before");
			
				DomConstruct.create('span', {
					className:	'input_label_150',
					innerHTML:	this.popupTranslations.sendTo
				}, rowNode, 'last');
				
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					var inputNode = DomConstruct.create('input', {
						type:		'checkbox',
						value:		'true',
						checked:	'checked',
						name:		'copyToAttendees'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	(type == 'date') ? this.popupTranslations.sendToAttendees : this.popupTranslations.sendToProcessors
					}, divNode, 'last');
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
			
			formManager.registerNode(inputNode);
		},
		
		createGroupRecipientsHTML: function(withGroups, groups)
		{
			var formManager = registry.byId("sendForm");
			
			var rowNode = DomConstruct.create('div', {
				className:	'input_row'
			}, this.lastFormRow, "before");
			
				DomConstruct.create('span', {
					className:	'input_label_150',
					innerHTML:	withGroups ? this.popupTranslations.sendToGroups : this.popupTranslations.recipient
				}, rowNode, 'last');
				
			dojo.forEach(groups, lang.hitch(this, function(group) {
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					var inputNode = DomConstruct.create('input', {
						type:		'checkbox',
						value:		group.value,
						checked:	group.checked,
						name:		'group_' + group.value
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	withGroups ? group.text : this.popupTranslations.all
					}, divNode, 'last');
				
				formManager.registerNode(inputNode);
			}));
			
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
		},
		
		createInstitutionsRecipientsHTML: function(institutions)
		{
			var formManager = registry.byId("sendForm");
			
			var rowNode = DomConstruct.create('div', {
				className:	'input_row'
			}, this.lastFormRow, "before");
			
				DomConstruct.create('span', {
					className:	'input_label_150',
					innerHTML:	this.popupTranslations.sendToInstitution
				}, rowNode, 'last');
				
			dojo.forEach(institutions, function(institution) {
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					var inputNode = DomConstruct.create('input', {
						type:		'checkbox',
						value:		institution.value,
						checked:	institution.checked,
						name:		'institution_' + institution.value
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	institution.text
					}, divNode, 'last');
				
				formManager.registerNode(inputNode);
			});
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
		},
		
		createAllMembersHTML: function()
		{
			var rowNode = DomConstruct.create('div', {
				className:	'input_row'
			}, this.lastFormRow, "before");
			
				DomConstruct.create('span', {
					className:	'input_label_150',
					innerHTML:	this.popupTranslations.recipient
				}, rowNode, 'last');
				
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					var inputNode = DomConstruct.create('input', {
						type:		'checkbox',
						value:		'true',
						checked:	'checked',
						name:		'allMembers'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	this.popupTranslations.all
					}, divNode, 'last');
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
			
			var formManager = registry.byId("sendForm");
			formManager.registerNode(inputNode);
		},
		
		addAdditionalFormElements: function()
		{
			var lastRowNode = query(this.addAdditionalNode).parent()[0];
			
			if ( lastRowNode )
			{
				var inputRowNode = DomConstruct.create("div",
				{
					className:			"input_row",
					style:				"display: none"
				}, lastRowNode, "before");
				
					var divNode = DomConstruct.create("div",
					{
						style:			"float: left; width: 180px;"
					}, inputRowNode, "last");
					
						var aNode = DomConstruct.create("a",
						{
							href:			"#",
							innerHTML:		"entfernen"
						}, divNode, "last");
				
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalFirstName_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalFirstName,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isText, invalidMessage:'" + PopupTranslations.errorMissing + "'"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalLastName_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalLastName,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isText, invalidMessage:'" + PopupTranslations.errorMissing + "'"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("input",
					{
						className:			"float-left",
						name:				"additionalMail_" + this.additionalIndex,
						required:			true,
						displayedValue:		PopupTranslations.additionalMail,
						"data-dojo-type":	"dijit/form/ValidationTextBox",
						"data-dojo-props":	"validator:dojox.validate.isEmailAddress, invalidMessage:'" + PopupTranslations.errorMail + "'",
						style:				"margin-left: 20px;"
						
					}, inputRowNode, "last");
					
					DomConstruct.create("div",
					{
						className:			"clear"
					}, inputRowNode, "last");
				
				this.additionalIndex++;
				
				var formManager = registry.byId("sendForm");
				
				on(aNode, "click", lang.hitch(this, function()
				{
					fx.wipeOut(
					{
						node:		inputRowNode,
						onEnd:		lang.hitch(this, function()
						{
							var widgetsInRow = registry.findWidgets(inputRowNode);
							dojo.forEach(widgetsInRow, function(widget)
							{
								formManager.unregisterWidget(widget);
								formManager.unregisterWidgetDescendants(widget);
								widget.set("disabled", true);
								widget.set("displayedValue", "");
							});
							
							DomConstruct.destroy(inputRowNode);
						})
					}).play();
				}));
				
				parser.parse(inputRowNode).then(lang.hitch(this, function(instances)
				{
					dojo.forEach(instances, function(instance)
					{
						formManager.registerWidget(instance);
					});
					
					fx.wipeIn(
					{
						node:		inputRowNode
					}).play();
				}));
			}
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onClickAddAdditional: function(event)
		{
			this.addAdditionalFormElements();
		},
		
		onSubmit: function(event)
		{
			event.preventDefault();
			
			var formManager = registry.byId("sendForm");
			
			if (formManager.isValid()) {
				this.setupLoading();
				var formValues = formManager.gatherFormValues();
				formValues.itemId = this.iid
				
				this.AJAXRequest(	"send",
									"send",
									formValues,
									lang.hitch(this, function(response) {
					// remove loading indicator and close this popup
					this.destroyLoading();
					this.Close();
					
					/*
					// prepare mixin data
					var mixin = {
						mailSuccess:	true,
						mail:			confirmData
					};
					
					// get instance of confirm widget
					var widgetManager = this.getWidgetManager();
					widgetManager.GetInstance("commsy/widgets/MailConfirmWidget/MailConfirmWidget", mixin).then(function(deferred)
					{
						var widgetInstance = deferred.instance;
						
						// open widget
						widgetInstance.Open();
					});
					*/
				}));
			} else {
				formManager.validate();
			}
		}
	});
});