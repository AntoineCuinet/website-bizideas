<?php

namespace App\Service;

use App\Entity\BusinessIdea;
use App\Entity\User;
use App\Repository\UserRepository;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class NotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UserRepository $userRepository,
        private UrlGeneratorInterface $urlGenerator,
        private TranslatorInterface $translator,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Sends an email notification to all other users when a new idea is added.
     */
    public function notifyNewIdea(BusinessIdea $idea, User $creator): void
    {
        $users = $this->userRepository->findAll();
        $recipientEmails = [];

        foreach ($users as $user) {
            if ($user->getId() !== $creator->getId()) {
                $recipientEmails[] = $user->getEmail();
            }
        }

        if (empty($recipientEmails)) {
            return;
        }

        $rateUrl = $this->urlGenerator->generate(
            'app_home',
            ['open' => $idea->getId()],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = $this->translator->trans('email.new_idea.subject', ['%title%' => $idea->getTitle()]);

        try {
            foreach ($recipientEmails as $emailAddress) {
                $email = (new TemplatedEmail())
                    ->from('noreply@bizideas.acuinet.fr')
                    ->to($emailAddress)
                    ->subject($subject)
                    ->htmlTemplate('email/new_idea.html.twig')
                    ->context([
                        'idea' => $idea,
                        'creator' => $creator,
                        'rateUrl' => $rateUrl,
                    ]);

                $this->mailer->send($email);
            }
        } catch (\Exception $e) {
            // Log error so that local testing doesn't crash on invalid DSN
            $this->logger->error(sprintf('Failed to send notification emails: %s', $e->getMessage()), [
                'exception' => $e,
            ]);
        }
    }
}
