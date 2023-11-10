<?php
//
///*
// * This file is part of EC-CUBE
// *
// * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
// *
// * http://www.ec-cube.co.jp/
// *
// * For the full copyright and license information, please view the LICENSE
// * file that was distributed with this source code.
// */
//
//namespace Eccube\Tests\Command;
//
//use Eccube\Command\GenerateDummyDataCommand;
//use Eccube\Tests\EccubeTestCase;
//use Symfony\Component\Console\Tester\CommandTester;
//
//class GenerateDummyDataCommandTest extends EccubeTestCase
//{
//
//    public function testExecute()
//    {
//        $commandArg = [];
//        $options["products"] = 1;
//        $options["order"] = 1;
//        $options["customers"] = 1;
//        /** @var GenerateDummyDataCommand $command */
//        $command = static::getContainer()->get(GenerateDummyDataCommand::class);
//        $CommandTester = new CommandTester($command);
//        $result = $CommandTester->execute($commandArg, $options);
//
//        $this->expected = 0;
//        $this->actual = $result;
//        $this->verify();
//
//
//    }
//}
