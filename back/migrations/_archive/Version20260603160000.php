<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260603160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'EquipeJoueur : origine + statuts en_attente|confirme|refuse (invite → en_attente)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("UPDATE equipe_joueur SET statut = 'en_attente' WHERE statut = 'invite'");

        $this->addSql(
            "ALTER TABLE equipe_joueur ADD origine VARCHAR(255) NOT NULL DEFAULT 'invitation_club'"
        );
        $this->addSql('ALTER TABLE equipe_joueur ALTER origine DROP DEFAULT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE equipe_joueur DROP origine');
        $this->addSql("UPDATE equipe_joueur SET statut = 'invite' WHERE statut = 'en_attente'");
    }
}
