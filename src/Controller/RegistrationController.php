<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/signup', name: 'app_signup', methods: ['GET', 'POST'])]
    public function signup(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserMailer $userMailer,
    ): Response {
        if ($this->getUser()) {
            return $this->redirectToRoute('app_profile');
        }

        $data = [
            'email' => trim((string) $request->request->get('email', '')),
            'firstName' => trim((string) $request->request->get('firstName', '')),
            'lastName' => trim((string) $request->request->get('lastName', '')),
            'phone' => trim((string) $request->request->get('phone', '')),
        ];

        $errors = [];

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token', '');
            if (!$this->isCsrfTokenValid('signup', $token)) {
                $errors[] = 'Անվավեր պաշտպանական token (CSRF)։ Խնդրում ենք կրկին փորձել։';
            }

            $email = $data['email'];
            $firstName = $data['firstName'];
            $lastName = $data['lastName'];
            $phone = $data['phone'] !== '' ? $data['phone'] : null;

            $plainPassword = (string) $request->request->get('password', '');
            $plainPassword2 = (string) $request->request->get('passwordConfirm', '');

            if ($firstName === '' || mb_strlen($firstName) < 2) {
                $errors[] = 'Անունը պետք է լինի առնվազն 2 նիշ։';
            }
            if ($lastName === '' || mb_strlen($lastName) < 2) {
                $errors[] = 'Ազգանունը պետք է լինի առնվազն 2 նիշ։';
            }
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = 'Մուտքագրեք ճիշտ էլ․ հասցե։';
            }

            if ($plainPassword === '' || mb_strlen($plainPassword) < 8) {
                $errors[] = 'Գաղտնաբառը պետք է լինի առնվազն 8 նիշ։';
            }
            if ($plainPassword !== $plainPassword2) {
                $errors[] = 'Գաղտնաբառերը չեն համընկնում։';
            }

            if ($email !== '') {
                $existing = $em->getRepository(User::class)->findOneBy(['email' => $email]);
                if ($existing) {
                    $errors[] = 'Այս էլ․ հասցեով օգտատեր արդեն կա։ Փորձեք մուտք գործել։';
                }
            }

            if (!$errors) {
                $user = new User();
                $user->setEmail($email);
                $user->setFirstName($firstName);
                $user->setLastName($lastName);
                $user->setPhone($phone);
                $user->setRoles([]); // ROLE_USER will be added automatically in getRoles()

                $user->setPassword($passwordHasher->hashPassword($user, $plainPassword));

                $em->persist($user);
                $em->flush();

                // Best-effort welcome email (do not fail signup if email fails)
                $userMailer->sendWelcome($user);

                $this->addFlash('success', 'Հաշիվը ստեղծվեց։ Կարող եք մուտք գործել։');
                return $this->redirectToRoute('app_login');
            }
        }

        return $this->render('security/signup.html.twig', [
            'data' => $data,
            'errors' => $errors,
        ]);
    }
}


