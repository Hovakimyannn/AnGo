<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Service\PasswordResetService;
use App\Service\UserMailer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly UserMailer $userMailer,
        private readonly PasswordResetService $passwordResetService,
    ) {}

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityPermission('ROLE_ADMIN')
            ->setEntityLabelInSingular('Օգտատեր')
            ->setEntityLabelInPlural('Օգտատերեր')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['email', 'firstName', 'lastName', 'phone']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email', 'Էլ․ հասցե')
            ->setFormTypeOption('required', true);

        yield TextField::new('firstName', 'Անուն');
        yield TextField::new('lastName', 'Ազգանուն');
        yield TextField::new('phone', 'Հեռախոս')->hideOnIndex();

        yield ChoiceField::new('roles', 'Դերեր')
            ->allowMultipleChoices()
            ->renderExpanded(false)
            ->setHelp('Նշեք աշխատակցի դերը։ ROLE_USER-ը միշտ ավտոմատ առկա է։')
            ->setChoices([
                'Ադմին' => 'ROLE_ADMIN',
                'Վարպետ' => 'ROLE_ARTIST',
            ]);
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof User) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        // Create a random internal password so the user can't log in until they set their own password.
        $internalPassword = $this->generateRandomPassword();
        $entityInstance->setPassword($this->passwordHasher->hashPassword($entityInstance, $internalPassword));

        $token = $this->passwordResetService->createToken($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);

        if (!$this->userMailer->sendAccountSetup($entityInstance, $token)) {
            $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);
            $this->addFlash('warning', sprintf(
                'Օգտատերը ստեղծվեց, բայց email ուղարկել չհաջողվեց (ստուգեք MAILER_DSN / MAILER_FROM prod-ում, SendGrid-ի դեպքում՝ verified sender)։ Reset link: %s',
                $resetUrl
            ));

            return;
        }

        $this->addFlash('success', 'Օգտատերը ստեղծվեց։ Գաղտնաբառ սահմանելու հղումը ուղարկվեց email-ով։');
    }

    private function generateRandomPassword(): string
    {
        try {
            return bin2hex(random_bytes(32));
        } catch (\Throwable) {
            return bin2hex((string) microtime(true));
        }
    }
}
