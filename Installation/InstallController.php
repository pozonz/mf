<?php

namespace MillenniumFalcon\Installation;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class InitController extends AbstractController
{
    /**
     * @Route("/install")
     */
    public function index()
    {
        return $this->json([
            'message' => 'Welcome to your new controller!',
            'path' => 'src/Controller/CmsController.php',
        ]);
    }
}