<?php

/*
 * This file is part of the Wilson web framework.
 *
 * (c) Nick Rawe <rawebone@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Wilson\Tests\Security;

use Wilson\Security\ValidationException;

class ValidationExceptionTest extends \PHPUnit_Framework_TestCase
{
    function testGivesParamAndExpectation()
    {
        $ex = ValidationException::invalid("latitude", "float");

        $this->assertInstanceOf("Wilson\\Security\\ValidationException", $ex);
        $this->assertEquals("latitude", $ex->param);
        $this->assertEquals("float", $ex->expected);
    }
}
