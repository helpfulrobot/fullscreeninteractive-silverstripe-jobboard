<?php

/**
 * @package jobboard
 */
class Job extends DataObject {

	/**
	 * @var boolean $require_moderation
	 *
	 * @config
	 */
	private static $require_moderation = true;
	
	/**
	 * @var string $email_from_address
	 *
	 * @config
	 */
	private static $email_from_address;

	/**
	 * @var string $email_subject
	 *
	 * @config
	 */
	private static $email_subject;

	/**
	 * @var string $notify_address
	 *
	 * @config
	 */
	private static $notify_address;

	/**
	 * @var array $required_fields
	 *
	 * @config
	 */
	private static $required_fields = array(
		'Title', 'Email'
	);
	
	/**
	 * @var array
	 */
	private static $db = array(
		'Title' 		=> 'Varchar(255)',
		'Content'		=> 'HTMLText',
		'Moderated'		=> 'Boolean',
		'Company' 		=> 'Varchar(200)',
		'URL' 			=> 'Varchar(200)',
		'ApplyContent' 	=> 'HTMLText',
		'Location' 		=> 'Varchar(200)',
		'PriceGuide'	=> 'Varchar(200)',
		'Type' 			=> "Enum('Full Time Job, Part Time Job, Fixed Term Contract, Ongoing Contract, Internship, Other')",
		'isActive' 		=> 'Boolean',
		'Slug' 			=> 'Varchar(200)'
	);
	
	/**
	 * @var array
	 */
	private static $has_one = array(
		'Member' => 'Member'
	);
	
	/**
	 * @var array
	 */
	private static $defaults = array(
		'Moderated' => 0
	);

	/**
	 * @var array
	 */
	private static $price_guides = array(
		'$0 - $500'			=> '$0 - $500',
		'$501 - $2,000'		=> '$501 - $2,000',
		'$2,001 - $10,000'	=> '$2,001 - $10,000',
		'$10,001 - $30,000' => '$10,001 - $30,000',
		'$30,001 - $50,000' => '$30,001 - $50,000',
		'$50,000+'			=> '$50,000+'
	);

	/**
	 * When saving the job ensure we have a URL generated.
	 *
	 * @return void
	 */
	public function onBeforeWrite() {
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
				if($check == 20) {
					break; // something went wrong
				}
				
				$query = $str .'-'. substr(md5(rand()), 0, 16);
				
				$check++;
				$return = $query;
			}

			$this->Slug = ($return) ? $return : $str;
		}
	}

	/**
	 * @param Member
	 *
	 * @return boolean
	 */
	public function canEdit($member = null) {
		if(!$member) {
			$member = Member::currentUser();
		}

		return (Permission::check('ADMIN') || ($member && $member->ID == $this->MemberID));
	}
	
	/**
	 * @return FieldSet
	 */
	public function getFields() {
		$email = ($member = Member::currentUser()) ? $member->Email : "";
		
		// reduce tinymce
		HtmlEditorConfig::set_active('job');
		HtmlEditorConfig::get('job')->disablePlugins(array('contextmenu', 'table', 'emotions', 'paste', '../../tinymce_advcode', 'spellchecker'));
		HtmlEditorConfig::get('job')->setButtonsForLine('1', array('bold','italic','underline','bullist', 'numlist','cut','copy','paste','pastetext','pasteword', 'undo', 'redo'));	
		HtmlEditorConfig::get('job')->setButtonsForLine('2', array());
		
		$fields = new FieldList(
			new HeaderField('JobInformation', 'Job Information'),
			new TextField('Title', 'Title of Listing <span>(Appears On Main Page)</span>'),
			new DropdownField('Type', 'Type', $this->dbObject('Type')->enumValues()),
			$guide = new DropdownField('PriceGuide', 'Price Guide ($USD) <span>(Optional)</span>', self::$price_guides),
			new HtmlEditorField('Content', 'Job Description'),
			new HtmlEditorField('ApplyContent', 'How to Apply <span>(Include your Contact Details)</span>'),
			new HeaderField('YourInformation', 'Your Information'),
			new EmailField('Email', 'Your Email <span>(Required)</span>', $email),
			new TextField('Company', 'Company Name <span>(Optional)</span>'),
			new TextField('URL', 'Company URL <span>(Optional) </span>'),
			$location = new DropdownField('Location', 'Location', Geoip::getCountryDropDown())
		);

		$guide->setEmptyString('');
		$location->setEmptyString('');
		
		if(Permission::check('ADMIN')) {
			$fields->push(new CheckboxField('Moderated'));
		}

		$this->extend('updateFields', $fields);
		
		return $fields;
	}
	
	/**
	 * @return RequiredFields
	 */
	public function getValidator() {
		return ($fields = $this->config()->required_fields) ? new RequiredFields($fields) : false;
	}
	
	/**
	 * @return string
	 */
	public function Link($action = "") {
		if(!$action) {
			$action = 'show';
		}

		$url = Config::inst()->get('Job', 'job_url');

		return Controller::join_links(
			Director::baseURL(), $url, $action, $this->Slug
		);
	}

	/** 
	 * @return string
	 */
	public function AbsoluteLink($action = "") {
		return Director::absoluteURL($this->Link($action));
	}

	/**
	 * Ensure the URL entered begins with http.
	 *
	 * @return string
	 */
	public function getNiceURL() {
		return (substr($this->URL, 0, 4) != "http") ? "http://". $this->URL : $this->URL;
	}
	

	/**
	 * Return the full country name for the 2 letter country code this has 
	 * stored in the database.
	 *
	 * @return string
	 */
	public function getNiceLocation() {
		return ($this->Location) ? Geoip::countryCode2name($this->Location) : "Anywhere";
	}
}
