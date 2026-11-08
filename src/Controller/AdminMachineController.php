<?php

namespace App\Controller;

use App\Entity\Machine;
use App\Repository\MachineRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\MachineType;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/machines')]
#[IsGranted('ROLE_ADMIN')]
class AdminMachineController extends AbstractController
{
    #[Route('/', name: 'app_admin_machine_index', methods: ['GET'])]
    public function index(MachineRepository $machineRepository): Response
    {
        return $this->render('admin/machine/index.html.twig', [
            'machines' => $machineRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_admin_machine_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $machine = new Machine();
        $form = $this->createForm(MachineType::class, $machine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->handleFileUpload($form, $machine, $slugger);

            $entityManager->persist($machine);
            $entityManager->flush();
            
            $this->addFlash('success', 'Nowa jednostka sprzętowa: ' . $machine->getName() . ' została pomyślnie dodana do bazy.');
            return $this->redirectToRoute('app_admin_machine_index');
        }

        return $this->render('admin/machine/new.html.twig', [
            'machine' => $machine,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_machine_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Machine $machine, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(MachineType::class, $machine);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $this->handleFileUpload($form, $machine, $slugger);

            $entityManager->flush();
            
            $this->addFlash('success', 'Maszyna ' . $machine->getName() . ' została zaktualizowana.');
            return $this->redirectToRoute('app_admin_machine_index');
        }

        return $this->render('admin/machine/edit.html.twig', [
            'machine' => $machine,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_admin_machine_delete', methods: ['POST'])]
    public function delete(Request $request, Machine $machine, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$machine->getId(), $request->request->get('_token'))) {
            try {
                $entityManager->remove($machine);
                $entityManager->flush();
                $this->addFlash('success', 'Maszyna została pomyślnie usunięta z bazy.');
            } catch (ForeignKeyConstraintViolationException $e) {
                $this->addFlash('error', 'Nie można usunąć sprzętu: "' . $machine->getName() . '", ponieważ posiada już zapisaną historię treningową lub jest w aktywnym planie.');
            }
        }

        return $this->redirectToRoute('app_admin_machine_index');
    }

    // Pomocnicza metoda zajmująca się wgrywaniem na dysk
    private function handleFileUpload($form, Machine $machine, SluggerInterface $slugger): void
    {
        $imageStartFile = $form->get('imageStart')->getData();
        $imageEndFile = $form->get('imageEnd')->getData();
        $uploadDirectory = $this->getParameter('kernel.project_dir') . '/public/images/machines';

        // 1. Generujemy bezpieczny rdzeń nazwy na podstawie NAZWY MASZYNY (np. "Wyciskanie Nóg" -> "wyciskanie-nog")
        $baseFilename = strtolower($slugger->slug($machine->getName()));

        if ($imageStartFile) {
            // 2. Budujemy nową nazwę dla Fazy 1: nazwa-maszyny_start.rozszerzenie
            $newFilenameStart = $baseFilename . '_start.' . $imageStartFile->guessExtension();

            try {
                // Plik nadpisze stary obrazek o tej samej nazwie, co jest pożądanym zachowaniem przy edycji
                $imageStartFile->move($uploadDirectory, $newFilenameStart);
                $machine->setImageStart($newFilenameStart);
            } catch (FileException $e) {
                $this->addFlash('error', 'Wystąpił błąd przy wgrywaniu obrazu Fazy 1.');
            }
        }

        if ($imageEndFile) {
            // 3. Budujemy nową nazwę dla Fazy 2: nazwa-maszyny_end.rozszerzenie
            $newFilenameEnd = $baseFilename . '_end.' . $imageEndFile->guessExtension();

            try {
                $imageEndFile->move($uploadDirectory, $newFilenameEnd);
                $machine->setImageEnd($newFilenameEnd);
            } catch (FileException $e) {
                $this->addFlash('error', 'Wystąpił błąd przy wgrywaniu obrazu Fazy 2.');
            }
        }
    }
}