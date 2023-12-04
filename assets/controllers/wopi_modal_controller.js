import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  static targets = [ "content", "modal" ]
  static values = {
    url: String
  }

  initialize() {
    // bind context of event handler callback functions
    this._show = this._show.bind(this);
    this._hide = this._hide.bind(this);
  }

  connect() {
    // Using .addEventListener seems not to be working with the 'show.uk.modal' event
    $(this.modalTarget).on('show.uk.modal', this._show);
    $(this.modalTarget).on('hide.uk.modal', this._hide);
  }

  disconnect() {
    $(this.modalTarget).off('show.uk.modal', this._show);
    $(this.modalTarget).off('hide.uk.modal', this._hide);
  }

  _show(event) {
    const frame = document.createElement('iframe');
    frame.style.width = '100%';
    frame.style.height = '100%';
    this.contentTarget.replaceChildren(frame);

    frame.src = this.urlValue;
  }

  _hide() {
    window.location.reload(true);
  }
}
