import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['viewContainer', 'editContainer', 'textarea', 'renderedContent', 'saveBtn', 'status'];
    static values = {
        version: String,
        url: String,
        token: String
    };

    connect() {
        this.originalContent = this.hasTextareaTarget ? this.textareaTarget.value : '';
    }

    startEdit(event) {
        event.preventDefault();
        this.originalContent = this.textareaTarget.value;
        this.viewContainerTarget.style.display = 'none';
        this.editContainerTarget.style.display = 'block';
        this.textareaTarget.focus();

        // Trigger input event and immediately adjust height
        this.textareaTarget.style.height = 'auto';
        this.textareaTarget.style.height = `${this.textareaTarget.scrollHeight + 3}px`;
        this.textareaTarget.dispatchEvent(new Event('input', { bubbles: true }));
    }

    cancelEdit(event) {
        event.preventDefault();
        this.textareaTarget.value = this.originalContent;
        this.editContainerTarget.style.display = 'none';
        this.viewContainerTarget.style.display = 'block';
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = '';
        }
    }

    async save(event) {
        event.preventDefault();
        const content = this.textareaTarget.value;
        const originalBtnText = this.saveBtnTarget.textContent;

        this.saveBtnTarget.disabled = true;
        this.saveBtnTarget.textContent = 'Enregistrement...';
        if (this.hasStatusTarget) {
            this.statusTarget.textContent = '';
        }

        try {
            const response = await fetch(this.urlValue, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    version: this.versionValue,
                    content: content,
                    _token: this.tokenValue
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                this.originalContent = content;
                this.renderedContentTarget.innerHTML = data.html || '<p class="text-muted italic">Aucun contenu.</p>';
                this.editContainerTarget.style.display = 'none';
                this.viewContainerTarget.style.display = 'block';

                // Subtle flash/feedback animation on view container
                this.viewContainerTarget.classList.add('highlight-flash');
                setTimeout(() => {
                    this.viewContainerTarget.classList.remove('highlight-flash');
                }, 1500);
            } else {
                if (this.hasStatusTarget) {
                    this.statusTarget.textContent = data.error || 'Une erreur est survenue lors de la sauvegarde.';
                }
            }
        } catch (err) {
            if (this.hasStatusTarget) {
                this.statusTarget.textContent = 'Erreur réseau lors de la sauvegarde.';
            }
        } finally {
            this.saveBtnTarget.disabled = false;
            this.saveBtnTarget.textContent = originalBtnText;
        }
    }
}
