<?php

namespace App\Controller;

use App\Services\RandomCreationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController as Controller;
use Symfony\Component\HttpFoundation\Response;

class RandomCreationController extends Controller
{
    public function generateAction($nombre, $generateVote = false, RandomCreationService $randomService)
    {
        $lastElementCreated = $randomService->generate($nombre, $generateVote);

        return new Response('elements successfully generated');
    }
}
