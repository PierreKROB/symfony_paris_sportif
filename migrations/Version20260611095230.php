<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611095230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sport_event ADD source VARCHAR(20) DEFAULT \'manual\' NOT NULL, ADD riot_match_id VARCHAR(150) DEFAULT NULL, ADD riot_puuid VARCHAR(100) DEFAULT NULL, ADD summoner_name VARCHAR(100) DEFAULT NULL, ADD team_alogo_url VARCHAR(255) DEFAULT NULL, ADD team_blogo_url VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE sport_event DROP source, DROP riot_match_id, DROP riot_puuid, DROP summoner_name, DROP team_alogo_url, DROP team_blogo_url');
    }
}
