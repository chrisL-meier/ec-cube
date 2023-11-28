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

namespace Eccube\Twig\Sandbox;

use Twig\Sandbox\SecurityPolicy as BasePolicy;
use Twig\Sandbox\SecurityPolicyInterface;

class SecurityPolicyDecorator implements SecurityPolicyInterface {

    /** @var BasePolicy  */
    private $securityPolicy;

    public function __construct(BasePolicy $securityPolicy)
    {
        $this->securityPolicy = $securityPolicy;
    }

    /**
     * @param array<mixed> $tags
     * @param array<mixed> $filters
     * @param array<mixed> $functions
     * @return void
     * @throws \Twig\Sandbox\SecurityError
     */
    public function checkSecurity($tags, $filters, $functions)
    {
        $this->securityPolicy->checkSecurity($tags, $filters, $functions);
    }

    /**
     * @param mixed $obj
     * @param string $method
     * @return void
     * @throws \Twig\Sandbox\SecurityNotAllowedMethodError
     */
    public function checkMethodAllowed($obj, $method)
    {
        // __toStringの場合はチェックをスキップする
        if ($method === '__toString') {
            return;
        }
        $this->securityPolicy->checkMethodAllowed($obj, $method);
    }

    /**
     * @param mixed $obj
     * @param string $method
     * @return void
     * @throws \Twig\Sandbox\SecurityNotAllowedPropertyError
     */
    public function checkPropertyAllowed($obj, $method)
    {
        $this->securityPolicy->checkPropertyAllowed($obj, $method);
    }
}
