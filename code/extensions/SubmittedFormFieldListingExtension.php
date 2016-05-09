<?php

class SubmittedFormFieldListingExtension extends DataExtension {
	private static $whitelist_fields = array(
		'Value'
	);

	private static $remove_fields = array(
		'ParentID',
		'Name'
	);

	public function updateCMSFields(FieldList $fields) {
		if ($this->owner->UserSubmissionHolder()) {
			$fields->removeByName($this->owner->config()->remove_fields);
			$whitelist_fields = $this->owner->config()->whitelist_fields;
			foreach ($fields->dataFields() as $field)
			{
				if (!in_array($field->getName(), $whitelist_fields))
				{
					$fields->replaceField($field->getName(), $field->performReadonlyTransformation());
				}
			}
		}
	}

	public function onBeforeWrite() {
		$changedFields = $this->owner->getChangedFields();
		unset($changedFields['SecurityID']);
		if ($changedFields) {
			$page = $this->owner->UserSubmissionPage();
			if ($page && $page->exists()) {
				$page->writeAndUpdateDBFromSubmission();
			}
		}
	}

	public function UserSubmissionPage() {
		$parent = $this->owner->Parent();
		if ($parent) 
		{
			$page = $parent->UserSubmissionPage();
			if ($page && $page->exists()) {
				return $page;
			}
		}
		return null;
	}

	public function UserSubmissionHolder() {
		$parent = $this->owner->Parent();
		if ($parent) 
		{
			$parentParent = $parent->Parent();
			if ($parentParent && $parentParent instanceof UserSubmissionHolder) {
				return $parentParent;
			}
		}
		return null;
	}
}
