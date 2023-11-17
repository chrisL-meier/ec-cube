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

namespace Eccube\EventListener;

use Eccube\Common\EccubeConfig;
use Eccube\Request\Context;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpFoundation\IpUtils;

class IpAddrListener implements EventSubscriberInterface
{
    /**
     * @var EccubeConfig
     */
    protected $eccubeConfig;

    /**
     * @var Context
     */
    protected $requestContext;

    public function __construct(EccubeConfig $eccubeConfig, Context $requestContext)
    {
        $this->eccubeConfig = $eccubeConfig;
        $this->requestContext = $requestContext;
    }

    /**
     * @param RequestEvent $event
     * @return void
     */
    public function onKernelRequest(RequestEvent $event)
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $clientIp = $event->getRequest()->getClientIp();
        log_debug('Client IP: '.$clientIp);

        if (!$this->requestContext->isAdmin()) {

            // IPアドレス許可リスト範囲になければ拒否
            $allowFrontHosts = $this->eccubeConfig['eccube_front_allow_hosts'];
            if (!empty($allowFrontHosts) && !$this->isClientIpInList($allowFrontHosts, $clientIp)) {
                throw new AccessDeniedHttpException();
            }

            // IPアドレス拒否リスト範囲にあれば拒否
            $denyFrontHosts =  $this->eccubeConfig['eccube_front_deny_hosts'];
            if (!empty($denyFrontHosts) && $this->isClientIpInList($denyFrontHosts, $clientIp)) {
                throw new AccessDeniedHttpException();
            }

            return;
        }

        // IPアドレス許可リスト範囲になければ拒否
        $allowAdminHosts = $this->eccubeConfig['eccube_admin_allow_hosts'];
        if (!empty($allowAdminHosts) && !$this->isClientIpInList($allowAdminHosts, $clientIp)) {
            throw new AccessDeniedHttpException();
        }

        // IPアドレス拒否リストを確認
        $denyAdminHosts = $this->eccubeConfig['eccube_admin_deny_hosts'];
        if (!empty($denyAdminHosts) && $this->isClientIpInList($denyAdminHosts, $clientIp)) {
            throw new AccessDeniedHttpException();
        }
    }

    /**
     * @param mixed $hostList
     * @param string|null $clientIp
     * @return bool
     */
    private function isClientIpInList($hostList, $clientIp)
    {
        log_debug('Host List: '. implode(',', $hostList));
        if ($hostList) {
            $isInList = array_filter($hostList, function ($host) use ($clientIp) {
                return IpUtils::checkIp($clientIp, $host);
            });
            return count($isInList) > 0;
        }
        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public static function getSubscribedEvents()
    {
        return [
            'kernel.request' => ['onKernelRequest', 512],
        ];
    }
}
