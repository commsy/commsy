import { Controller } from '@hotwired/stimulus';
import {
  Autoformat,
  BlockQuote,
  Bold,
  ClassicEditor,
  Essentials,
  Heading,
  Indent,
  Italic,
  Link,
  List,
  Paragraph,
  PasteFromOffice,
  Table,
  TableToolbar,
  TextTransformation,
} from 'ckeditor5';
import 'ckeditor5/ckeditor5.css';
import {getComponent} from "@symfony/ux-live-component";

let germanEditor;
let englishEditor;
let focusedEditor;

/*
* The predefined @ckeditor/ckeditor5-build-classic package is no longer
* maintained, so the editor is composed from the ckeditor5 package instead.
* Plugins and toolbar reproduce what the classic build offered here: its
* default toolbar minus uploadImage and mediaEmbed, which this editor used to
* strip via removeItems.
*/
const editorConfig = {
  // CKEditor 5 is dual-licensed; 'GPL' selects the open source terms, which
  // match our own GPLv2. Since v44 the key has to be stated explicitly.
  licenseKey: 'GPL',
  plugins: [
    Autoformat,
    BlockQuote,
    Bold,
    Essentials,
    Heading,
    Indent,
    Italic,
    Link,
    List,
    Paragraph,
    PasteFromOffice,
    Table,
    TableToolbar,
    TextTransformation,
  ],
  toolbar: {
    items: [
      'undo', 'redo', '|',
      'heading', '|',
      'bold', 'italic', '|',
      'link', 'insertTable', 'blockQuote', '|',
      'bulletedList', 'numberedList', 'outdent', 'indent',
    ],
  },
  table: {
    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells'],
  },
}

/*
* The following line makes this controller "lazy": it won't be downloaded until needed
* See https://github.com/symfony/stimulus-bridge#lazy-controllers
*/
/* stimulusFetch: 'lazy' */
export default class extends Controller {
  static values = {
    fieldNameGerman: String,
    fieldNameEnglish: String
  }

  async initialize() {
    this.component = await getComponent(this.element);

    window.addEventListener('editor:get-value', () => {
      germanEditor.setData(document.getElementById(this.fieldNameGermanValue).value);
      englishEditor.setData(document.getElementById(this.fieldNameEnglishValue).value);
    });
  }

  connect() {
    this.createEditor(this.fieldNameGermanValue).then(newEditor => {
        germanEditor = newEditor;
      }).catch(error => {
      console.error(error);
    });

    this.createEditor(this.fieldNameEnglishValue).then(newEditor => {
        englishEditor = newEditor;
      }).catch(error => {
      console.error(error);
    });
  }

  createEditor(node) {
    let htmlNode = document.getElementById(node);

    return ClassicEditor.create(htmlNode, editorConfig).then(newEditor => {
      newEditor.model.document.on('change:data', () => {
        if (htmlNode.value !== newEditor.getData()) {
          htmlNode.value = newEditor.getData();
          htmlNode.dispatchEvent(new Event('change', { bubbles: true }));
        }
      });

      // remember which editor the cursor is in, so a placeholder gets inserted there
      newEditor.editing.view.document.on('change:isFocused', (evt, name, isFocused) => {
        if (isFocused) {
          focusedEditor = newEditor;
        }
      });

      return newEditor;
    })
  }

  // insert a placeholder token (e.g. "{recipientName}") at the cursor of the focused editor
  insert(event) {
    const token = event.params.token;
    const editor = focusedEditor || germanEditor;
    if (!editor || !token) {
      return;
    }

    editor.model.change(writer => {
      editor.model.insertContent(writer.createText(token));
    });
    editor.editing.view.focus();
  }
}
