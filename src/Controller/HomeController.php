<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController
{
    /**
     * @Route("/", name="app_index")
     */
    public function index()
    {
        return new Response("<body><h1>Hello World</h1></body>");
    }
}
