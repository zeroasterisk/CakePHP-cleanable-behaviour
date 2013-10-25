<?php
App::import('Core', array('AppModel', 'Model'));
App::import('Lib', 'Templates.AppTestCase');
class CleanableTestCase extends AppTestCase {
	public $plugin = 'app';
	public $fixtures = array('app.util');
	protected $_testsToRun = array();
	/**
	* Start Test callback
	* @param string $method
	* @return void
	* @access public
	*/
	public function startTest($method) {
		$this->Util = ClassRegistry::init('Util');
		$this->Util->bindModel(array(
			'hasMany' => array(
				'UtilHasMany1' => array(
					'className' => 'Util',
					'foreignKey' => 'id',
					),
				),
			'hasAndBelongsToMany' => array(
				'UtilHasAndBelongsToMany1' => array(
					'className' => 'Util',
					'with' => 'Util',
					'joinTable' => $this->Util->useTable,
					'foreignKey' => 'id',
					'associationForeignKey' => 'id',
					),
				),
			));
		$this->Util->Behaviors->load('Cleanable.Cleanable');
	}
	/**
	* End Test callback
	* @param string $method
	* @return void
	* @access public
	*/
	public function endTest($method) {
		parent::endTest($method);
		unset($this->Util);
		ClassRegistry::flush();
	}
	/**
	* Cleanable doFormat()
	* /
	public function testDoFormat() {
		$data = array(
			'id' => '1234',
			'key' => 'test',
			'val' => 'testing un-nested input array',
			);
		$response = $this->Util->doFormat($data);
		$expected = array('Util' => $data);
		$this->AssertEqual($response, $expected);
		$response = $this->Util->doFormat($expected);
		$this->AssertEqual($response, $expected);
		$data = array(
			'Util' => array(
				'id' => '12345',
				'val' => 'testing nested arrays reformatting',
				),
			'UtilHasAndBelongsToMany1' => array( 
				array('id' => 'a', 'val' => 'habtm1'),
				array('id' => 'b', 'val' => 'habtm2'),
				),
			);
		$response = $this->Util->doFormat($data);
		$expected = $data;
		$expected['UtilHasAndBelongsToMany1']['UtilHasAndBelongsToMany1'] = array('a', 'b');
		$this->AssertEqual($response, $expected);
	}
	/**
	* Cleanable doClean()
	* /
	public function testDoClean() {
		$data = array(
			'Util' => array(
				'id' => '12345',
				'val' => 'testing',
				'misc1' => 'testing',
				),
			'UtilHasAndBelongsToMany1' => array( 
				array('id' => 'a', 'val' => 'habtm1'),
				array('id' => 'b', 'val' => 'habtm2'),
				),
			);
		$response = $this->Util->doClean($data);
		$expected = $data;
		$this->AssertEqual($response, $expected);
		// clean HTML/images/scripts/iframes
		$bad = 'good<a href="">link</a><img src="/blank.gif"/><img><script src="/script.js"></script><script>alert("yo");</script><SCriPT >alert("yo");</scRIpt	><IFraME>stuff';
		$good = 'goodstuff';
		$data['UtilHasAndBelongsToMany1'][0]['key'] = $data['Util']['key'] = $bad;
		$expected['UtilHasAndBelongsToMany1'][0]['key'] = $expected['Util']['key'] = $good;
		// clean images/scripts/iframes (keep HTML because this is a type=text)
		$bad = 'good<a href="">link</a><img src="/blank.gif"/><img><script src="/script.js"></script><script>alert("yo");</script><SCriPT >alert("yo");</scRIpt	><IFraME>stuff';
		$good = 'good<a href="">link</a>stuff';
		$data['UtilHasAndBelongsToMany1'][0]['val'] = $data['Util']['val'] = $bad;
		$expected['UtilHasAndBelongsToMany1'][0]['val'] = $expected['Util']['val'] = $good;
		// clean URL Encoded HTML/images/scripts/iframes
		$bad = 'good%3CScRipT%20%3Ealert%28%27test%27%29%3B%3C%2FScRipT%20%3Estuff';
		$good = 'goodstuff';
		$data['UtilHasAndBelongsToMany1'][0]['misc1'] = $data['Util']['misc1'] = $bad;
		$expected['UtilHasAndBelongsToMany1'][0]['misc1'] = $expected['Util']['misc1'] = $good;
		$bad = 'good%3CScRipT%20%3Ealert%28%27test%27%29%3B%3C%2FScRipT%20%3Estuff';
		$good = 'goodstuff';
		$data['UtilHasAndBelongsToMany1'][0]['misc2'] = $data['Util']['misc2'] = $bad;
		$expected['UtilHasAndBelongsToMany1'][0]['misc2'] = $expected['Util']['misc2'] = $good;
		$bad = 'good%22%3E%27%3E%3CIfRaME%3Estuff';
		$good = 'good">\'>stuff';
		$data['UtilHasAndBelongsToMany1'][0]['misc3'] = $data['Util']['misc3'] = $bad;
		$expected['UtilHasAndBelongsToMany1'][0]['misc3'] = $expected['Util']['misc3'] = $good;
		$response = $this->Util->doClean($data);
		$this->AssertEqual($response, $expected);
	}
	/**
	* Cleanable determineCleanOptions()
	* /
	public function testDetermineCleanOptions() {
		// autosetting options based on schema
		$schema = $this->Util->schema();
		$settings = $this->Util->Behaviors->Cleanable->settings($this->Util);
		$expected = $settings['clean_default'];
		$expected['emptyStringIfNull'] = true;
		$response = $this->Util->Behaviors->Cleanable->determineCleanOptions('key', $settings, $schema);
		$this->AssertEqual($response, $expected);
		$response = $this->Util->Behaviors->Cleanable->determineCleanOptions('val', $settings, $schema);
		$expected['stripHtml'] = false;
		$expected['remove_html'] = false;
		$expected['encode'] = false;
		$expected['emptyStringIfNull'] = false;
		$this->AssertEqual($response, $expected);
		$response = $this->Util->Behaviors->Cleanable->determineCleanOptions('member_id', $settings, $schema);
		$expected = $settings['clean_default'];
		$expected['emptyStringIfNull'] = true;
		$expected['numbersOnly'] = true;
		$expected['emptyStringIfNull'] = false;
		$this->AssertEqual($response, $expected);
		$response = $this->Util->Behaviors->Cleanable->determineCleanOptions('created', $settings, $schema);
		$expected = $settings['clean_default'];
		$expected['nullIfEmpty'] = true;
		$this->AssertEqual($response, $expected);
	}
	/**
	* Cleanable doCleanValue()
	*/
	public function testDoCleanValue() {
		$schema = $this->Util->schema();
		$settings = $this->Util->Behaviors->Cleanable->settings($this->Util);
		$options = $options_default = $settings['clean_default'];
		$bad = 'good<a href="">link</a><img src="/blank.gif"/><img><script src="/script.js"></script><script>alert("yo");</script><SCriPT >alert("yo");</scRIpt	><IFraME>stuff';
		$expected = 'goodstuff';
		$options['stripHtml'] = true;
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$this->AssertEqual($response, $expected);
		$options['stripHtml'] = false;
		$expected = 'good<a href="">link</a>stuff';
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$this->AssertEqual($response, $expected);
		// clean URL Encoded HTML/images/scripts/iframes
		$bad = 'good%3CScRipT%20%3Ealert%28%27test%27%29%3B%3C%2FScRipT%20%3E%3CScRipT%20%3Ealert%28%27test%27%29%3B%3C%2FScRipT%20%3E%22%3E%27%3E%3CIfRaME%3Estuff';
		$expected = 'good">\'>stuff';
		$options['stripHtml'] = true;
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$this->AssertEqual($response, $expected);
		$options['stripHtml'] = false;
		$options['clean'] = false;
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$this->AssertEqual($response, $expected);
		$options['stripHtml'] = false;
		$options['clean'] = true;
		$options['encode'] = false;
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$this->AssertEqual($response, $expected);
		$options['encode'] = true;
		$response = $this->Util->Behaviors->Cleanable->doCleanValue($bad, $options);
		$expected = 'good&quot;&gt;&#039;&gt;stuff';
		$this->AssertEqual($response, $expected);
	}
	/*  */
}
?>