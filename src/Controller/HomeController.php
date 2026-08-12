<?php

namespace App\Controller;

use App\Repository\BusinessIdeaRepository;
use App\Service\CriteriaManager;
use App\Service\ExportService;
use App\Service\RatingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(
        Request $request,
        BusinessIdeaRepository $businessIdeaRepository,
        RatingService $ratingService,
        ExportService $exportService
    ): Response {
        $user = $this->getUser();

        // 1. If not authenticated, show landing/presentation page
        if (!$user) {
            return $this->render('home/landing.html.twig');
        }

        // 2. Fetch all ideas
        $ideas = $businessIdeaRepository->findAll();

        // 3. Get sorting criteria
        $sortBy = $request->query->get('sort', 'global_score');
        $rankedIdeas = $ratingService->getRankedIdeas($ideas, $user, $sortBy);

        // 4. Handle export requests if present
        $exportFormat = $request->query->get('export');
        if ($exportFormat && in_array($exportFormat, ['csv', 'markdown', 'pdf'], true)) {
            $exportData = $exportService->export($rankedIdeas, $user, $exportFormat);

            $response = new Response($exportData['content']);
            $response->headers->set('Content-Type', $exportData['contentType']);
            $response->headers->set(
                'Content-Disposition',
                sprintf('attachment; filename="%s"', $exportData['filename'])
            );

            return $response;
        }

        // 5. Check if we need to auto-open an idea popup
        $openIdeaId = $request->query->getInt('open', 0);
        $openIdeaItem = null;
        if ($openIdeaId > 0) {
            foreach ($rankedIdeas as $item) {
                if ($item['idea']->getId() === $openIdeaId) {
                    $openIdeaItem = $item;
                    break;
                }
            }
        }

        return $this->render('home/dashboard.html.twig', [
            'rankedIdeas' => $rankedIdeas,
            'sortBy' => $sortBy,
            'openIdeaItem' => $openIdeaItem,
            'criteria' => CriteriaManager::getRatedCriteria(),
        ]);
    }
}
