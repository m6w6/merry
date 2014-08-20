<?php

namespace merry;

require __DIR__."/../../vendor/autoload.php";

/**
 * @covers merry\Config
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
	public function testBasicSection() {
		$config = [
			"primary" => [
				"foo" => "bar",
				"bar" => "foo",
			],
			"secondary : primary" => [
				"bar" => "foo2",
				"baz" => "sec"
			],
			"tertiary : secondary" => [
				"bar" => "foo3",
				"bat" => "tri"
			],
			"alternative : primary" => [
				"bar" => "fux",
				"baz" => "alt"
			]
		];
		$object = new Config($config, "primary");
		$this->assertEquals($config["primary"], $object->toArray());
		
		$object = new Config($config, "secondary");
		$this->assertEquals($config["secondary : primary"] + $config["primary"], $object->toArray());
		
		$object = new Config($config, "tertiary");
		$this->assertEquals($config["tertiary : secondary"] + $config["secondary : primary"] + $config["primary"], $object->toArray());
		
		$object = new Config($config, "alternative");
		$this->assertEquals($config["alternative : primary"] + $config["primary"], $object->toArray());
	}
}
