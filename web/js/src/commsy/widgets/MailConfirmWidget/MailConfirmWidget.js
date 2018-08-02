define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/MailConfirmWidget.html",
 	"dojo/i18n!./nls/popup",
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
		baseClass:			"mailConfirmWidget",
		
		mailSuccess:		true,
		mail:				null,							///< mail data mixed in by calling class
		
		// attributes
		title:				"",
		_setTitleAttr:		{ node: "titleNode", type: "innerHTML" },
		
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
			this.set("title", ( this.mailSuccess ) ? this.popupTranslations.titleSuccess : this.popupTranslations.titleFailure);
			
			/*
			this.fromNode = this.mail.from;
			
			
			"from"			=> $mail['from_email'],
			"to"			=> $recipients,
			"copyToSender"	=> (isset($form_data["copyToSender"]) && $form_data["copyToSender"] == true),
			"recipientsBcc"	=> $recipients_bcc,
			"subject"		=> $form_data["subject"],
			"body"			=> $form_data["body"]*/
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
			
			// set reciever
			dojo.forEach(this.mail.to, Lang.hitch(this, function(reciever, index, arr)
			{
				DomConstruct.create("li",
				{
					innerHTML:	reciever
				}, this.recieverListNode, "last");
			}));
		}
		
		/************************************************************************************
		 * Getter / Setter
		 ************************************************************************************/
		
		/************************************************************************************
		 * Helper Functions
		 ************************************************************************************/
		
		/************************************************************************************
		 * Event Handling
		 ************************************************************************************/
	});
});