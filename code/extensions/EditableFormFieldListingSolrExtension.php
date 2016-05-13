<?php

class EditableFormFieldListingSolrExtension extends DataExtension {
	public static function get_extra_config($class, $extension, $args) {
		if (!UserSubmissionHolder::config()->enable_solr) {
			return array();
		}

		return array(
			'db' => array(
				'EnableSolr' => 'Boolean',
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
			$fields->addFieldToTab('Root.Search', CheckboxField::create('EnableSolr', 'Use field for Solr search?'));
		}
	}
}
