<?php

namespace App\Twig;

use App\Repository\BusinessIdeaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

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
        if (!$user) {
            return [
                'totalIdeas' => 0,
                'collaboratorsStats' => [],
            ];
        }

        $ideas = $this->businessIdeaRepository->findAll();
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
}
