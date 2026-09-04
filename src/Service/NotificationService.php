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
        private LoggerInterface $logger,
        private string $replyToEmail
    ) {
    }

    /**
     * Sends an email notification to all other users when a new idea is added.
     */
    public function notifyNewIdea(BusinessIdea $idea, User $creator): void
    {
        if ($idea->isDraft()) {
            return;
        }

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
                    ->from($this->replyToEmail)
                    ->replyTo($this->replyToEmail)
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

    /**
     * Sends a generic email notification to all users when a new version is released.
     */
    public function notifyNewVersion(string $version, User $sender): void
    {
        $users = $this->userRepository->findAll();
        $recipientEmails = [];

        foreach ($users as $user) {
            $recipientEmails[] = $user->getEmail();
        }

        if (empty($recipientEmails)) {
            return;
        }

        $whatsNewUrl = $this->urlGenerator->generate(
            'app_whats_new',
            [],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $subject = $this->translator->trans('email.new_version.subject', ['%version%' => $version]);

        try {
            foreach ($recipientEmails as $emailAddress) {
                $email = (new TemplatedEmail())
                    ->from($this->replyToEmail)
                    ->replyTo($this->replyToEmail)
                    ->to($emailAddress)
                    ->subject($subject)
                    ->htmlTemplate('email/new_version.html.twig')
                    ->context([
                        'version' => $version,
                        'sender' => $sender,
                        'whatsNewUrl' => $whatsNewUrl,
                    ]);

                $this->mailer->send($email);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf('Failed to send version notification emails: %s', $e->getMessage()), [
                'exception' => $e,
            ]);
        }
    }
}

