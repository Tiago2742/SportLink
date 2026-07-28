<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601091230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE disputer (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) DEFAULT NULL, game_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_F251F86EE48FD905 (game_id), INDEX IDX_F251F86E6D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, sport VARCHAR(255) NOT NULL, niveau VARCHAR(255) DEFAULT NULL, localisation VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, createur_id INT NOT NULL, INDEX IDX_2449BA1573A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe_joueur (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) DEFAULT NULL, utilisateur_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_F046CF6DFB88E14F (utilisateur_id), INDEX IDX_F046CF6D6D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, sport VARCHAR(255) NOT NULL, date_match DATETIME NOT NULL, lieu VARCHAR(255) DEFAULT NULL, niveau_requis VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) DEFAULT NULL, createur_id INT NOT NULL, INDEX IDX_232B318C73A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, game_id INT NOT NULL, expediteur_id INT NOT NULL, INDEX IDX_B6BD307FE48FD905 (game_id), INDEX IDX_B6BD307F10335F61 (expediteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(255) NOT NULL, game_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_AB55E24FE48FD905 (game_id), INDEX IDX_AB55E24FFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat (id INT AUTO_INCREMENT NOT NULL, score_equipe1 INT DEFAULT NULL, score_equipe2 INT DEFAULT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_E7DB5DE2E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE disputer ADD CONSTRAINT FK_F251F86EE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE disputer ADD CONSTRAINT FK_F251F86E6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA1573A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE equipe_joueur ADD CONSTRAINT FK_F046CF6DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE equipe_joueur ADD CONSTRAINT FK_F046CF6D6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C73A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT FK_AB55E24FFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE2E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE disputer DROP FOREIGN KEY FK_F251F86EE48FD905');
        $this->addSql('ALTER TABLE disputer DROP FOREIGN KEY FK_F251F86E6D861B89');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA1573A201E5');
        $this->addSql('ALTER TABLE equipe_joueur DROP FOREIGN KEY FK_F046CF6DFB88E14F');
        $this->addSql('ALTER TABLE equipe_joueur DROP FOREIGN KEY FK_F046CF6D6D861B89');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C73A201E5');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE48FD905');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FE48FD905');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY FK_AB55E24FFB88E14F');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE2E48FD905');
        $this->addSql('DROP TABLE disputer');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE equipe_joueur');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE resultat');
    }
}
