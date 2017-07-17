<?php

/**
 * A wrapper around SubmittedFormField to allow direct access to the records data in templates.
 */
class UserSubmissionViewableData extends ViewableData {
	/**
	 * A failover object to attempt to get data from if it is not present on this object.
	 *
	 * @var SubmittedFormField
	 */
	protected $failover;

	public function __construct(SubmittedFormField $submittedFormField) {
		$this->failover = $submittedFormField;
	}

	public function exists() {
		return $this->failover && $this->failover->exists();
	}

	public function forTemplate() {
		return $this->failover->getFormattedValue();
	}
}
