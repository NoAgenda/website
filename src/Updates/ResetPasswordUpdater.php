<?php

namespace App\Updates;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class ResetPasswordUpdater extends AbstractUpdater
{
    public function update(User $user): bool
    {
        if (!$user->getEmail()) {
            // If the user doesn't have an email, act like the email was sent for security purposes
            return true;
        }

        $message = (new TemplatedEmail())
            ->from(new Address($this->getAuthorEmail(), $this->getAuthorName()))
            ->to(new Address($user->getEmail(), $user->getUsername()))
            ->subject('Reset password')
            ->htmlTemplate('email/reset_password.html.twig')
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
