import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
  initialize() {
    this._onPreConnect = this._onPreConnect.bind(this);
  }

  connect() {
    this.element.addEventListener('autocomplete:pre-connect', this._onPreConnect);
  }

  disconnect() {
    this.element.removeEventListener('autocomplete:connect', this._onPreConnect);
  }

  _onPreConnect(event) {
    event.detail.options.render.option_create = function (data, escape) {
      return '<div class="create">+ <strong>' + escape(data.input) + '</strong>&hellip;</div>';
    };

    console.log(event.detail.options); // Options that will be used to initialize TomSelect
    event.detail.options.onChange = (value) => {
      // ...
    };
  }
}
