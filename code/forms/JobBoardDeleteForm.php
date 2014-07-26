<?php

/**
 * @package jobboard
 */
class JobBoardDeleteForm extends Form {

	/**
	 * @return Form
	 */
	public function __construct($controller, $job = null) {
		$fields = new FieldList(
			new HiddenField('JobID', 'Job ID', ($job) ? $job->ID : null,
			new LiteralField('Confirm', '<h2>'. _t('Jobboard.CONFIRMDELETE', 'Confirm Delete') .'</h2>
				<p>Clicking Delete will remove this listing from the site and the data will be removed.</p><p>You cannot undo this action</p>'
			),
			new CheckboxField('ConfirmTick', 
				_t('Jobboard.DELETECONFIRMATIONWARNING','I understand that I will not be able to restore this listing after clicking delete')	
			)
		);

		$actions = new FieldList(
			new FormAction('doDeleteJobForm', _t('Jobboard.CONFIRMDELETE','Confirm Delete'))
		);

		$required = new RequiredFields('ConfirmTick');
		
		parent::__construct($controller, 'DeleteJobForm', $fields, $actions, $required);
	}
}