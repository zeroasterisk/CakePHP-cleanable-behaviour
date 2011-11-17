<?php
/**
 * Clean $this->data values
 * Helps to prevent XSS attacks (you can clean in the controller)
 * Helps reformat input data into saveable structures
 *
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/CakePHP-cleanable-behaviour
 * @copyright (c) 2011 Alan Blount
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 * @access public
 */
class CleanableBehavior extends ModelBehavior{
	var $settings = array();
	var $excludedKeys = array();
	var $settings_default = array(
		'doClean' => true,
		'doFormat' => true,
		'clean_default' => array(), # will merge with $this->clean_default
		'clean_text' => array('stripHtml' => false, 'remove_html' => false, 'encode' => false),
		'clean_blob' => array('stripHtml' => false, 'remove_html' => false, 'encode' => false),
		'clean_string' => array(),
		'clean_date' => array('nullIfEmpty' => true),
		'clean_datetime' => array('nullIfEmpty' => true),
		'clean_integer' => array('numbersOnly' => true),
		'clean_float' => array('numbersAndPeriodOnly' => true),
		// example of field name specific cleanup
		'id' => array(),
		'html' => array('stripHtml' => false),
		'body' => array('stripHtml' => false),
		);
	var $clean_default = array(
		'ignore' => false, 
		'numbersOnly' => false,
		'numbersAndPeriodOnly' => false,
		'nullIfEmpty' => false,
		'emptyStringIfNull' => false, #autoset if field!=null
		'stripWhitespace' => true, 
		'stripScripts' => true, 
		'stripIframes' => true, 
		'stripImages' => true, 
		'stripHtml' => true,
		'clean' => true, #all options below are clean Options
		'odd_spaces' => true, 
		'encode' => null, #calculated based on HTML settings 
		'dollar' => false, 
		'carriage' => false, 
		'unicode' => true, 
		'escape' => false, 
		'backslash' => false, 
		'remove_html' => true,
		);
	/**
	* Convenience function for coordinating the settings array
	* @param object $Model
	* @param array $settings
	* @return bool
	*/
	function setup(&$Model, $settings = array()) {
		if (!isset($this->settings[$Model->alias])) {
			$this->settings[$Model->alias] = $this->settings_default;
		}
		$this->settings[$Model->alias] = array_merge($this->settings[$Model->alias], (array) $settings);
		$this->settings[$Model->alias]['clean_default'] = array_merge($this->settings[$Model->alias]['clean_default'], $this->clean_default);
		return true;
	}
	/**
	* Convenience function for coordinating the settings array
	* @param object $Model
	* @param array $settings
	* @return array $settings
	*/
	public function settings(&$Model, $settings = array()) {
		$_settings = $this->settings[$Model->alias];
		if (isset($Model->cleanable) && is_array($Model->cleanable)) {
			$_settings = array_merge($_settings, $Model->cleanable);
		}
		$settings = array_merge($_settings, (array) $settings);
		return $settings;
	}
	/**
	* Clean all the data/fields
	* @param object $Model
	* @param array $data
	* @param array $settings
	* @return array $data
	*/
	public function cleanData(&$Model, $data, $settings=null) {
		$settings = $this->settings($Model, $settings);
		if ($settings['doFormat']) {
			$data = $this->doFormat($Model, $data, $settings);
		}
		if ($settings['doClean']) {
			$data = $this->doClean($Model, $data, $settings);
		}
		return $data;
	}
	/**
	* This is all of the basic format functionality, split out for readability
	* restructures data array to properly "belong" to the parent model
	* fixes hasAndBelongsToMany structure for saving
	* @param object $Model
	* @param array $data
	* @param array $settings
	* @return array $data
	*/
	public function doFormat(&$Model, $data, $settings=null) {
		$settings = $this->settings($Model, $settings);
		if (!array_key_exists($Model->alias, $data)) {
			$data = array($Model->alias => $data);
		}
		// reformat HABTM data so you can save via saveAll()
		foreach ( $Model->hasAndBelongsToMany as $modelAlias => $habtmSettings ) { 
			if (array_key_exists($modelAlias, $data) && !array_key_exists($modelAlias, $data[$modelAlias])) {
				$primaryKeys = set::extract($data[$modelAlias], "/{$habtmSettings['associationForeignKey']}");
				$data[$modelAlias][$modelAlias] = $primaryKeys;
			}
		}
		return $data;
	}
	/**
	* This is all of the basic cleanup functionality, split out for readability
	* @param object $Model
	* @param array $data
	* @param array $settings
	* @return array $data
	*/
	public function doClean(&$Model, $data, $settings=null) {
		$settings = $this->settings($Model, $settings);
		$schema = $Model->schema();
		foreach ( $data as $modelName => $_data ) {
			if ($modelName==$Model->alias) {
				// clean on this models (main functionality)
				$data[$modelName] = $this->doClean($Model, $_data, $settings);
			} elseif (isset($Model->modelName) && is_object($Model->modelName) && is_array($_data)) {
				// clean on other models
				$data[$modelName] = $this->doClean($Model->modelName, $_data, $settings);
			} elseif (is_array($_data)) {
				// clean on nested data as arrays
				foreach ( $_data as $field => $value ) {
					unset($_data[$field]);
					$options = $this->determineCleanOptions($field, $settings, $schema);
					$field = $this->doCleanValue($field, $settings['clean_default']);
					$_data[$field] = $this->doCleanValue($value, $options);
				}
				$data[$modelName] = $_data;
			} else {
				// clean on self as data array
				$field = $modelName;
				$value = $_data;
				unset($data[$field]);
				$options = $this->determineCleanOptions($field, $settings, $schema);
				$field = $this->doCleanValue($field, $options);
				$data[$field] = $this->doCleanValue($value, $options);
			}
		}
		return $data;
	}
	/**
	* Determine options for doClean based on field/schema/settings
	* @param string $field
	* @param array $settings
	* @param array $schema
	* @return array $options
	*/
	public function determineCleanOptions($field, $settings, $schema) {
		$options = $settings['clean_default'];
		if (array_key_exists($field, $schema) && is_array($schema[$field])) {
			if (array_key_exists('type', $schema[$field])) {
				$clean_type = "clean_{$schema[$field]['type']}";
				if (array_key_exists($clean_type, $settings) && is_array($settings[$clean_type])) {
					$options = array_merge($options, $settings[$clean_type]);
				}
			}
			if (array_key_exists('null', $schema[$field])) {
				$options['emptyStringIfNull'] = empty($schema[$field]['null']);
			}
		}
		if (array_key_exists($field, $settings) && is_array($settings[$field])) {
			$options = array_merge($options, $settings[$field]);
		}
		return $options;
	}
	/**
	* Cleans any specific value based on options
	* @param mixed $value
	* @param array $options
	* @return mixed $value
	*/
	public function doCleanValue($value, $options=array()) {
		if (empty($options)) {
			$options = $this->clean_default;
		}
		if ($options['ignore']) {
			return $value;
		}
		if ($options['numbersOnly']) {
			return preg_replace('/[^0-9]/is', '', $value);
		}
		if ($options['numbersAndPeriodOnly']) {
			return preg_replace('/[^0-9\.]/is', '', $value);
		}
		if (empty($value) && $options['nullIfEmpty']) {
			return null;
		}
		if (empty($value) || is_int($value) || is_float($value)) {
			return $value;
		}
		if (is_array($value)) {
			foreach ( $value as $key => $val ) {
				unset($value[$key]);
				$key = $this->doCleanValue($key, $options);
				$value[$key] = $this->doCleanValue($val, $options);
			}
			return $value;
		}
		$value = trim(urldecode(trim($value)));
		if (!class_exists('Sanitize')) {
			App::import('Core', 'Sanitize');
		}
		// clean any " >" 
		$value = preg_replace('#[\s\n\r\t]+>#', '>', $value);
		if ($options['stripWhitespace']) {
			$value = Sanitize::stripWhitespace($value);
		}
		if ($options['stripScripts']) {
			$value = Sanitize::stripScripts($value);
		}
		if ($options['stripIframes']) {
			$value = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $value);
			$value = preg_replace('/<iframe[^>]*>/is', '', $value);
		}
		if ($options['stripImages']) {
			$value = Sanitize::stripImages($value);
		}
		if ($options['stripHtml']) {
			$value = preg_replace('/<[^>]*?>.*?<\/[^>]*?>/is', '', $value);
			$value = preg_replace('/<[^>]*?>/is', '', $value);
			$options['remove_html'] = true;
		} else {
			$options['remove_html'] = false;
		}
		if ($options['encode']===null) {
			$options['encode'] = $options['remove_html'];
		}
		if ($options['clean']) {
			$value = Sanitize::clean($value, $options);
		}
		return $value;
	}
	/**
	* Clean the Data beforeSave
	*/
	public function beforeSave(&$Model) {
		$Model->data = $this->cleanData($Model, $Model->data);
		return true;
	}
}
?>
