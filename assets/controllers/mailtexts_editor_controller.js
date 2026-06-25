import { Controller } from '@hotwired/stimulus';
import ClassicEditor from "@ckeditor/ckeditor5-build-classic";
import {getComponent} from "@symfony/ux-live-component";

let germanEditor;
let englishEditor;
let focusedEditor;

const toolbarConfig = {
  removeItems: ['uploadImage', 'mediaEmbed']
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

    return ClassicEditor.create(htmlNode, {
      toolbar: toolbarConfig,
    }).then(newEditor => {
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
