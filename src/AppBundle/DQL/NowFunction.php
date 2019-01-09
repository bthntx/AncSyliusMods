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
class NowFunction extends FunctionNode
{
    public function getSql( SqlWalker $sqlWalker )
    {
        return 'NOW()';
    }

    public function parse( Parser $parser )
    {
        $parser->Match( Lexer::T_IDENTIFIER );
        $parser->Match( Lexer::T_OPEN_PARENTHESIS );
        $parser->Match( Lexer::T_CLOSE_PARENTHESIS );
    }
}