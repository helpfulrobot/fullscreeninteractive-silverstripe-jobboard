<?php

/**
 * Job Holder.
 *
 * Acts as the main page for the {@link Job} listings
 *
 * @package jobboard
 */
class JobHolder extends Page {
	
	static $db = array(
		'TermsAndConditionsText' => 'HTMLText',
		'EmailFromAddress' => 'Varchar',
		'EmailSubject' => 'Varchar',
		'NotifyAddress' => 'Varchar(100)',
		'JobSortMode' => 'Enum("RAND(),Created DESC, Created ASC", "RAND()")'
	);
	
	function getCMSFields() {
		$fields = parent::getCMSFields();
		
		$fields->addFieldsToTab('Root.Content.JobOptions', array(
			new HtmlEditorField('TermsAndConditionsText', _t('JobHolder.TERMSANDCONDITIONS', 'Terms and Conditions text')),
			new EmailField('EmailFromAddress', _t('JobHolder.POSTEDFROMEMAILADDRESS', 'Job posted email from address (set to a valid email address)')),
			new EmailField('NotifyAddress', _t('JobHolder.NOTIFYEMAILADDRESS', 'Email to notify when job posted')),
			new TextField('EmailSubject', _t('JobHolder.POSTEDEMAILSUBJECT', 'Job posted email subject')),
			new DropdownField('JobSortMode', _t('JobHolder.JOBLISTINGSORT', 'Job Listing Sort', array(
				'RAND()' => _t('JobHolder.RANDOM', 'Random'),
				'Created DESC' => _t('JobHolder.CREATEDDESC', 'Created Descending'),
				'Created ASC' => _t('JobHolder.CREATEASC', 'Created Ascending')
			)))
		));
		
		return $fields;
	}
}

/**
 * @package jobboard
 */
class JobHolder_Controller extends Page_Controller {
	
	public static $allowed_actions = array(
		'index',
		'job',
		'post',
		'AddJobForm',
		'edit',
		'EditJobForm',
		'delete',
		'DeleteJobForm',
		'thanks',
		'updated',
		'removed',
	);
	
	/**
	 * Root index action. Displays the list of jobs
	 *
	 * @return array
	 */
	function index() {
		return array(
			'Jobs' => DataObject::get('Job', "\"isActive\" = '1'", $this->JobSortMode),
			'ShowJobs' => true
		);
	}

	/**
	 * Handler for displaying a job - home/job/{{$slug}}
	 *
	 * @return array
	 */
	function job() {
		if($this->urlParams['Action'] == "job") {
			$slug = Convert::raw2sql($this->urlParams['ID']);
			
			if(!$slug) return $this->httpError('404');
		
			$job = DataObject::get_one('Job', "\"Slug\" = '$slug' AND \"isActive\" = '1'");		
			if(!$job) return $this->httpError('404');
			
			return array(
				'Job' => $job,
				'CurrentJob' => $job
			);
		}
	}
	
	/**
	 * Handler for posting a job
	 *
	 * @return array
	 */
	function post() {
		return array(
			'Title' => DBField::create('HTMLText',_t('JobHolder.CREATEPOSTING','Create a new Posting')),
			'Form' => $this->AddJobForm()
		);
	}
	
	/**
	 * Form for adding a job to the page
	 * 
	 * @return Form
	 */
	function AddJobForm() {
		$fields = singleton('Job')->getFields();
		
		$fields->push(new LiteralField('Conditions', $this->TermsAndConditionsText));
		$actions = new FieldSet(
			new FormAction('doConfirmAddJobForm', 'Place Listing')
		);
		
		$required = singleton('Job')->getValidator();

		$form = new Form($this, 'AddJobForm', $fields, $actions, $required);
		
		if(class_exists('SpamProtectorManager'))
			SpamProtectorManager::update_form($form);
		
		return $form;
	}
	
	/**
	 * Add a job 
	 */
	function doConfirmAddJobForm($data, $form) {
		// save the data
		$job = new Job();
		$form->saveInto($job);
		$job->isActive = true;
		$job->write();
		
		Session::set('JobID', $job->ID);
		
		// look for a member with that email.
		$SQL_email = Convert::raw2sql($data['Email']);
		
		$member = DataObject::get_one('Member', "Email = '$SQL_email'");
		
		// send prepare email 
		$from = ($this->EmailFromAddress) ? $this->FromEmailAddress : Email::getAdminEmail();
		$subject = ($this->EmailSubject) ? $this->EmailSubject : _t('JobHolder.EMAILSUBJECT', 'Thanks for your job listing');
		
		$email = new Email($from, $data['Email'], $subject);
		$password = false;
		if(!$member) {
			$member = new Member();
			$member->Email = $SQL_email;
			$member->FirstName = isset($data['Company']) ? $data['Company'] : false;
			$password = Member::create_new_password();
			$member->Password = $password;
			$member->write();
			$member->addToGroupByCode('job-posters', _t('JobHolder.JOBPOSTERSGROUP','Job Posters'));
		}
			
		// send the welcome email.
		$email->setTemplate('JobPosting');
		$email->populateTemplate(array(
			'Member' => $member,
			'Password' => $password,
			'FirstPost' => ($password) ? true : false,
			'Job' => $job
		));
		
		if($this->NotifyAddress) 
			$email->setBcc($this->NotifyAddress);
			
		$member->logIn();
					
		$email->send();
		$job->MemberID = $member->ID;
		$job->write();
		
		return $this->redirect($this->Link('thanks'));
	}

