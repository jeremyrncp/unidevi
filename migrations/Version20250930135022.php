<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250930135022 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upsell ADD devis_id INT NOT NULL');
        $this->addSql('ALTER TABLE upsell ADD CONSTRAINT FK_A925D27F41DEFADA FOREIGN KEY (devis_id) REFERENCES devis (id)');
        $this->addSql('CREATE INDEX IDX_A925D27F41DEFADA ON upsell (devis_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE upsell DROP FOREIGN KEY FK_A925D27F41DEFADA');
        $this->addSql('DROP INDEX IDX_A925D27F41DEFADA ON upsell');
        $this->addSql('ALTER TABLE upsell DROP devis_id');
    }
}
