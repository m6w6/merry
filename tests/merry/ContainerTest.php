<?php

namespace merry;

require __DIR__."/../../vendor/autoload.php";

/**
 * @covers merry\Container
 */
class ContainerTest extends \PHPUnit_Framework_TestCase
{
	public function testBasic() {
		$config = ["foo" => "bar", "bar" => "foo"];
		$object = new Container($config);
		$this->assertEquals($config, $object->toArray());
		$this->assertEquals("bar", $object->foo);
		$this->assertEquals("foo", $object->bar);
		$this->assertTrue(isset($object->foo));
		$this->assertFalse(isset($object->foobar));
		unset($object->bar);
		$this->assertFalse(isset($object->bar));
	}
	
	public function testBasicOffset() {
		$config = ["foo" => "bar", "bar" => "foo"];
		$object = new Container($config);
		$this->assertEquals("bar", $object["foo"]);
		$this->assertEquals("foo", $object["bar"]);
		$this->assertTrue(isset($object["foo"]));
		$this->assertFalse(isset($object["foobar"]));
		unset($object["bar"]);
		$this->assertFalse(isset($object["bar"]));
	}
	
	public function testSetArray() {
		$config = ["foo" => "bar", "arr" => [1,2,3]];
		$object = new Container($config);
		$object["foo"] = [$object->foo, "baz"];
		$object["arr"][] = 4;
		
		$this->assertEquals(["bar", "baz"], $object["foo"]->toArray());
		$this->assertEquals([1,2,3,4], $object["arr"]->toArray());
		
		$this->assertEquals(["foo"=>["bar","baz"], "arr"=>[1,2,3,4]], $object->toArray());
	}
	
	public function testApply() {
		$config = [
			"level1" => [
				"level2" => [
					"level3" => "123"
				],
				"level2-1" => [
					"level3-1" => "321"
				]
			]
		];
		$object = new Container($config);
		$this->assertEquals("123", $object->level1["level2"]->level3);
		$reverse = function ($v){return strrev($v);};
		$object->apply([
			"level1" => [
				"level2" => [
					"level3" => $reverse
				],
				"level2-1" => [
					"level3-1" => $reverse
				]
			]
		]);
		$compare = [
			"level1" => [
				"level2" => [
					"level3" => "321"
				],
				"level2-1" => [
					"level3-1" => "123"
				]
			]
		];
		$this->assertEquals($compare, $object->toArray());
		
		$object->apply(function() {
			return null;
		});
		$this->assertEquals(["level1" => null], $object->toArray());
	}

	public function testIterator() {
		$config = [
			"level1-0" => [
				"level2-0" => "1-0.2-0",
				"level2-1" => "1-0.2-1",
				"level2-2" => [
					"level3" => "1-0.2-2.3"
				]
			],
			"level1-1" => [
				1,2,3
			]
		];
		$object = new Container($config);
		$array = [];
		foreach (new \RecursiveIteratorIterator($object) as $key => $val) {
			$array[$key] = $val;
		}
		$compare = [
			'level2-0' => '1-0.2-0',
			'level2-1' => '1-0.2-1',
			'level3' => '1-0.2-2.3',
			1, 2, 3
		];
		$this->assertEquals($compare, $array);
	}
	
	public function testJsonSerialize() {
		$container = new Container;
		$container->foo = "bar";
		$container->bar->foo = "bar";
		$this->assertJsonStringEqualsJsonString(
			'{"foo": "bar", "bar": {"foo": "bar"}}',
			json_encode($container)
		);
	}
}
