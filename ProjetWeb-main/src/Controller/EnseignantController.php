<?php

namespace App\Controller;

use App\Entity\Enseignant;
use App\Form\EnseignantType;
use App\Repository\EnseignantRepository;
use App\Service\UserManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

#[Route('/enseignant')]
class EnseignantController extends AbstractController
{
    #[Route('/', name: 'enseignant_index', methods: ['GET'])]
    public function index(EnseignantRepository $enseignantRepository): Response
    {
        return $this->render('enseignant/index.html.twig', [
            'enseignants' => $enseignantRepository->findAll(),
            'title'=>'enseignants'
        ]);
    }

    #[Route('/new', name: 'enseignant_new', methods: ['GET', 'POST'])]
    public function new(Request $request,UserManager $userManager,UserPasswordEncoderInterface $encoder): Response
    {
        $enseignant = new Enseignant();
        $enseignant->setRoles([$userManager::ROLE_ENSEIGNANT]);
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password=$form->get('password')->getData();
            $encoded=$encoder->encodePassword($enseignant,(string)$password);
            $enseignant>setPassword($encoded);
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($enseignant);
            $entityManager->flush();

            return $this->redirectToRoute('enseignant_index');
        }

        return $this->render('enseignant/new.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
            'title'=>'ajouter un enseignant'
        ]);
    }

    #[Route('/{id}', name: 'enseignant_show', methods: ['GET'])]
    public function show(Enseignant $enseignant): Response
    {
        return $this->render('enseignant/show.html.twig', [
            'enseignant' => $enseignant,
            'title'=>'enseignant : ' . $enseignant->getNom() . " " . $enseignant->getPrenom(),
        ]);
    }

    #[Route('/{id}/edit', name: 'enseignant_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Enseignant $enseignant,UserPasswordEncoderInterface $encoder): Response
    {
        $form = $this->createForm(EnseignantType::class, $enseignant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $password=$form->get('password')->getData();
            $encoded=$encoder->encodePassword($enseignant,(string)$password);
            $enseignant->setPassword($encoded);
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('enseignant_index');
        }

        return $this->render('enseignant/edit.html.twig', [
            'enseignant' => $enseignant,
            'form' => $form->createView(),
            'title'=>'modifier un enseignant'
        ]);
    }

    #[Route('/{id}', name: 'enseignant_delete', methods: ['POST'])]
    public function delete(Request $request, Enseignant $enseignant): Response
    {
        if ($this->isCsrfTokenValid('delete'.$enseignant->getId(), $request->request->get('_token'))) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->remove($enseignant);
            $entityManager->flush();
        }

        return $this->redirectToRoute('enseignant_index');
    }
}
