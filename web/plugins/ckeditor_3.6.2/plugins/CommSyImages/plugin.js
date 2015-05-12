//CKEDITOR.plugins.add('CommSyImages',{
//    requires: ['iframedialog'],
//    init:function(editor){
//        var cmd = editor.addCommand('CommSyImages', {exec:CommSyImages_onclick});
//        cmd.modes={wysiwyg:1,source:1};
//        cmd.canUndo=false;
//        editor.ui.addButton('CommSyImages',{ label:'CommSyImages..', command:'CommSyImages', icon:this.path+'images/anchor.gif' })
//    }
//})

CKEDITOR.plugins.add( 'CommSyImages',
{
	requires : [ 'dialog' ],

	init : function( editor )
	{
		editor.addCommand( 'CommSyImages', new CKEDITOR.dialogCommand( 'CommSyImages' ) );
		editor.ui.addButton( 'CommSyImages',
			{
				label : ckeditor_images,
				command : 'CommSyImages',
				icon: this.path + 'images/CommSyImages.png'
			});
		CKEDITOR.dialog.add( 'CommSyImages', this.path + 'dialogs/CommSyImages.js' );
	}
} );

//function CommSyImages_onclick(editor)
//{
//    // run when custom button is clicked
//	var element = CKEDITOR.dom.element.createFromHtml( '<span>(:image:)</span>' );
//	editor.insertElement( element );
//	editor.openDialog('commsy_images')
//}