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

namespace Eccube\Event;

use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class EventArgs extends GenericEvent
{
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response|null
     */
    private $response;

    /**
     * EventArgs constructor.
     *
     * @param array<mixed> $arguments
     * @param Request|null $request
     */
    public function __construct(array $arguments = [], Request $request = null)
    {
        parent::__construct(null, $arguments);
        $this->request = $request;
    }

    /**
     * @param Request $request
     *
     * @return void
     */
    public function setRequest(Request $request)
    {
        $this->request = $request;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Response $response
     *
     * @return void
     */
    public function setResponse(Response $response)
    {
        $this->response = $response;
    }

    /**
     * @return Response|null
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @return bool
     */
    public function hasResponse()
    {
        return $this->response instanceof Response;
    }
}
