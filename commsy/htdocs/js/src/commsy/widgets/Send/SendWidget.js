define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/SendWidget.html",
 	"dojo/i18n!./nls/SendWidget",
 	"dojo/dom-construct",
 	"dojo/_base/lang"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations,
	DomConstruct,
	Lang
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"sendWidget",
		
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
			
			this.AJAXRequest(	"send",
								"init",
								{ itemId: this.iid },
								Lang.hitch(this, function(response)
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
					
					// group receivers / institution receivers
					if (response.showGroupReceivers) {
						this.createGroupReceiversHTML(response.withGroups, response.groups);
					} else if(response.showInstitutionReceivers) {
						this.createInstitutionsReceiversHTML(response.institutions);
					}
					
					// all members
					if (response.allMembers) {
						this.createAllMembersHTML();
					}
				}
				console.log(response);
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
				
					DomConstruct.create('input', {
						type:		'checkbox',
						value:		'true',
						checked:	'checked',
						name:		'form_data[copyToAttendees]'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	(type == 'date') ? this.popupTranslations.sendToAttendees : this.popupTranslations.sendToProcessors
					}, divNode, 'last');
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
		},
		
		createGroupReceiversHTML: function(withGroups, groups)
		{
			var rowNode = DomConstruct.create('div', {
				className:	'input_row'
			}, this.lastFormRow, "before");
			
				DomConstruct.create('span', {
					className:	'input_label_150',
					innerHTML:	withGroups ? this.popupTranslations.sendToGroups : this.popupTranslations.receiver
				}, rowNode, 'last');
				
			dojo.forEach(groups, function(group) {
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					DomConstruct.create('input', {
						type:		'checkbox',
						value:		group.value,
						checked:	group.checked,
						name:		'form_data[group_]' + group.value + ']'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	withGroups ? group.text : this.popupTranslations.all
					}, divNode, 'last');
			});
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
		},
		
		createInstitutionsReceiversHTML: function(institutions)
		{
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
				
					DomConstruct.create('input', {
						type:		'checkbox',
						value:		institution.value,
						checked:	institution.checked,
						name:		'form_data[institution_]' + institution.value + ']'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	institution.text
					}, divNode, 'last');
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
					innerHTML:	this.popupTranslations.receiver
				}, rowNode, 'last');
				
				var divNode = DomConstruct.create('div', {
					className:	'input_container_180'
				}, rowNode, 'last');
				
					DomConstruct.create('input', {
						type:		'checkbox',
						value:		'true',
						checked:	'checked',
						name:		'form_data[allMembers]'
					}, divNode, 'last');
					
					DomConstruct.create('span', {
						innerHTML:	this.popupTranslations.all
					}, divNode, 'last');
				
				DomConstruct.create('div', { className: 'clear' }, rowNode, 'last');
		},
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
		onSend: function(event)
		{
			
		}
	});
});