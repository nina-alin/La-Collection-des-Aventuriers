<?php

declare(strict_types=1);

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Service\EmailVerificationService;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.registration_limiter')]
        private readonly RateLimiterFactory $registrationLimiter,
        private readonly UserRegistrationService $registrationService,
        private readonly EmailVerificationService $verificationService,
        private readonly UserRepository $userRepository,
    ) {
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
        if ($this->getUser() !== null) {
            return $this->redirectToRoute('home');
        }

        $state = 'form';
        $emailSendError = null;

        $ip = $request->getClientIp() ?? '0.0.0.0';
        $limiter = $this->registrationLimiter->create($ip);
        $limit = $limiter->consume(0);

        if (!$limit->isAccepted()) {
            $remaining = (int) ceil(($limit->getRetryAfter()->getTimestamp() - time()) / 60);
            $this->addFlash('error', sprintf(
                'Trop de tentatives. Réessayez dans %d minute%s.',
                $remaining,
                $remaining > 1 ? 's' : '',
            ));
        }

        $confirmationFlash = $request->getSession()->getFlashBag()->get('confirmation');
        if (!empty($confirmationFlash)) {
            $state = 'confirmation';
        }

        $form = $this->createForm(RegistrationFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $limitResult = $limiter->consume();
            if (!$limitResult->isAccepted()) {
                $remaining = (int) ceil(($limitResult->getRetryAfter()->getTimestamp() - time()) / 60);
                $this->addFlash('error', sprintf(
                    'Trop de tentatives. Réessayez dans %d minute%s.',
                    $remaining,
                    $remaining > 1 ? 's' : '',
                ));

                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'state' => 'form',
                    'emailSendError' => null,
                    'stat_aventuriers' => $this->userRepository->countActive(),
                ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
            }

            if ($form->isValid()) {
                $pseudo = $form->get('pseudo')->getData();
                $email = $form->get('email')->getData();
                $plainPassword = $form->get('plainPassword')->getData();

                try {
                    $user = $this->registrationService->register($pseudo, $email, $plainPassword);

                    try {
                        $this->verificationService->sendConfirmationEmail($user);
                        $state = 'confirmation';
                    } catch (\RuntimeException $e) {
                        $emailSendError = 'Impossible d\'envoyer l\'e-mail de confirmation. Contactez le support.';
                        $state = 'form';
                    }
                } catch (\RuntimeException $e) {
                    $message = $e->getMessage();
                    if (str_contains($message, 'email') || str_contains($message, 'Email')) {
                        $form->get('email')->addError(
                            new \Symfony\Component\Form\FormError('Ces informations sont déjà utilisées.')
                        );
                    } else {
                        $form->get('pseudo')->addError(
                            new \Symfony\Component\Form\FormError('Ces informations sont déjà utilisées.')
                        );
                    }
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'state' => $state,
            'emailSendError' => $emailSendError,
            'stat_aventuriers' => $this->userRepository->countActive(),
        ]);
    }
}
