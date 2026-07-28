<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602132507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE match_camp (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) NOT NULL, statut VARCHAR(255) NOT NULL, game_id INT NOT NULL, equipe_id INT DEFAULT NULL, joueur_id INT DEFAULT NULL, INDEX IDX_A0FBF342E48FD905 (game_id), INDEX IDX_A0FBF3426D861B89 (equipe_id), INDEX IDX_A0FBF342A9E2D76C (joueur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE niveau (id INT AUTO_INCREMENT NOT NULL, libelle VARCHAR(100) NOT NULL, ordre INT NOT NULL, sport_id INT NOT NULL, INDEX IDX_4BDFF36BAC78BCF8 (sport_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sport (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, type VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_1A85EFD26C6E55B5 (nom), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE utilisateur_niveau (id INT AUTO_INCREMENT NOT NULL, utilisateur_id INT NOT NULL, sport_id INT NOT NULL, niveau_id INT NOT NULL, INDEX IDX_2A72C9E0FB88E14F (utilisateur_id), INDEX IDX_2A72C9E0AC78BCF8 (sport_id), INDEX IDX_2A72C9E0B3E9C81 (niveau_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF342E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF3426D861B89 FOREIGN KEY (equipe_id) REFERENCES equipe (id)');
        $this->addSql('ALTER TABLE match_camp ADD CONSTRAINT FK_A0FBF342A9E2D76C FOREIGN KEY (joueur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE niveau ADD CONSTRAINT FK_4BDFF36BAC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0AC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE utilisateur_niveau ADD CONSTRAINT FK_2A72C9E0B3E9C81 FOREIGN KEY (niveau_id) REFERENCES niveau (id)');
        $this->addSql('ALTER TABLE disputer DROP FOREIGN KEY `FK_F251F86E6D861B89`');
        $this->addSql('ALTER TABLE disputer DROP FOREIGN KEY `FK_F251F86EE48FD905`');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY `FK_AB55E24FE48FD905`');
        $this->addSql('ALTER TABLE participation DROP FOREIGN KEY `FK_AB55E24FFB88E14F`');
        $this->addSql('ALTER TABLE utilisateur_sport DROP FOREIGN KEY `FK_DC40B70CFB88E14F`');
        $this->addSql('DROP TABLE disputer');
        $this->addSql('DROP TABLE participation');
        $this->addSql('DROP TABLE utilisateur_sport');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY `FK_2449BA1573A201E5`');
        $this->addSql('DROP INDEX IDX_2449BA1573A201E5 ON equipe');
        $this->addSql('ALTER TABLE equipe ADD niveau_id INT DEFAULT NULL, ADD club_id INT NOT NULL, DROP sport, DROP niveau, CHANGE createur_id sport_id INT NOT NULL');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15AC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA15B3E9C81 FOREIGN KEY (niveau_id) REFERENCES niveau (id)');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT FK_2449BA1561190A32 FOREIGN KEY (club_id) REFERENCES utilisateur (id)');
        $this->addSql('CREATE INDEX IDX_2449BA15AC78BCF8 ON equipe (sport_id)');
        $this->addSql('CREATE INDEX IDX_2449BA15B3E9C81 ON equipe (niveau_id)');
        $this->addSql('CREATE INDEX IDX_2449BA1561190A32 ON equipe (club_id)');
        $this->addSql('ALTER TABLE equipe_joueur ADD statut VARCHAR(255) NOT NULL, CHANGE role role VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game ADD sport_id INT NOT NULL, ADD niveau_requis_id INT DEFAULT NULL, DROP sport, DROP niveau_requis, CHANGE statut statut VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318CAC78BCF8 FOREIGN KEY (sport_id) REFERENCES sport (id)');
        $this->addSql('ALTER TABLE game ADD CONSTRAINT FK_232B318C97DEF455 FOREIGN KEY (niveau_requis_id) REFERENCES niveau (id)');
        $this->addSql('CREATE INDEX IDX_232B318CAC78BCF8 ON game (sport_id)');
        $this->addSql('CREATE INDEX IDX_232B318C97DEF455 ON game (niveau_requis_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE disputer (id INT AUTO_INCREMENT NOT NULL, role VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_0900_ai_ci`, game_id INT NOT NULL, equipe_id INT NOT NULL, INDEX IDX_F251F86EE48FD905 (game_id), INDEX IDX_F251F86E6D861B89 (equipe_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE participation (id INT AUTO_INCREMENT NOT NULL, statut VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, game_id INT NOT NULL, utilisateur_id INT NOT NULL, INDEX IDX_AB55E24FE48FD905 (game_id), INDEX IDX_AB55E24FFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE utilisateur_sport (id INT AUTO_INCREMENT NOT NULL, sport VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, niveau VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_0900_ai_ci`, utilisateur_id INT NOT NULL, INDEX IDX_DC40B70CFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_0900_ai_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE disputer ADD CONSTRAINT `FK_F251F86E6D861B89` FOREIGN KEY (equipe_id) REFERENCES equipe (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE disputer ADD CONSTRAINT `FK_F251F86EE48FD905` FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT `FK_AB55E24FE48FD905` FOREIGN KEY (game_id) REFERENCES game (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE participation ADD CONSTRAINT `FK_AB55E24FFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('ALTER TABLE utilisateur_sport ADD CONSTRAINT `FK_DC40B70CFB88E14F` FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE CASCADE');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF342E48FD905');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF3426D861B89');
        $this->addSql('ALTER TABLE match_camp DROP FOREIGN KEY FK_A0FBF342A9E2D76C');
        $this->addSql('ALTER TABLE niveau DROP FOREIGN KEY FK_4BDFF36BAC78BCF8');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0FB88E14F');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0AC78BCF8');
        $this->addSql('ALTER TABLE utilisateur_niveau DROP FOREIGN KEY FK_2A72C9E0B3E9C81');
        $this->addSql('DROP TABLE match_camp');
        $this->addSql('DROP TABLE niveau');
        $this->addSql('DROP TABLE sport');
        $this->addSql('DROP TABLE utilisateur_niveau');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15AC78BCF8');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA15B3E9C81');
        $this->addSql('ALTER TABLE equipe DROP FOREIGN KEY FK_2449BA1561190A32');
        $this->addSql('DROP INDEX IDX_2449BA15AC78BCF8 ON equipe');
        $this->addSql('DROP INDEX IDX_2449BA15B3E9C81 ON equipe');
        $this->addSql('DROP INDEX IDX_2449BA1561190A32 ON equipe');
        $this->addSql('ALTER TABLE equipe ADD sport VARCHAR(255) NOT NULL, ADD niveau VARCHAR(255) DEFAULT NULL, ADD createur_id INT NOT NULL, DROP sport_id, DROP niveau_id, DROP club_id');
        $this->addSql('ALTER TABLE equipe ADD CONSTRAINT `FK_2449BA1573A201E5` FOREIGN KEY (createur_id) REFERENCES utilisateur (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_2449BA1573A201E5 ON equipe (createur_id)');
        $this->addSql('ALTER TABLE equipe_joueur DROP statut, CHANGE role role VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318CAC78BCF8');
        $this->addSql('ALTER TABLE game DROP FOREIGN KEY FK_232B318C97DEF455');
        $this->addSql('DROP INDEX IDX_232B318CAC78BCF8 ON game');
        $this->addSql('DROP INDEX IDX_232B318C97DEF455 ON game');
        $this->addSql('ALTER TABLE game ADD sport VARCHAR(255) NOT NULL, ADD niveau_requis VARCHAR(255) DEFAULT NULL, DROP sport_id, DROP niveau_requis_id, CHANGE statut statut VARCHAR(255) DEFAULT NULL');
    }
}
