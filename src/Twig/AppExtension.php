<?php

namespace App\Twig;

use App\Repository\BusinessIdeaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private string $kernelProjectDir,
        private readonly Security $security,
        private readonly UserRepository $userRepository,
        private readonly BusinessIdeaRepository $businessIdeaRepository
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_version', [$this, 'getAppVersion']),
            new TwigFunction('app_navbar_stats', [$this, 'getNavbarStats']),
        ];
    }

    public function getFilters(): array
    {
        return [
            new TwigFilter('app_markdown', [$this, 'parseMarkdown'], ['is_safe' => ['html']]),
        ];
    }

    public function getAppVersion(): ?string
    {
        $filePath = $this->kernelProjectDir . '/version.json';
        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);

        return $data['current']['version'] ?? null;
    }

    public function getNavbarStats(): array
    {
        $user = $this->security->getUser();
        if (!$user instanceof \App\Entity\User) {
            return [
                'totalIdeas' => 0,
                'collaboratorsStats' => [],
            ];
        }

        $ideas = $this->businessIdeaRepository->findVisibleForUser($user);
        $allUsers = $this->userRepository->findAll();

        $ideasCountByUser = [];
        foreach ($allUsers as $u) {
            $ideasCountByUser[$u->getId()] = 0;
        }
        foreach ($ideas as $idea) {
            $creator = $idea->getCreator();
            if ($creator) {
                $creatorId = $creator->getId();
                if (isset($ideasCountByUser[$creatorId])) {
                    $ideasCountByUser[$creatorId]++;
                }
            }
        }

        $collaboratorsStats = [];
        foreach ($allUsers as $u) {
            $collaboratorsStats[] = [
                'user' => $u,
                'ideasCount' => $ideasCountByUser[$u->getId()],
                'isCurrentUser' => ($u->getId() === $user->getId()),
            ];
        }

        usort($collaboratorsStats, function ($a, $b) {
            if ($b['ideasCount'] !== $a['ideasCount']) {
                return $b['ideasCount'] <=> $a['ideasCount'];
            }
            return strcasecmp($a['user']->getDisplayName(), $b['user']->getDisplayName());
        });

        return [
            'totalIdeas' => count($ideas),
            'collaboratorsStats' => $collaboratorsStats,
        ];
    }

    public function parseMarkdown(?string $text): string
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
            } elseif (preg_match('/^[-*]\s+(.+)$/', $line, $matches)) {
                if (!$inList) { $parsedLines[] = '<ul class="markdown-list">'; $inList = true; }
                $itemText = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $matches[1]);
                $parsedLines[] = '<li>' . $itemText . '</li>';
            } else {
                if ($inList) { $parsedLines[] = '</ul>'; $inList = false; }
                if ($line === '') {
                    $parsedLines[] = '<br>';
                } else {
                    $line = preg_replace('/\*\*(.+?)\*\*/', '<strong>$1</strong>', $line);
                    $parsedLines[] = $line;
                }
            }
        }
        if ($inList) { $parsedLines[] = '</ul>'; }

        $html = '';
        foreach ($parsedLines as $parsedLine) {
            $tag = substr($parsedLine, 0, 4);
            if (in_array($tag, ['<h3 ', '<h4 ', '<h5 ', '<ul>', '</ul', '<li ', '<li>', '<br>'])) {
                $html .= $parsedLine;
            } else {
                $html .= '<p class="markdown-p">' . $parsedLine . '</p>';
            }
        }

        return $html;
    }
}
