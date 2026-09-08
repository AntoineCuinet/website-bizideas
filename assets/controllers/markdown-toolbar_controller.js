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

    insertImage() {
        // Create a hidden file input dynamically
        const fileInput = document.createElement('input');
        fileInput.type = 'file';
        fileInput.accept = 'image/*';
        fileInput.style.display = 'none';

        fileInput.addEventListener('change', async (event) => {
            const file = event.target.files[0];
            if (!file) return;

            const formData = new FormData();
            formData.append('image', file);

            // Optional: insert a placeholder while uploading
            const textarea = document.getElementById(this.targetValue);
            const placeholder = `![Uploading ${file.name}...]()`;
            this.insertText(placeholder);

            try {
                // Assuming we can derive the upload URL from the current domain or a data attribute
                // For simplicity, hardcode the upload endpoint route path
                const response = await fetch('/whats-new/upload-image', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error('Upload failed');
                }

                const data = await response.json();
                
                // Replace the placeholder with the actual markdown image syntax
                const replacementText = `![${data.filename}](${data.url})`;
                
                // Preserve cursor position
                const currentStart = textarea.selectionStart;
                const currentEnd = textarea.selectionEnd;
                
                // Find where the placeholder is
                const placeholderIndex = textarea.value.indexOf(placeholder);
                if (placeholderIndex !== -1) {
                    textarea.value = textarea.value.replace(placeholder, replacementText);
                    
                    // Adjust cursor if it was after the placeholder
                    if (currentStart > placeholderIndex) {
                        const diff = replacementText.length - placeholder.length;
                        textarea.setSelectionRange(currentStart + diff, currentEnd + diff);
                    } else {
                        textarea.setSelectionRange(currentStart, currentEnd);
                    }
                }
                
                textarea.dispatchEvent(new Event('input', { bubbles: true }));
            } catch (error) {
                console.error(error);
                alert('Erreur lors du téléchargement de l\'image.');
                // Remove placeholder on error
                textarea.value = textarea.value.replace(placeholder, '');
            }
        });

        document.body.appendChild(fileInput);
        fileInput.click();
        document.body.removeChild(fileInput);
    }

    insertHeading1() {
        this.insertText('# ', '', 'Titre');
    }

    insertHeading2() {
        this.insertText('## ', '', 'Sous-titre');
    }

    insertBold() {
        this.insertText('**', '**', 'Texte en gras');
    }

    insertList() {
        this.insertText('- ', '', 'Élément de liste');
    }

    insertLink() {
        this.insertText('[', '](https://)', 'Titre du lien');
    }
}
