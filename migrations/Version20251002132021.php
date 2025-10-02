<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251002132021 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE article_invoice (id INT AUTO_INCREMENT NOT NULL, invoice_id INT DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, tva_rate DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_7DB753F32989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE invoice (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, owner_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, created_at DATETIME DEFAULT NULL, due_at DATETIME DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, name_company VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, siret_company VARCHAR(255) DEFAULT NULL, phone_number_company VARCHAR(255) DEFAULT NULL, email_company VARCHAR(255) DEFAULT NULL, mentions_legales VARCHAR(255) DEFAULT NULL, name_customer VARCHAR(255) DEFAULT NULL, address_customer VARCHAR(255) DEFAULT NULL, city_customer VARCHAR(255) DEFAULT NULL, postal_code_customer VARCHAR(255) DEFAULT NULL, siret_customer VARCHAR(255) DEFAULT NULL, number INT DEFAULT NULL, style VARCHAR(255) DEFAULT NULL, sended_at DATETIME DEFAULT NULL, color VARCHAR(255) DEFAULT NULL, font VARCHAR(255) DEFAULT NULL, country_company VARCHAR(255) DEFAULT NULL, postal_code_company VARCHAR(255) DEFAULT NULL, tva_rate DOUBLE PRECISION DEFAULT NULL, INDEX IDX_906517449395C3F3 (customer_id), INDEX IDX_906517447E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE upsell_invoice (id INT AUTO_INCREMENT NOT NULL, invoice_id INT NOT NULL, name VARCHAR(255) DEFAULT NULL, price INT DEFAULT NULL, tva_rate DOUBLE PRECISION DEFAULT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_87570E0B2989F1FD (invoice_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE article_invoice ADD CONSTRAINT FK_7DB753F32989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517449395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE invoice ADD CONSTRAINT FK_906517447E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE upsell_invoice ADD CONSTRAINT FK_87570E0B2989F1FD FOREIGN KEY (invoice_id) REFERENCES invoice (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE article_invoice DROP FOREIGN KEY FK_7DB753F32989F1FD');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517449395C3F3');
        $this->addSql('ALTER TABLE invoice DROP FOREIGN KEY FK_906517447E3C61F9');
        $this->addSql('ALTER TABLE upsell_invoice DROP FOREIGN KEY FK_87570E0B2989F1FD');
        $this->addSql('DROP TABLE article_invoice');
        $this->addSql('DROP TABLE invoice');
        $this->addSql('DROP TABLE upsell_invoice');
    }
}
