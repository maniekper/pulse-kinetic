<?php

namespace App\Controller;

use App\Repository\MachineRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MachineController extends AbstractController
{
    #[Route('/machines', name: 'app_machine_index')]
    public function index(MachineRepository $machineRepository): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        // Zabezpieczenie: jeśli ktoś nie jest zalogowany, odsyłamy do logowania
        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Wywołujemy spersonalizowaną metodę z MachineRepository
        $machines = $machineRepository->findRecommendedForUser($user);

        // Przekazujemy posortowaną listę do szablonu Twig
        return $this->render('machine/index.html.twig', [
            'machines' => $machines,
        ]);
    }
}
