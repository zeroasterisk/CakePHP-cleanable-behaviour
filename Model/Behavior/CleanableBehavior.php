<?php
/**
 * Clean $this->data values
 * Helps to prevent XSS attacks (you can clean in the controller)
 * Helps reformat input data into saveable structures
 *
 * This is intended as a pre-clean step before data validation
 *
 * @author Alan Blount <alan@zeroasterisk.com>
 * @link https://github.com/zeroasterisk/CakePHP-cleanable-behaviour
 * @copyright (c) 2011 Alan Blount
 * @license MIT License - http://www.opensource.org/licenses/mit-license.php
 * @access public
 *
 * In a Model you can customize:
 * public $cleanable = array(
 *     'name' => array('stripHtml' => false),
 *     'meta_title' => array('nullIfEmpty' => true, 'stripHtml' => false),
 *     'some_id' => array('numbersOnly' => true, 'zeroIfEmpty' => true),
 *     'some_uuid' => array('numbersOnly' => false, 'nullIfEmpty' => true),
 *     'crazy_text' => array('ignore' => true), // ignore ALL cleanable on this field (no cleaning nor formatting)
 *     'somewhat_crazy_text' => array('doFormat' => false), // will do cleaning, but not formatting
 *     'slightly_crazy_text' => array('doClean' => false), // will do formatting, but not cleaning
 *   );
 *
 * Formatting:
 * restructures data array to properly "belong" to the parent model
 * fixes hasAndBelongsToMany structure for saving
 *
 * Cleaning:
 * Optionally strips out HTML/images/extra-spaces/unauthroized-characters/etc
 * Optionally cleans up empty values to desired types (null/0/etc)
 *
 *
 */
