//CKEDITOR.plugins.add('CommSyImages',{
//    requires: ['iframedialog'],
//    init:function(editor){
//        var cmd = editor.addCommand('CommSyImages', {exec:CommSyImages_onclick});
//        cmd.modes={wysiwyg:1,source:1};
//        cmd.canUndo=false;
//        editor.ui.addButton('CommSyImages',{ label:'CommSyImages..', command:'CommSyImages', icon:this.path+'images/anchor.gif' })
//    }
//})

CKEDITOR.plugins.add( 'CommSyFiles',
{
	requires : [ 'dialog' ],

	init : function( editor )
	{
		editor.addCommand( 'CommSyFiles', new CKEDITOR.dialogCommand( 'CommSyFiles' ) );
		editor.ui.addButton( 'CommSyFiles',
			{
				label : ckeditor_files,
				command : 'CommSyFiles',
				icon: this.path + 'images/CommSyFiles.png'
			});
		CKEDITOR.dialog.add( 'CommSyFiles', this.path + 'dialogs/CommSyFiles.js' );
	}
} );

//function CommSyImages_onclick(editor)
//{
//    // run when custom button is clicked
//	var element = CKEDITOR.dom.element.createFromHtml( '<span>(:image:)</span>' );
//	editor.insertElement( element );
//	editor.openDialog('commsy_images')
//}