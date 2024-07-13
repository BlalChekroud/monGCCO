<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240712115836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, name VARCHAR(50) NOT NULL, latitude VARCHAR(255) NOT NULL, longitude VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2D5B0234F92F3E70 (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(25) NOT NULL, iso2 VARCHAR(2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B0234F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE bird_family CHANGE sub_family sub_family VARCHAR(50) DEFAULT NULL, CHANGE tribe tribe VARCHAR(25) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE ordre ordre VARCHAR(25) DEFAULT NULL, CHANGE family family VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE bird_species CHANGE french_name french_name VARCHAR(50) DEFAULT NULL, CHANGE image_filename image_filename VARCHAR(255) DEFAULT NULL, CHANGE authority authority VARCHAR(40) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE common_name common_name VARCHAR(50) DEFAULT NULL, CHANGE common_name_alt common_name_alt VARCHAR(100) DEFAULT NULL, CHANGE synonyms synonyms VARCHAR(50) DEFAULT NULL, CHANGE taxonomic_sources taxonomic_sources VARCHAR(400) DEFAULT NULL, CHANGE subspp_id subspp_id VARCHAR(15) DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign_status CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE collected_data CHANGE count_type count_type VARCHAR(1) DEFAULT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE method method JSON NOT NULL');
        $this->addSql('ALTER TABLE counting_campaign CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE description description VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE roles CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE site_collection CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE national_site_code national_site_code VARCHAR(50) DEFAULT NULL, CHANGE international_site_code international_site_code VARCHAR(50) DEFAULT NULL, CHANGE parent_site_name parent_site_name VARCHAR(50) DEFAULT NULL, CHANGE country country VARCHAR(50) DEFAULT NULL');
        $this->addSql('ALTER TABLE user CHANGE roles roles JSON NOT NULL, CHANGE image_filename image_filename VARCHAR(255) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B0234F92F3E70');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE country');
        $this->addSql('ALTER TABLE bird_family CHANGE sub_family sub_family VARCHAR(50) DEFAULT \'NULL\', CHANGE tribe tribe VARCHAR(25) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE ordre ordre VARCHAR(25) DEFAULT \'NULL\', CHANGE family family VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE bird_species CHANGE french_name french_name VARCHAR(50) DEFAULT \'NULL\', CHANGE image_filename image_filename VARCHAR(255) DEFAULT \'NULL\', CHANGE authority authority VARCHAR(40) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE common_name common_name VARCHAR(50) DEFAULT \'NULL\', CHANGE common_name_alt common_name_alt VARCHAR(100) DEFAULT \'NULL\', CHANGE synonyms synonyms VARCHAR(50) DEFAULT \'NULL\', CHANGE taxonomic_sources taxonomic_sources VARCHAR(400) DEFAULT \'NULL\', CHANGE subspp_id subspp_id VARCHAR(15) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE campaign_status CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE collected_data CHANGE count_type count_type VARCHAR(1) DEFAULT \'NULL\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE method method LONGTEXT NOT NULL COLLATE `utf8mb4_bin`');
        $this->addSql('ALTER TABLE counting_campaign CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE description description VARCHAR(255) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE messenger_messages CHANGE delivered_at delivered_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE roles CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE site_collection CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE national_site_code national_site_code VARCHAR(50) DEFAULT \'NULL\', CHANGE international_site_code international_site_code VARCHAR(50) DEFAULT \'NULL\', CHANGE parent_site_name parent_site_name VARCHAR(50) DEFAULT \'NULL\', CHANGE country country VARCHAR(50) DEFAULT \'NULL\'');
        $this->addSql('ALTER TABLE user CHANGE roles roles LONGTEXT NOT NULL COLLATE `utf8mb4_bin`, CHANGE created_at created_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE updated_at updated_at DATETIME DEFAULT \'NULL\' COMMENT \'(DC2Type:datetime_immutable)\', CHANGE image_filename image_filename VARCHAR(255) DEFAULT \'NULL\'');
    }
}
