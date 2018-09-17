<?php

namespace App\Controller;

use App\Entity\EpisodePart;
use App\Entity\EpisodePartCorrection;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AdminController as BaseAdminController;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends BaseAdminController
{
    public function approveAction(): Response
    {
        $id = $this->request->query->get('id');

        $correction = $this->em->getRepository(EpisodePartCorrection::class)->find($id);
        $correctionPart = $correction->getPart();

        if ($correction->getPosition() !== null) {
            $part = (new EpisodePart)
                ->setEpisode($correctionPart->getEpisode())
                ->setCreator($correction->getCreator())
                ->setName($correction->getName())
                ->setStartsAt($correction->getStartsAt())
            ;

            $correction
                ->setResult($part)
                ->setHandled(true)
            ;

            $this->em->persist($correction);
            $this->em->persist($part);
        }
        else if ($correction->getAction() !== null) {
            switch ($correction->getAction()) {
                case 'remove';
                    $correctionPart
                        ->setEnabled(false)
                    ;

                    break;

                case 'name';
                    $correctionPart
                        ->setName($correction->getName())
                    ;

                    break;

                case 'startsAt';
                    $correctionPart
                        ->setStartsAt($correction->getStartsAt())
                    ;

                    break;
            }

            $correction
                ->setResult($correctionPart)
                ->setHandled(true)
            ;

            $this->em->persist($correction);
            $this->em->persist($correctionPart);
        }
        else {
            throw new \Exception('Invalid correction');
        }

        $this->em->flush();

        $this->addFlash('success', 'Correction approved.');

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'show',
            'entity' => 'EpisodePartCorrection',
            'id' => $id,
        ));
    }

    public function dismissAction(): Response
    {
        $id = $this->request->query->get('id');

        $correction = $this->em->getRepository(EpisodePartCorrection::class)->find($id);

        $correction
            ->setResult(null)
            ->setHandled(true)
        ;

        $this->em->persist($correction);

        $this->em->flush();

        $this->addFlash('success', 'Correction dismissed.');

        return $this->redirectToRoute('easyadmin', array(
            'action' => 'show',
            'entity' => 'EpisodePartCorrection',
            'id' => $id,
        ));
    }
}
