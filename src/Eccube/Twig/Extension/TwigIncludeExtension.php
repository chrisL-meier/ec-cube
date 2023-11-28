<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Eccube\Twig\Extension;

use Eccube\Common\EccubeConfig;
use Eccube\Entity\BaseInfo;
use Eccube\Entity\Block;
use Eccube\Entity\Layout;
use Eccube\Entity\Page;
use Symfony\Bridge\Twig\AppVariable;
use Symfony\Component\HttpKernel\Debug\TraceableEventDispatcher;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class TwigIncludeExtension extends AbstractExtension
{
    /**
     * @var \Twig\Environment
     */
    protected $twig;

    public function __construct(\Twig\Environment $twig)
    {
        $this->twig = $twig;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('include_dispatch', [$this, 'include_dispatch'],
                ['needs_context' => true, 'is_safe' => ['all']]),
        ];
    }

    /**
     * @param array<string, AppVariable|BaseInfo|EccubeConfig|TraceableEventDispatcher|Layout|Page|string|boolean|array<int, Block>|array<int, mixed>|int|Block> $context
     * @param string $template
     * @param array<string, mixed> $variables
     * @return string
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function include_dispatch($context, $template, $variables = [])
    {
        if (!empty($variables)) {
            $context = array_merge($context, $variables);
        }

        return $this->twig->render($template, $context);
    }
}
