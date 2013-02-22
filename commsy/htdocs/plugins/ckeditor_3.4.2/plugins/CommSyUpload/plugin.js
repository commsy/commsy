//CKEDITOR.plugins.add('CommSyImages',{
//    requires: ['iframedialog'],
//    init:function(editor){
//        var cmd = editor.addCommand('CommSyImages', {exec:CommSyImages_onclick});
//        cmd.modes={wysiwyg:1,source:1};
//        cmd.canUndo=false;
//        editor.ui.addButton('CommSyImages',{ label:'CommSyImages..', command:'CommSyImages', icon:this.path+'images/anchor.gif' })
//    }
//})

CKEDITOR.plugins.add( 'CommSyUpload',
{
	requires : [ 'dialog' ],

	init : function( editor )
	{
		editor.addCommand( 'CommSyUpload', new CKEDITOR.dialogCommand( 'CommSyUpload' ) );
		editor.ui.addButton( 'CommSyUpload',
			{
				label : ckeditor_images,
				command : 'CommSyUpload',
				icon: this.path + 'images/CommSyUpload.png'
			});
		CKEDITOR.dialog.add( 'CommSyUpload', this.path + 'dialogs/CommSyUpload.js' );
	}
} );

//function CommSyImages_onclick(editor)
//{
//    // run when custom button is clicked
//	var element = CKEDITOR.dom.element.createFromHtml( '<span>(:image:)</span>' );
//	editor.insertElement( element );
//	editor.openDialog('commsy_images')
//}