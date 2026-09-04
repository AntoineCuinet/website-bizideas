import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        version: String
    };

    connect() {
        const dismissedVersion = localStorage.getItem('whats_new_dismissed_version');
        if (dismissedVersion !== this.versionValue) {
            this.element.style.display = 'inline-flex';
        } else {
            this.element.style.display = 'none';
        }
    }

    dismiss(event) {
        event.preventDefault();
        event.stopPropagation();
        localStorage.setItem('whats_new_dismissed_version', this.versionValue);
        this.element.style.display = 'none';
    }
}
