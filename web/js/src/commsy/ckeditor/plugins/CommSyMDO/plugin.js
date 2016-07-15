CKEDITOR.plugins.add( 'CommSyMDO',
{
	requires : [ 'dialog' ],
	lang: [ 'de', 'en'],

	init : function( editor )
	{
		var lang = editor.lang.CommSyMDO;

		editor.addCommand( 'CommSyMDO', new CKEDITOR.dialogCommand( 'CommSyMDO' ) );
		editor.ui.addButton( 'CommSyMDO',
			{
				label : lang.ckeditor_mdo,
				command : 'CommSyMDO',
				icon: this.path + 'images/logoMDO.png'
			});
		CKEDITOR.dialog.add( 'CommSyMDO', this.path + 'dialogs/CommSyMDO.js' );
	}
} );