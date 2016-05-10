<?php

class EditableFormFieldListingExtension extends DataExtension {
	public static function get_extra_config($class, $extension, $args) {
		if (!UserSubmissionHolder::config()->enable_search_form) {
			return array();
		}

		return array(
			'db' => array(
				'EnableOnSearchForm' => 'Boolean',
				'UseInKeywordSearch' => 'Boolean',
			)
		);
	}

	public function updateCMSFields(FieldList $fields) {
		if (!UserSubmissionHolder::config()->enable_search_form)
		{
			return;
		}

		$parent = $this->owner->Parent();
		if ($parent && $parent instanceof UserSubmissionHolder)
		{
			$fields->addFieldToTab('Root.Search', CheckboxField::create('EnableOnSearchForm', 'Show field on search form?'));
			$fields->addFieldToTab('Root.Search', CheckboxField::create('UseInKeywordSearch', 'Use for "Keywords" field search?'));
		}
	}
}
