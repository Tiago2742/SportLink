<?php

namespace App\Tests\Unit\Service;

use App\Entity\Equipe;
use App\Entity\EquipeJoueur;
use App\Entity\Utilisateur;
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
    private EquipeService $service;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->utilisateurRepository = $this->createMock(UtilisateurRepository::class);
        $this->service = new EquipeService($this->em, $this->utilisateurRepository);
    }

    public function testCreerEquipeAvecChampsObligatoires(): void
    {
        $createur = new Utilisateur();
        $donnees = ['nom' => 'Les Aigles', 'sport' => 'football', 'niveau' => 'D1', 'localisation' => 'Paris'];

        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(Equipe::class));
        $this->em->expects($this->once())->method('flush');

        $equipe = $this->service->creer($donnees, $createur);

        $this->assertEquals('Les Aigles', $equipe->getNom());
        $this->assertEquals('football', $equipe->getSport());
        $this->assertEquals('D1', $equipe->getNiveau());
        $this->assertEquals('Paris', $equipe->getLocalisation());
        $this->assertSame($createur, $equipe->getCreateur());
    }

    public function testCreerEquipeSansChampsOptionels(): void
    {
        $createur = new Utilisateur();
        $donnees = ['nom' => 'Les Faucons', 'sport' => 'basketball'];

        $this->em->method('persist');
        $this->em->method('flush');

        $equipe = $this->service->creer($donnees, $createur);

        $this->assertNull($equipe->getNiveau());
        $this->assertNull($equipe->getLocalisation());
        $this->assertNull($equipe->getLogo());
    }

    public function testModifierNomEtSport(): void
    {
        $equipe = new Equipe();
        $equipe->setNom('Ancien nom');
        $equipe->setSport('football');

        $this->em->expects($this->once())->method('flush');

        $resultat = $this->service->modifier($equipe, ['nom' => 'Nouveau nom', 'sport' => 'basketball']);

        $this->assertEquals('Nouveau nom', $resultat->getNom());
        $this->assertEquals('basketball', $resultat->getSport());
    }

    public function testModifierAvecNiveauNull_EffaceLeNiveau(): void
    {
        $equipe = new Equipe();
        $equipe->setNiveau('D1');

        $this->em->method('flush');

        $this->service->modifier($equipe, ['niveau' => null]);

        $this->assertNull($equipe->getNiveau());
    }

    public function testModifierSansChamp_NeModifiePasLeChamp(): void
    {
        $equipe = new Equipe();
        $equipe->setNom('Nom intact');

        $this->em->method('flush');

        $this->service->modifier($equipe, ['sport' => 'tennis']);

        $this->assertEquals('Nom intact', $equipe->getNom());
    }

    public function testAjouterMembre_UtilisateurIntrouvable_LeveException(): void
    {
        $equipe = new Equipe();
        $this->utilisateurRepository->method('find')->willReturn(null);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Utilisateur introuvable.');

        $this->service->ajouterMembre($equipe, 99, 'gestionnaire');
    }

    public function testAjouterMembre_DejaPresent_LeveException(): void
    {
        $utilisateur = new Utilisateur();

        $membreExistant = new EquipeJoueur();
        $membreExistant->setUtilisateur($utilisateur);

        $equipe = $this->creerEquipeAvecMembres([$membreExistant]);

        $this->utilisateurRepository->method('find')->willReturn($utilisateur);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('déjà membre');

        $this->service->ajouterMembre($equipe, 1, null);
    }

    public function testAjouterMembreSucces(): void
    {
        $utilisateur = new Utilisateur();
        $equipe = new Equipe();

        $this->utilisateurRepository->method('find')->willReturn($utilisateur);
        $this->em->expects($this->once())->method('persist')->with($this->isInstanceOf(EquipeJoueur::class));
        $this->em->expects($this->once())->method('flush');

        $membreEquipe = $this->service->ajouterMembre($equipe, 1, 'gestionnaire');

        $this->assertSame($utilisateur, $membreEquipe->getUtilisateur());
        $this->assertSame($equipe, $membreEquipe->getEquipe());
        $this->assertEquals('gestionnaire', $membreEquipe->getRole()->value);
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

    private function creerEquipeAvecMembres(array $membres): Equipe
    {
        $collection = new ArrayCollection($membres);

        $equipe = $this->createPartialMock(Equipe::class, ['getMembres']);
        $equipe->method('getMembres')->willReturn($collection);

        return $equipe;
    }
}
