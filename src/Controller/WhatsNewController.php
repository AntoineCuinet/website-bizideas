<?php

namespace App\Controller;

use App\Entity\ReleaseNote;
use App\Entity\User;
use App\Repository\ReleaseNoteRepository;
use App\Service\MarkdownParser;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class WhatsNewController extends AbstractController
{
    #[Route('/whats-new', name: 'app_whats_new')]
    public function index(
        #[Autowire('%kernel.project_dir%')] string $projectDir,
        ReleaseNoteRepository $releaseNoteRepository
    ): Response {
        $filePath = $projectDir . '/version.json';
        $versionData = null;

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $versionData = json_decode($content, true);
            }
        }

        $current = $versionData['current'] ?? null;
        $history = $versionData['history'] ?? [];

        // Limit to 10 most recent versions total (current + previous)
        $maxHistory = $current ? 9 : 10;
        $limitedHistory = array_slice($history, 0, $maxHistory);

        // Collect all version strings to fetch from database
        $versionKeys = [];
        if ($current && !empty($current['version'])) {
            $versionKeys[] = $current['version'];
        }
        foreach ($limitedHistory as $item) {
            if (!empty($item['version'])) {
                $versionKeys[] = $item['version'];
            }
        }

        $dbNotes = $releaseNoteRepository->findByVersionsIndexed($versionKeys);

        return $this->render('whats_new/index.html.twig', [
            'current' => $current,
            'history' => $limitedHistory,
            'dbNotes' => $dbNotes,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
        ]);
    }

    #[Route('/whats-new/update', name: 'app_whats_new_update', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateNote(
        Request $request,
        EntityManagerInterface $entityManager,
        ReleaseNoteRepository $releaseNoteRepository,
        MarkdownParser $markdownParser
    ): JsonResponse {
        $data = json_decode($request->getContent(), true) ?: $request->request->all();
        $version = (string) ($data['version'] ?? '');
        $content = (string) ($data['content'] ?? '');
        $token = (string) ($data['_token'] ?? '');

        if (!$this->isCsrfTokenValid('whats_new_update', $token)) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid CSRF token'], Response::HTTP_FORBIDDEN);
        }

        if ($version === '') {
            return new JsonResponse(['success' => false, 'error' => 'Version is required'], Response::HTTP_BAD_REQUEST);
        }

        $releaseNote = $releaseNoteRepository->findOneBy(['version' => $version]);
        if (!$releaseNote) {
            $releaseNote = new ReleaseNote();
            $releaseNote->setVersion($version);
            $entityManager->persist($releaseNote);
        }

        $releaseNote->setContent($content);
        $entityManager->flush();

        $parsedHtml = $markdownParser->parse($content);

        return new JsonResponse([
            'success' => true,
            'version' => $version,
            'raw' => $content,
            'html' => $parsedHtml,
        ]);
    }

    #[Route('/whats-new/notify', name: 'app_whats_new_notify', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function notifyUsers(
        Request $request,
        NotificationService $notificationService,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): Response {
        $token = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('whats_new_notify', $token)) {
            throw $this->createAccessDeniedException('Invalid CSRF token.');
        }

        $filePath = $projectDir . '/version.json';
        $version = '1.0.0';

        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            if ($content !== false) {
                $versionData = json_decode($content, true);
                $version = $versionData['current']['version'] ?? $version;
            }
        }

        /** @var User $user */
        $user = $this->getUser();
        $notificationService->notifyNewVersion($version, $user);

        $this->addFlash('success', 'app.success_version_notified');

        return $this->redirectToRoute('app_whats_new');
    }
}
