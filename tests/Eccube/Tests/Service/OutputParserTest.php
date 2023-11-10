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

namespace Eccube\Tests\Service;


use Eccube\Service\Composer\ComposerApiService;
use Eccube\Service\Composer\OutputParser;

class OutputParserTest extends AbstractServiceTestCase
{

    /**
     * @var OutputParser
     */
    protected $outputParser;
    /**
     * @var ComposerApiService
     */
    private $composerService;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->composerService = static::getContainer()->get(ComposerApiService::class);

    }

    public function testParseInfo(){

        $output = $this->composerService->execInfo("doctrine/orm","^2.11");

        $this->assertNotEmpty($output);
    }
}
