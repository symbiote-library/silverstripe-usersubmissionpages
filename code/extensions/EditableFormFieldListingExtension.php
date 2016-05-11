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
			if ($this->owner instanceof EditableDropdown)
			{
				$fieldType = 'dropdown';
				$fields->addFieldToTab('Root.Search', LiteralField::create('DropdownWarning_Readonly', "<p><span style=\"color: #C00;\">Warning:</span>: This {$fieldType} field won't show on the search form until a submisson exists that uses it.<br/><br/>This {$fieldType} field will also only populate with options that have been used (ie. submitted and published).</p>"));
			}
		}
	}
}
