<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250925142444 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE token_password (id INT AUTO_INCREMENT NOT NULL, owner_id INT DEFAULT NULL, token VARCHAR(255) NOT NULL, used_at DATETIME DEFAULT NULL, INDEX IDX_419D9B567E3C61F9 (owner_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE token_password ADD CONSTRAINT FK_419D9B567E3C61F9 FOREIGN KEY (owner_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE user ADD avatar VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE token_password DROP FOREIGN KEY FK_419D9B567E3C61F9');
        $this->addSql('DROP TABLE token_password');
        $this->addSql('ALTER TABLE `user` DROP avatar');
    }
}
