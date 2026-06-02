<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function inscrire(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em,
        ValidatorInterface $validator,
    ): JsonResponse {
        $donnees = json_decode($request->getContent(), true);

        $champsRequis = ['email', 'password', 'nom', 'prenom', 'type'];
        foreach ($champsRequis as $champ) {
            if (empty($donnees[$champ])) {
                return $this->json(['erreur' => "Le champ \"$champ\" est requis."], 400);
            }
        }

        if ($em->getRepository(Utilisateur::class)->findOneBy(['email' => $donnees['email']])) {
            return $this->json(['erreur' => 'Cette adresse email est déjà utilisée.'], 409);
        }

        $utilisateur = new Utilisateur();
        $utilisateur->setEmail($donnees['email']);
        $utilisateur->setPassword($passwordHasher->hashPassword($utilisateur, $donnees['password']));
        $utilisateur->setNom($donnees['nom']);
        $utilisateur->setPrenom($donnees['prenom']);
        $utilisateur->setType($donnees['type']);
        $utilisateur->setNiveau($donnees['niveau'] ?? null);
        $utilisateur->setLocalisation($donnees['localisation'] ?? null);
        $utilisateur->setDateInscription(new \DateTime());

        $erreurs = $validator->validate($utilisateur);
        if (count($erreurs) > 0) {
            $messages = [];
            foreach ($erreurs as $erreur) {
                $messages[$erreur->getPropertyPath()] = $erreur->getMessage();
            }
            return $this->json(['erreurs' => $messages], 422);
        }

        $em->persist($utilisateur);
        $em->flush();

        return $this->json([
            'id'     => $utilisateur->getId(),
            'email'  => $utilisateur->getEmail(),
            'nom'    => $utilisateur->getNom(),
            'prenom' => $utilisateur->getPrenom(),
            'type'   => $utilisateur->getType(),
        ], 201);
    }
}
