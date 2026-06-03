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
    private EquipeService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->equipeJoueurRepository = $this->createMock(EquipeJoueurRepository::class);
        $this->service = new EquipeService(
            $this->em,
            $this->utilisateurRepository,
            $this->equipeJoueurRepository,
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

        $membreExistant = new EquipeJoueur();
        $membreExistant->setUtilisateur($joueur);
        $membreExistant->setStatut(\App\Enum\StatutMembreEquipe::Confirme);

        $equipe = $this->creerEquipeAvecMembres([$membreExistant], $club);

        $this->utilisateurRepository->method('find')->willReturn($joueur);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('fait déjà partie');

        $this->service->inviterJoueur($equipe, 1, $club);
    }

    public function testDemanderAdhesionSucces(): void
    {
        $club = $this->creerClub();
        $joueur = $this->creerJoueur();
        $equipe = new Equipe();
        $equipe->setClub($club);
        $sport = new Sport();
        $sport->setType(TypeSport::Collectif);
        $equipe->setSport($sport);

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
