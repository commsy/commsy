define(
[
	"dojo/_base/declare",
 	"dijit/form/SimpleTextarea",
 	"dijit/form/ValidationTextBox"
], function
(
	Declare,
	SimpleTextarea,
	ValidationTextBox
) {
	return Declare("ValidationTextArea", [ValidationTextBox, SimpleTextarea],
	{
		invalidMessage: "This field is required",

        postCreate: function()
        {
            this.inherited(arguments);
        },

        validate: function()
        {
            this.inherited(arguments);
            if (arguments.length==0) this.validate(true);
        },

        onFocus: function()
        {
            if (!this.isValid()) {
                this.displayMessage(this.getErrorMessage());
            }
        },

        onBlur: function()
        {
            this.validate(false);
        }
	});
});