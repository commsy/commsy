# CKEditor

## Voraussetzungen
- CommSy
- aktuelle CKEditor Version aus dem Master branch

### CKEditor
In dem Ordner /dev/builder/build-config.js anpassen und danach die build.sh ausführen. 

### CommSy
Die neue build Version in den CommSy Ordner /htdocs/js/3rdParty/ckeditor_X.X.X einfügen. Die Pfade zum CKEditor Ordner in den folgenden Dateien anpassen: 
- /htdocs/js/build.js
- /htdocs/js/src/buildConfig.js
- /htdocs/js/src/layerConfig.js
- /htdocs/js/src/sourceConfig.js

Pfad für die Einbindung des CKEditors in der Datei /htdocs/templates/themes/default/layout_html.tpl anpassen.