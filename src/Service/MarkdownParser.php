<?php

namespace App\Service;

class MarkdownParser
{
    public function parse(?string $text): string
    {
        if ($text === null || $text === '') {
            return '';
        }

        $lines = explode("\n", $text);
        $parsedLines = [];
        $inList = false;

        foreach ($lines as $line) {
            $line = htmlspecialchars($line, ENT_QUOTES, 'UTF-8');
            $line = trim($line);

            if (preg_match('/^#\s+(.+)$/', $line, $matches)) {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                $parsedLines[] = '<h3 class="markdown-h1">' . $matches[1] . '</h3>';
            } elseif (preg_match('/^##\s+(.+)$/', $line, $matches)) {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                $parsedLines[] = '<h4 class="markdown-h2">' . $matches[1] . '</h4>';
            } elseif (preg_match('/^###\s+(.+)$/', $line, $matches)) {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                $parsedLines[] = '<h5 class="markdown-h3">' . $matches[1] . '</h5>';
            } elseif (preg_match('/^!\[(.*?)\]\((.+?)\)$/', $line, $matches)) {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                $caption = $matches[1];
                $url = $matches[2];
                $captionHtml = $caption !== '' ? '<span class="markdown-img-caption">' . $caption . '</span>' : '';
                $parsedLines[] = '<div class="markdown-img-box"><img src="' . $url . '" alt="' . $caption . '" class="markdown-img" />' . $captionHtml . '</div>';
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if (!$inList) { $parsedLines[] = '<ul class="markdown-list">'; $inList = true; }
                $itemText = $this->parseInline($matches[1]);
                $parsedLines[] = '<li>' . $itemText . '</li>';
            } else {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                if ($line === '') {
                    $parsedLines[] = '<br>';
                } else {
                    $parsedLines[] = $this->parseInline($line);
                }
            }
        }
        if ($inList) { $parsedLines[] = '</ul>'; }

        $html = '';
        foreach ($parsedLines as $parsedLine) {
            $tag = substr($parsedLine, 0, 4);
            if (in_array($tag, ['<h3 ', '<h4 ', '<h5 ', '<ul>', '</ul', '<li ', '<li>', '<br>', '<div'], true)) {
                $html .= $parsedLine;
            } else {
                $html .= '<p class="markdown-p">' . $parsedLine . '</p>';
            }
        }

        return $html;
    }

    private function parseInline(string $text): string
    {
        // Bold: **text**
        $text = (string) preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $text);
        // Italic: *text*
        $text = (string) preg_replace('/(?<!\*)\*(?!\*)(.+?)(?<!\*)\*(?!\*)/', '<em>$1</em>', $text);
        // Links: [text](url)
        $text = (string) preg_replace('/\[(.*?)\]\((https?:\/\/[^\s\)]+|\/[^\s\)]+)\)/', '<a href="$2" target="_blank" rel="noopener noreferrer" class="markdown-link">$1</a>', $text);

        return $text;
    }
}
