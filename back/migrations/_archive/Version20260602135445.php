<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602135445 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE equipe (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, logo VARCHAR(255) DEFAULT NULL, sport_id INT NOT NULL, niveau_id INT DEFAULT NULL, club_id INT NOT NULL, INDEX IDX_2449BA15AC78BCF8 (sport_id), INDEX IDX_2449BA15B3E9C81 (niveau_id), INDEX IDX_2449BA1561190A32 (club_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE equipe_joueur (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, utilisateur_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_F046CF6DFB88E14F (utilisateur_id), INDEX IDX_F046CF6D6D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE game (id INT AUTO_INCREMENT NOT NULL, date_match DATETIME NOT NULL, lieu VARCHAR(255) DEFAULT NULL, statut VARCHAR(255) NOT NULL, sport_id INT NOT NULL, niveau_requis_id INT DEFAULT NULL, createur_id INT NOT NULL, INDEX IDX_232B318CAC78BCF8 (sport_id), INDEX IDX_232B318C97DEF455 (niveau_requis_id), INDEX IDX_232B318C73A201E5 (createur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE match_camp (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, game_id INT NOT NULL, equipe_id INT DEFAULT NULL, joueur_id INT DEFAULT NULL, INDEX IDX_A0FBF342E48FD905 (game_id), INDEX IDX_A0FBF3426D861B89 (equipe_id), INDEX IDX_A0FBF342A9E2D76C (joueur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE message (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_envoi DATETIME NOT NULL, game_id INT NOT NULL, expediteur_id INT NOT NULL, INDEX IDX_B6BD307FE48FD905 (game_id), INDEX IDX_B6BD307F10335F61 (expediteur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE niveau (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, ordre INT NOT NULL, sport_id INT NOT NULL, INDEX IDX_4BDFF36BAC78BCF8 (sport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE resultat (id INT AUTO_INCREMENT NOT NULL, score_camp1 INT NOT NULL, score_camp2 INT NOT NULL, game_id INT NOT NULL, UNIQUE INDEX UNIQ_E7DB5DE2E48FD905 (game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sport (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1A85EFD26C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, nom VARCHAR(255) NOT NULL, prenom VARCHAR(255) NOT NULL, type VARCHAR(255) NOT NULL, localisation VARCHAR(255) DEFAULT NULL, date_inscription DATETIME NOT NULL, UNIQUE INDEX UNIQ_IDENTIFIER_EMAIL (email), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur_niveau (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, sport_id INT NOT NULL, niveau_id INT NOT NULL, INDEX IDX_2A72C9E0FB88E14F (utilisateur_id), INDEX IDX_2A72C9E0AC78BCF8 (sport_id), INDEX IDX_2A72C9E0B3E9C81 (niveau_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15AC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15B3E9C81 FOREIGN KEY (niveau_id) REFERENCES niveau (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA1561190A32 FOREIGN KEY (club_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE equipe_joueur ADD CONSTRAINT FK_F046CF6DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE equipe_joueur ADD CONSTRAINT FK_F046CF6D6D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CAC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C97DEF455 FOREIGN KEY (niveau_requis_id) REFERENCES niveau (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C73A201E5 FOREIGN KEY (createur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF342E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF3426D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF342A9E2D76C FOREIGN KEY (joueur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307FE48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE message ADD CONSTRAINT FK_B6BD307F10335F61 FOREIGN KEY (expediteur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE niveau ADD CONSTRAINT FK_4BDFF36BAC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE resultat ADD CONSTRAINT FK_E7DB5DE2E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0AC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0B3E9C81 FOREIGN KEY (niveau_id) REFERENCES niveau (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15AC78BCF8');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15B3E9C81');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA1561190A32');
        $this->addSql('ALTER TABLE equipe_joueur DROP FOREIGN KEY FK_F046CF6DFB88E14F');
        $this->addSql('ALTER TABLE equipe_joueur DROP FOREIGN KEY FK_F046CF6D6D861B89');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CAC78BCF8');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C97DEF455');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C73A201E5');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF342E48FD905');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF3426D861B89');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF342A9E2D76C');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307FE48FD905');
        $this->addSql('ALTER TABLE message DROP FOREIGN KEY FK_B6BD307F10335F61');
        $this->addSql('ALTER TABLE niveau DROP FOREIGN KEY FK_4BDFF36BAC78BCF8');
        $this->addSql('ALTER TABLE resultat DROP FOREIGN KEY FK_E7DB5DE2E48FD905');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0AC78BCF8');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0B3E9C81');
        $this->addSql('DROP TABLE equipe');
        $this->addSql('DROP TABLE equipe_joueur');
        $this->addSql('DROP TABLE game');
        $this->addSql('DROP TABLE match_camp');
        $this->addSql('DROP TABLE message');
        $this->addSql('DROP TABLE niveau');
        $this->addSql('DROP TABLE resultat');
        $this->addSql('DROP TABLE sport');
        $this->addSql('DROP TABLE utilisateur');
        $this->addSql('DROP TABLE utilisateur_niveau');
    }
}
