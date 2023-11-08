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

namespace Eccube\Service;

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\Persistence\Mapping\Driver\MappingDriverChain;
use Eccube\Doctrine\ORM\Mapping\Driver\NopAnnotationDriver;
use Eccube\Doctrine\ORM\Mapping\Driver\ReloadSafeAnnotationDriver;
use Eccube\Util\StringUtil;
use Doctrine\DBAL;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;
use Doctrine\Bundle\DoctrineBundle\Mapping\MappingDriver;

class SchemaService
{
    /**
     * @var EntityManagerInterface
     */
    protected $entityManager;
    /**
     * @var PluginContext
     */
    private $pluginContext;

    /**
     * SchemaService constructor.
     *
     * @param EntityManagerInterface $entityManager
     * @param PluginContext $pluginContext
     */
    public function __construct(EntityManagerInterface $entityManager, PluginContext $pluginContext)
    {
        $this->entityManager = $entityManager;
        $this->pluginContext = $pluginContext;
    }

    /**
     * Doctrine Metadata を生成してコールバック関数を実行する.
     *
     * コールバック関数は主に SchemaTool が利用されます.
     * Metadata を出力する一時ディレクトリを指定しない場合は内部で生成し, コールバック関数実行後に削除されます.
     *
     * @param callable $callback Metadata を生成した後に実行されるコールバック関数
     * @param array<mixed> $generatedFiles Proxy ファイルパスの配列
     * @param string $proxiesDirectory Proxy ファイルを格納したディレクトリ
     * @param string $outputDir Metadata の出力先ディレクトリ
     *
     * @return void
     */
    public function executeCallback(callable $callback, $generatedFiles, $proxiesDirectory, $outputDir = null)
    {
        $createOutputDir = false;
        if (is_null($outputDir)) {
            $outputDir = sys_get_temp_dir().'/metadata_'.StringUtil::random(12);
            mkdir($outputDir);
            $createOutputDir = true;
        }

        try {
            /** @var MappingDriver $mappingDriver */
            $mappingDriver = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
            /** @var MappingDriverChain $driverChain */
            $driverChain = $mappingDriver->getDriver();
            $drivers = $driverChain->getDrivers();
            /**
             * @var string $namespace
             * @var ReloadSafeAnnotationDriver $oldDriver
             */
            foreach ($drivers as $namespace => $oldDriver) {
                if ('Eccube\Entity' === $namespace || preg_match('/^Plugin\\\\.*\\\\Entity$/', $namespace)) {
                    // Setup to AnnotationDriver
                    $newDriver = new ReloadSafeAnnotationDriver(
                        new AnnotationReader(),
                        $oldDriver->getPaths()
                    );
                    $newDriver->setFileExtension($oldDriver->getFileExtension());
                    $newDriver->addExcludePaths($oldDriver->getExcludePaths());
                    $newDriver->setTraitProxiesDirectory($proxiesDirectory);
                    $newDriver->setNewProxyFiles($generatedFiles);
                    $newDriver->setOutputDir($outputDir);
                    $driverChain->addDriver($newDriver, $namespace);
                }

                if ($this->pluginContext->isUninstall()) {
                    foreach ($this->pluginContext->getExtraEntityNamespaces() as $extraEntityNamespace) {
                        if ($extraEntityNamespace === $namespace) {
                            $driverChain->addDriver(new NopAnnotationDriver(new AnnotationReader()), $namespace);
                        }
                    }
                }
            }

            $tool = new SchemaTool($this->entityManager);
            $metaData = $this->entityManager->getMetadataFactory()->getAllMetadata();

            call_user_func($callback, $tool, $metaData);
        } finally {
            if ($createOutputDir) {
                $files = Finder::create()
                    ->in($outputDir)
                    ->files();
                $f = new Filesystem();
                $f->remove($files);
            }
        }
    }

    /**
     * Doctrine Metadata を生成して UpdateSchema を実行する.
     *
     * @param array<mixed> $generatedFiles Proxy ファイルパスの配列
     * @param string $proxiesDirectory Proxy ファイルを格納したディレクトリ
     * @param bool $saveMode UpdateSchema を即時実行する場合 true
     *
     * @return void
     */
    public function updateSchema($generatedFiles, $proxiesDirectory, $saveMode = false)
    {
        $this->executeCallback(function (SchemaTool $tool, array $metaData) use ($saveMode) {
            $tool->updateSchema($metaData, $saveMode);
        }, $generatedFiles, $proxiesDirectory);
    }

    /**
     * ネームスペースに含まれるEntityのテーブルを削除する
     *
     * @param  string $targetNamespace 削除対象のネームスペース
     *
     * @return void
     */
    public function dropTable($targetNamespace)
    {
        /** @var MappingDriver $mappingDriver */
        $mappingDriver = $this->entityManager->getConfiguration()->getMetadataDriverImpl();
        /** @var MappingDriverChain $driverChain */
        $driverChain = $mappingDriver->getDriver();
        $drivers = $driverChain->getDrivers();

        $dropMetas = [];
        foreach ($drivers as $namespace => $driver) {
            if ($targetNamespace === $namespace) {
                $allClassNames = $driver->getAllClassNames();

                foreach ($allClassNames as $className) {
                    $dropMetas[] = $this->entityManager->getMetadataFactory()->getMetadataFor($className);
                }
            }
        }
        $tool = new SchemaTool($this->entityManager);
        $tool->dropSchema($dropMetas);
    }
}
