<?php

namespace HoPeter1018\ServiceHelperBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('HoPeter1018ServiceHelperBundle:Default:index.html.twig');
    }
}
