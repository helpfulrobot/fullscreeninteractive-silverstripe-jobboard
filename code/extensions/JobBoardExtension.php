<?php

/**
 * @package jobboard
 */
class JobBoardExtension extends Extension {

	/**
	 * @var array
	 */
	private static $allowed_actions = array(
		'show',
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
	 * @return DataList
	 */
	public function getActiveModeratedJobs($filters = array()) {
		$filters = array_merge($filters, array(
			"isActive" => 1,
			"Moderated" => 1
		));

		return Job::get()->filter($filters);
	}
	

	/**
	 * Handler for displaying a job - home/job/{{$slug}}
	 *
	 * @return array
	 */
	public function show() {
		if($this->urlParams['Action'] == "job") {
			$slug = Convert::raw2sql($this->urlParams['ID']);
			
			if(!$slug) {
				return $this->httpError('404');
			}
		
			$job = Job::get()->filter(array(
				"Slug" => $slug,
				"isActive" => 1
			))->first();		
			
			if(!$job) {
				return $this->httpError('404');
			}

			if($this->RequireModeration) {
				if(!$job->Moderated) {
					if(!isset($_GET['asp'])) {
						return $this->httpError('400');
					}
				}
			}
			
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
	public function post() {
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
	public function AddJobForm() {
		$fields = singleton('Job')->getFields();
		
		$fields->push(new LiteralField('Conditions', $this->TermsAndConditionsText));

		$actions = new FieldSet(
			new FormAction('doConfirmAddJobForm', 'Place Listing')
		);
		
		$required = singleton('Job')->getValidator();

		$form = new Form($this, 'AddJobForm', $fields, $actions, $required);
		
		if(class_exists('SpamProtectorManager')) {
			SpamProtectorManager::update_form($form);
		}
		
		return $form;
	}
	
	/**
	 * Add a job 
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function doConfirmAddJobForm($data, $form) {
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
			'Holder' => $this,
			'Job' => $job
		));
		
		if($this->NotifyAddress) {
			$email->setBcc($this->NotifyAddress);
		}
			
		$member->logIn();
		
		$email->send();
		$job->MemberID = $member->ID;
		$job->write();
		
		return $this->redirect($this->Link('thanks'));
	}

	/**
	 * Thanks page.
	 *
	 * @return array
	 */
	public function thanks() {
		if($job = Session::get('JobID')) {
			$job = Job::get()->byId($job);
		}

		return array(
			'Job' => $job
		);
	}
	
	/**
	 * Edit a Job
	 *
	 * @return array
	 */
	public function edit() {
		// try and get a job
		$id = $this->urlParams['ID'];

		if(!$id) {
			return $this->httpError(404);
		}
	
		$job = Job::get()->byId($id);

		if(!$job) {
			return $this->httpError(404);
		}

		// see if they are logged in
		$member = Member::currentUser();

		if(!$member || ($job->MemberID != $member->ID && !Permission::check('ADMIN'))) {
			return Security::permissionFailure($this);
		}
		
		return array(
			'Form' => $this->EditJobForm(),
			'CurrentJob' => $job
		);
	}

	/**
	 * @return Form
	 */
	public function EditJobForm() {
		$id = Director::urlParam('ID');
		$job = ($id) ? Job::get()->byId($id) : null;
		
		if(!$job || $job->isActive != 1) {
			return $this->httpError(404);
		}
		 
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
	
	/**
	 * @param array $data
	 * @param Form $form
	 */
	public function doEditJobForm($data, $form) {
		if(isset($data['JobID'])) {
			$job = Job::get()->byId($data['JobID']);
			
			// check user has permission
			if(!$job || ($job->MemberID != Member::currentUserID() && !Permission::check('ADMIN'))) {
				return Security::permissionFailure($this);
			}

			// save the job
			$form->saveInto($job);
			$job->write();
		}
		
		return $this->redirect($this->Link('updated'));
	}
	
	/**
	 * @return array
	 */
	public function delete() {
		// try and get a job
		$id = Director::urlParam('ID');
		
		if(!$id) {
			return $this->httpError(404);
		}

		$job = Job::get()->byId($id);

		if(!$job) {
			return $this->httpError(404);
		}

		// see if they are logged in
		$member = Member::currentUser();

		if(!$member || ($job->MemberID != $member->ID && !Permission::check('ADMIN'))) {
			return Security::permissionFailure($this);
		}

		return array(
			'Form' => $this->DeleteJobForm(),
			'CurrentJob' => $job
		);
	}
	
	/**
	 * @return Form
	 */
	public function DeleteJobForm() {
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
	
	/**
	 * Delete a given job
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function doDeleteJobForm($data, $form) {
		if(isset($data['JobID'])) {
			$job = DataObject::get_by_id('Job', $data['JobID']);

			// check user has permission
			if(!$job || ($job->MemberID != Member::currentUserID() && !Permission::check('ADMIN'))) {
				return Security::permissionFailure($this);
			}
			
			$job->isActive = false;
			$job->write();
		}
		
		return $this->redirect($this->Link('removed'));	
	}
}