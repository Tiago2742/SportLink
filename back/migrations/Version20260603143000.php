<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'prenom nullable sur utilisateur (comptes club)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur CHANGE prenom prenom VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE utilisateur CHANGE prenom prenom VARCHAR(255) NOT NULL');
    }
}
