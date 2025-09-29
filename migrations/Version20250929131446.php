<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250929131446 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article (id INT AUTO_INCREMENT NOT NULL, devis_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, tva_rate DOUBLE PRECISION DEFAULT NULL, INDEX IDX_23A0E6641DEFADA (devis_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, city VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(255) DEFAULT NULL, country VARCHAR(255) DEFAULT NULL, siret VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE devis (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, owner_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, due_at DATETIME DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, name_company VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, siret_company VARCHAR(255) DEFAULT NULL, phone_number_company VARCHAR(255) DEFAULT NULL, email_company VARCHAR(255) DEFAULT NULL, mentions_legales VARCHAR(255) DEFAULT NULL, name_customer VARCHAR(255) DEFAULT NULL, address_customer VARCHAR(255) DEFAULT NULL, city_customer VARCHAR(255) DEFAULT NULL, postal_code_customer VARCHAR(255) DEFAULT NULL, siret_customer VARCHAR(255) DEFAULT NULL, number INT DEFAULT NULL, INDEX IDX_8B27C52B9395C3F3 (customer_id), INDEX IDX_8B27C52B7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE upsell (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, tva_rate DOUBLE PRECISION DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article ADD CONSTRAINT FK_23A0E6641DEFADA FOREIGN KEY (devis_id) REFERENCES devis (id)');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B9395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE devis ADD CONSTRAINT FK_8B27C52B7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article DROP FOREIGN KEY FK_23A0E6641DEFADA');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B9395C3F3');
        $this->addSql('ALTER TABLE devis DROP FOREIGN KEY FK_8B27C52B7E3C61F9');
        $this->addSql('DROP TABLE article');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE devis');
        $this->addSql('DROP TABLE upsell');
    }
}
