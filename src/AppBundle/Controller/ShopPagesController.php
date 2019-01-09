<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 10.07.2018
 * Time: 12:06
 */
declare(strict_types=1);
namespace AppBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Templating\EngineInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ShopPagesController
{
    /**
     * @var EngineInterface
     */
    private $templatingEngine;

    /**
     * @param EngineInterface $templatingEngine
     */
    public function __construct(EngineInterface $templatingEngine)
    {
        $this->templatingEngine = $templatingEngine;
    }

    /**
     * @param Request $request
     *
     * @return Response
     */
    public function showAction(Request $request, string $page): Response
    {
        return $this->templatingEngine->renderResponse('@SyliusShop/StaticPages/'.$page.'.html.twig');
    }
}