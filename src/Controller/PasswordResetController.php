<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\PasswordResetService;
use App\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

final class PasswordResetController extends AbstractController
{
    #[Route('/forgot-password', name: 'app_forgot_password', methods: ['GET', 'POST'])]
    public function forgotPassword(
        Request $request,
        EntityManagerInterface $em,
        PasswordResetService $passwordResetService,
        UserMailer $userMailer,
    ): Response {
        $data = [
            'email' => trim((string) $request->request->get('email', '')),
        ];

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('forgot_password', $token)) {
                $errors[] = 'Անվավեր պաշտպանական token (CSRF)։ Խնդրում ենք կրկին փորձել։';
            }

            $email = $data['email'];
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Մուտքագրեք ճիշտ էլ․ հասցե։';
            }

            if (!$errors) {
                $user = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($user instanceof User) {
                    $resetToken = $passwordResetService->createToken($user);
                    $em->flush();

                    // Best-effort (do not reveal existence)
                    $userMailer->sendPasswordReset($user, $resetToken);
                }

                $this->addFlash('success', 'Եթե այս էլ․ հասցեով հաշիվ կա, մենք ուղարկել ենք գաղտնաբառի վերականգնման հղումը։');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/forgot_password.html.twig', [
            'data' => $data,
            'errors' => $errors,
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password', methods: ['GET', 'POST'])]
    public function resetPassword(
        string $token,
        Request $request,
        EntityManagerInterface $em,
        PasswordResetService $passwordResetService,
        UserPasswordHasherInterface $passwordHasher,
    ): Response {
        $token = trim($token);
        $user = null;

        if ($token !== '') {
            $hash = $passwordResetService->hashToken($token);
            $candidate = $em->getRepository(User::class)->findOneBy(['passwordResetTokenHash' => $hash]);
            if ($candidate instanceof User && $passwordResetService->isTokenValid($candidate, $token)) {
                $user = $candidate;
            }
        }

        $errors = [];
        $data = [
            'password' => '',
            'passwordConfirm' => '',
        ];

        if ($request->isMethod('POST')) {
            $data['password'] = (string) $request->request->get('password', '');
            $data['passwordConfirm'] = (string) $request->request->get('passwordConfirm', '');

            $csrf = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('reset_password', $csrf)) {
                $errors[] = 'Անվավեր պաշտպանական token (CSRF)։ Խնդրում ենք կրկին փորձել։';
            }

            if (!$user) {
                $errors[] = 'Վերականգնման հղումը անվավեր է կամ ժամկետանց։';
            }

            $plainPassword = $data['password'];
            $plainPassword2 = $data['passwordConfirm'];

            if ($plainPassword === '' || mb_strlen($plainPassword) < 8) {
                $errors[] = 'Գաղտնաբառը պետք է լինի առնվազն 8 նիշ։';
            }
            if ($plainPassword !== $plainPassword2) {
                $errors[] = 'Գաղտնաբառերը չեն համընկնում։';
            }

            if (!$errors && $user) {
                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));
                $user->clearPasswordResetToken();
                $em->flush();

                $this->addFlash('success', 'Գաղտնաբառը թարմացվեց։ Կարող եք մուտք գործել։');

                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/reset_password.html.twig', [
            'tokenValid' => (bool) $user,
            'errors' => $errors,
            'data' => $data,
        ]);
    }
}


