<?php

namespace Tatooine\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class CmsController extends AbstractController
{
    /**
     * @Route("/cms")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }
}