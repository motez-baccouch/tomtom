<?php

namespace App\Controller;

use App\Entity\Note;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ValidateurController extends AbstractController
{
    #[Route('/validateur', name: 'validateur')]
    public function index(): Response
    {
        $repository = $this->getDoctrine()->getRepository('App:Filiere');
        $filiere = $repository->findall();


        $filieres=array();
        foreach($filiere as $fil){
            $niveau=$fil->getNiveaux();
            $fila=$fil->getFiliere();
            $filieres[$fila]=array();
            foreach($niveau as $niv){
                array_push($filieres[$fila],$niv->getNiveau());


            }
        }



        return $this->render('validateur/index.html.twig', [
            'filieres'=>$filieres,
            'title' => 'Validateur',

        ]);
    }


    #[Route('/validateur/{semester}/{filiere}/{niveau}', name: 'matiereValide')]
    public function mats(Request $request,int $semester,string $filiere,int $niveau): Response
    {
        $repository = $this->getDoctrine()->getRepository('App:Filiere');
        $repository2 = $this->getDoctrine()->getRepository('App:MatiereNiveauFiliere');
        $repository3= $this->getDoctrine()->getRepository('App:Niveau');
        $repository4= $this->getDoctrine()->getRepository('App:Note');
        $repository5= $this->getDoctrine()->getRepository('App:Etudiant');

        $fil = $repository->findOneBy(['filiere'=>$filiere]);
        $niv = $repository3->findOneBy(['niveau'=>$niveau]);
        $matieres=$repository2->findall();


            if($fil){
                $filId=$fil->getId();
            }else{
                return $this->redirectToRoute('not_found');
            }



            if($niv){
                $nivId=$niv->getId();

            }else{
                return $this->redirectToRoute('not_found');
            }



        foreach($matieres as $mat){
            $matName=$mat->getMatiere()->getNom();


            $ds=true;
            $tp=true;
            $exam=true;

            $etudiants=$repository5->findBy(['filiere'=>$fil , 'niveau'=>$niv]);

            $notes=$repository4->findBy(['matiere'=>$mat]);

            if($notes==null){
                $ds=false;
                $tp=false;
                $exam=false;}
            else {
                foreach ($etudiants as $etudiant) {

                    foreach ($notes as $note) {
                        if ($note->getEtudiant() == $etudiant) {
                            if ($note->getNoteDS() == null) {
                                $ds = false;
                            }
                            if ($note->getNoteTp() == null) {
                                $tp = false;
                            }
                            if ($note->getNoteExamen() == null) {
                                $exam = false;
                            }

                        }
                    }
                }
            }


            if($mat->getFiliere()->getId()==$filId && $mat->getNiveau()->getId()==$nivId && ($ds==true || $tp==true ||$exam==true)){
                $matiere[$matName]=array();
                if($mat->getTp() && $tp==true){ array_push($matiere[$matName],"TP");}
                if($mat->getDs() && $ds==true){ array_push($matiere[$matName],"DS");}
                if($mat->getExamen() && $exam==true){ array_push($matiere[$matName],"Exam");}

            }
            else{$matiere=array();}
        }



        return $this->render('validateur/matieresV.html.twig', [
            'controller_name' => 'ScolariteController',
            'matiere'=>$matiere,
            'title' => 'Matieres',
            'semester'=>$semester,
            'filiere'=>$filiere,
            'niveau'=>$niveau
        ]);
    }


    #[Route('/validateur/{semester}/{filiere}/{niveau}/{matiere}/{type}', name: 'validation')]
    public function notes(Request $request,int $semester,string $filiere,int $niveau, string $type,string $matiere): Response
    {
        $repository = $this->getDoctrine()->getRepository('App:Etudiant');
        $repository2 = $this->getDoctrine()->getRepository('App:Matiere');
        $repository3 = $this->getDoctrine()->getRepository('App:MatiereNiveauFiliere');
        $repository4 = $this->getDoctrine()->getRepository('App:Filiere');
        $repository5 = $this->getDoctrine()->getRepository('App:Niveau');
        $repository6 = $this->getDoctrine()->getRepository('App:Note');

        $fil=$repository4->findOneBy(['filiere'=>$filiere]);
        $niv=$repository5->findOneBy(['niveau'=>$niveau]);

        $etudiants=$repository->findAll();
        $mat=$repository2->findOneBy(['nom'=>$matiere]);

        if(!$mat){return $this->redirectToRoute('not_found');}
        if(!$fil){return $this->redirectToRoute('not_found');}
        if(!$niv){return $this->redirectToRoute('not_found');}

        if($semester!=1 && $semester!=2){return $this->redirectToRoute('not_found');}
        if(strtoupper($type)<>"DS" && strtoupper($type)<>"TP" && strtoupper($type)!="EXAM"){return $this->redirectToRoute('not_found');}

        $matNivFil=$repository3->findOneBy(['matiere'=>$mat]);

        $notes=array();

        foreach($etudiants as $etudiant){
            if($etudiant->getFiliere()->getFiliere()==$filiere && $etudiant->getNiveau()->getNiveau()==$niveau ){

                $note=$repository6->findOneBy(['etudiant'=>$etudiant,'matiere'=>$matNivFil ]);

                array_push($notes,$note);

            }
        }




        if($request->isMethod('post')){
            $posts = $request->request->all();

            unset($posts["DataTables_Table_0_length"]);

            foreach($etudiants as $etudiant){
                foreach ($posts as $key=>$post){
                    $etudiant=$repository->findOneBy(['numInscription'=>$key]);
                    if($etudiant){
                        $note=$repository6->findOneBy(['etudiant'=>$etudiant,'matiere'=>$matNivFil ]);
                        if(strtoupper($type)=="DS" && strtoupper($type)<>"TP" && strtoupper($type)!="EXAM"){
                            $note->setDsValid("true");
                            $entityManager = $this->getDoctrine()->getManager();
                            $entityManager->flush();
                        }
                        if( strtoupper($type)=="TP"){
                            $note->setTpValid("true");
                            $entityManager = $this->getDoctrine()->getManager();
                            $entityManager->flush();
                        }
                        if(  strtoupper($type)=="EXAM"){
                            $note->setExamenValid("true");
                            $entityManager = $this->getDoctrine()->getManager();
                            $entityManager->flush();
                        }
                    }
                }
            }



        }




        return $this->render('validateur/validation.html.twig', [
            'etudiants'=>$notes,
            'title' => 'validation',
            'type'=>$type
        ]);

    }





}