class CleanableBehavior extends ModelBehavior{
	public $config = array();
	public $excludedKeys = array();
	/**
	 * These config customize the defauls by field type
	 * they are variations on the clean_defaults (below)
	 * they can be over-ridden by custom config on the model
	 *
	 * @var array
	 */
	public $settings_default = array(
		'doClean' => true,
		'doFormat' => true,
		'clean_default' => array(), // will merge with $this->clean_default
		'clean_text' => array('stripImages' => false, 'stripHtml' => false),
		'clean_blob' => array('stripImages' => false, 'stripHtml' => false),
		'clean_string' => array(), // defaults are used (below)
		'clean_date' => array('nullIfEmpty' => true),
		'clean_datetime' => array('nullIfEmpty' => true),
		'clean_integer' => array('numbersOnly' => true, 'zeroIfEmpty' => true),
		'clean_float' => array('numbersAndPeriodOnly' => true, 'zeroIfEmpty' => true),
		'clean_boolean' => array('zeroIfEmpty' => true),
		// example of field name specific cleanup
		'id' => array(),
		'html' => array('stripImages' => false, 'stripHtml' => false),
		'body' => array('stripImages' => false, 'stripHtml' => false),
	);
	/**
	 * These are the clean_defaults which are applied to all fields/data by default
	 * they can be over-ridden by field type via settings_default (above)
	 * and also by custom config on the model
	 *
	 * @var array
	 */
	public $clean_default = array(
		'ignore' => false,
		// format & custom clean options
		'numbersOnly' => false,
		'numbersAndPeriodOnly' => false,
		'nullIfEmpty' => false,
		'zeroIfEmpty' => false, //autoset if field!=null && type=int|float
		'emptyStringIfNull' => false, //autoset if field!=null
		'stripWhitespace' => true,
		'stripScripts' => true,
		'stripIframes' => true,
		'stripImages' => true,
		'stripHtml' => true,
		// Sanitize::clean() options
		'clean' => true, // do clean at all
		'odd_spaces' => true,
		'dollar' => false,
		'carriage' => false,
		'unicode' => true,
		'escape' => false,
		'backslash' => false,
		// set to false, because it's too distructive
		// use stripHtml instead
		'encode' => false,
		'remove_html' => false,
	);
	/**
	 * Convenience function for coordinating the config array
	 *
	 * @param object  $Model
	 * @param array   $config
	 * @return bool
	 */
	public function setup(Model $Model, $config = array()) {
		if (!isset($this->config[$Model->alias])) {
			$this->config[$Model->alias] = $this->settings_default;
		}
		$this->config[$Model->alias] = array_merge($this->config[$Model->alias], (array) $config);
		$this->config[$Model->alias]['clean_default'] = array_merge($this->config[$Model->alias]['clean_default'], $this->clean_default);
		return true;
	}
	/**
	 * Convenience function for coordinating the config array
	 *
	 * @param object  $Model
	 * @param array   $config
	 * @return array $config
	 */
	public function config(&$Model, $config = array()) {
		$_settings = $this->config[$Model->alias];
		if (isset($Model->cleanable) && is_array($Model->cleanable)) {
			$_settings = array_merge($_settings, $Model->cleanable);
		}
		$config = array_merge($_settings, (array) $config);
		return $config;
	}
	/**
	 * Clean all the data/fields
	 *
	 * @param object  $Model
	 * @param array   $data
	 * @param array   $config
	 * @return array $data
	 */
	public function cleanData(&$Model, $data, $config=null) {
		$config = $this->config($Model, $config);
		if ($config['doFormat']) {
			$data = $this->doFormat($Model, $data, $config);
		}
		if ($config['doClean']) {
			$data = $this->doClean($Model, $data, $config);
		}
		return $data;
	}
	/**
	 * This is all of the basic format functionality, split out for readability
	 * restructures data array to properly "belong" to the parent model
	 * fixes hasAndBelongsToMany structure for saving
	 *
	 * @param object  $Model
	 * @param array   $data
	 * @param array   $config
	 * @return array $data
	 */
	public function doFormat(&$Model, $data, $config=null) {
		$config = $this->config($Model, $config);
		// shuffle core data to properly nest
		$coreData = array();
		if (empty($data)) {
			$data = array();
		}
		if (array_key_exists($Model->alias, $data)) {
			$coreData = $data[$Model->alias];
			unset($data[$Model->alias]);
		}
		if (Set::countDim($data)==1) {
			$coreData = array_merge($data, $coreData);
			$data = array();
		} elseif (!array_key_exists($Model->alias, $data)) {
			foreach ( $data as $key => $val ) {
				if (!is_array($val)) {
					$coreData[$key] = $val;
				}
			}
		}
		$data[$Model->alias] = $coreData;
		// reformat HABTM data so you can save via saveAll()
		foreach ( $Model->hasAndBelongsToMany as $modelAlias => $habtmSettings ) {
			if (array_key_exists($modelAlias, $data) && !array_key_exists($modelAlias, $data[$modelAlias])) {
				if (isset($Model->$modelAlias->primaryKey)) {
					$primaryKeys = set::extract($data[$modelAlias], "/{$Model->$modelAlias->primaryKey}");
				} else {
					$primaryKeys = set::extract($data[$modelAlias], "/{$habtmSettings['associationForeignKey']}");
				}
				if (empty($primaryKeys)) {
					$primaryKeys = set::extract($data[$modelAlias], "/id");
				}
				$data[$modelAlias][$modelAlias] = $primaryKeys;
			}
		}
		return $data;
	}
	/**
	 * This is all of the basic cleanup functionality, split out for readability
	 * Optionally strips out HTML/images/extra-spaces/unauthroized-characters/etc
	 * Optionally cleans up empty values to desired types (null/0/etc)
	 *
	 * @param object  $Model
	 * @param array   $data
	 * @param array   $config
	 * @return array $data
	 */
	public function doClean(&$Model, $data, $config=null) {
		if (!is_array($data) || empty($data)) {
			return $data;
		}
		$config = $this->config($Model, $config);
		$schema = $Model->schema();
		foreach ( $data as $modelName => $_data ) {
			if (is_numeric($modelName)) {
				// HABTM can return values like this
				// for now, we are going to ignore them...
				continue;
			}
			if ($modelName===$Model->alias) {
				// clean on this models (main functionality)
				$data[$modelName] = $this->doClean($Model, $_data, $config);
			} elseif (isset($Model->$modelName) && is_object($Model->$modelName) && is_array($_data)) {
				// clean on other models
				$data[$modelName] = $this->doClean($Model->$modelName, $_data, $config);
			} elseif (is_array($_data)) {
				// clean on nested data as arrays
				foreach ( $_data as $field => $value ) {
					unset($_data[$field]);
					$options = $this->determineCleanOptions($field, $config, $schema);
					$field = $this->doCleanValue($field, $config['clean_default']);
					$_data[$field] = $this->doCleanValue($value, $options);
				}
				$data[$modelName] = $_data;
			} else {
				// clean on self as data array
				$field = $modelName;
				$value = $_data;
				unset($data[$field]);
				$options = $this->determineCleanOptions($field, $config, $schema);
				$field = $this->doCleanValue($field, $config['clean_default']);
				$data[$field] = $this->doCleanValue($value, $options);
				//Remove unnecessary fields
				foreach ($data as $k => $v) {
					if (is_int($k) || empty($k)) {
						unset($data[$k]);
					}
				}
			}
		}
		return $data;
	}
	/**
	 * Determine options for doClean based on field/schema/config
	 *
	 * @param string  $field
	 * @param array   $config
	 * @param array   $schema
	 * @return array $options
	 */
	public function determineCleanOptions($field, $config, $schema) {
		$options = $config['clean_default'];
		if (!empty($schema) && array_key_exists($field, $schema) && is_array($schema[$field])) {
			if (array_key_exists('type', $schema[$field])) {
				$clean_type = "clean_{$schema[$field]['type']}";
				if (array_key_exists($clean_type, $config) && is_array($config[$clean_type])) {
					$options = array_merge($options, $config[$clean_type]);
				}
			}
			if (array_key_exists('null', $schema[$field])) {
				$options['emptyStringIfNull'] = empty($schema[$field]['null']);
				if (array_key_exists('type', $schema[$field]) && ($schema[$field]['type'] == 'integer' || $schema[$field]['type'] == 'float')) {
					$options['zeroIfEmpty'] = true;
				}
			}
		}
		if (array_key_exists($field, $config) && is_array($config[$field])) {
			$options = array_merge($options, $config[$field]);
		}
		return $options;
	}
	/**
	 * Cleans any specific value based on options
	 *
	 * @param mixed   $value
	 * @param array   $options
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
			$value = preg_replace('/[^0-9\-]/is', '', $value);
		} elseif ($options['numbersAndPeriodOnly']) {
			$value = preg_replace('/[^0-9\-\.]/is', '', $value);
		}
		if (empty($value)) {
			if ($options['nullIfEmpty'] && $value!='0' && $value!==false) {
				return null;
			} elseif ($options['zeroIfEmpty']) {
				return 0;
			} elseif ($options['emptyStringIfNull'] && $value!='0') {
				return '';
			}
			return $value;
		}
		if (empty($value) || is_int($value) || is_float($value) || $options['numbersOnly'] || $options['numbersAndPeriodOnly']) {
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
		$value = str_replace('+', '[|#|#plus#|#|]', $value);
		$value = trim(urldecode(trim($value)));
		$value = str_replace('[|#|#plus#|#|]', '+', $value);
		if (!class_exists('Sanitize')) {
			App::uses('Sanitize', 'Utility');
		}
		// clean any " >"
		$value = preg_replace('#[\s\n\r\t]+>#', '>', $value);
		if ($options['stripWhitespace']) {
			$value = Sanitize::stripWhitespace($value);
		}
		if ($options['stripScripts']) {
			// not using Sanitize::stripScripts() because it's also stripping images
			$value = preg_replace('/(<link[^>]+rel="[^"]*stylesheet"[^>]*>|<style="[^"]*")|<script[^>]*>.*?<\/script>|<style[^>]*>.*?<\/style>|<!--.*?-->/is', '', $value);
		}
		if ($options['stripIframes']) {
			$value = preg_replace('/<iframe[^>]*>.*?<\/iframe>/is', '', $value);
			$value = preg_replace('/<iframe[^>]*>/is', '', $value);
		}
		if ($options['stripImages']) {
			$value = Sanitize::stripImages($value);
		}
		if ($options['stripHtml']) {
			$value = preg_replace('/<[^>]*?>(.*?)<\/[^>]*?>/is', '$1', $value);
			$value = preg_replace('/<[^>]*?>/is', '', $value);
		}
		if ($options['clean']) {
			$value = Sanitize::clean($value, $options);
		}
		return $value;
	}
	/**
	 * Clean the Data beforeSave
	 */
	public function beforeSave(Model $Model, $options = array()) {
		if (property_exists($Model, 'cleanable') && $Model->cleanable===false) {
			return true;
		}
		$Model->data = $this->cleanData($Model, $Model->data);
		return true;
	}
}
