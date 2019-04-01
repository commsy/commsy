;(function () {

    "use strict";

    CKEDITOR.dialog.add('commsyhelpDialog', function (editor) {

        return {
            title: editor.lang.commsyhelp.title,
            minWidth: 500,
            minHeight: 200,
            contents : [
                {
                    id : 'tab',
                    label : editor.lang.commsyhelp.formatting,
                    title : editor.lang.commsyhelp.formatting,
                    expand : false,
                    padding : 0,
                    elements :
                        [
                            {
                                type : 'html',
                                html :
                                    '<div>' +
                                        'Die nachfolgend beschriebenen Textauszeichnungen können direkt verwendet werden:' +
                                        '<br><br>' +
                                        '<span style="font-weight: bold;">Listen:</span> # für numerierte, - für unnumerierte Listen<br>' +
                                        '<span style="font-weight: bold;">Auszeichnungen:</span> *text* wird zu <span style="font-weight: bold;">text</span>, _text_ wird zu <span style="font-style: italic;">text</span><br>' +
                                        '<span style="font-weight: bold;">Überschriften:</span> !Text, !!Text oder !!!Text erzeugt Überschriften<br>' +
                                        '<span style="font-weight: bold;">Trennlinie:</span> --- für eine horizontale Linie' +
                                        '<br><br>' +
                                        'Wenn Sie eine Auszeichnung verwenden möchten, ohne diese zu interpretieren können Sie diese escapen: \\!Text für !Text.' +
                                        '<br><br>' +
                                        '<span style="font-weight: bold;">Links:</span><br>' +
                                        '<span style="font-weight: bold;">Externe Links:</span> (:link ZIEL text=TEXT:)<br>' +
                                        '<span style="font-weight: bold;">Interne Links:</span> (:item ID text=TEXT:)<br>' +
                                        '<br><br>' +
                                        'Argumente:' +
                                        '<ul>' +
                                            '<li><span style="font-weight: bold;">newwin</span> In neuem Tab öffnen</li>' +
                                            '<li><span style="font-weight: bold;">text=TEXT</span> Einfache Beschriftung</li>' +
                                            '<li><span style="font-weight: bold;">text=\'LANGER TEXT\'</span> Lange Beschriftung</li>' +
                                        '</ul>' +
                                        '<br>' +
                                        'Beispiel: (:item 12345 text=\'My File\' newwin:)' +
                                        '<br><br>' +
                                        '<span style="font-weight: bold;">Medien:</span><br>' +
                                        'Für die Einbettung von Medien können Sie die eingebauten Dialoge des Editors verwenden.' +
                                    '</div>'
                            }
                        ]
                }
            ],
            buttons : []
        };
    });

})();