	/**
	 * Thanks page
	 */
	function thanks() {
		$job = (Session::get('JobID')) ? DataObject::get_by_id('Job', Session::get('JobID')) : false;

		return array(
			'Job' => $job
		);
	}
	
	/**
	 * Edit a Job
	 */
	function edit() {
		// try and get a job
		$id = $this->urlParams['ID'];
		if(!$id) return $this->httpError(404);
	
		$job = DataObject::get_by_id("Job", $id);
		if(!$job) return $this->httpError(404);

		// see if they are logged in
		$member = Member::currentUser();

		if(!$member || ($job->MemberID != $member->ID && !Permission::check('ADMIN'))) return Security::permissionFailure($this);
		
		return array(
			'Form' => $this->EditJobForm(),
			'CurrentJob' => $job
		);
	}
	
	function EditJobForm() {
		$job = (Director::urlParam('ID')) ? DataObject::get_by_id("Job", Director::urlParam('ID')) : null;
		
		if($job && $job->isActive != 1) return $this->httpError(404);
		 
		$member = Member::currentUser();

		$fields = singleton('Job')->getFields();

		
		$actions = new FieldSet(
			new FormAction('doEditJobForm', _t('JobHolder.EDITLISTING','Edit Listing'))
		);
		
		$required = singleton('Job')->getValidator();

		$form = new Form($this, 'EditJobForm', $fields, $actions, $required);
		
		if($job) {
			$form->loadDataFrom($job);
			$form->Fields()->push(new HiddenField('JobID', "Job ID",$job->ID));
		}
		
		return $form;
	}
	
	function doEditJobForm($data, $form) {
		if(isset($data['JobID'])) {
			$job = DataObject::get_by_id('Job', $data['JobID']);
			
			// check user has permission
			if(!$job || ($job->MemberID != Member::currentUserID() && !Permission::check('ADMIN'))) return Security::permissionFailure($this);

			// check if they have assigned this to another member
			$member = DataObject::get_one("Member", "Email = '". Convert::raw2sql($data['Email']) . "'");
		
			// send prepare email 
			$from = ($this->EmailFromAddress) ? $this->FromEmailAddress : Email::getAdminEmail();
			$subject = ($this->EmailSubject) ? $this->EmailSubject : _t('JobHolder.EMAILSUBJECT', 'Thanks for your job listing');

			$email = new Email($from, $data['Email'], $subject);

			if(!$member) {
				$member = new Member();
				$member->Email = $SQL_email;
				$member->FirstName = isset($data['Company']) ? $data['Company'] : false;
				$password = Member::create_new_password();
				$member->Password = $password;
				$member->write();
				$member->addToGroupByCode('job-posters', _t('JobHolder.JOBPOSTERSGROUP','Job Posters'));
				$first = true;
			
				// send the welcome email.
				$email->setTemplate('JobPosting');
				$email->populateTemplate(array(
					'Member' => $member,
					'Password' => $password,
					'FirstPost' => true,
					'Job' => $job
				));
			}
			
			// save the job
			$form->saveInto($job);
			$job->isActive = 1;
			$job->MemberID = $member->ID;
			$job->write();
		}
		
		return $this->redirect($this->Link('updated'));
	}
	
	function delete() {
		// try and get a job
		$id = Director::urlParam('ID');
		if(!$id) return $this->httpError(404);

		$job = DataObject::get_by_id("Job", $id);

		if(!$job) return $this->httpError(404);

		// see if they are logged in
		$member = Member::currentUser();

		if(!$member || ($job->MemberID != $member->ID && !Permission::check('ADMIN'))) return Security::permissionFailure($this);

		return array(
			'Form' => $this->DeleteJobForm(),
			'CurrentJob' => $job
		);
	}
	
	function DeleteJobForm() {
		return new Form($this, 'DeleteJobForm', 
			new FieldSet(
				new HiddenField('JobID', 'Job ID', (Director::urlParam('ID')) ? Director::urlParam('ID') : ""),
				new LiteralField('Confirm', '<h2>Confirm Delete</h2>
					<p>Clicking Delete will remove this listing from the site and the data will be removed.</p><p>You cannot undo this action</p>'
				),
				new CheckboxField('ConfirmTick', 
					_t('JobHolder.DELETECONFIRMATIONWARNING','I understand that I will not be able to restore this listing after clicking delete')	
				)
			),
			new FieldSet(new FormAction('doDeleteJobForm', _t('JobHolder.CONFIRMDELETE','Confirm Delete'))),
			new RequiredFields('ConfirmTick')
		);
	}
	
	function doDeleteJobForm($data, $form) {
		if(isset($data['JobID'])) {
			$job = DataObject::get_by_id('Job', $data['JobID']);

			// check user has permission
			if(!$job || ($job->MemberID != Member::currentUserID() && !Permission::check('ADMIN'))) return Security::permissionFailure($this);
			
			$job->isActive = false;
			$job->write();
		}
		return $this->redirect($this->Link('removed'));	
	}
}