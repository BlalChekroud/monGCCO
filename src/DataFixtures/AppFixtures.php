<?php

namespace App\DataFixtures;

use App\Entity\CampaignStatus;
use App\Entity\Language;
use App\Entity\User;
use App\Entity\UserStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(private readonly  UserPasswordHasherInterface $userPasswordHasher) {}
    public function load(ObjectManager $manager): void
    {
        // Création des statuts d'utilisateur
        $actifStatus = new UserStatus();
        $actifStatus->setCreatedAt(new \DateTimeImmutable())
            ->setLabel('Actif');
        $manager->persist($actifStatus);
        
        $inactifStatus = new UserStatus();
            $inactifStatus->setCreatedAt(new \DateTimeImmutable())
                ->setLabel('Inactif');
        $manager->persist($inactifStatus);

        // Création d'un utilisateur super administrateur
        $user = new User();
        $user->setEmail('superadmin@gccom.com')
             ->setName('super')
             ->setLastName('Admin')
             ->setPhone('')
             ->setCreatedAt(new \DateTimeImmutable())
             ->setRoles(['ROLE_SUPER_ADMIN'])
             ->setUserStatus($actifStatus)
             ->setPassword($this->userPasswordHasher->hashPassword($user, 'admin'));
             //  ->setIsVerified(true)
             //  ->setApiToken('admin_token')
        $manager->persist($user);

        // Ajout des langues FR, EN et AR
        $fr = new Language();
        $fr->setName('Français')
            ->setIso2('FR')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($fr);
        
        $en = new Language();
        $en->setName('English')
            ->setIso2('EN')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($en);

        $ar = new Language();
        $ar->setName('اللغة العربية')
            ->setIso2('AR')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($ar);

        // Création des status de campagne
        $planned = new CampaignStatus();
        $planned->setLabel('Planifiée')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($planned);
        
        $InProgress = new CampaignStatus();
        $InProgress->setLabel('En cours')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($InProgress);

        $completed = new CampaignStatus();
        $completed->setLabel('Terminée')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($completed);

        $closed = new CampaignStatus();
        $closed->setLabel('Clôturée')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($closed);

        $Error = new CampaignStatus();
        $Error->setLabel('Erreur')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($Error);

        $suspended = new CampaignStatus();
        $suspended->setLabel('Suspendue')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($suspended);

        $cancelled = new CampaignStatus();
        $cancelled->setLabel('Annulée')
            ->setCreatedAt(new \DateTimeImmutable());
        $manager->persist($cancelled);
        // $product = new Product();
        // $manager->persist($product);

        $manager->flush();
    }
}
