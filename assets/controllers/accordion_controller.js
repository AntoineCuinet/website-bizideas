import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        alwaysOpen: { type: Boolean, default: true }
    };

    toggle(event) {
        event.preventDefault();
        const button = event.currentTarget;
        const targetId = button.getAttribute('aria-controls') || button.dataset.accordionTarget;
        const targetCollapse = this.element.querySelector(`#${targetId}`);

        if (!targetCollapse) return;

        const isExpanded = button.getAttribute('aria-expanded') === 'true';

        if (!this.alwaysOpenValue && !isExpanded) {
            // Close other items in this accordion if alwaysOpen is false
            const allButtons = this.element.querySelectorAll('.accordion-button');
            allButtons.forEach((btn) => {
                if (btn !== button) {
                    btn.setAttribute('aria-expanded', 'false');
                    btn.classList.add('collapsed');
                    const otherId = btn.getAttribute('aria-controls') || btn.dataset.accordionTarget;
                    const otherCollapse = this.element.querySelector(`#${otherId}`);
                    if (otherCollapse) {
                        otherCollapse.classList.remove('show');
                    }
                }
            });
        }

        if (isExpanded) {
            button.setAttribute('aria-expanded', 'false');
            button.classList.add('collapsed');
            targetCollapse.classList.remove('show');
        } else {
            button.setAttribute('aria-expanded', 'true');
            button.classList.remove('collapsed');
            targetCollapse.classList.add('show');
        }
    }
}
