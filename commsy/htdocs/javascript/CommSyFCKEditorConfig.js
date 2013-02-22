/*
 * FCKeditor - The text editor for internet
 * Copyright (C) 2003-2005 Frederico Caldeira Knabben
 *
 * Licensed under the terms of the GNU Lesser General Public License:
 * 		http://www.opensource.org/licenses/lgpl-license.php
 *
 * For further information visit:
 * 		http://www.fckeditor.net/
 *
 * "Support Open Source software. What about a donation today?"
 *
 * File Name: fckconfig.js
 * 	Editor configuration settings.
 * 	See the documentation for more info.
 *
 * File Authors:
 * 		Frederico Caldeira Knabben (fredck@fckeditor.net)
 */
function smileypath() {
   var metas = parent.document.getElementsByTagName("meta");
   var commsypath = "";
   for (i = 0; i < metas.length; i++) {
    if (metas[i].name == "CommsyBaseURL") {
      commsypath = metas[i].content;
    }
  }
   var smileypath = commsypath + '/images/FCKeditor/smileys/';
   return smileypath;
}

FCKConfig.SkinPath = FCKConfig.BasePath + 'skins/silver/' ;

/* for fckeditor version <= 2.3.2 */
FCKConfig.UseBROnCarriageReturn = true ;
/* for fckeditor version => 2.4.0 */
FCKConfig.EnterMode             = 'br' ;
FCKConfig.ShiftEnterMode        = 'p' ;

FCKConfig.ToolbarStartExpanded  = true ;
FCKConfig.GeckoUseSPAN          = true ;

FCKConfig.ToolbarSets["CommSy"] = [
   ['FontFormat','FontSize','-','Table','Link','Rule','PasteWord','-','Smiley','-','FitWindow','-','About'],
   ['Bold','Italic','Underline','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','TextColor','BGColor','-','Subscript','Superscript']
];

FCKConfig.ToolbarSets["MinCommSy"] = [
         ['Bold','Italic','Underline','FitWindow','About']
];

FCKConfig.ToolbarSets["homepage"] = [
   ['Undo','Redo','-','Find','Replace','-','JustifyLeft','JustifyCenter','JustifyRight','JustifyFull','-','OrderedList','UnorderedList','-','Outdent','Indent','-','Table','BGColor','-','Rule','-','PasteWord','-','Link','Unlink','-','Smiley','-','FitWindow','-','About'],
   ['FontFormat','FontName','FontSize','-','Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript','-','TextColor']
];

FCKConfig.ToolbarSets["Default"] = [
   ['Source','DocProps','-','Save','NewPage','Preview','-','Templates'],
   ['Cut','Copy','Paste','PasteText','PasteWord','-','Print','SpellCheck'],
   ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
   ['Form','Checkbox','Radio','TextField','Textarea','Select','Button','ImageButton','HiddenField'],
   '/',
   ['Bold','Italic','Underline','StrikeThrough','-','Subscript','Superscript'],
   ['OrderedList','UnorderedList','-','Outdent','Indent'],
   ['JustifyLeft','JustifyCenter','JustifyRight','JustifyFull'],
   ['Link','Unlink','Anchor'],
   ['Image','Flash','Table','Rule','Smiley','SpecialChar','PageBreak','-'],
   '/',
   ['Style','FontFormat','FontName','FontSize'],
   ['TextColor','BGColor'],
   ['FitWindow','-','About']
] ;

FCKConfig.FontFormats	= 'p;h1;h2;h3' ;
FCKConfig.FontSizes		= 'xx-small;x-small;small;medium;large;x-large' ;

FCKConfig.ContextMenu = ['Generic','Link','Anchor','Image','Flash','Select','Textarea','Checkbox','Radio','TextField','HiddenField','ImageButton','Button','BulletedList','NumberedList','TableCell','Table','Form'];

FCKConfig.LinkBrowser = false;
FCKConfig.LinkDlgHideTarget = false;
FCKConfig.LinkDlgHideAdvanced = true;
FCKConfig.LinkUpload = false;

FCKConfig.SmileyPath	= smileypath();
FCKConfig.SmileyImages	= ['smallSmile.gif','Smile.gif','BigSmile.gif','sad.gif','Cry-Color.gif','blush.png','Cool.gif','innocent.gif','wink.gif','surprise.gif','tongue-Color.gif','Sceptical.gif'];
FCKConfig.SmileyColumns = 4 ;
FCKConfig.SmileyWindowWidth = 200 ;
FCKConfig.SmileyWindowHeight	= 200 ;