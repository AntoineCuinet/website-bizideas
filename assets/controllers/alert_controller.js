import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    close() {
        this.element.classList.add('fade-out');
        setTimeout(() => {
            this.element.remove();
        }, 300);
    }
}
