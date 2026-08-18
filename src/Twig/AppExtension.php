<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function __construct(
        private string $kernelProjectDir
    ) {}

    public function getFunctions(): array
    {
        return [
            new TwigFunction('app_version', [$this, 'getAppVersion']),
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
}
