import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        view: Object,
    };

    syncing = false;

    connect() {
        // Early return, if there is already an instance
        const $el = $(this.element);
        if ($el.data('jstree')) return;

        this.dispatch('tree:init');

        const coreConfig = {
            themes: { icons: false },
            multiple: true,
            data: this.viewValue.data,
        };
        const extraOptions = this.viewValue.options ?? {};

        $(this.element).jstree({ core: coreConfig, ...extraOptions });

        // Synchronize changes with checkbox states
        this.bindTreeToCheckboxes();

        // After initialization, sync tree state with checkboxes
        $(this.element).on('ready.jstree', () => {
            this.syncTreeFromCheckboxes();
        });
    }

    disconnect() {
        // Check for existing instance
        const instance = $(this.element).jstree(true);
        if (!instance) return;

        instance.destroy();

        this.dispatch('tree:disconnect');
    }

    // This is called by stimulus when the viewValue changes.
    viewValueChanged() {
        // Check for existing instance
        const instance = $(this.element).jstree(true);
        if (!instance) return;

        // Update data
        instance.settings.core.data = this.viewValue.data;
        instance.refresh();
    }

    bindTreeToCheckboxes() {
        $(this.element).on('changed.jstree', (event, data) => {
            if (this.syncing) return;
            if (!data || !Array.isArray(data.selected)) return;

            const selectedIds = new Set(data.selected);

            const checkboxes = this.getCheckboxes();
            this.syncing = true;
            try {
                checkboxes.forEach((checkbox) => {
                    const value = String(checkbox.value);
                    const shouldBeChecked = selectedIds.has(value);

                    if (checkbox.checked !== shouldBeChecked) {
                        checkbox.checked = shouldBeChecked;
                        checkbox.dispatchEvent(new Event('change', {bubbles: true}));
                    }
                });
            } finally {
                this.syncing = false;
            }
        });
    }

    syncTreeFromCheckboxes() {
        const instance = $(this.element).jstree(true);
        if (!instance) return;

        const checkedIds = this.getCheckboxes()
            .filter((c) => c.checked)
            .map((c) => String(c.value));

        // suppress_events
        instance.deselect_all(true);
        checkedIds.forEach((id) => instance.select_node(id, true));
    }

    getCheckboxes() {
        const form = this.element.closest('form');
        if (!form) return [];
        return Array.from(form.querySelectorAll(this.viewValue.checkboxSelector));
    }
}
