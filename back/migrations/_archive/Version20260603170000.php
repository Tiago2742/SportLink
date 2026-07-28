<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'RoleEquipe : capitaine → gestionnaire';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE equipe_joueur SET role = 'gestionnaire' WHERE role = 'capitaine'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("UPDATE equipe_joueur SET role = 'capitaine' WHERE role = 'gestionnaire'");
    }
}
