<?php

namespace App\Service;

use App\Entity\BusinessIdea;
use App\Entity\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;

class ExportService
{
    public function __construct(
        private RatingService $ratingService,
        private TranslatorInterface $translator,
        private Environment $twig
    ) {
    }

    /**
     * Exports the ranked ideas into the requested format.
     * Returns an array with keys: 'content', 'contentType', 'filename'.
     */
    public function export(array $rankedIdeas, User $currentUser, string $format): array
    {
        return match ($format) {
            'csv' => $this->exportCsv($rankedIdeas, $currentUser),
            'markdown' => $this->exportMarkdown($rankedIdeas, $currentUser),
            'pdf' => $this->exportPdf($rankedIdeas, $currentUser),
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
            'Note globale',
        ]);

        foreach ($rankedIdeas as $item) {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            $globalScore = $item['globalScore'];
            $rank = $item['rank'];

            fputcsv($buffer, [
                $rank,
                $idea->getTitle(),
                $idea->getDescription(),
                $this->translator->trans('status.' . $idea->getStatus()),
                $this->translator->trans('revenue.' . $idea->getRevenueModel()),
                $this->translator->trans('audience.' . $idea->getTargetAudience()),
                $idea->getCreator()->getDisplayName(),
                $idea->getCreatedAt()->format('Y-m-d H:i'),
                $globalScore > 0 ? $globalScore . '/5' : 'N/A',
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

        // Detailed section
        foreach ($rankedIdeas as $item) {
            /** @var BusinessIdea $idea */
            $idea = $item['idea'];
            $globalScore = $item['globalScore'];
            $rank = $item['rank'];

            $md .= sprintf("## %d. %s (Rang #%d)\n\n", $rank, $idea->getTitle(), $rank);
            $md .= "**Créateur :** " . $idea->getCreator()->getDisplayName() . " | ";
            $md .= "**Créé le :** " . $idea->getCreatedAt()->format('d/m/Y H:i') . "\n";
            $md .= "**Statut :** " . $this->translator->trans('status.' . $idea->getStatus()) . "\n";
            $md .= "**Modèle de revenus :** " . $this->translator->trans('revenue.' . $idea->getRevenueModel()) . "\n";
            $md .= "**Cible :** " . $this->translator->trans('audience.' . $idea->getTargetAudience()) . "\n";
            $md .= "**Note globale :** " . ($globalScore > 0 ? $globalScore . '/5' : 'Non noté') . "\n\n";

            $md .= "### Description\n";
            $md .= $idea->getDescription() . "\n\n";

            // Detailed ratings
            $md .= "### Évaluations des critères\n\n";
            $md .= "| Critère | Auto-évaluation (Créateur) | Note collaborateurs |\n";
            $md .= "| :--- | :---: | :---: |\n";

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
                $creatorScoreStr = $creatorRating ? ($creatorRating->getScoreFor($key) ?? '-') : '-';
                $otherScoreStr = $otherRating ? ($otherRating->getScoreFor($key) ?? '-') : '-';
                $md .= sprintf("| %s | %s / 5 | %s / 5 |\n", $cLabel, $creatorScoreStr, $otherScoreStr);
            }

            $md .= "\n---\n\n";
        }

        return [
            'content' => $md,
            'contentType' => 'text/markdown; charset=utf-8',
            'filename' => 'bizideas_' . date('Ymd_His') . '.md',
        ];
    }

    private function exportPdf(array $rankedIdeas, User $currentUser): array
    {
        $html = $this->twig->render('export/pdf.html.twig', [
            'rankedIdeas' => $rankedIdeas,
            'currentUser' => $currentUser,
            'criteria' => CriteriaManager::getRatedCriteria(),
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
