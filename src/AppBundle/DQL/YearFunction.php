<?php
/**
 * Created by PhpStorm.
 * User: beathan
 * Date: 01.10.2018
 * Time: 14:51
 */

namespace AppBundle\DQL;

use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\SqlWalker;
use Doctrine\ORM\Query\Parser;

/**
 * Class DateFormatFunction
 *
 * Adds the hability to use the MySQL DATE_FORMAT function inside Doctrine
 *
 * @package Vf\Bundle\VouchedforBundle\DQL
 */
class YearFunction extends FunctionNode
{
    public $date;
    public function getSql(\Doctrine\ORM\Query\SqlWalker $sqlWalker)
    {
        return 'YEAR(' . $sqlWalker->walkArithmeticPrimary($this->date) . ')';
    }
    public function parse(\Doctrine\ORM\Query\Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->date = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }
}