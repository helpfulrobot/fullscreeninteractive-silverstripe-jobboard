<?php

/**
 * @package jobboard
 */
class JobBoardFormProcessor extends Controller {
	
	private static $allowed_actions = array(
		'doJobForm'
	);

	/**
	 * Adds or modifies a job on the website.
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function doJobForm() {
		$data = $this->request->getVars();
		$form = new JobBoardForm($this);
		$form->loadDataFrom($data);

		$existed = false;

		if(!isset($data['JobID'])) {
			$job = new Job();
		} else {
			$job = Job::get()->byId($data['JobID']);
			$existed = true;

			if(!$job->canEdit()) {
				return $this->owner->httpError(404);
			}
		}

		$form->saveInto($job);

		$job->isActive = true;
		$job->write();
		
		Session::set('JobID', $job->ID);
		
		$member = Member::get()->filter(array(
			'Email' => $data['Email']
		))->first();
		
		if(!$member) {
			$member = new Member();
			$member->Email = $SQL_email;
			$member->FirstName = isset($data['Company']) ? $data['Company'] : false;
			$password = Member::create_new_password();
			$member->Password = $password;
			$member->write();
			$member->addToGroupByCode('job-posters', _t('Jobboard.JOBPOSTERSGROUP','Job Posters'));
		}

		$member->logIn();

		$job->MemberID = $member->ID;
		$job->write();
		
		if(!$existed) {
			$email = new Email();
			$email->setSubject($data['EmailSubject']);
			$email->setFrom($data['EmailFrom']); 
			$email->setTo($member->Email);
			
			// send the welcome email.
			$email->setTemplate('JobPosting');
			$email->populateTemplate(array(
				'Member' => $member,
				'Password' => (isset($password)) ? $password : false,
				'FirstPost' => ($password) ? true : false,
				'Holder' => $this,
				'Job' => $job
			));
		
			if($notify = $form->getController()->getJobNotifyAddress()) {
				$email->setBcc($notify);
			}

			$email->send();
		}

		return $this->redirect($data['BackURL']);
	}

	/**
	 * Delete a given job
	 *
	 * @param array $data
	 * @param Form $form
	 */
	public function doDeleteJobForm($data, $form) {
		if(isset($data['JobID'])) {
			$job = Job::get()->byId($data['JobID']);

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