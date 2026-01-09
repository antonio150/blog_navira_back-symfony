<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
class LoginController 
{
    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function __invoke(Request $request)
    {
        // Ce contrôleur ne fait rien : l'authentificateur va intercepter
        throw new \LogicException('Cette méthode ne devrait jamais être appelée directement');
    }
}