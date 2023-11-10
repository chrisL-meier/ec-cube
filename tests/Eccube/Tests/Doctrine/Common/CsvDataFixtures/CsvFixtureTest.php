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

namespace Eccube\Tests\Doctrine\Common\CsvDataFixtures;

use Doctrine\Persistence\ObjectManager;
use Eccube\Doctrine\Common\CsvDataFixtures\CsvFixture;
use Eccube\Repository\Master\JobRepository;
use Eccube\Tests\EccubeTestCase;

class CsvFixtureTest extends EccubeTestCase
{
    /**
     * @var CsvFixture
     */
    protected $fixture;

    /**
     * @var \SplFileObject
     */
    protected $file;

    /**
     * @var JobRepository
     */
    protected $jobRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->jobRepository = $this->entityManager->getRepository(\Eccube\Entity\Master\Job::class);

        $Jobs = $this->jobRepository->findAll();
        foreach ($Jobs as $Job) {
            $this->entityManager->remove($Job);
        }
        $this->entityManager->flush();

        $this->file = new \SplFileObject(
            __DIR__.'/../../../../../Fixtures/import_csv/mtb_job.csv'
        );
        $this->fixture = new CsvFixture($this->file);
    }

    public function testNewInstance()
    {
        $this->assertInstanceOf(CsvFixture::class, $this->fixture);
    }

    public function testGetSql()
    {
        $this->file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
        $headers = $this->file->current();

        $this->expected = 'INSERT INTO mtb_job (id, name, sort_no, discriminator_type) VALUES (?, ?, ?, ?)';
        $this->actual = $this->fixture->getSql('mtb_job', $headers);
        $this->verify();
    }

    public function testLoad()
    {
        $this->file->setFlags(\SplFileObject::READ_CSV | \SplFileObject::READ_AHEAD | \SplFileObject::SKIP_EMPTY);
        $this->file->rewind();
        $headers = $this->file->current();
        $this->file->next();

        // ファイルのデータ行を取得しておく
        $rows = [];
        while (!$this->file->eof()) {
            $rows[] = $this->file->current();
            $this->file->next();
        }

        $this->file->rewind();
        $this->assertNull($this->fixture->load(new TestObjectManager()));
        $this->fixture->load($this->entityManager);
        $Jobs = $this->jobRepository->findAll();

        $this->expected = count($rows);
        $this->actual = count($Jobs);
        $this->verify('行数は一致するか？');
        foreach ($Jobs as $key => $Job) {
            $this->expected = $rows[$key][0].', '.$rows[$key][1].', '.$rows[$key][2];
            $this->actual = $Job->getId().', '.$Job->getName().', '.$Job->getSortNo();
            $this->verify($key.'行目のデータは一致するか？');
        }
    }
}
class TestObjectManager implements ObjectManager
{

    public function find($className, $id)
    {
        // TODO: Implement find() method.
    }

    public function persist($object)
    {
        // TODO: Implement persist() method.
    }

    public function remove($object)
    {
        // TODO: Implement remove() method.
    }

    public function merge($object)
    {
        // TODO: Implement merge() method.
    }

    public function clear($objectName = null)
    {
        // TODO: Implement clear() method.
    }

    public function detach($object)
    {
        // TODO: Implement detach() method.
    }

    public function refresh($object)
    {
        // TODO: Implement refresh() method.
    }

    public function flush()
    {
        // TODO: Implement flush() method.
    }

    public function getRepository($className)
    {
        // TODO: Implement getRepository() method.
    }

    public function getClassMetadata($className)
    {
        // TODO: Implement getClassMetadata() method.
    }

    public function getMetadataFactory()
    {
        // TODO: Implement getMetadataFactory() method.
    }

    public function initializeObject($obj)
    {
        // TODO: Implement initializeObject() method.
    }

    public function contains($object)
    {
        // TODO: Implement contains() method.
    }
}
