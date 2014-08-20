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
class Config extends Container
{
	/**
	 * Create a new configuration container
	 * @param array $array the configuration array
	 * @param string $section the section to use (i.e. first level key)
	 * @param string $section_sep a separator for section extension
	 * @param string $key_sep a separator for key traversal
	 */
	public function __construct(array $array = null, $section = null, $section_sep = ":", $key_sep = ".") {
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
			parent::__construct($config, false);
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
}

/* vim: set noet ts=4 sw=4: */
