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

namespace Eccube\Doctrine\Query;

use Doctrine\ORM\QueryBuilder;

abstract class WhereCustomizer implements QueryCustomizer
{
    /**
     * @param QueryBuilder $builder
     * @param array<string,mixed> $params
     * @param string $queryKey
     *
     * @return void
     */
    final public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        foreach ($this->createStatements($params, $queryKey) as $whereClause) {
            $whereClause->build($builder);
        }
    }

    /**
     * @param array<string,mixed> $params
     * @param string $queryKey
     *
     * @return WhereClause[]
     */
    abstract protected function createStatements($params, $queryKey);
}
