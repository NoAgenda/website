<?php

namespace App\Updates;

use App\Entity\User;

class ResetPasswordUpdater extends AbstractUpdater
{
    public function update(User $user): bool
    {
        if (!$user->getEmail()) {
            // If the user doesn't have an email, act like the email was sent for security purposes
            return true;
        }

        $contents = $this->renderTemplate('emails/reset_password.html.twig', [
            'user' => $user,
            'remote_address' => $this->locator->get('request_stack')->getCurrentRequest()->getClientIp(),
            'activation_url' => $this->generateUrl('security_reset_password', ['token' => $user->getActivationToken()])
        ]);

        $message = (new \Swift_Message)
            ->setFrom($this->getAuthorEmail(), $this->getAuthorName())
            ->setTo($user->getEmail(), $user->getUsername())
            ->setSubject('Reset password')
            ->setBody($contents, 'text/html')
        ;

        $result = $this->sendMessage($message);

        // todo log result if false?
        // $this->log('error', '');

        return true;
    }
}
