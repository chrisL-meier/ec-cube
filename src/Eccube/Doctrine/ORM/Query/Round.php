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

namespace Eccube\Doctrine\ORM\Query;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;
use Eccube\Entity\TaxRule;

/**
 * RoundFunction ::= "ROUND" "(" AggregateExpression ")"
 */
class Round extends FunctionNode
{
    protected $number;
    protected $em;
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->number = $parser->AggregateExpression();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
        $this->em = $parser->getEntityManager();
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        $type = $this->em->getRepository(TaxRule::class)->findBy(['ProductClass' => null],['apply_date' => 'DESC'])[0]->getRoundingType()->getName();
        switch ($type) {
            case '四捨五入':
                return "ROUND(".$sqlWalker->walkAggregateExpression($this->number).")";
                break;
            case '切り捨て':
                return "FLOOR(".$sqlWalker->walkAggregateExpression($this->number).")";
                break;
            case '切り上げ':
                return "CEIL(".$sqlWalker->walkAggregateExpression($this->number).")";
                break;
        }

    }
}
