<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250927115643 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE payment (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, amount_cents INT NOT NULL, state VARCHAR(255) NOT NULL, created_at DATETIME NOT NULL, stripe_id VARCHAR(255) DEFAULT NULL, event_type VARCHAR(255) DEFAULT NULL, INDEX IDX_6D28840D7E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE subscription (id INT AUTO_INCREMENT NOT NULL, owner_id INT NOT NULL, created_at DATETIME NOT NULL, subscription_stripe_id VARCHAR(255) NOT NULL, active TINYINT(1) NOT NULL, INDEX IDX_A3C664D37E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE payment ADD CONSTRAINT FK_6D28840D7E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE subscription ADD CONSTRAINT FK_A3C664D37E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD trial_ended_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE payment DROP FOREIGN KEY FK_6D28840D7E3C61F9');
        $this->addSql('ALTER TABLE subscription DROP FOREIGN KEY FK_A3C664D37E3C61F9');
        $this->addSql('DROP TABLE payment');
        $this->addSql('DROP TABLE subscription');
        $this->addSql('ALTER TABLE `user` DROP trial_ended_at');
    }
}
