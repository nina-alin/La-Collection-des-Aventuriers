<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\ContactMailerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ContactController extends AbstractController
{
    private const RAISON_WHITELIST = [
        'question-site',
        'signaler-probleme',
        'erreur-fiche',
        'suggerer-oeuvre',
        'devenir-moderateur',
        'contester-moderation',
        'donnees-personnelles',
        'partenariat',
        'autre',
    ];

    #[Route('/contact', name: 'app_contact', methods: ['GET'])]
    public function index(): Response
    {
        $user = $this->getUser();

        return $this->render('contact/contact.html.twig', [
            'userPseudo' => $user?->getPseudo(),
            'userEmail'  => $user?->getEmail() ?? '',
        ]);
    }

    #[Route('/contact/send', name: 'app_contact_send', methods: ['POST'])]
    public function send(Request $request, ContactMailerService $mailer): JsonResponse
    {
        if (!str_contains($request->headers->get('Content-Type', ''), 'application/json')) {
            return $this->json(['success' => false, 'message' => 'Requête invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);

        if (!is_array($data)) {
            return $this->json(['success' => false, 'message' => 'Requête invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (!$this->isCsrfTokenValid('contact', $data['_token'] ?? '')) {
            return $this->json(['success' => false, 'message' => 'Requête invalide.'], Response::HTTP_FORBIDDEN);
        }

        $errors = $this->validate($data);

        if (!empty($errors)) {
            return $this->json(['success' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $prenom  = isset($data['prenom'])  ? trim((string) $data['prenom'])  : null;
        $nom     = isset($data['nom'])     ? trim((string) $data['nom'])     : null;
        $pseudo  = isset($data['pseudo'])  ? trim((string) $data['pseudo'])  : null;
        $email   = trim((string) ($data['email']   ?? ''));
        $raison  = trim((string) ($data['raison']  ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        try {
            $mailer->send(
                $prenom ?: null,
                $nom ?: null,
                $pseudo ?: null,
                $email,
                $raison,
                $message,
            );
        } catch (\Throwable) {
            return $this->json(
                ['success' => false, 'message' => 'Une erreur est survenue, veuillez réessayer.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $this->json(['success' => true]);
    }

    /** @return string[] */
    private function validate(array $data): array
    {
        $errors = [];

        $prenom  = trim((string) ($data['prenom']  ?? ''));
        $nom     = trim((string) ($data['nom']     ?? ''));
        $pseudo  = trim((string) ($data['pseudo']  ?? ''));
        $email   = trim((string) ($data['email']   ?? ''));
        $raison  = trim((string) ($data['raison']  ?? ''));
        $message = trim((string) ($data['message'] ?? ''));

        // Identity
        if ($pseudo === '' && ($prenom === '' || $nom === '')) {
            $errors[] = 'Veuillez indiquer un pseudonyme ou votre prénom et nom.';
        }

        // Length constraints
        if (mb_strlen($prenom) > 100) {
            $errors[] = 'Le prénom ne peut pas dépasser 100 caractères.';
        }
        if (mb_strlen($nom) > 100) {
            $errors[] = 'Le nom ne peut pas dépasser 100 caractères.';
        }
        if (mb_strlen($pseudo) > 100) {
            $errors[] = 'Le pseudonyme ne peut pas dépasser 100 caractères.';
        }

        // Email
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 254) {
            $errors[] = 'Veuillez indiquer une adresse email valide.';
        }

        // Raison
        if (!in_array($raison, self::RAISON_WHITELIST, true)) {
            $errors[] = 'Veuillez sélectionner une raison valide.';
        }

        // Message
        if ($message === '') {
            $errors[] = 'Le message ne peut pas être vide.';
        } elseif (mb_strlen($message) > 5000) {
            $errors[] = 'Le message ne peut pas dépasser 5000 caractères.';
        }

        return $errors;
    }
}
