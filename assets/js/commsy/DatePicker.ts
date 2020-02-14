'use strict';

const flatpickr = require("flatpickr");
//import flatpickr from "flatpickr";

export class DatePicker {
    private defaultOptions = {
        'time_24hr': true
    };

    public static bootstrap() {
        $(".js-flatpickr").each(function() {
            let edit = new DatePicker();
            edit.init(this);
        });
    }

    private init(element) {
        let options = $(element).data('flatpickr-options');
        flatpickr(element, $.extend(this.defaultOptions, options));
    }
}