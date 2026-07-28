<?php

namespace App\Tests\Unit\Service;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Sport;
use App\Entity\Utilisateur;
use App\Enum\OrigineMembreEquipe;
use App\Enum\StatutMembreEquipe;
use App\Enum\TypeSport;
use App\Enum\TypeUtilisateur;
use App\Repository\EquipeJoueurRepository;
use App\Repository\UtilisateurNiveauRepository;
use App\Repository\UtilisateurRepository;
use App\Service\EquipeService;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class EquipeServiceTest extends TestCase
{
    private EntityManagerInterface&MockObject $em;
    private UtilisateurRepository&MockObject $utilisateurRepository;
    private EquipeJoueurRepository&MockObject $equipeJoueurRepository;
    private UtilisateurNiveauRepository&MockObject $utilisateurNiveauRepository;
    private EquipeService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->equipeJoueurRepository = $this->createMock(EquipeJoueurRepository::class);
        $this->utilisateurNiveauRepository = $this->createMock(UtilisateurNiveauRepository::class);
        $this->service = new EquipeService(
            $this->em,
            $this->utilisateurRepository,
            $this->equipeJoueurRepository,
            $this->utilisateurNiveauRepository,
        );
    }

    public function testInviterJoueur_Introuvable_LeveException(): void
    {
        $club = $this->creerClub();
        $equipe = new Equipe();
        $equipe->setClub($club);

        $this->utilisateurRepository->method('find')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Joueur introuvable.');

        $this->service->inviterJoueur($equipe, 99, $club);
    }

    public function testInviterJoueur_DejaPresent_LeveException(): void
    {
        $club = $this->creerClub();
        $joueur = $this->creerJoueur();
        $this->setId($joueur, 1);

        $membreExistant = new EquipeJoueur();
        $membreExistant->setUtilisateur($joueur);
        $membreExistant->setStatut(\App\Enum\StatutMembreEquipe::Confirme);

        $equipe = $this->creerEquipeAvecMembres([$membreExistant], $club);
        // Le service (R : sport déclaré) interroge UtilisateurNiveauRepository avant
        // de vérifier l'appartenance existante : le joueur doit déclarer le sport de
        // l'équipe pour atteindre la branche "déjà membre".
        $this->setId($equipe->getSport(), 10);
        $this->utilisateurNiveauRepository->method('findSportIdsByUtilisateur')->willReturn([10]);

        $this->utilisateurRepository->method('find')->willReturn($joueur);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fait déjà partie');

        $this->service->inviterJoueur($equipe, 1, $club);
    }

    public function testDemanderAdhesionSucces(): void
    {
        $club = $this->creerClub();
        $joueur = $this->creerJoueur();
        $this->setId($joueur, 2);
        $equipe = new Equipe();
        $equipe->setClub($club);
        $sport = new Sport();
        $sport->setType(TypeSport::Collectif);
        $this->setId($sport, 20);
        $equipe->setSport($sport);

        // Le sport doit être déclaré par le joueur (vérifié via UtilisateurNiveauRepository)
        // avant que la demande d'adhésion ne soit acceptée.
        $this->utilisateurNiveauRepository->method('findSportIdsByUtilisateur')->willReturn([20]);
        $this->equipeJoueurRepository->method('aAdhesionActiveSurSport')->willReturn(false);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(EquipeJoueur::class));
        $this->em->expects($this->once())->method('flush');

        $membre = $this->service->demanderAdhesion($equipe, $joueur);

        $this->assertSame($joueur, $membre->getUtilisateur());
        $this->assertEquals(StatutMembreEquipe::EnAttente, $membre->getStatut());
        $this->assertEquals(OrigineMembreEquipe::DemandeJoueur, $membre->getOrigine());
    }

    public function testRepondreDemandeJoueur_ParLeClub(): void
    {
        $club = $this->creerClub();
        $joueur = $this->creerJoueur();
        $sport = new Sport();
        $sport->setType(TypeSport::Collectif);
        $equipe = new Equipe();
        $equipe->setClub($club);
        $equipe->setSport($sport);

        $membre = new EquipeJoueur();
        $membre->setUtilisateur($joueur);
        $membre->setEquipe($equipe);
        $membre->setStatut(StatutMembreEquipe::EnAttente);
        $membre->setOrigine(OrigineMembreEquipe::DemandeJoueur);

        $this->equipeJoueurRepository->method('aAdhesionActiveSurSport')->willReturn(false);
        $this->em->expects($this->once())->method('flush');

        $resultat = $this->service->repondreAdhesion($membre, StatutMembreEquipe::Confirme, $club);

        $this->assertEquals(StatutMembreEquipe::Confirme, $resultat->getStatut());
    }

    public function testSupprimerAdhesion_JoueurQuitteConfirme(): void
    {
        $joueur = $this->creerJoueur();
        $membre = new EquipeJoueur();
        $membre->setUtilisateur($joueur);
        $membre->setRole(\App\Enum\RoleEquipe::Joueur);
        $membre->setStatut(StatutMembreEquipe::Confirme);

        $this->em->expects($this->once())->method('remove')->with($membre);
        $this->em->expects($this->once())->method('flush');

        $this->service->supprimerAdhesion($membre, $joueur);
    }

    public function testSupprimerAdhesion_Gestionnaire_LeveException(): void
    {
        $club = $this->creerClub();
        $membre = new EquipeJoueur();
        $membre->setUtilisateur($club);
        $membre->setRole(\App\Enum\RoleEquipe::Gestionnaire);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('gestionnaire');

        $this->service->supprimerAdhesion($membre, $club);
    }

    public function testSupprimerAdhesion_ClubRetireJoueur(): void
    {
        $club = $this->creerClub();
        $joueur = $this->creerJoueur();
        $equipe = new Equipe();
        $equipe->setClub($club);

        $membre = new EquipeJoueur();
        $membre->setUtilisateur($joueur);
        $membre->setEquipe($equipe);
        $membre->setRole(\App\Enum\RoleEquipe::Joueur);
        $membre->setStatut(StatutMembreEquipe::Confirme);

        $this->em->expects($this->once())->method('remove')->with($membre);
        $this->em->expects($this->once())->method('flush');

        $this->service->supprimerAdhesion($membre, $club);
    }

    public function testSupprimerAdhesion_Intrus_LeveException(): void
    {
        $joueur = $this->creerJoueur();
        $autre = $this->creerJoueur();
        $membre = new EquipeJoueur();
        $membre->setUtilisateur($joueur);
        $membre->setRole(\App\Enum\RoleEquipe::Joueur);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Accès refusé');

        $this->service->supprimerAdhesion($membre, $autre);
    }

    public function testRetirerMembre(): void
    {
        $membre = new EquipeJoueur();

        $this->em->expects($this->once())->method('remove')->with($membre);
        $this->em->expects($this->once())->method('flush');

        $this->service->retirerMembre($membre);
    }

    public function testSupprimerEquipe(): void
    {
        $equipe = new Equipe();

        $this->em->expects($this->once())->method('remove')->with($equipe);
        $this->em->expects($this->once())->method('flush');

        $this->service->supprimer($equipe);
    }

    /** Force l'id (auto-généré normalement) d'une entité fraîchement construite en test. */
    private function setId(object $entite, int $id): void
    {
        $ref = new \ReflectionProperty($entite, 'id');
        $ref->setValue($entite, $id);
    }

    private function creerClub(): Utilisateur
    {
        $club = new Utilisateur();
        $club->setType(TypeUtilisateur::Club);

        return $club;
    }

    private function creerJoueur(): Utilisateur
    {
        $joueur = new Utilisateur();
        $joueur->setType(TypeUtilisateur::Joueur);

        return $joueur;
    }

    private function creerEquipeAvecMembres(array $membres, Utilisateur $club): Equipe
    {
        $collection = new ArrayCollection($membres);

        $equipe = $this->createPartialMock(Equipe::class, ['getMembres', 'getClub', 'getSport']);
        $equipe->method('getMembres')->willReturn($collection);
        $equipe->method('getClub')->willReturn($club);

        $sport = new Sport();
        $sport->setType(TypeSport::Collectif);
        $equipe->method('getSport')->willReturn($sport);

        return $equipe;
    }
}
