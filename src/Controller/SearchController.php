<?php

declare(strict_types=1);

namespace araise\CrudBundle\Controller;

use araise\SearchBundle\Manager\SearchManager;
use araise\SearchBundle\Traits\SearchTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SearchController extends AbstractController
{
    use SearchTrait;

    public function searchAction(Request $request, SearchManager $searchManager): Response
    {
        $templateParams = $this->getGlobalResults($request, $searchManager);

        return $this->render($this->getSearchTemplate(), $templateParams);
    }
}
