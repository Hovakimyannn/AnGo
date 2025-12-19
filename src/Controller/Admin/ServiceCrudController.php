<?php

namespace App\Controller\Admin;

use App\Entity\Service;
use App\Repository\ServiceCategoryRepository;
use App\Repository\ServiceRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class ServiceCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ServiceCategoryRepository $serviceCategoryRepository,
        private readonly ServiceRepository $serviceRepository,
    ) {}

    public static function getEntityFqcn(): string
    {
        return Service::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud->setEntityPermission('ROLE_ADMIN');
    }

    public function configureFields(string $pageName): iterable
    {
        yield TextField::new('name', 'Անվանում');

        $choices = $this->getCategoryChoices();

        yield ChoiceField::new('category', 'Կատեգորիա')
            ->setChoices($choices);

        yield IntegerField::new('durationMinutes', 'Տևողություն (րոպե)');

        yield MoneyField::new('price', 'Գին')
            ->setCurrency('AMD')
            ->setStoredAsCents(false); // Ete uzum eq pahi tchisht tivy (orinak 5000), voch te lumanerov
    }

    /**
     * @return array<string,string> label => value
     */
    private function getCategoryChoices(): array
    {
        $choices = [];
        $knownKeys = [];

        try {
            $cats = $this->serviceCategoryRepository->findBy([], ['sortOrder' => 'ASC', 'label' => 'ASC']);
        } catch (\Throwable) {
            $cats = [];
        }
        foreach ($cats as $cat) {
            $key = (string) ($cat->getKey() ?? '');
            if ($key === '') {
                continue;
            }

            $label = (string) ($cat->getLabel() ?? $key);
            $suffix = $cat->isActive() ? '' : ' [inactive]';
            $choices["{$label} ({$key}){$suffix}"] = $key;
            $knownKeys[$key] = true;
        }

        // Ensure any existing values in services remain selectable, even if missing in categories table.
        foreach ($this->serviceRepository->findDistinctCategoryKeys() as $key) {
            $key = (string) $key;
            if ($key === '' || isset($knownKeys[$key])) {
                continue;
            }
            $choices[$key] = $key;
        }

        // Ultimate fallback
        if (count($choices) === 0) {
            $choices = [
                'Վարսահարդարում (hair)' => 'hair',
                'Դիմահարդարում (makeup)' => 'makeup',
                'Մատնահարդարում (nails)' => 'nails',
            ];
        }

        return $choices;
    }
}