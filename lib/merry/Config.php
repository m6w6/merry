<?php

/**
 * merry\Config
 * 
 * @author Michael Wallner <mike@php.net>
 */
namespace merry;

/**
 * A merry config container
 * 
 * @see https://github.com/m6w6/merry
 * @package merry\Config
 */
class Config implements \ArrayAccess, \RecursiveIterator
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
	 * Create a new configuration container
	 * @param array $array the configuration array
	 * @param string $section the section to use (i.e. first level key)
	 * @param string $section_sep a separator for section extension
	 * @param string $key_sep a separator for key traversal
	 */
	public function __construct(array $array = null, $section = null, $section_sep = ":", $key_sep = ".") {
		$this->props = new \stdClass;
		
		if (isset($section) && strlen($section_sep)) {
			$array = $this->combine($array, $section_sep)[$section];
		}
		if ($array) {
			$config = array();
			
			if (strlen($key_sep)) {
				foreach ($array as $key => $val) {
					$this->walk($config, $key, $val, $key_sep);
				}
			}
			
			foreach ($config as $property => $value) {
				$this->__set($property, $value);
			}
		}
	}
	
	/**
	 * Combine individual sections with their parent section
	 * @param array $array the config array
	 * @param string $section_sep the section extension separator
	 * @return array merged sections
	 */
	protected function combine($array, $section_sep) {
		foreach ($array as $section_spec => $settings) {
			$section_spec = array_map("trim", explode($section_sep, $section_spec));
			if (count($section_spec) > 1) {
				$sections[$section_spec[0]] = array_merge(
					$sections[$section_spec[1]], 
					$settings
				);
			} else {
				$sections[$section_spec[0]] = $settings;
			}
		}
		return $sections;
	}
	
	/**
	 * Walk a key split by the key separator into an array up and set the 
	 * respective value on the leaf
	 * @param mixed $ptr current leaf pointer in the array
	 * @param string $key the array key
	 * @param mixed $val the value to set
	 * @param string $key_sep the key separator for traversal
	 */
	protected function walk(&$ptr, $key, $val, $key_sep) {
		foreach (explode($key_sep, $key) as $sub) {
			$ptr = &$ptr[$sub];
		}
		$ptr = $val;
	}
	
	/**
	 * Recursively turn a Config instance and its childs into an array
	 * @param \merry\Config $o the Config instance to convert to an array
	 * @return array
	 */
	protected function arrayify(Config $o) {
		$a = [];
		
		foreach ($o->props as $k => $v) {
			if ($v instanceof Config) {
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
	function __get($prop) {
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
		return current($this->riter) instanceof Config;
	}
	
	/**
	 * @ignore
	 */
	function getChildren() {
		return current($this->riter);
	}
}
