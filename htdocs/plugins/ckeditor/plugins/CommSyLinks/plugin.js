//CKEDITOR.plugins.add('CommSyImages',{
//    requires: ['iframedialog'],
//    init:function(editor){
//        var cmd = editor.addCommand('CommSyImages', {exec:CommSyImages_onclick});
//        cmd.modes={wysiwyg:1,source:1};
//        cmd.canUndo=false;
//        editor.ui.addButton('CommSyImages',{ label:'CommSyImages..', command:'CommSyImages', icon:this.path+'images/anchor.gif' })
//    }
//})

CKEDITOR.plugins.add( 'CommSyLinks',
{
	requires : [ 'dialog' ],

	init : function( editor )
	{
		editor.addCommand( 'CommSyLinks', new CKEDITOR.dialogCommand( 'CommSyLinks' ) );
		editor.ui.addButton( 'CommSyLinks',
			{
				label : ckeditor_links,
				command : 'CommSyLinks',
				icon: this.path + 'images/CommSyLinks.png'
			});
		CKEDITOR.dialog.add( 'CommSyLinks', this.path + 'dialogs/CommSyLinks.js' );
	}
} );

//function CommSyImages_onclick(editor)
//{
//    // run when custom button is clicked
//	var element = CKEDITOR.dom.element.createFromHtml( '<span>(:image:)</span>' );
//	editor.insertElement( element );
//	editor.openDialog('commsy_images')
//}