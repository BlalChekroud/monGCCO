<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250310083411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE agents_group (id INT AUTO_INCREMENT NOT NULL, leader_id INT NOT NULL, country_id INT NOT NULL, created_by_id INT NOT NULL, group_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_B102AC9773154ED4 (leader_id), INDEX IDX_B102AC97F92F3E70 (country_id), INDEX IDX_B102AC97B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE agents_group_user (agents_group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_4A77B07872A1F93D (agents_group_id), INDEX IDX_4A77B078A76ED395 (user_id), PRIMARY KEY(agents_group_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_family (id INT AUTO_INCREMENT NOT NULL, family_name VARCHAR(255) NOT NULL, sub_family VARCHAR(255) DEFAULT NULL, tribe VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ordre VARCHAR(255) NOT NULL, family VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_life_tax_treat (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_species (id INT AUTO_INCREMENT NOT NULL, bird_family_id INT DEFAULT NULL, coverage_id INT DEFAULT NULL, bird_life_tax_treat_id INT DEFAULT NULL, iucn_red_list_category_id INT DEFAULT NULL, image_id INT DEFAULT NULL, scientific_name VARCHAR(255) NOT NULL, french_name VARCHAR(255) DEFAULT NULL, wispeciescode VARCHAR(255) DEFAULT NULL, authority VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', common_name VARCHAR(255) DEFAULT NULL, common_name_alt VARCHAR(100) DEFAULT NULL, synonyms VARCHAR(255) DEFAULT NULL, taxonomic_sources VARCHAR(500) DEFAULT NULL, sis_rec_id INT DEFAULT NULL, spc_rec_id INT DEFAULT NULL, subspp_id VARCHAR(255) DEFAULT NULL, english_name VARCHAR(255) DEFAULT NULL, INDEX IDX_D373D04E705A8725 (bird_family_id), INDEX IDX_D373D04E9F5AA71B (coverage_id), INDEX IDX_D373D04E902F3AE5 (bird_life_tax_treat_id), INDEX IDX_D373D04E6E09E6BF (iucn_red_list_category_id), UNIQUE INDEX UNIQ_D373D04E3DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE bird_species_count (id INT AUTO_INCREMENT NOT NULL, collected_data_id INT NOT NULL, bird_species_id INT DEFAULT NULL, count INT NOT NULL, INDEX IDX_450338A0EDED93F9 (collected_data_id), INDEX IDX_450338A0547180B0 (bird_species_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE campaign_status (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(25) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE city (id INT AUTO_INCREMENT NOT NULL, region_id INT NOT NULL, name VARCHAR(255) NOT NULL, latitude VARCHAR(255) NOT NULL, longitude VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_2D5B023498260155 (region_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collected_data (id INT AUTO_INCREMENT NOT NULL, counting_campaign_id INT NOT NULL, site_collection_id INT NOT NULL, created_by_id INT NOT NULL, count_type_id INT NOT NULL, quality_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_79374E7C60B458C7 (counting_campaign_id), INDEX IDX_79374E7C3EA3E56 (site_collection_id), INDEX IDX_79374E7CB03A8386 (created_by_id), INDEX IDX_79374E7CDFB9E947 (count_type_id), INDEX IDX_79374E7CBCFC6D57 (quality_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collected_data_bird_species (collected_data_id INT NOT NULL, bird_species_id INT NOT NULL, INDEX IDX_7B39E3BBEDED93F9 (collected_data_id), INDEX IDX_7B39E3BB547180B0 (bird_species_id), PRIMARY KEY(collected_data_id, bird_species_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE collected_data_method (collected_data_id INT NOT NULL, method_id INT NOT NULL, INDEX IDX_BCC68720EDED93F9 (collected_data_id), INDEX IDX_BCC6872019883967 (method_id), PRIMARY KEY(collected_data_id, method_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE count_type (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE counting_campaign (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, campaign_status_id INT DEFAULT NULL, campaign_name VARCHAR(255) NOT NULL, start_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', end_date DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', description VARCHAR(255) DEFAULT NULL, INDEX IDX_55508ABB03A8386 (created_by_id), INDEX IDX_55508ABAB7F1CC6 (campaign_status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE country (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, iso2 VARCHAR(2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE coverage (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE disturbed (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE environmental_conditions (id INT AUTO_INCREMENT NOT NULL, disturbed_id INT NOT NULL, ice_id INT NOT NULL, tidal_id INT NOT NULL, water_id INT NOT NULL, weather_id INT NOT NULL, collected_data_id INT DEFAULT NULL, site_collection_id INT NOT NULL, counting_campaign_id INT NOT NULL, user_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_96C106D26DC4E10B (disturbed_id), INDEX IDX_96C106D2D553E9BF (ice_id), INDEX IDX_96C106D28CD10E1D (tidal_id), INDEX IDX_96C106D27721C36E (water_id), INDEX IDX_96C106D28CE675E (weather_id), UNIQUE INDEX UNIQ_96C106D2EDED93F9 (collected_data_id), INDEX IDX_96C106D23EA3E56 (site_collection_id), INDEX IDX_96C106D260B458C7 (counting_campaign_id), INDEX IDX_96C106D2A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE ice (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE image (id INT AUTO_INCREMENT NOT NULL, image_filename VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE iucn_red_list_category (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE language (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, iso2 VARCHAR(2) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE logo (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_E48E9A133DA5256D (image_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE method (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE nature_reserve (id INT AUTO_INCREMENT NOT NULL, created_by_id INT NOT NULL, reserve_leader_id INT NOT NULL, reserve_name VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_763A1F7B03A8386 (created_by_id), INDEX IDX_763A1F7C87FFE17 (reserve_leader_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, message VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', seen TINYINT(1) NOT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE quality (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE region (id INT AUTO_INCREMENT NOT NULL, country_id INT NOT NULL, name VARCHAR(255) NOT NULL, region_code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_F62F176F92F3E70 (country_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE reset_password_request (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, selector VARCHAR(20) NOT NULL, hashed_token VARCHAR(100) NOT NULL, requested_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', expires_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7CE748AA76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_agents_group (id INT AUTO_INCREMENT NOT NULL, counting_campaign_id INT NOT NULL, site_collection_id INT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_7A88312460B458C7 (counting_campaign_id), INDEX IDX_7A8831243EA3E56 (site_collection_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_agents_group_agents_group (site_agents_group_id INT NOT NULL, agents_group_id INT NOT NULL, INDEX IDX_3B5B54BD7C3B27D6 (site_agents_group_id), INDEX IDX_3B5B54BD72A1F93D (agents_group_id), PRIMARY KEY(site_agents_group_id, agents_group_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE site_collection (id INT AUTO_INCREMENT NOT NULL, city_id INT NOT NULL, nature_reserve_id INT DEFAULT NULL, site_name VARCHAR(255) NOT NULL, site_code VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', national_site_code VARCHAR(255) DEFAULT NULL, lat_depart VARCHAR(255) NOT NULL, long_depart VARCHAR(255) NOT NULL, lat_fin VARCHAR(255) NOT NULL, long_fin VARCHAR(255) NOT NULL, parent_site VARCHAR(255) DEFAULT NULL, INDEX IDX_DC44EAF78BAC62AF (city_id), INDEX IDX_DC44EAF7B56D1D28 (nature_reserve_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE tidal (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, image_id INT DEFAULT NULL, user_status_id INT DEFAULT NULL, language_id INT DEFAULT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, name VARCHAR(255) NOT NULL, last_name VARCHAR(255) NOT NULL, phone VARCHAR(15) NOT NULL, created_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', UNIQUE INDEX UNIQ_8D93D6493DA5256D (image_id), INDEX IDX_8D93D6496B178D59 (user_status_id), INDEX IDX_8D93D64982F1BAF4 (language_id), UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_status (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE water (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE weather (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', available_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_75EA56E0FB7336F0 (queue_name), INDEX IDX_75EA56E0E3BD61CE (available_at), INDEX IDX_75EA56E016BA31DB (delivered_at), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE agents_group ADD CONSTRAINT FK_B102AC9773154ED4 FOREIGN KEY (leader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE agents_group ADD CONSTRAINT FK_B102AC97F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE agents_group ADD CONSTRAINT FK_B102AC97B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE agents_group_user ADD CONSTRAINT FK_4A77B07872A1F93D FOREIGN KEY (agents_group_id) REFERENCES agents_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE agents_group_user ADD CONSTRAINT FK_4A77B078A76ED395 FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E705A8725 FOREIGN KEY (bird_family_id) REFERENCES bird_family (id)');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E9F5AA71B FOREIGN KEY (coverage_id) REFERENCES coverage (id)');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E902F3AE5 FOREIGN KEY (bird_life_tax_treat_id) REFERENCES bird_life_tax_treat (id)');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E6E09E6BF FOREIGN KEY (iucn_red_list_category_id) REFERENCES iucn_red_list_category (id)');
        $this->addSql('ALTER TABLE bird_species ADD CONSTRAINT FK_D373D04E3DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE bird_species_count ADD CONSTRAINT FK_450338A0EDED93F9 FOREIGN KEY (collected_data_id) REFERENCES collected_data (id)');
        $this->addSql('ALTER TABLE bird_species_count ADD CONSTRAINT FK_450338A0547180B0 FOREIGN KEY (bird_species_id) REFERENCES bird_species (id)');
        $this->addSql('ALTER TABLE city ADD CONSTRAINT FK_2D5B023498260155 FOREIGN KEY (region_id) REFERENCES region (id)');
        $this->addSql('ALTER TABLE collected_data ADD CONSTRAINT FK_79374E7C60B458C7 FOREIGN KEY (counting_campaign_id) REFERENCES counting_campaign (id)');
        $this->addSql('ALTER TABLE collected_data ADD CONSTRAINT FK_79374E7C3EA3E56 FOREIGN KEY (site_collection_id) REFERENCES site_collection (id)');
        $this->addSql('ALTER TABLE collected_data ADD CONSTRAINT FK_79374E7CB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE collected_data ADD CONSTRAINT FK_79374E7CDFB9E947 FOREIGN KEY (count_type_id) REFERENCES count_type (id)');
        $this->addSql('ALTER TABLE collected_data ADD CONSTRAINT FK_79374E7CBCFC6D57 FOREIGN KEY (quality_id) REFERENCES quality (id)');
        $this->addSql('ALTER TABLE collected_data_bird_species ADD CONSTRAINT FK_7B39E3BBEDED93F9 FOREIGN KEY (collected_data_id) REFERENCES collected_data (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collected_data_bird_species ADD CONSTRAINT FK_7B39E3BB547180B0 FOREIGN KEY (bird_species_id) REFERENCES bird_species (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collected_data_method ADD CONSTRAINT FK_BCC68720EDED93F9 FOREIGN KEY (collected_data_id) REFERENCES collected_data (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE collected_data_method ADD CONSTRAINT FK_BCC6872019883967 FOREIGN KEY (method_id) REFERENCES method (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE counting_campaign ADD CONSTRAINT FK_55508ABB03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE counting_campaign ADD CONSTRAINT FK_55508ABAB7F1CC6 FOREIGN KEY (campaign_status_id) REFERENCES campaign_status (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D26DC4E10B FOREIGN KEY (disturbed_id) REFERENCES disturbed (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D2D553E9BF FOREIGN KEY (ice_id) REFERENCES ice (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D28CD10E1D FOREIGN KEY (tidal_id) REFERENCES tidal (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D27721C36E FOREIGN KEY (water_id) REFERENCES water (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D28CE675E FOREIGN KEY (weather_id) REFERENCES weather (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D2EDED93F9 FOREIGN KEY (collected_data_id) REFERENCES collected_data (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D23EA3E56 FOREIGN KEY (site_collection_id) REFERENCES site_collection (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D260B458C7 FOREIGN KEY (counting_campaign_id) REFERENCES counting_campaign (id)');
        $this->addSql('ALTER TABLE environmental_conditions ADD CONSTRAINT FK_96C106D2A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE logo ADD CONSTRAINT FK_E48E9A133DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE nature_reserve ADD CONSTRAINT FK_763A1F7B03A8386 FOREIGN KEY (created_by_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE nature_reserve ADD CONSTRAINT FK_763A1F7C87FFE17 FOREIGN KEY (reserve_leader_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE region ADD CONSTRAINT FK_F62F176F92F3E70 FOREIGN KEY (country_id) REFERENCES country (id)');
        $this->addSql('ALTER TABLE reset_password_request ADD CONSTRAINT FK_7CE748AA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE site_agents_group ADD CONSTRAINT FK_7A88312460B458C7 FOREIGN KEY (counting_campaign_id) REFERENCES counting_campaign (id)');
        $this->addSql('ALTER TABLE site_agents_group ADD CONSTRAINT FK_7A8831243EA3E56 FOREIGN KEY (site_collection_id) REFERENCES site_collection (id)');
        $this->addSql('ALTER TABLE site_agents_group_agents_group ADD CONSTRAINT FK_3B5B54BD7C3B27D6 FOREIGN KEY (site_agents_group_id) REFERENCES site_agents_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_agents_group_agents_group ADD CONSTRAINT FK_3B5B54BD72A1F93D FOREIGN KEY (agents_group_id) REFERENCES agents_group (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE site_collection ADD CONSTRAINT FK_DC44EAF78BAC62AF FOREIGN KEY (city_id) REFERENCES city (id)');
        $this->addSql('ALTER TABLE site_collection ADD CONSTRAINT FK_DC44EAF7B56D1D28 FOREIGN KEY (nature_reserve_id) REFERENCES nature_reserve (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6493DA5256D FOREIGN KEY (image_id) REFERENCES image (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D6496B178D59 FOREIGN KEY (user_status_id) REFERENCES user_status (id)');
        $this->addSql('ALTER TABLE user ADD CONSTRAINT FK_8D93D64982F1BAF4 FOREIGN KEY (language_id) REFERENCES language (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE agents_group DROP FOREIGN KEY FK_B102AC9773154ED4');
        $this->addSql('ALTER TABLE agents_group DROP FOREIGN KEY FK_B102AC97F92F3E70');
        $this->addSql('ALTER TABLE agents_group DROP FOREIGN KEY FK_B102AC97B03A8386');
        $this->addSql('ALTER TABLE agents_group_user DROP FOREIGN KEY FK_4A77B07872A1F93D');
        $this->addSql('ALTER TABLE agents_group_user DROP FOREIGN KEY FK_4A77B078A76ED395');
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E705A8725');
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E9F5AA71B');
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E902F3AE5');
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E6E09E6BF');
        $this->addSql('ALTER TABLE bird_species DROP FOREIGN KEY FK_D373D04E3DA5256D');
        $this->addSql('ALTER TABLE bird_species_count DROP FOREIGN KEY FK_450338A0EDED93F9');
        $this->addSql('ALTER TABLE bird_species_count DROP FOREIGN KEY FK_450338A0547180B0');
        $this->addSql('ALTER TABLE city DROP FOREIGN KEY FK_2D5B023498260155');
        $this->addSql('ALTER TABLE collected_data DROP FOREIGN KEY FK_79374E7C60B458C7');
        $this->addSql('ALTER TABLE collected_data DROP FOREIGN KEY FK_79374E7C3EA3E56');
        $this->addSql('ALTER TABLE collected_data DROP FOREIGN KEY FK_79374E7CB03A8386');
        $this->addSql('ALTER TABLE collected_data DROP FOREIGN KEY FK_79374E7CDFB9E947');
        $this->addSql('ALTER TABLE collected_data DROP FOREIGN KEY FK_79374E7CBCFC6D57');
        $this->addSql('ALTER TABLE collected_data_bird_species DROP FOREIGN KEY FK_7B39E3BBEDED93F9');
        $this->addSql('ALTER TABLE collected_data_bird_species DROP FOREIGN KEY FK_7B39E3BB547180B0');
        $this->addSql('ALTER TABLE collected_data_method DROP FOREIGN KEY FK_BCC68720EDED93F9');
        $this->addSql('ALTER TABLE collected_data_method DROP FOREIGN KEY FK_BCC6872019883967');
        $this->addSql('ALTER TABLE counting_campaign DROP FOREIGN KEY FK_55508ABB03A8386');
        $this->addSql('ALTER TABLE counting_campaign DROP FOREIGN KEY FK_55508ABAB7F1CC6');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D26DC4E10B');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D2D553E9BF');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D28CD10E1D');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D27721C36E');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D28CE675E');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D2EDED93F9');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D23EA3E56');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D260B458C7');
        $this->addSql('ALTER TABLE environmental_conditions DROP FOREIGN KEY FK_96C106D2A76ED395');
        $this->addSql('ALTER TABLE logo DROP FOREIGN KEY FK_E48E9A133DA5256D');
        $this->addSql('ALTER TABLE nature_reserve DROP FOREIGN KEY FK_763A1F7B03A8386');
        $this->addSql('ALTER TABLE nature_reserve DROP FOREIGN KEY FK_763A1F7C87FFE17');
        $this->addSql('ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395');
        $this->addSql('ALTER TABLE region DROP FOREIGN KEY FK_F62F176F92F3E70');
        $this->addSql('ALTER TABLE reset_password_request DROP FOREIGN KEY FK_7CE748AA76ED395');
        $this->addSql('ALTER TABLE site_agents_group DROP FOREIGN KEY FK_7A88312460B458C7');
        $this->addSql('ALTER TABLE site_agents_group DROP FOREIGN KEY FK_7A8831243EA3E56');
        $this->addSql('ALTER TABLE site_agents_group_agents_group DROP FOREIGN KEY FK_3B5B54BD7C3B27D6');
        $this->addSql('ALTER TABLE site_agents_group_agents_group DROP FOREIGN KEY FK_3B5B54BD72A1F93D');
        $this->addSql('ALTER TABLE site_collection DROP FOREIGN KEY FK_DC44EAF78BAC62AF');
        $this->addSql('ALTER TABLE site_collection DROP FOREIGN KEY FK_DC44EAF7B56D1D28');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6493DA5256D');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D6496B178D59');
        $this->addSql('ALTER TABLE user DROP FOREIGN KEY FK_8D93D64982F1BAF4');
        $this->addSql('DROP TABLE agents_group');
        $this->addSql('DROP TABLE agents_group_user');
        $this->addSql('DROP TABLE bird_family');
        $this->addSql('DROP TABLE bird_life_tax_treat');
        $this->addSql('DROP TABLE bird_species');
        $this->addSql('DROP TABLE bird_species_count');
        $this->addSql('DROP TABLE campaign_status');
        $this->addSql('DROP TABLE city');
        $this->addSql('DROP TABLE collected_data');
        $this->addSql('DROP TABLE collected_data_bird_species');
        $this->addSql('DROP TABLE collected_data_method');
        $this->addSql('DROP TABLE count_type');
        $this->addSql('DROP TABLE counting_campaign');
        $this->addSql('DROP TABLE country');
        $this->addSql('DROP TABLE coverage');
        $this->addSql('DROP TABLE disturbed');
        $this->addSql('DROP TABLE environmental_conditions');
        $this->addSql('DROP TABLE ice');
        $this->addSql('DROP TABLE image');
        $this->addSql('DROP TABLE iucn_red_list_category');
        $this->addSql('DROP TABLE language');
        $this->addSql('DROP TABLE logo');
        $this->addSql('DROP TABLE method');
        $this->addSql('DROP TABLE nature_reserve');
        $this->addSql('DROP TABLE notification');
        $this->addSql('DROP TABLE quality');
        $this->addSql('DROP TABLE region');
        $this->addSql('DROP TABLE reset_password_request');
        $this->addSql('DROP TABLE site_agents_group');
        $this->addSql('DROP TABLE site_agents_group_agents_group');
        $this->addSql('DROP TABLE site_collection');
        $this->addSql('DROP TABLE tidal');
        $this->addSql('DROP TABLE user');
        $this->addSql('DROP TABLE user_status');
        $this->addSql('DROP TABLE water');
        $this->addSql('DROP TABLE weather');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
