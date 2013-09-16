<?php
/* Util Fixture generated on: 2011-11-17 12:11:29 : 1321552109 */
class UtilFixture extends CakeTestFixture {
/**
 * Name
 *
 * @var string
 * @access public
 */

/**
 * Fields
 *
 * @var array
 * @access public
 */
	public $fields = array(
		'id' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 36, 'key' => 'primary', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'created' => array('type' => 'datetime', 'null' => true, 'default' => NULL, 'key' => 'index'),
		'modified' => array('type' => 'datetime', 'null' => true, 'default' => NULL),
		'key' => array('type' => 'string', 'null' => false, 'default' => NULL, 'length' => 64, 'key' => 'index', 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'val' => array('type' => 'text', 'null' => true, 'default' => NULL, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'misc1' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 256, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'misc2' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 256, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'misc3' => array('type' => 'string', 'null' => true, 'default' => NULL, 'length' => 256, 'collate' => 'utf8_general_ci', 'charset' => 'utf8'),
		'member_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'job_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'event_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'resume_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'email_id' => array('type' => 'integer', 'null' => true, 'default' => NULL),
		'indexes' => array('PRIMARY' => array('column' => 'id', 'unique' => 1), 'key' => array('column' => 'key', 'unique' => 0), 'created' => array('column' => array('created', 'key'), 'unique' => 0)),
		'tableParameters' => array('charset' => 'latin1', 'collate' => 'latin1_swedish_ci', 'engine' => 'MyISAM')
	);

/**
 * Records
 *
 * @var array
 * @access public
 */
	public $records = array(
		array(
			'id' => '4ec548ea-cb78-4f54-8c40-567ae017215a',
			'created' => '2011-11-17 12:48:26',
			'modified' => '2011-11-17 12:48:26',
			'key' => 'Lorem ipsum dolor sit amet',
			'val' => 'Lorem ipsum dolor sit amet, aliquet feugiat. Convallis morbi fringilla gravida, phasellus feugiat dapibus velit nunc, pulvinar eget sollicitudin venenatis cum nullam, vivamus ut a sed, mollitia lectus. Nulla vestibulum massa neque ut et, id hendrerit sit, feugiat in taciti enim proin nibh, tempor dignissim, rhoncus duis vestibulum nunc mattis convallis.',
			'misc1' => 'Lorem ipsum dolor sit amet',
			'misc2' => 'Lorem ipsum dolor sit amet',
			'misc3' => 'Lorem ipsum dolor sit amet',
			'member_id' => 1,
			'job_id' => 1,
			'event_id' => 1,
			'resume_id' => 1,
			'email_id' => 1
		),
	);

}
?>