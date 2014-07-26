<?php

/**
 * @package jobboard
 */
class JobBoardForm extends Form {
	
	/**
	 * @param Controller $controller
	 * @param Job $job (optional)
	 */
	public function __construct($controller, $job = null) {
		if($job) {
			$fields = $job->getFields();
			$required = $job->getValidator();
		} else {
			$fields = singleton('Job')->getFields();
			$required = singleton('Job')->getValidator();
		}

		$fields->merge(new FieldList(
			new LiteralField('Conditions', $controller->TermsAndConditionsText),
			new HiddenField('BackURL', '', $controller->Link('thanks')),
			new HiddenField('EmailFrom', '', $controller->getJobEmailFromAddress()),
			new HiddenField('EmailSubject', '', $controller->getJobEmailSubject()),
			$jobId = new HiddenField('JobID')
		));

		if($job) {
			$jobId->setValue($job->ID);

			$actions = new FieldList(
				new FormAction('doEditJob', _t('Jobboard.EDITLISTING','Edit Listing'))
			);
		} else {
			$actions = new FieldList(
				new FormAction('doAddJob', _t('JobBoard.CONFIRM', 'Confirm'))
			);
		}

		parent::__construct($controller, 'AddJobForm', $fields, $actions, $required);

		$this->setFormAction('JobBoardFormProcessor/doJobForm');
		$this->setFormMethod('GET');

		if($job) {
			$this->loadDataFrom($job);
		} else {
			$this->enableSpamProtection();
		}	
	}
}