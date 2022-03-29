<?php

namespace App\Updates;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class ResetPasswordUpdater extends AbstractUpdater
{
    public function update(User $user): bool
    {
        if (!$user->getEmail()) {
            // If the user doesn't have an email, act like the email was sent for security purposes
            return true;
        }

        $message = (new TemplatedEmail())
            ->from($this->getAuthorEmail(), $this->getAuthorName())
            ->to($user->getEmail(), $user->getUsername())
            ->subject('Reset password')
            ->htmlTemplate('emails/reset_password.html.twig')
            ->context([
                'user' => $user,
                'remote_address' => $this->requestStack->getCurrentRequest()->getClientIp(),
                'activation_url' => $this->generateUrl('security_reset_password', ['token' => $user->getActivationToken()])
            ])
        ;

        $this->mailer->send($message);

        return true;
    }
}
