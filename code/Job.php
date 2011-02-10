<?php

/**
 * The main Job object. Stores information about the position and who
 * submitted it.
 *
 * To extend the fields and options in this use your own {@link DataObjectDecorator}
 *
 * @package jobboard
 */
class Job extends DataObject {

	/**
	 * @var array Names of required fields
	 */
	private static $required_fields = array('Title', 'Content', 'ApplyContent', 'Email');
	
	static $db = array(
		'Title' 		=> 'Varchar(255)',
		'Content'		=> 'HTMLText',
		'Company' 		=> 'Varchar(200)',
		'URL' 			=> 'Varchar(200)',
		'ApplyContent' 	=> 'HTMLText',
		'Location' 		=> 'Varchar(200)',
		'PriceGuide'	=> 'Varchar(200)',
		'Type' 			=> "Enum('Full Time Job, Part Time Job, Fixed Term Contract, Ongoing Contract, Internship, Other')",
		'isActive' 		=> 'Boolean',
		'Slug' 			=> 'Varchar(200)'
	);
	
	static $has_one = array(
		'Member' => 'Member'
	);
	
	static $price_guides = array(
		'$0 - $500'			=> '$0 - $500',
		'$501 - $2,000'		=> '$501 - $2,000',
		'$2,001 - $10,000'	=> '$2,001 - $10,000',
		'$10,001 - $30,000' => '$10,001 - $30,000',
		'$30,001 - $50,000' => '$30,001 - $50,000',
		'$50,000+'			=> '$50,000+'
	);
	
	/**
	 * Set the required fields for the submit and edit form
	 *
	 * @param array
	 */
	public static function set_required_fields($fields) {
		self::$required_fields = $fields;
	}
	
	/**
	 * Returns the required fields
	 *
	 * @param array
	 */
	public static function get_required_fields() {
		return self::$required_files;
	}
	
	/**
	 * When saving the job ensure we have a URL generated
	 *
	 * @return void
	 */
	function onBeforeWrite() {
		parent::onBeforeWrite();
		if(!$this->Slug) {
			$str = strtolower(trim($this->Title));
			$str = preg_replace('/[^a-z0-9-]/', '-', $str);
			$str = preg_replace('/-+/', "-", $str);
			
			// check for conflicts
			$check = 0;
			$query = $str;
			$return = false;
			while(DB::query("SELECT COUNT(*) FROM \"Job\" WHERE \"Slug\" = '$query'")->value() > 0) {
				if($check == 20) break; // something went wrong
				
				$query = $str .'-'. substr(md5(rand()), 0, 16);
				
				$check++;
				$return = $query;
			}

			$this->Slug = ($return) ? $return : $str;
		}
	}
	
	/**
	 * @return FieldSet
	 */
	function getFields() {
		$email = ($member = Member::currentUser()) ? $member->Email : "";
		
		// reduce tinymce
		HtmlEditorConfig::set_active('job');
		HtmlEditorConfig::get('job')->disablePlugins(array('contextmenu', 'table', 'emotions', 'paste', '../../tinymce_advcode', 'spellchecker'));
		HtmlEditorConfig::get('job')->setButtonsForLine('1', array('bold','italic','underline','bullist', 'numlist','cut','copy','paste','pastetext','pasteword', 'undo', 'redo'));	
		HtmlEditorConfig::get('job')->setButtonsForLine('2', array());
		
		$fields = new FieldSet(
			new HeaderField('JobInformation', 'Job Information'),
			new TextField('Title', 'Title of Listing <span>(Appears On Main Page)</span>'),
			new DropdownField('Type', 'Type', $this->dbObject('Type')->enumValues()),
			new DropdownField('PriceGuide', 'Price Guide ($USD) <span>(Optional)</span>', self::$price_guides, '', null, ''),
			new HtmlEditorField('Content', 'Job Description'),
			new HtmlEditorField('ApplyContent', 'How to Apply <span>(Include your Contact Details)</span>'),
			new HeaderField('YourInformation', 'Your Information'),
			new EmailField('Email', 'Your Email <span>(Required)</span>', $email),
			new TextField('Company', 'Company Name <span>(Optional)</span>'),
			new TextField('URL', 'Company URL <span>(Optional) </span>'),
			new DropdownField('Location', 'Location', Geoip::getCountryDropDown(), null, null, 'Anywhere')
		);
		
		$this->extend('updateFields', $fields);
		
		return $fields;
	}
	
	/**
	 * @return RequiredFields
	 */
	function getValidator() {
		return ($fields = self::get_required_fields()) ? new RequiredFields($fields) : false;
	}
	
	/**
	 * @return string
	 */
	function Link() {
		$holder = DataObject::get_one('JobHolder');
		
		return ($holder) ? $holder->Link('job/'.$this->Slug) : false;
	}

	/** 
	 * @return string
	 */
	function AbsoluteLink() {
		$holder = DataObject::get_one('JobHolder');
		
		return ($holder) ? $holder->AbsoluteLink('job/'.$this->Slug) : false;
	}
	
	/**
	 * Ensure the URL entered begins with http
	 *
	 * @return string
	 */
	function getNiceURL() {
		return (substr($this->URL, 0, 4) != "http") ? "http://".$this->URL : $this->URL;
	}
	

	/**
	 * Return the full country name for the 2 letter country code this
	 * has stored in the database
	 *
	 * @return string
	 */
	function getNiceLocation() {
		return ($this->Location) ? Geoip::countryCode2name($this->Location) : "Anywhere	";
	}
}

