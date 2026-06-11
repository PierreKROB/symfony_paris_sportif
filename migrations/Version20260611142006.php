<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611142006 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ sport sur sport_event (requis par l\'énoncé)';
    }

    public function up(Schema $schema): void
    {
        // Valeur par défaut pour les lignes déjà en base
        $this->addSql("ALTER TABLE sport_event ADD sport VARCHAR(100) NOT NULL DEFAULT 'League of Legends'");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sport_event DROP sport');
    }
}
