CKEDITOR.plugins.add( 'CommSyMDO',
{
	requires : [ 'dialog' ],

	init : function( editor )
	{
		editor.addCommand( 'CommSyMDO', new CKEDITOR.dialogCommand( 'CommSyMDO' ) );
		editor.ui.addButton( 'CommSyMDO',
			{
				label : ckeditor_mdo,
				command : 'CommSyMDO',
				icon: this.path + 'images/CommSyMDO.png'
			});
		CKEDITOR.dialog.add( 'CommSyMDO', this.path + 'dialogs/CommSyMDO.js' );
	}
} );