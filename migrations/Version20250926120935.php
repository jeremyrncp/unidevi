<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250926120935 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user ADD tva_rate INT DEFAULT NULL, ADD validity_devis INT DEFAULT NULL, ADD price_type_devis VARCHAR(255) DEFAULT NULL, ADD mentions_legales_devis VARCHAR(255) DEFAULT NULL, ADD display_fourchette_ia TINYINT(1) DEFAULT NULL, ADD proposer_automatiquement_upsells_devis TINYINT(1) NOT NULL, ADD autorise_suppression_globale_upsells_devis TINYINT(1) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE `user` DROP tva_rate, DROP validity_devis, DROP price_type_devis, DROP mentions_legales_devis, DROP display_fourchette_ia, DROP proposer_automatiquement_upsells_devis, DROP autorise_suppression_globale_upsells_devis');
    }
}
