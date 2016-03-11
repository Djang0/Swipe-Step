<?php

namespace Slim\Handlers;   // name espace you need set on project

use Slim\Handlers\NotFound; 
use Slim\Views\Twig; 
use Psr\Http\Message\ServerRequestInterface; 
use Psr\Http\Message\ResponseInterface;

class MyNotFound extends NotFound {

    private $view;

    public function __construct(Twig $view) { 
        $this->view = $view; 
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response) { 
        parent::__invoke($request, $response);

        $this->view->render($response, '404.twig');

        return $response->withStatus(404); 
    }

}