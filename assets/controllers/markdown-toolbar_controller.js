import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        target: String
    }

    insertText(before, after = '', defaultText = '') {
        const textarea = document.getElementById(this.targetValue);
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selectedText = text.substring(start, end);
        
        const contentToInsert = selectedText || defaultText;
        const replacement = before + contentToInsert + after;
        textarea.value = text.substring(0, start) + replacement + text.substring(end);
        
        // Focus and select the inserted text
        textarea.focus();
        if (selectedText) {
            textarea.setSelectionRange(start + before.length, start + before.length + selectedText.length);
        } else if (defaultText) {
            textarea.setSelectionRange(start + before.length, start + before.length + defaultText.length);
        } else {
            textarea.setSelectionRange(start + replacement.length, start + replacement.length);
        }
        
        // Trigger input event to update autogrow and any other listeners
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    insertHeading1() {
        this.insertText('# ', '', 'Titre');
    }

    insertHeading2() {
        this.insertText('## ', '', 'Sous-titre');
    }

    insertBold() {
        this.insertText('**', '**', 'texte en gras');
    }

    insertList() {
        this.insertText('- ', '', 'élément de liste');
    }

    insertImage() {
        this.insertText('![', '](https://)', 'description de l\'image');
    }

    insertLink() {
        this.insertText('[', '](https://)', 'titre du lien');
    }
}
