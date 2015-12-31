<?php

/**
 * An extension to you can add to your page if you're using the CMS Module. This
 * puts the configuration information into the CMS rather than using the config
 * API.
 *
 * @package jobboard
 */
class JobBoardPageExtension extends DataExtension
{
    
    private static $db = array(
        'TermsAndConditionsText' => 'HTMLText',
        'EmailFromAddress' => 'Varchar(100)',
        'EmailSubject' => 'Varchar(100)',
        'RequireModeration' => 'Boolean',
        'NotifyAddress' => 'Varchar(100)',
    );
    
    public function updateCMSFields(FieldList $fields)
    {
        $fields->addFieldsToTab('Root.Content.JobOptions', array(
            new HtmlEditorField('TermsAndConditionsText', _t('Jobboard.TERMSANDCONDITIONS', 'Terms and Conditions text')),
            new CheckboxField('RequireModeration', _t('Jobboard.REQUIREMODERATION', 'Require moderation')),
            new EmailField('EmailFromAddress', _t('Jobboard.POSTEDFROMEMAILADDRESS', 'Job posted email from address (set to a valid email address)')),
            new EmailField('NotifyAddress', _t('Jobboard.NOTIFYEMAILADDRESS', 'Email to notify when job posted')),
            new TextField('EmailSubject', _t('Jobboard.POSTEDEMAILSUBJECT', 'Job posted email subject'))
        ));
        
        return $fields;
    }
}
