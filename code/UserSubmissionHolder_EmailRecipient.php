<?php

class UserSubmissionHolder_EmailRecipient extends UserDefinedForm_EmailRecipient {
	private static $defaults = array(
		'EmailBody' => "There is a new submission available for you under the 'Submissions' tab here:\n\$LinkTag",
		'EmailBodyHtml' => "<p>There is a new submission available for you under the 'Submissions' tab here:<br/>\$LinkTag</p>",
	);

	private static $default_email_to = 'approver@admin.com';

	private static $default_email_from = 'sender@admin.com';

	/**
	 * @return FieldList
	 */
	public function getCMSFields() {
		$self = $this;
		$this->beforeUpdateCMSFields(function($fields) use ($self) {
			// Updates form fields with a placeholder that shows the user
			// what the default value will be.
			$field = $fields->dataFieldByName('EmailFrom');
			if ($field && !$self->getField('EmailFrom')) {
				$email = $self->DefaultEmailFrom();
				if ($email) {
					$field->setAttribute('placeholder', $email);
				}
			}
			$field = $fields->dataFieldByName('EmailAddress');
			if ($field && !$self->getField('EmailAddress')) {
				$email = $self->DefaultEmailTo();
				if ($email) {
					$field->setAttribute('placeholder', $email);
				}
			}
			// Update body fields to tell the user what template variables
			// exist.
			$instructions = '';
			foreach ($self->EmailBodyVariables() as $varname => $value) {
				$instructions .= $varname .' = ' . Convert::raw2xml($value) . '<br/>';
			}

			$field = $fields->dataFieldByName('EmailBody');
			if ($field) {
				$field->setRightTitle($instructions);
			}
			$field = $fields->dataFieldByName('EmailBodyHtml');
			if ($field) {
				$field->setRightTitle($instructions);
			}
		});
		return parent::getCMSFields();
	}

	/**
	 * @return string
	 */
	public function getEmailBodyContent() {
		$replaceFrom = array();
		$replaceTo = array();
		foreach ($this->EmailBodyVariables() as $varname => $value) {
			$replaceFrom[] = $varname;
			$replaceTo[] = $value;
		}
		return str_replace($replaceFrom, $replaceTo, parent::getEmailBodyContent());
	}

	/**
	 * @return array
	 */
	public function EmailBodyVariables() {
		$editPageLink = singleton('CMSPageEditController')->Link('show');
		$editPageLink .= '/'.$this->FormID;
		$absoluteEditPageLink = Controller::join_links(Director::absoluteBaseURL(), $editPageLink);
		return array(
			'$LinkTag' => '<a href="'.$absoluteEditPageLink.'">'.$absoluteEditPageLink.'</a>',
			'$Link' => $absoluteEditPageLink,
		);
	}

	/**
	 * @return string
	 */
	public function EmailAddress() {
		$val = $this->getField(__FUNCTION__);
		if (!$val) {
			return $this->DefaultEmailTo();
		}
		return $val;
	}

	/**
	 * @return string
	 */
	public function EmailFrom() {
		$val = $this->getField(__FUNCTION__);
		if (!$val) {
			return $this->DefaultEmailFrom();
		}
		return $val;
	}

	/**
	 * @return string
	 */
	public function DefaultEmailTo() {
		$result = static::config()->default_email_to;
		if (!$result) {
			return Email::config()->admin_email;
		}
		return $result;
	}

	/**
	 * @return string
	 */
	public static function DefaultEmailFrom() {
		$result = static::config()->default_email_from;
		if (!$result) {
			return Email::config()->admin_email;
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getEmailTemplateDropdownValues() {
		$templates = array(
			'UserSubmissionEmail' => 'UserSubmissionEmail',
		);
		return $templates;
	}
}