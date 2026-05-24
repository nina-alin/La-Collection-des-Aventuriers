<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\CorrectionProposal;
use App\Entity\WorkEntry;
use App\Repository\WorkEntryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/work-entries')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class WorkEntryController extends AbstractController
{
    #[Route('', name: 'work_entry_submit', methods: ['POST'])]
    public function submit(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('work_entry_submit', $token)) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $title = (string) $request->request->get('title', '');
        $entry = new WorkEntry($title, $this->getUser());
        $em->persist($entry);
        $em->flush();

        $this->addFlash('success', 'Entrée soumise avec succès.');

        return $this->redirectToRoute('home');
    }

    #[Route('/{id}/corrections', name: 'work_entry_correction_submit', methods: ['POST'])]
    public function submitCorrection(string $id, Request $request, WorkEntryRepository $workEntryRepository, EntityManagerInterface $em): Response
    {
        $entry = $workEntryRepository->find($id);
        if ($entry === null) {
            throw $this->createNotFoundException('Entrée introuvable.');
        }

        $token = $request->request->get('_csrf_token');
        if (!$this->isCsrfTokenValid('correction_submit_'.$id, $token)) {
            return new Response('Invalid CSRF token.', Response::HTTP_FORBIDDEN);
        }

        $proposedContentRaw = $request->request->get('proposedContent', '');
        $proposal = new CorrectionProposal($entry, ['content' => $proposedContentRaw], $this->getUser());
        $em->persist($proposal);
        $em->flush();

        $this->addFlash('success', 'Correction soumise avec succès.');

        return $this->redirectToRoute('home');
    }
}
