<?php

namespace App\Service;

use App\Entity\BusinessIdea;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ExportService
{
    public function __construct(
        private TranslatorInterface $translator,
        private Environment $twig,
        private UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Exports the ranked ideas into the requested format.
     * Returns an array with keys: 'content', 'contentType', 'filename'.
     */
    public function export(array $rankedIdeas, User $currentUser, string $format): array
    {
        // Exclude drafts and abandoned ideas from all exports
        $filteredIdeas = array_values(array_filter($rankedIdeas, function (array $item): bool {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            return $idea->getStatus() === BusinessIdea::STATUS_ADOPTED;
        }));

        // Re-calculate sequential ranks for the exported list
        $currentRank = 1;
        $previousScore = null;
        foreach ($filteredIdeas as $index => &$item) {
            if ($previousScore !== null && $item['globalScore'] < $previousScore) {
                $currentRank = $index + 1;
            }
            $item['rank'] = $currentRank;
            $previousScore = $item['globalScore'];
        }
        unset($item);

        return match ($format) {
            'csv' => $this->exportCsv($filteredIdeas, $currentUser),
            'markdown' => $this->exportMarkdown($filteredIdeas, $currentUser),
            'pdf' => $this->exportPdf($filteredIdeas, $currentUser),
            default => throw new \InvalidArgumentException('Unsupported format: ' . $format),
        };
    }

    private function exportCsv(array $rankedIdeas, User $currentUser): array
    {
        $buffer = fopen('php://temp', 'r+');

        // Headers
        fputcsv($buffer, [
            'Rang',
            'Titre',
            'Description',
            'Statut',
            'Modèle de revenus',
            'Cible',
            'Créateur',
            'Date de création',
            'Catégories',
            'Note globale',
            'Notes individuelles et commentaires généraux',
            'Fichiers joints',
        ]);

        $baseUrl = $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseUrl = rtrim($baseUrl, '/');

        foreach ($rankedIdeas as $item) {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            $globalScore = $item['globalScore'];
            $rank = $item['rank'];

            $categories = implode(', ', $idea->getCategories()->map(fn($c) => $c->getName())->toArray());

            $files = [];
            foreach ($idea->getAttachmentFilenames() as $filename) {
                $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                $files[] = sprintf('%s [%s] (%s/uploads/ideas/%s)', $filename, $ext, $baseUrl, $filename);
            }
            $filesStr = implode("\n", $files);

            $indivScores = [];
            foreach ($item['ratingsData'] as $rd) {
                $scoreStr = $rd['score'] > 0 ? $rd['score'] . '/5' : 'N/A';
                $commentStr = $rd['comment'] ? " (Commentaire: " . $rd['comment'] . ")" : "";
                $indivScores[] = sprintf("%s: %s%s", $rd['displayName'], $scoreStr, $commentStr);
            }
            $indivScoresStr = implode("\n", $indivScores);

            fputcsv($buffer, [
                $rank,
                $idea->getTitle(),
                $idea->getDescription(),
                $this->translator->trans('status.' . $idea->getStatus()),
                $this->translator->trans('revenue.' . $idea->getRevenueModel()),
                $this->translator->trans('audience.' . $idea->getTargetAudience()),
                $idea->getCreator()->getDisplayName(),
                $idea->getCreatedAt()->format('Y-m-d H:i'),
                $categories,
                $globalScore > 0 ? $globalScore . '/5' : 'N/A',
                $indivScoresStr,
                $filesStr,
            ]);
        }

        rewind($buffer);
        $content = stream_get_contents($buffer);
        fclose($buffer);

        // Add UTF-8 BOM for Excel compatibility
        $content = "\xEF\xBB\xBF" . $content;

        return [
            'content' => $content,
            'contentType' => 'text/csv; charset=utf-8',
            'filename' => 'bizideas_' . date('Ymd_His') . '.csv',
        ];
    }

    private function exportMarkdown(array $rankedIdeas, User $currentUser): array
    {
        $md = "# " . $this->translator->trans('app.title') . " - Export des idées\n\n";
        $md .= "Généré le : " . date('d/m/Y H:i') . "\n";
        $md .= "Utilisateur : " . $currentUser->getDisplayName() . "\n\n";

        // Summary table
        $md .= "| Rang | Titre | Statut | Modèle de revenus | Public cible | Créateur | Note globale |\n";
        $md .= "| :--- | :--- | :--- | :--- | :--- | :--- | :--- |\n";

        foreach ($rankedIdeas as $item) {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            $globalScore = $item['globalScore'];
            $rank = $item['rank'];

            $md .= sprintf(
                "| #%d | **%s** | %s | %s | %s | %s | %s |\n",
                $rank,
                $idea->getTitle(),
                $this->translator->trans('status.' . $idea->getStatus()),
                $this->translator->trans('revenue.' . $idea->getRevenueModel()),
                $this->translator->trans('audience.' . $idea->getTargetAudience()),
                $idea->getCreator()->getDisplayName(),
                $globalScore > 0 ? $globalScore . '/5' : 'N/A'
            );
        }

        $md .= "\n---\n\n";

        $baseUrl = $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseUrl = rtrim($baseUrl, '/');

        // Detailed section
        foreach ($rankedIdeas as $item) {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            $globalScore = $item['globalScore'];
            $rank = $item['rank'];

            $categories = implode(', ', $idea->getCategories()->map(fn($c) => $c->getName())->toArray());
            
            $md .= sprintf("## %d. %s (Rang #%d)\n\n", $rank, $idea->getTitle(), $rank);
            $md .= "**Créateur :** " . $idea->getCreator()->getDisplayName() . " | ";
            $md .= "**Créé le :** " . $idea->getCreatedAt()->format('d/m/Y H:i') . "\n";
            $md .= "**Statut :** " . $this->translator->trans('status.' . $idea->getStatus()) . "\n";
            $md .= "**Modèle de revenus :** " . $this->translator->trans('revenue.' . $idea->getRevenueModel()) . "\n";
            $md .= "**Cible :** " . $this->translator->trans('audience.' . $idea->getTargetAudience()) . "\n";
            $md .= "**Catégories :** " . ($categories ?: 'Aucune') . "\n";
            $md .= "**Note globale :** " . ($globalScore > 0 ? $globalScore . '/5' : 'Non noté') . "\n\n";

            // Global individual scores and general comments
            $md .= "**Notes globales par personne :**\n\n";
            foreach ($item['ratingsData'] as $rd) {
                $md .= "- " . $rd['displayName'] . " : " . ($rd['score'] > 0 ? $rd['score'] . "/5" : "N/A") . "\n\n";
                if ($rd['comment']) {
                    $comment = $rd['comment'];
                    $comment = preg_replace('/^##\s+(.*)$/m', '##### $1', $comment);
                    $comment = preg_replace('/^#\s+(.*)$/m', '#### $1', $comment);
                    $md .= " > Commentaire général :\n > " . str_replace("\n", "\n > ", $comment) . "\n";
                }
            }
            $md .= "\n";

            if (count($idea->getAttachmentFilenames()) > 0) {
                $md .= "**Fichiers liés :**\n\n";
                foreach ($idea->getAttachmentFilenames() as $filename) {
                    $ext = strtoupper(pathinfo($filename, PATHINFO_EXTENSION));
                    $url = $baseUrl . '/uploads/ideas/' . $filename;
                    $md .= sprintf("- [%s (%s)](%s)\n", $filename, $ext, $url);
                }
                $md .= "\n";
            }

            $md .= "### Description\n\n";
            $desc = $idea->getDescription();
            // Demote headings: ### -> ###### (wait, just # and ## as requested, but let's do generally if we can, or just # and ##)
            $desc = preg_replace('/^##\s+(.*)$/m', '##### $1', $desc);
            $desc = preg_replace('/^#\s+(.*)$/m', '#### $1', $desc);
            $md .= $desc . "\n\n";

            // Detailed ratings
            $md .= "### Évaluations des critères\n\n";
            $md .= "| Critère | Auto-évaluation (Créateur) | Note collaborateurs |\n";
            $md .= "| :--- | :--- | :--- |\n";

            $criteria = CriteriaManager::getRatedCriteria();
            $creatorRating = $idea->getRatingByUser($idea->getCreator());
            
            // Find another rating
            $otherRating = null;
            foreach ($idea->getRatings() as $rating) {
                if ($rating->getUser()->getId() !== $idea->getCreator()->getId()) {
                    $otherRating = $rating;
                    break;
                }
            }

            foreach ($criteria as $key => $config) {
                $cLabel = $this->translator->trans($config['label']);
                
                $creatorScore = $creatorRating ? $creatorRating->getScoreFor($key) : null;
                $creatorComment = $creatorRating ? $creatorRating->getCommentFor($key) : null;
                $creatorCell = $creatorScore !== null ? $creatorScore . " / 5" : "-";
                if ($creatorComment) {
                    $creatorCell .= "<br>_" . htmlspecialchars(str_replace("\n", " ", $creatorComment)) . "_";
                }

                $otherScore = $otherRating ? $otherRating->getScoreFor($key) : null;
                $otherComment = $otherRating ? $otherRating->getCommentFor($key) : null;
                $otherCell = $otherScore !== null ? $otherScore . " / 5" : "-";
                if ($otherComment) {
                    $otherCell .= "<br>_" . htmlspecialchars(str_replace("\n", " ", $otherComment)) . "_";
                }

                $md .= sprintf("| %s | %s | %s |\n", $cLabel, $creatorCell, $otherCell);
            }

            $md .= "\n---\n";
        }

        return [
            'content' => $md,
            'contentType' => 'text/markdown; charset=utf-8',
            'filename' => 'bizideas_' . date('Ymd_His') . '.md',
        ];
    }

    private function exportPdf(array $rankedIdeas, User $currentUser): array
    {
        $baseUrl = $this->urlGenerator->generate('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $baseUrl = rtrim($baseUrl, '/');

        $html = $this->twig->render('export/pdf.html.twig', [
            'rankedIdeas' => $rankedIdeas,
            'currentUser' => $currentUser,
            'criteria' => CriteriaManager::getRatedCriteria(),
            'baseUrl' => $baseUrl,
        ]);

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', false);
        $options->set('defaultPaperSize', 'A4');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return [
            'content' => $dompdf->output(),
            'contentType' => 'application/pdf',
            'filename' => 'bizideas_' . date('Ymd_His') . '.pdf',
        ];
    }
}
