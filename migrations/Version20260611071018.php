<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260611071018 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE bet (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, locked_odds DOUBLE PRECISION NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, outcome_id INT NOT NULL, INDEX IDX_FBF0EC9BA76ED395 (user_id), INDEX IDX_FBF0EC9BE6EE6D63 (outcome_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE outcome (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(100) NOT NULL, odds DOUBLE PRECISION NOT NULL, is_winner TINYINT DEFAULT NULL, event_id INT NOT NULL, INDEX IDX_30BC6DC271F7E88B (event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sport_event (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, tournament VARCHAR(100) NOT NULL, team_a VARCHAR(100) NOT NULL, team_b VARCHAR(100) NOT NULL, status VARCHAR(100) NOT NULL, starts_at DATETIME NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, amount NUMERIC(10, 2) NOT NULL, type VARCHAR(20) NOT NULL, description VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_723705D1A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE user (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, username VARCHAR(100) NOT NULL, birth_date DATE NOT NULL, balance NUMERIC(10, 2) NOT NULL, is_suspended TINYINT NOT NULL, self_excluded_until DATETIME DEFAULT NULL, daily_bet_limit NUMERIC(10, 2) DEFAULT NULL, weekly_bet_limit NUMERIC(10, 2) DEFAULT NULL, daily_deposit_limit NUMERIC(10, 2) DEFAULT NULL, weekly_deposit_limit NUMERIC(10, 2) DEFAULT NULL, pending_daily_bet_limit NUMERIC(10, 2) DEFAULT NULL, pending_daily_bet_limit_at DATETIME DEFAULT NULL, pending_weekly_bet_limit NUMERIC(10, 2) DEFAULT NULL, pending_weekly_bet_limit_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE bet ADD CONSTRAINT FK_FBF0EC9BA76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
        $this->addSql('ALTER TABLE bet ADD CONSTRAINT FK_FBF0EC9BE6EE6D63 FOREIGN KEY (outcome_id) REFERENCES outcome (id)');
        $this->addSql('ALTER TABLE outcome ADD CONSTRAINT FK_30BC6DC271F7E88B FOREIGN KEY (event_id) REFERENCES sport_event (id)');
        $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES user (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bet DROP FOREIGN KEY FK_FBF0EC9BA76ED395');
        $this->addSql('ALTER TABLE bet DROP FOREIGN KEY FK_FBF0EC9BE6EE6D63');
        $this->addSql('ALTER TABLE outcome DROP FOREIGN KEY FK_30BC6DC271F7E88B');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('DROP TABLE bet');
        $this->addSql('DROP TABLE outcome');
        $this->addSql('DROP TABLE sport_event');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE user');
    }
}
