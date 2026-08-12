import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['modal'];

    connect() {
        // Proactively scan for any modal that has data-auto-open="true" and show it
        this.modalTargets.forEach((modal) => {
            if (modal.getAttribute('data-auto-open') === 'true') {
                modal.showModal();
            }
        });
    }

    open(event) {
        const ideaId = event.currentTarget.getAttribute('data-idea-id');
        const modal = this.element.querySelector(`#modal-${ideaId}`);
        if (modal) {
            modal.showModal();
        }
    }

    close(event) {
        event.preventDefault();
        event.stopPropagation();
        const ideaId = event.currentTarget.getAttribute('data-idea-id');
        const modal = this.element.querySelector(`#modal-${ideaId}`);
        if (modal) {
            modal.close();
        }
    }
}
