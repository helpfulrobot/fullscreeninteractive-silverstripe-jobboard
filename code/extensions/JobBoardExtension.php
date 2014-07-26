<?php

/**
 * @package jobboard
 */
class JobBoardExtension extends Extension {

	/**
	 * Returns a list of the shows to be shown on the site.
	 *
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
	 * Handler for displaying a job.
	 *
	 * If the job cannot be viewed or doesn't exist then this will return a 
	 * HTTPResponse with the error code.
	 *
	 * @return array|HTTPResponse
	 */
	public function getJobBoardShowAction() {
		$job = $this->getJobFromParams();

		if(!$job || $job->isActive != 1) {
			return $this->owner->httpError(404);
		}

		if(Config::inst()->get('Job', 'require_moderation') || $this->owner->RequireModeration) {
			if(!$job->Moderated) {
				if(!isset($_GET['asp'])) {
					return $this->owner->httpError('400');
				}
			}
		}
		
		return array(
			'Title' => $job->Title,
			'Job' => $job,
			'CurrentJob' => $job
		);
	}
	
	/**
	 * Handler for posting a job.
	 *
	 * Passes a new instance of a JobBoardForm to the template. Any permission
	 * checking or authentication can be handled by your caller function.
	 *
	 * @return array
	 */
	public function getJobBoardPostAction() {
		return array(
			'Title' => DBField::create_field('HTMLText', _t('Jobboard.LISTNEWJOB','List a new job')),
			'Form' => new JobBoardForm($this->owner)
		);
	}

	/**
	 * Handler for the job thanks page. 
	 *
	 * Mostly this action requires template work so the caller function is in
	 * charge of doing most of the work but this will at least pass back the
	 * current {@link Job} in scope.
	 *
	 * @return array
	 */
	public function getJobBoardThanksAction() {
		if($job = Session::get('JobID')) {
			$job = Job::get()->byId($job);
		}

		return array(
			'Title' => DBField::create_field('HTMLText', _t('Jobboard.THANKS','Thanks')),
			'Job' => $job
		);
	}
	
	/**
	 * Handler for the Job editing view.
	 *
	 * If the user does not have permission or the job doesn't exist, then a 
	 * {@link SS_HTTPResponse} object is returned.
	 *
	 * @return array
	 */
	public function getJobBoardEditAction() {
		if(!$job = $this->getJobFromParams()) {
			return $this->owner->httpError(404);
		}

		$member = Member::currentUser();

		if(!$member || ($job->MemberID != $member->ID && !Permission::check('ADMIN'))) {
			return Security::permissionFailure($this->owner);
		}
		
		return array(
			'Title' => DBField::create_field('HTMLText', _t('Jobboard.EDITJOB','Edit job')),
			'Form' => new JobBoardForm($this->owner, $job),
			'CurrentJob' => $job
		);
	}

	/**
	 * @return Job
	 */
	public function getJobFromParams() {
		$params = $this->owner->getURLParams();
		
		if(!$params['ID']) {
			return false;
		}

		$job = Job::get()->filter(array(
			'Slug' => $params['ID']
		))->first();

		return $job;
	}

	/**
	 * @return array
	 */
	public function getJobBoardDeleteAction() {
		if(!$job = $this->getJobFromParams()) {
			return $this->httpError(404);
		}

		// see if they are logged in
		if(!$job->canEdit()) {
			return Security::permissionFailure($this->owner);
		}

		return array(
			'Form' => new JobBoardDeleteForm($this->owner, $job),
			'CurrentJob' => $job
		);
	}

	/**
	 * @return string
	 */
	public function getJobEmailFromAddress() {
		if($this->EmailFromAddress) {
			return $this->EmailFromAddress;
		}
		
		if($email = Config::inst()->get('Job', 'email_from_address')) {
			return $email;
		}
		
		return Config::inst()->get('Email', 'admin_email');
	}

	/**
	 * @return string
	 */
	public function getJobEmailSubject() {
		if($this->EmailSubject) {
			return $this->EmailSubject;
		}
		
		if($subject = Config::inst()->get('Job', 'email_subject')) {
			return $subject;
		}

		return _t('Jobboard.EMAILSUBJECT', 'Thanks for your job listing');
	}

	/**
	 * @return boolean
	 */
	public function getJobRequireModeration() {
		if($this->RequireModeration) {
			return $this->RequireModeration;
		}
		
		return Config::inst()->get('Job', 'require_moderation');
	}

	/**
	 * @return string
	 */
	public function getJobNotifyAddress() {
		if($this->NotifyAddress) {
			return $this->NotifyAddress;
		}
		
		return Config::inst()->get('Job', 'notify_address');
	}
}