<?php

namespace merry;

class Container implements \ArrayAccess, \RecursiveIterator, \JsonSerializable
{
	/**
	 * Index for a numerically indexed array
	 * @internal
	 * @var int
	 */
	private $index = 0;
	
	/**
	 * Container
	 * @internal
	 * @var stdClass
	 */
	private $props;
	
	/**
	 * State for the RecursiveIterator
	 * @internal
	 * @var array
	 */
	private $riter;
	
	/**
	 * Whether to auto populate a requested property with a new container
	 * @internal
	 * @var bool
	 */
	private $auto;

	/**
	 * Create a new container
	 * @param array $array container data
	 * @param bool $auto whether to auto populate a requested property with a new container
	 */
	function __construct(array $array = null, $auto = true) {
		$this->props = new \stdClass;
		$this->auto = $auto;
		
		foreach ((array) $array as $property => $value) {
			$this->__set($property, $value);
		}
	}

	/**
	 * Recursively turn a container and its childs into an array
	 * @param \merry\Container $o the Container instance to convert to an array
	 * @return array
	 */
	protected function arrayify(Container $o) {
		$a = [];
		
		foreach ($o->props as $k => $v) {
			if ($v instanceof Container) {
				$a[$k] = $this->arrayify($v);
			} else {
				$a[$k] = $v;
			}
		}
		
		return $a;
	}
	
	/**
	 * Apply one or mor modifier callbacks
	 * @param mixed $modifier
	 * @return \merry\Config
	 */
	public function apply($modifier) {
		if (is_callable($modifier)) {
			foreach ($this->props as $prop => $value) {
				$this->__set($prop, $modifier($value, $prop));
			}
		} else {
			foreach ($modifier as $key => $mod) {
				if (is_callable($mod)) {
					$this->props->$key = $mod(isset($this->props->$key) ? $this->props->$key : null, $key);
				} elseif (is_array($mod)) {
					$this->props->$key->apply($mod);
				} else {
					/* */
				}
			}
		}
		return $this;
	}
	
	/**
	 * Return the complete config as array
	 * @return array
	 */
	function toArray() {
		return $this->arrayify($this);
	}
	
	/**
	 * @ignore
	 */
	function &__get($prop) {
		if ($this->auto && !isset($this->props->$prop)) {
			$this->props->$prop = new static;
		}
		return $this->props->$prop;
	}
	
	/**
	 * @ignore
	 */
	function __set($prop, $value) {
		if (isset($value) && !is_scalar($value) && !($value instanceof Config)) {
			$value = new static((array) $value);
		}
		if (!strlen($prop)) {
			$prop = $this->index++;
		} elseif (is_numeric($prop) && !strcmp($prop, (int) $prop)) {
			/* update internal index */
			if ($prop >= $this->index) {
				$this->index = $prop + 1;
			}
		}
		
		$this->props->$prop = $value;
	}
	
	/**
	 * @ignore
	 */
	function __isset($prop) {
		return isset($this->props->$prop);
	}
	
	/**
	 * @ignore
	 */
	function __unset($prop) {
		unset($this->props->$prop);
	}
	
	/**
	 * @ignore
	 */
	function offsetGet($o) {
		return $this->props->$o;
	}
	
	/**
	 * @ignore
	 */
	function offsetSet($o, $v) {
		$this->__set($o, $v);
	}
	
	/**
	 * @ignore
	 */
	function offsetExists($o) {
		return isset($this->props->$o);
	}
	
	/**
	 * @ignore
	 */
	function offsetUnset($o) {
		unset($this->props->$o);
	}
	
	/**
	 * @ignore
	 */
	function rewind() {
		$this->riter = (array) $this->props;
		reset($this->riter);
	}

	/**
	 * @ignore
	 */
	function valid() {
		return NULL !== key($this->riter);
	}
	
	/**
	 * @ignore
	 */
	function next() {
		next($this->riter);
	}
	
	/**
	 * @ignore
	 */
	function key() {
		return key($this->riter);
	}
	
	/**
	 * @ignore
	 */
	function current() {
		return current($this->riter);
	}
	
	/**
	 * @ignore
	 */
	function hasChildren() {
		return current($this->riter) instanceof Container;
	}
	
	/**
	 * @ignore
	 */
	function getChildren() {
		return current($this->riter);
	}
	
	/**
	 * @ignore
	 */
	function jsonSerialize() {
		return $this->arrayify($this);
	}
}

/* vim: set noet ts=4 sw=4: */
