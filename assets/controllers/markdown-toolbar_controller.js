import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static values = {
        target: String
    }

    insertText(before, after = '') {
        const textarea = document.getElementById(this.targetValue);
        if (!textarea) return;

        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const selectedText = text.substring(start, end);
        
        const replacement = before + selectedText + after;
        textarea.value = text.substring(0, start) + replacement + text.substring(end);
        
        // Focus and select the inserted text
        textarea.focus();
        textarea.setSelectionRange(start + before.length, start + before.length + selectedText.length);
        
        // Trigger input event to update autogrow and any other listeners
        textarea.dispatchEvent(new Event('input', { bubbles: true }));
    }

    insertHeading1() {
        this.insertText('# ');
    }

    insertHeading2() {
        this.insertText('## ');
    }

    insertBold() {
        this.insertText('**', '**');
    }

    insertList() {
        this.insertText('- ');
    }
}
