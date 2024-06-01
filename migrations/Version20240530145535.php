<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240530145535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bird_family (id INT AUTO_INCREMENT NOT NULL, family_name VARCHAR(50) NOT NULL, sub_family VARCHAR(50) DEFAULT NULL, tribe VARCHAR(25) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_species (id INT AUTO_INCREMENT NOT NULL, bird_family_id INT DEFAULT NULL, scientific_name VARCHAR(50) NOT NULL, french_name VARCHAR(50) NOT NULL, wispeciescode VARCHAR(25) NOT NULL, image VARCHAR(255) DEFAULT NULL, authority VARCHAR(40) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', bird_life_tax_treat VARCHAR(2) NOT NULL, common_name VARCHAR(50) DEFAULT NULL, common_name_alt VARCHAR(100) DEFAULT NULL, iucn_red_list_category VARCHAR(7) DEFAULT NULL, synonyms VARCHAR(50) DEFAULT NULL, taxonomic_sources VARCHAR(300) DEFAULT NULL, sis_rec_id INT DEFAULT NULL, spc_rec_id INT DEFAULT NULL, subspp_id VARCHAR(15) DEFAULT NULL, INDEX IDX_D373D04E705A8725 (bird_family_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_species_group (id INT AUTO_INCREMENT NOT NULL, scientificname VARCHAR(50) NOT NULL, alias VARCHAR(50) DEFAULT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E705A8725 FOREIGN KEY (bird_family_id) REFERENCES bird_family (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E705A8725');
        $this->addSql('DROP TABLE bird_family');
        $this->addSql('DROP TABLE bird_species');
        $this->addSql('DROP TABLE bird_species_group');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
