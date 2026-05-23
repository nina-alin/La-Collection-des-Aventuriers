<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Service\UserRegistrationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
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
        private readonly Security $security,
    ) {
    }

    #[Route('/inscription', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(Request $request): Response
    {
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
                ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
            }

            if ($form->isValid()) {
                $pseudo = $form->get('pseudo')->getData();
                $email = $form->get('email')->getData();
                $plainPassword = $form->get('plainPassword')->getData();

                try {
                    $user = $this->registrationService->register($pseudo, $email, $plainPassword);
                    $this->security->login($user, 'form_login');

                    return $this->redirectToRoute('home');
                } catch (\RuntimeException $e) {
                    $message = $e->getMessage();
                    if (str_contains($message, 'email')) {
                        $form->get('email')->addError(
                            new \Symfony\Component\Form\FormError($message)
                        );
                    } else {
                        $form->get('pseudo')->addError(
                            new \Symfony\Component\Form\FormError($message)
                        );
                    }
                }
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }
}
