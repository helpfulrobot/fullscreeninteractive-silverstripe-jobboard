<?php

/**
 * @package jobboard
 */
class JobBoardPageExtension extends DataExtension {
	
	private static $db = array(
		'TermsAndConditionsText' => 'HTMLText',
		'EmailFromAddress' => 'Varchar(100)',
		'EmailSubject' => 'Varchar(100)',
		'RequireModeration' => 'Boolean',
		'NotifyAddress' => 'Varchar(100)',
		'JobSortMode' => 'Enum("RAND(),Created DESC, Created ASC, LastEdited DESC, LastEdited ASC", "RAND()")'
	);
	
	public function updateCMSFields(FieldList $fields) {
		$fields->addFieldsToTab('Root.Content.JobOptions', array(
			new HtmlEditorField('TermsAndConditionsText', _t('JobHolder.TERMSANDCONDITIONS', 'Terms and Conditions text')),
			new CheckboxField('RequireModeration', _t('JobHolder.REQUIREMODERATION', 'Require moderation')),
			new EmailField('EmailFromAddress', _t('JobHolder.POSTEDFROMEMAILADDRESS', 'Job posted email from address (set to a valid email address)')),
			new EmailField('NotifyAddress', _t('JobHolder.NOTIFYEMAILADDRESS', 'Email to notify when job posted')),
			new TextField('EmailSubject', _t('JobHolder.POSTEDEMAILSUBJECT', 'Job posted email subject')),
			new DropdownField('JobSortMode', _t('JobHolder.JOBLISTINGSORT', 'Job Listing Sort'), array(
				'RAND()' => _t('JobHolder.RANDOM', 'Random'),
				'Created DESC' => _t('JobHolder.CREATEDDESC', 'Created Descending'),
				'Created ASC' => _t('JobHolder.CREATEASC', 'Created Ascending'),	
				'LastEdited DESC' => _t('JobHolder.EDITEDDESC', 'Last Edited Descending'),
				'LastEdited ASC' => _t('JobHolder.EDITEDASC', 'Last Edited Ascending')
			))
		));
		
		return $fields;
	}
}