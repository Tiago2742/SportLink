<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260731122930 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE demande_match (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(255) NOT NULL, date_creation DATETIME NOT NULL, game_id INT NOT NULL, demandeur_id INT NOT NULL, equipe_id INT DEFAULT NULL, INDEX IDX_8C85462CE48FD905 (game_id), INDEX IDX_8C85462C95A6EE59 (demandeur_id), INDEX IDX_8C85462C6D861B89 (equipe_id), UNIQUE INDEX uq_demande_game_demandeur (game_id, demandeur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE demande_match ADD CONSTRAINT FK_8C85462CE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE demande_match ADD CONSTRAINT FK_8C85462C95A6EE59 FOREIGN KEY (demandeur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE demande_match ADD CONSTRAINT FK_8C85462C6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE demande_match DROP FOREIGN KEY FK_8C85462CE48FD905');
        $this->addSql('ALTER TABLE demande_match DROP FOREIGN KEY FK_8C85462C95A6EE59');
        $this->addSql('ALTER TABLE demande_match DROP FOREIGN KEY FK_8C85462C6D861B89');
        $this->addSql('DROP TABLE demande_match');
    }
}
