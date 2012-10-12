define(
[
 	"dojo/_base/declare",
 	"commsy/widgets/PopupBase",
 	"dijit/_TemplatedMixin",
 	"dojo/text!./templates/MailConfirmWidget.html",
 	"dojo/i18n!./nls/popup"
], function
(
	declare,
	PopupBase,
	TemplatedMixin,
	Template,
	PopupTranslations
) {
	return declare([PopupBase, TemplatedMixin],
	{
		templateString:		Template,
		baseClass:			"mailConfirmWidget",
		
		mailSuccess:		true,
		mail:				null,							///< mail data mixed in by calling class
		
		constructor: function(options)
		{
			options = options || {};
			declare.safeMixin(this, options);
			
			this.popupTranslations = PopupTranslations;
		},
		
		/**
		 * \brief	Main customization method
		 * 
		 * By far, the most important method to keep in mind when creating your own widgets is the postCreate method.
		 * This is fired after all properties of a widget are defined, and the document fragment representing the widget
		 * is created—but before the fragment itself is added to the main document.
		 * The reason why this method is so important is because it is the main place where you, the developer,
		 * get a chance to perform any last-minute modifications before the widget is presented to the user,
		 * including setting any kind of custom attributes, and so on. When developing a custom widget,
		 * most (if not all) of your customization will occur here. 
		 */
		postCreate: function()
		{
			/************************************************************************************
			 * Initialization is done here
			 ************************************************************************************/
			this.titleNode.innerHTML = ( this.mailSuccess ) ? this.popupTranslations.titleSuccess : this.popupTranslations.titleFailure;
			
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
		 * \brief 
		 * 
		 * Probably the second-most important method in the Dijit lifecycle is the startup method.
		 * This method is designed to handle processing after any DOM fragments have been actually added
		 * to the document; it is not fired until after any potential child widgets have been created and started as well.
		 * This is useful for composite widgets as well as layout widgets. 
		 */
		startup: function()
		{
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