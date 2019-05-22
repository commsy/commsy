CKEDITOR.dialog.add('commsydocument', function (editor) {

    var slideshareVidId = function (url) {
        var p = /^\[slideshare\sid=(\d+)?(?:\S+)?$/;
        return ( url.match(p) ) ? RegExp.$1 : false;
    };

    return {
        title: editor.lang.commsydocument.title,
        minWidth: 500,
        minHeight: 200,
        contents: [
            {
                id: 'videoTab',
                elements: [
                    {
                        type: 'html',
                        html:
                        '<div>' +
                        editor.lang.commsydocument.helpintro +
                        '<ul>' +
                        '<li>' + editor.lang.commsydocument.helpslideshare + '</li>' +
                        '</ul>' +
                        '</div>'
                    },
                    {
                        type: 'hbox',
                        widths: ['100%'],
                        children: [
                            {
                                id: 'videoUrl',
                                type: 'text',
                                label: editor.lang.commsydocument.urlcode,
                                required: true,
                                validate: CKEDITOR.dialog.validate.notEmpty(editor.lang.commsydocument.emptyUrl),
                                setup: function (widget) {
                                    this.setValue(widget.data.src);
                                },
                                commit: function (widget) {
                                    var slideshareId = slideshareVidId(this.getValue());
                                    if (slideshareId) {
                                        widget.setData('type', 'slideshare');
                                        widget.setData('src', slideshareId);
                                        return;
                                    }

                                    widget.setData('type', 'commsy');
                                    widget.setData('src', this.getValue());
                                }
                            }
                        ]
                    },
                    {
                        type: 'hbox',
                        children: [
                            {
                                type: 'text',
                                id: 'width',
                                label: editor.lang.commsydocument.width,
                                'default': '400',

                                commit: function (widget) {
                                    widget.setData('width', this.getValue());
                                },

                                setup: function (widget) {
                                    if (widget.data.width) {
                                        this.setValue(widget.data.width);
                                    }
                                }
                            },
                            {
                                type: 'text',
                                id: 'height',
                                label: editor.lang.commsydocument.height,
                                'default': '300',

                                commit: function (widget) {
                                    widget.setData('height', this.getValue());
                                },

                                setup: function (widget) {
                                    if (widget.data.height) {
                                        this.setValue(widget.data.height);
                                    }
                                }
                            }
                        ]
                    }
                ]
            }
        ]
    };
});


//             CKEDITOR.dialog.add( 'CommSyDocument', function ( instance )
//             {
//                 var fileName;
//                 var audio;
//
//                 var SelectBoxItems = new Array(
//                     new Array( '<Bitte Medientyp auswählen>', 'null'),
//                     new Array( 'MDO', 'mdo'),
//                     new Array( 'Slideshare', 'slideshare' ),
//                     new Array( 'Onyx', 'onyx' )
//                 );
//
//                 // parse filenames from edit dialog
//                 var files = document.getElementsByName('file_name');
//
//                 fileItems = new Array (
//                     new Array( '<Auswahl>' , null)
//                 );
//
//                 var floatItems = new Array (
//                     new Array ('<nichts>','null'),
//                     new Array ('Links','left'),
//                     new Array ('Rechts','right')
//                 );
//
//                 var saveItems = new Array (
//                     new Array ('<nichts>',''),
//                     new Array ('anonym','1'),
//                     new Array ('pseudonym','2')
//                 );
//
//                 var saveperiodItems = new Array (
//                     new Array ('<nichts>',''),
//                     new Array ('Tag','day'),
//                     new Array ('Woche','week'),
//                     new Array ('Monat','month')
//                 );
//
//                 return {
//                     title : 'Sonstige Medien-Eigenschaften',
//                     minWidth : 500,
//                     minHeight : 200,
//                     contents :
//                         [{
//                             id : 'documentTab',
//                             label: 'Document',
//                             expand : true,
//                             elements :
//                                 [{
//                                     type: 'select',
//                                     id: 'selectbox',
//                                     style: 'width=100%',
//                                     label: 'Medientyp',
//                                     items: SelectBoxItems,
//                                     'default' : 'null',
//                                     onLoad : function ()
//                                     {
//                                         var dialog = this.getDialog();
//                                         var startAt = dialog.getContentElement( 'documentTab', 'startAt');
//                                         var uploadButton = dialog.getContentElement( 'documentTab', 'uploadButton');
//                                         var upload = dialog.getContentElement( 'documentTab', 'upload');
//
//                                         uploadButton.disable();
//                                         upload.disable();
//
//                                         startAt.enable();
//                                     },
//                                     onChange : function ()
//                                     {
//                                         // show input if onyx is selected
//                                         var dialog = this.getDialog();
//                                         var textInput = dialog.getContentElement('documentTab', 'linkText');
//                                         var fileSelect = dialog.getContentElement( 'documentTab', 'fileselect' );
//                                         var startAt = dialog.getContentElement( 'documentTab', 'startAt');
//                                         var uploadButton = dialog.getContentElement( 'documentTab', 'uploadButton');
//                                         var upload = dialog.getContentElement( 'documentTab', 'upload');
//
//                                         var naviParam = dialog.getContentElement( 'documentTab', 'naviParam');
//                                         var saveParam = dialog.getContentElement( 'documentTab', 'saveParam');
//                                         var saveaimParam = dialog.getContentElement( 'documentTab', 'saveaimParam');
//                                         var saveperiodParam = dialog.getContentElement( 'documentTab', 'saveperiodParam');
//
//                                         if(this.getValue() == 'onyx'){
//                                             textInput.enable();
//                                             fileSelect.enable();
//                                             startAt.disable();
//                                             uploadButton.enable();
//                                             upload.enable();
//
//                                             naviParam.enable();
//                                             saveParam.enable();
//                                             saveaimParam.enable();
//                                             saveperiodParam.enable();
//
//                                             // select only zip files for onyx
//                                             var j;
//                                             fileSelect.clear();
//                                             fileSelect.add('<Auswahl>', 'null');
//                                             for(j = 0; j < fileItems.length; j++) {
//                                                 if(fileItems[j][2] == 'zip'){
//                                                     fileSelect.add(fileSelect.items[j][0],fileSelect.items[j][1]);
//                                                 }
//                                             }
//                                         } else if (this.getValue() == 'mdo') {
//                                             startAt.enable();
//                                             textInput.enable();
//                                             fileSelect.disable();
//                                             uploadButton.disable();
//                                             upload.disable();
//
//                                             naviParam.disable();
//                                             saveParam.disable();
//                                             saveaimParam.disable();
//                                             saveperiodParam.disable();
//                                         } else {
//                                             startAt.enable();
//                                             textInput.disable();
//                                             fileSelect.disable();
//                                             startAt.disable();
//                                             uploadButton.disable();
//                                             upload.disable();
//
//                                             naviParam.disable();
//                                             saveParam.disable();
//                                             saveaimParam.disable();
//                                             saveperiodParam.disable();
//                                         }
//                                     }
//                                 },
//                                     {
//                                         type: 'hbox',
//                                         widths: ['50%','50%'],
//                                         children:
//                                             [
//                                                 {
//                                                     type : 'select',
//                                                     id: 'fileselect',
//                                                     label: 'Angehängte Datei auswählen',
//                                                     items : fileItems,
//                                                     onLoad : function ()
//                                                     {
//                                                         var dialog = this.getDialog();
//                                                         var filelistUrl = $('*[data-cs-filelisturl]').data("csFilelisturl").path;
//
//                                                         if (filelistUrl) {
//                                                             $.ajax({
//                                                                 url: filelistUrl,
//                                                             }).done(function(response) {
//                                                                 // var dialog = $this.getDialog();
//                                                                 // fill dropdown with file entries
//                                                                 var fileSelect = dialog.getContentElement( 'documentTab', 'fileselect' );
//                                                                 for(i = 0; i < response.files.length; i++){
//
//                                                                     fileSelect.add(
//                                                                         response.files[i].name,
//                                                                         response.files[i].path,
//                                                                         response.files[i].ext
//                                                                     );
//                                                                 }
//                                                             });
//                                                         }
//                                                     },
//                                                     onChange : function ()
//                                                     {
//                                                         // disable textInput if file is selected
//                                                         var dialog = this.getDialog();
//                                                         var inputUrl = dialog.getContentElement( 'documentTab', 'documentUrl' );
//                                                         if(this.getValue() == 'null'){
//                                                             inputUrl.enable();
//                                                             inputUrl.setValue('');
//                                                             inputUrl.focus();
//                                                         } else {
//                                                             inputUrl.disable();
//                                                             // set file url in textInput
//
//                                                             var input = this.getInputElement().$;
//
//                                                             if(dialog.getContentElement('documentTab', 'selectbox').getValue() == 'onyx') {
//                                                                 fileName = input.options[input.selectedIndex].text;
//                                                                 fileUrl = this.getValue();
//                                                             } else {
//                                                                 fileUrl = this.getValue();
//                                                             }
//
//                                                             encodeFileUrl = encodeURI(fileUrl);
//                                                             //												alert(encodeFileUrl);
//                                                             inputUrl.setValue(encodeFileUrl);
//                                                         }
//                                                     }
//                                                 },
//                                                 {
//                                                     type: 'vbox',
//                                                     children:
//                                                         [
//                                                             {
//                                                                 type: 'file',
//                                                                 id: 'upload',
//                                                                 label: 'neue Datei hochladen',
//                                                                 style: 'height:40px',
//                                                                 size: 38
//                                                             },
//                                                             {
//                                                                 type: 'fileButton',
//                                                                 id: 'uploadButton',
//                                                                 filebrowser: 'documentTab:documentUrl',
//                                                                 label: 'Hochladen',
//                                                                 'for': [ 'documentTab', 'upload' ]
//                                                             }
//                                                         ]
//                                                 }
//                                             ]
//                                     },
//                                     {
//                                         id : 'linkText',
//                                         type : 'text',
//                                         label : 'Text',
//                                         onLoad : function ()
//                                         {
//                                             this.disable();
//                                         }
//                                     },
//                                     {
//                                         type : 'hbox',
//                                         widths : [ '70%', '15%', '15%' ],
//                                         children :
//                                             [
//                                                 {
//                                                     id : 'documentUrl',
//                                                     type : 'text',
//                                                     label : '"Shortcode für Wordpress" von Slideshare unter "Share" verwenden [slideshare id"..."]',
//                                                     validate : function ()
//                                                     {
//                                                         if ( this.isEnabled() )
//                                                         {
//                                                             if ( !this.getValue() )
//                                                             {
//                                                                 alert( 'Bitte geben Sie eine URL an.' );
//                                                                 return false;
//                                                             }
//                                                         }
//                                                     }
//                                                 }
//                                             ]
//                                     },
//                                     {
//                                         type : 'hbox',
//                                         widths : ['20%', '20%', '20%', '20%', '20%'],
//                                         children :
//                                             [
//                                                 {
//                                                     type : 'text',
//                                                     id : 'documentWidth',
//                                                     width : '60px',
//                                                     label : 'Breite',
//                                                     'default' : '640',
//                                                     validate : function ()
//                                                     {
//                                                         if ( this.getValue() )
//                                                         {
//                                                             var width = parseInt ( this.getValue() ) || 0;
//
//                                                             if ( width === 0 )
//                                                             {
//                                                                 alert( 'invalidWidth' );
//                                                                 return false;
//                                                             }
//                                                         }
//                                                         else {
//                                                             alert( 'noWidth' );
//                                                             return false;
//                                                         }
//                                                     }
//                                                 },
//                                                 {
//                                                     type : 'text',
//                                                     id : 'documentHeight',
//                                                     width : '60px',
//                                                     label : 'Höhe',
//                                                     'default' : '360',
//                                                     validate : function ()
//                                                     {
//                                                         if ( this.getValue() )
//                                                         {
//                                                             var height = parseInt ( this.getValue() ) || 0;
//
//                                                             if ( height === 0 )
//                                                             {
//                                                                 alert( 'invalidHeight' );
//                                                                 return false;
//                                                             }
//                                                         }
//                                                         else {
//                                                             alert( 'noHeight' );
//                                                             return false;
//                                                         }
//                                                     }
//                                                 },
//                                                 {
//                                                     type : 'select',
//                                                     id : 'float',
//                                                     label : 'Ausrichtung',
//                                                     items : floatItems
//                                                 },
//                                                 {
//                                                     type : 'text',
//                                                     id : 'border',
//                                                     width : '60px',
//                                                     label : 'Rahmen',
//                                                     'default' : ''
//                                                 },
//                                                 {
//                                                     type : 'text',
//                                                     id : 'marginH',
//                                                     width : '60px',
//                                                     label : 'H-Abstand',
//                                                     'default' : ''
//                                                 },
//                                                 {
//                                                     type : 'text',
//                                                     id : 'marginV',
//                                                     width : '60px',
//                                                     label : 'V-Abstand',
//                                                     'default' : ''
//                                                 }
//                                             ]
//                                     },
//                                     {
//                                         type: 'hbox',
//                                         widths : [ '50px', '50px', '50px', '50px','50px'],
// //										style: 'margin-top:px',
//                                         children:
//                                             [
//                                                 {
//                                                     type : 'text',
//                                                     id : 'startAt',
//                                                     width : '60px',
//                                                     label : 'Start mit Folie',
//                                                     'default' : ''
//                                                 }
//                                             ]
//                                     },
//                                     {
//                                         type: 'hbox',
//                                         widths : [ '50px', '50px'],
// //										style: 'margin-top:px',
//                                         children:
//                                             [
//                                                 {
//                                                     type : 'checkbox',
//                                                     id : 'naviParam',
//                                                     //width : '60px',
//                                                     label : 'Navigation anzeigen',
//                                                     'default' : ''
//                                                 },
//                                                 {
//                                                     type : 'checkbox',
//                                                     id : 'saveaimParam',
//                                                     width : '60px',
//                                                     label : 'In Abschnitte Speichern',
//                                                     'default' : ''
//                                                 },
//                                                 {
//                                                     type : 'select',
//                                                     id : 'saveParam',
//                                                     width : '60px',
//                                                     label : 'Speichern',
//                                                     'default' : '',
//                                                     items: saveItems
//                                                 },
//                                                 {
//                                                     type : 'select',
//                                                     id : 'saveperiodParam',
//                                                     width : '60px',
//                                                     label : 'Abschnitt pro',
//                                                     'default' : '',
//                                                     items: saveperiodItems
//                                                 }
//                                             ]
//                                     },
//                                     {
//                                         type: 'hbox',
//                                         widths : [ '50px', '50px'],
// //										style: 'margin-top:px',
//                                         children:
//                                             [
//                                                 {
//                                                     type : 'checkbox',
//                                                     id : 'repParam',
//                                                     label : 'Auswertung'
//                                                 },
//                                                 {
//                                                     type : 'checkbox',
//                                                     id : 'repMode',
//                                                     label : 'Statistische Auswertung'
//                                                 }
//                                             ]
//                                     }
//                                 ]
//                         }
//                         ],
//                     onOk: function()
//                     {
//                         var content = '';
//                         var tabFloat = this.getValueOf( 'documentTab', 'float');
//
//                         var style = '',
//                             tempStyle = '',
//                             borderWidth = this.getValueOf( 'documentTab', 'border' ),
//                             horizontalMargin = this.getValueOf( 'documentTab', 'marginH'),
//                             verticalMargin = this.getValueOf( 'documentTab', 'marginV');
//
//                         style = 'style="';
//
//                         if ( borderWidth !== "" ) {
//                             tempStyle += 'border-style: solid; border-width:' + borderWidth + 'px;';
//                         }
//
//                         if ( horizontalMargin != "" ) {
//                             tempStyle += 'margin-top:' + horizontalMargin + 'px;';
//                             tempStyle += 'margin-bottom:' + horizontalMargin + 'px;';
//                         }
//
//                         if ( verticalMargin !== "" ) {
//                             tempStyle += 'margin-left:' + verticalMargin + 'px;';
//                             tempStyle += 'margin-right:' + verticalMargin + 'px;';
//                         }
//
//                         if(tabFloat != 'null' && tabFloat == 'right'){
//                             tempStyle += 'float:right;';
//                         } else if (tabFloat != 'null' && tabFloat == 'left') {
//                             tempStyle += 'float:left;';
//                         }
//                         style += tempStyle;
//                         style += '"';
//
//                         // document type
//                         if(this.getValueOf('documentTab', 'selectbox') == 'slideshare'){
//
// //								content = 'http://lecture2go.uni-hamburg.de/';
//
//                             var width = this.getValueOf( 'documentTab', 'documentWidth' );
//                             var height = this.getValueOf( 'documentTab', 'documentHeight' );
//                             var documentUrl = this.getValueOf( 'documentTab', 'documentUrl');
//                             var startAt = this.getValueOf( 'documentTab', 'startAt');
//                             var floatValue = '';
//                             var param = '';
//
//                             if (startAt !== "") {
//                                 param += '?startSlide='+startAt;
//                             }
//
//                             var regex = /iframe.src="([^"]*)"/,
//                                 match,
//                                 docId;
//
//                             // wordpress shortcode regex
//                             // var wp_regex = /\[slideshare id=\d*&doc=(.*)]/,
//                             // 	match,
//                             // 	docId;
//
//                             if(documentUrl.match(regex)){
//                                 match = documentUrl.match(regex);
//                                 docId = match[1];
//                             }
//
//                             content += '<iframe src="'+docId+param+'" width="425" height="355" frameborder="0" marginwidth="0" marginheight="0" scrolling="no" style="border:1px solid #CCC; border-width:1px; margin-bottom:5px; max-width: 100%;" allowfullscreen> </iframe>';
//
//
//                             // flash
//                             // content += '<object width="' + width + '" height="' + height + '>';
//                             // content += '<param value="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + docId + '" name="movie">';
//                             // content += '<param value="true" name="allowFullScreen">';
//                             // content += '<param value="always" name="allowScriptAccess">';
//                             // content += '<embed ' + style + ' width="' + width + '" height="' + height + '" wmode="opaque" allowfullscreen="true" allowscriptaccess="always" type="application/x-shockwave-flash" src="http://static.slideshare.net/swf/ssplayer2.swf?doc=' + docId + '&amp;rel=0' + param +'">';
//                             // content += '</object>';
//
//                         } else if(this.getValueOf('documentTab', 'selectbox') == 'mdo') {
//
//                             var documentUrl = this.getValueOf( 'documentTab', 'documentUrl');
//                             var linkText = this.getValueOf('documentTab','linkText');
//                             // perform ajax request
//                             var json_data = new Object();
//                             // json_data.mdo_search      = jQuery('input[name="ckeditor_mdo_search"]').val();
//                             // json_data.mdo_andor       = jQuery('select[name="ckeditor_mdo_andor"]').val();
//                             // json_data.mdo_wordbegin   = jQuery('select[name="ckeditor_mdo_wordbegin"]').val();
//                             // json_data.mdo_titletext   = jQuery('select[name="ckeditor_mdo_titletext"]').val();
//
//                             // id
//                             var regex = /id=(.*)&/;
//
//                             if(documentUrl.match(regex)) {
//                                 match = documentUrl.match(regex);
//                                 id = match[1];
//                                 json_data.identifier = id;
//
//                                 var cid = unescape((RegExp('cid=(.+?)(&|$)').exec(window.location.href)||[,null])[1]);
//                                 var ckInstance = this.getParentEditor();
//
//                                 mdoAjax(cid, linkText, json_data, function(retVal) {
//                                     ckInstance.insertHtml(retVal);
//                                 });
//
//                                 //                      jQuery.ajax({
//                                 //                          url:      'commsy.php?cid=' + cid + '&mod=ajax&fct=mdo_perform_search&action=search',
//                                 //                          data:     json_data,
//                                 //                          success:  function(message) {
//                                 //                              var result = eval('(' + message + ')');
//                                 //                              if(result.status === 'success' && result.data.length > 0) {
//                                 //                              	// get content by ajax
//                                 // // link in die Mediathek
//                                 // content = '<a href="'+result.data.url+'">'+linkText+'</a>';
//                                 //                              }
//
//                                 //                          }
//                                 //                      });
//                             }
//
//
//
//                         } else if (this.getValueOf('documentTab', 'selectbox') == 'onyx') {
//
//                             var naviParam = this.getValueOf('documentTab','naviParam');
//                             var saveParam = this.getValueOf('documentTab','saveParam');
//                             var saveaimParam = this.getValueOf('documentTab','saveaimParam');
//                             var saveperiodParam = this.getValueOf('documentTab','saveperiodParam');
//
//                             // rep
//                             var repParam = this.getValueOf('documentTab','repParam');
//                             var repMode = this.getValueOf('documentTab','repMode');
//
//                             if(repParam){
//                                 var repLinkParam = 4;
//                                 if(repMode){
//                                     repLinkParam = 5;
//                                 }
//                                 var linkText = this.getValueOf('documentTab','linkText');
//                                 var link = this.getValueOf('documentTab', 'documentUrl');
//
//                                 link = link.replace("showqti", "showrep");
//                                 link = link.replace("iid", "fid");
//
//                                 if(linkText == ''){
//                                     linkText = fileName;
//                                 }
//
//                                 var a = editor.document.createElement( 'a' );
//                                 //					            a.setAttribute( 'href', link);
//
//                                 a.setAttribute( 'href', link+'&choice='+repLinkParam);
//                                 a.setAttribute('target', '_blank');
//                                 a.setAttribute('style', tempStyle);
//                                 a.setText( linkText );
//
//
//                                 editor.insertElement( a );
//
//                             } else {
//
//                                 var paramArray = new Array();
//                                 var paramArray2 = new Array();
//
//                                 //var params;
//                                 //paramArray['#'] = new Array();
//
//                                 //paramArray['0'] = "";
//                                 //paramArray['1'] = fileName;
//
//                                 paramString = '';
//
//                                 if(naviParam){
//                                     paramArray['navi'] = naviParam;
//                                     paramArray2.push('navi');
//                                     paramArray2.push(naviParam);
//
//                                     paramString = 'navi='+naviParam;
//
//                                     //params = "navi="+naviParam;
//                                 }
//                                 if(saveParam != 'null'){
//                                     paramArray['save'] = saveParam;
//                                     paramArray2.push('save');
//                                     paramArray2.push(saveParam);
//
//                                     paramString = paramString+" save="+saveParam;
//                                     //params = params + "save="+saveParam;
//                                 }
//                                 if(saveaimParam != ''){
//                                     paramArray['saveaim'] = saveaimParam;
//                                     paramArray2.push('saveaim');
//                                     paramArray2.push(saveaimParam);
//
//                                     paramString = paramString+" saveaim="+saveaimParam;
//                                     //params = params + "saveaim="+saveaimParam;
//                                 }
//                                 if(saveperiodParam != ''){
//                                     paramArray['saveperiod'] = saveperiodParam;
//                                     paramArray2.push('saveperiod');
//                                     paramArray2.push(saveperiodParam);
//
//                                     paramString = paramString+" saveperiod="+saveperiodParam;
//                                     //params = params + "saveperiod="+saveperiodParam;
//                                 }
//                                 paramArray['#'] = paramArray2;
//
//                                 var saveaimParam;
//                                 if(saveaimParam){
//                                     saveaimSection = "section";
//                                 } else {
//                                     saveaimSection = "";
//                                 }
//
//                                 //var fileIdRegEx = /commsy.php\/\.*iid=(\d*)/;
//                                 // regex filename
//                                 var fileNameRegEx = /file\/(.*)/;
//                                 var filename = this.getValueOf('documentTab','fileselect');
//                                 var link = this.getValueOf('documentTab', 'documentUrl');
//
//                                 if (filename == 'null') {
//                                     match = link.match(fileNameRegEx);
//                                     filename = match[1];
//                                 }
//
//                                 link = link.replace("material", "onyx");
//                                 link = link.replace("getfile", "showqti");
//                                 link = link.replace("/"+filename, "");
//
//                                 var data = {
//                                     0: "(:qti " + filename + " "+paramString+":)",
//                                     1: fileName,
//                                     navi: naviParam,
//                                     save: saveParam,
//                                     saveaim: saveaimSection,
//                                     saveperiod: saveperiodParam,
//                                     "#": ["navi",naviParam,"save",saveParam,"saveaim",saveaimParam,"saveperiod",saveperiodParam]
//                                 };
//
//                                 var jsonString = JSON.stringify(data);
//                                 var paramString = "&params="+encodeURIComponent(jsonString);
//
//                                 var cid = getUrlParam('cid');
//
//                                 var dialog = this;
//                                 var linkText = this.getValueOf('documentTab','linkText');
//
//
//
//                                 if(!linkText){
//                                     var linkText = 'Onyx Test';
//                                     // var linkText = filename;
//                                 }
//                                 var a = editor.document.createElement( 'a' );
//                                 //					            a.setAttribute( 'href', link);
//
//                                 a.setAttribute( 'href', link+paramString);
//                                 a.setAttribute('target', '_blank');
//                                 a.setAttribute('style', tempStyle);
//                                 a.setText( linkText );
//
//
//                                 editor.insertElement( a );
//                             }
//
//                         }
//
//
//                         var instance = this.getParentEditor();
//                         instance.insertHtml( content );
//                     }
//                 };
//             });













// function mdoAjax (cid, linkText, json_data, callback) {
//     var identifier = json_data.identifier;
//     jQuery.ajax({
//         url:      'commsy.php?cid=' + cid + '&mod=ajax&fct=mdo_perform_search&action=search',
//         data:     json_data,
//         success:  function(message) {
//             var result = eval('(' + message + ')');
//             if(result.status === 'success') {
//                 // get content by ajax
//                 // link in die Mediathek
//                 content = '<a href="'+result.data.url+'" class="mdoLink" id="'+identifier+'" target="_new">'+linkText+'</a>';
//                 callback(content);
//             }
//
//         }
//     });
// }
//
// function getUrlParam( param )
// {
//     param = param.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
//
//     var regexS = "[\\?&]"+param+"=([^&#]*)";
//     var regex = new RegExp( regexS );
//     var results = regex.exec( window.location.href );
//
//     if ( results == null )
//         return "";
//     else
//         return results[1];
// }