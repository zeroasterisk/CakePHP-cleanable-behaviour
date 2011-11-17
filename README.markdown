Cleanable Behavior

A CakePHP Behavior which makes it really easy to clean $data and $this->data based on a model's schema

This became an issue when we were trying to become PCI compliant and XSS attacks failed... 
nothing passed CakePHP's validation, so nothing "bad" hit the database,
but by default `$this->data` on the controller was not cleaned and thus, the XSS attack text was displayed to the page in errors.  

Obviously there are a lot of ways to solve this problem, but 
attaching to the model/schema seemed like a good idea as 
it knows how to clean the values based on the field types and null parameters in the schema.

Implementation

    cd app/plugins
    git clone __repo_path__ cleanable
    
Add to any model as you would any other behavior

    public $actsAs = array('Cleanable.Cleanable', /*....*/);

This will cause the Cleanable cleanData() method to clean $Model->data on it's beforeSave() callback.

But if you want to also clean $this->data on the controller, 
you're going to need reset $this->data somewhere in your controller, 
probalby where you tried to save or otherwise interact with the model.

	$this->data = $this->Model->cleanData($this->data); 
	if ($this->Model->save($this->data)) {
		$this->redirect(array('action' => 'edit', $this->Model->id));
	}
    
Configuration

You can certainly edit the CleanableBehavior::$settings_default and CleanableBehavior::$clean_default

But you may also set any of the settings when you load the behavior on a model:

    public $actsAs = array('Cleanable.Cleanable' => array('doFormat' => false), /*....*/);
    
You can also set any of the settings on the model itself, in a (array) property called $cleanable

    public $actsAs = array('Cleanable.Cleanable', /*....*/);
    public $cleanable = array('doFormat' => false);
    
Here are the parameters you can set as basic settings:

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

Unit Tests

About

author Alan Blount <alan@zeroasterisk.com>
copyright (c) 2011 Alan Blount
license MIT License - http://www.opensource.org/licenses/mit-license.php
https://github.com/zeroasterisk/CakePHP-cleanable-behaviour
