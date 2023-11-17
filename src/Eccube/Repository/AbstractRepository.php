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

namespace Eccube\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Eccube\Entity\AbstractEntity;

/**
 * ECCUBE AbstractRepository
 *
 * @method AbstractEntity|null find($id, $lockMode = null, $lockVersion = null)
 * @method AbstractEntity|null findOneBy(array $criteria, array $orderBy = null)
 * @method AbstractEntity[]    findAll()
 *
 * @template T of AbstractEntity
 *
 * @extends ServiceEntityRepository<T>
 */
abstract class AbstractRepository extends ServiceEntityRepository
{
    /**
     * @var \Eccube\Common\EccubeConfig<string, mixed>
     */
    protected $eccubeConfig;

    /**
     * エンティティを削除します。
     *
     * @param AbstractEntity $entity
     *
     * @return void
     */
    public function delete($entity)
    {
        $this->getEntityManager()->remove($entity);
    }

    /**
     * エンティティの登録/保存します。
     *
     * @param AbstractEntity $entity
     *
     * @return void
     */
    public function save($entity)
    {
        $this->getEntityManager()->persist($entity);
    }

    /**
     * @return int|string|null
     */
    protected function getCacheLifetime()
    {
        if ($this->eccubeConfig !== null) {
            return $this->eccubeConfig['eccube_result_cache_lifetime'];
        }

        return 0;
    }

    /**
     * PostgreSQL環境かどうかを判定します。
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function isPostgreSQL()
    {
        return 'postgresql' == $this->getEntityManager()->getConnection()->getDatabasePlatform()->getName();
    }

    /**
     * MySQL環境かどうかを判定します。
     *
     * @return bool
     *
     * @throws \Doctrine\DBAL\Exception
     */
    protected function isMySQL()
    {
        return 'mysql' == $this->getEntityManager()->getConnection()->getDatabasePlatform()->getName();
    }
}
