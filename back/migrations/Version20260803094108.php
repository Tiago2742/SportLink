<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803094108 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE avis (id INT AUTO_INCREMENT NOT NULL, ponctualite INT NOT NULL, fair_play INT NOT NULL, niveau_conforme INT NOT NULL, date_creation DATETIME NOT NULL, notant_id INT NOT NULL, evalue_id INT NOT NULL, game_id INT NOT NULL, INDEX IDX_8F91ABF0FBB3368D (notant_id), INDEX IDX_8F91ABF06CFFB48E (evalue_id), INDEX IDX_8F91ABF0E48FD905 (game_id), UNIQUE INDEX uniq_avis_notant_game (notant_id, game_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0FBB3368D FOREIGN KEY (notant_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF06CFFB48E FOREIGN KEY (evalue_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE avis ADD CONSTRAINT FK_8F91ABF0E48FD905 FOREIGN KEY (game_id) REFERENCES game (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0FBB3368D');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF06CFFB48E');
        $this->addSql('ALTER TABLE avis DROP FOREIGN KEY FK_8F91ABF0E48FD905');
        $this->addSql('DROP TABLE avis');
    }
}
