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

use Wilson\Http\Request;
use Wilson\Security\Filter;
use Wilson\Security\RequestValidation;
use Wilson\Tests\Fixtures\OptionResourceFixture;

class RequestValidationTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Filter
     */
    protected $filter;

    /**
     * @var OptionResourceFixture
     */
    protected $fixture;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var RequestValidation
     */
    protected $validation;

    protected function setUp()
    {
        $this->filter = new Filter();
        $this->fixture = new OptionResourceFixture();
        $this->request = new Request();
        $this->validation = new RequestValidation($this->filter, $this->request);
    }

    function testParseWithNoOptions()
    {
        $options = array();
        $this->validation->parseOptionFromComment($options, "");
        $this->assertEmpty($options);
    }

    function testParseWithSingleOption()
    {
        $options = array();
        $comment = "/**\r* @option param type arg1 arg2\r*/";

        $this->validation->parseOptionFromComment($options, $comment);
        $this->assertCount(1, $options);

        $this->assertEquals("param", $options[0]->name);
        $this->assertEquals("type", $options[0]->filter);
        $this->assertEquals(array(null, "arg1", "arg2"), $options[0]->args);
    }

    function testParseWithMultipleOptions()
    {
        $options = array();
        $comment = "/**\r* @option param type arg1 arg2\r*\r @option param2 type2 arg1 arg2\r*/";

        $this->validation->parseOptionFromComment($options, $comment);
        $this->assertCount(2, $options);

        $this->assertEquals("param2", $options[1]->name);
        $this->assertEquals("type2", $options[1]->filter);
        $this->assertEquals(array(null, "arg1", "arg2"), $options[1]->args);
    }

    function testGetFromMethods()
    {
        $methods = array();
        $methods[] = array($this->fixture, "controller");

        $this->assertCount(1, $this->validation->getOptionsFromMethods($methods));
    }

    function testGetWithNoMethodOptions()
    {
        $methods = array();
        $methods[] = array($this->fixture, "middlewareWithoutOption");

        $this->assertEmpty($this->validation->getOptionsFromMethods($methods));
    }

    function testGetFromMultipleMethods()
    {
        $methods = array();
        $methods[] = array($this->fixture, "controller");
        $methods[] = array($this->fixture, "middlewareWithOption");

        $this->assertCount(2, $this->validation->getOptionsFromMethods($methods));
    }

    /**
     * @expectedException \Wilson\Security\ValidationException
     */
    function testValidateWithInvalidParam()
    {
        $this->request->setParam("action", 0);

        $controllers = array(array($this->fixture, "middlewareWithOption"));

        $this->validation->validate($controllers);
    }

    function testValidateWithValidParam()
    {
        $this->request->setParam("action", "1");

        $controllers = array(array($this->fixture, "middlewareWithOption"));

        $this->validation->validate($controllers);

        $this->assertEquals(1, $this->request->getParam("action"));
    }

    function testValidateWithSkippedParam()
    {
        $controllers = array(array($this->fixture, "controller"));
        $this->validation->validate($controllers);
    }
}
