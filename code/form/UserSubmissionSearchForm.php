<?php

class UserSubmissionSearchFormValidator extends RequiredFields {
	public function php($data) {
		$valid = parent::php($data);
		return $valid;
	}
}

class UserSubmissionSearchForm extends Form {
	private static $dropdown_empty_string = '- Please select -';

	private static $submit_use_button_tag = true;

	private static $submit_classes = 'button btn';

	protected $formMethod = 'get';

	public function __construct($controller, $name) {
		$userSubmissionHolder = $controller->data();
		$editableFormFields = $userSubmissionHolder->Fields()->filter(array('EnableOnSearchForm' => true));

		$fields = new FieldList();
		foreach ($editableFormFields as $editableFormField)
		{
			$field = $this->getFormFieldFromEditableFormField($editableFormField);
			if ($field)
			{
				$fields->push($field);
			}
		}

		$willCreateKeywordsField = $userSubmissionHolder->Fields()->find('UseInKeywordSearch', true);
		if ($willCreateKeywordsField) {
			$fields->unshift($field = TextField::create('Keywords', 'Keywords'));
		}

		$actions = new FieldList(
            $submitButton = FormAction::create('doSearch', 'Search')
                ->setUseButtonTag($this->config()->submit_use_button_tag)
                ->addExtraClass($this->config()->submit_classes)
        );

        $validator = UserSubmissionSearchFormValidator::create();
        $validator->fields = $fields;

		parent::__construct($controller, $name, $fields, $actions, $validator);

		$this->disableSecurityToken();

		// Retain search text and selections for pagination pages 
		// (as pagination doesn't trigger the httpSubmission function)
		$this->loadDataFrom($controller->getRequest()->requestVars());

		// note(Jake): Probably the perfect way to extend the form. Leaving commented until real use case arises.
		//$this->extend('updateForm');
	}

	public function getFormFieldFromEditableFormField(EditableFormField $fieldRecord)
	{
		$field = $fieldRecord->getFormField();
		// Remove templates added by EditableFormField
		$field->setFieldHolderTemplate(null);
		$field->setTemplate(null);
		// Attach EditableFormField to differentiate EditableFormField fields from regular ones
		// in the form.
		$field->EditableFormField = $fieldRecord;
		if ($field->hasMethod('setHasEmptyDefault') && ($dropdownEmptyString = $this->config()->dropdown_empty_string))
		{
			// Defaults to '- Please select -', configured above.
			$field->setEmptyString($dropdownEmptyString);
		}
		return $field;
	}

	public function doSearch($data) {
		$userSubmissionHolder = $this->controller->data();

		// Get list of page IDs of approved submissions
		$submissionIDs = array();
		foreach ($userSubmissionHolder->AllListing_DataLists() as $class => $dataList)
		{
			// Multiple data lists are to support pages using UserSubmissionExtension
			foreach ($dataList->column('SubmissionID') as $id)
			{
				$submissionIDs[$id] = $id;
			}
		}

		// If the 'Keywords' field exists, then utilize sent Keywords data.
		$keywords = '';
		if (isset($data['Keywords']) && $data['Keywords'] && ($this->Fields()->dataFieldByName('Keywords')))
		{
			$keywords = $data['Keywords'];
		}
		
		// Sets up where statements to be seperated by disjunctive (OR) or conjunctive (AND) keywords.
		$wheres = array();
		foreach ($this->Fields() as $field)
		{
			if ($field->EditableFormField)
			{
				$name = $field->getName();
				if (isset($data[$name]) && ($value = $data[$name]))
				{
					$nameEscaped = Convert::raw2sql($name);
					if ($field instanceof DropdownField) {
						// eg. (Name = 'EditableTextField_34' AND Value = 'VIC')
						$valueEscaped = (is_string($value)) ? "'".Convert::raw2sql($value)."'" : (int)$value;
						$wheres[$name] = array(
							"Name = '{$nameEscaped}'",
							"Value = $valueEscaped",
						);
					} else {
						// eg. (Name = 'EditableTextField_33' AND Value LIKE '%hello%')
						$valueEscaped = (is_string($value)) ? "LIKE '%".Convert::raw2sql($value)."%'" : '= '.((int)$value);
						$wheres[$name] = array(
							"Name = '{$nameEscaped}'",
							"Value $valueEscaped",
						);
					}
				}
			}
		}

		// Do a keyword search on each of the fields that have it enabled.
		//
		// eg: (((Name = 'EditableTextField_33' AND Value LIKE '%hello%') OR (Name = 'EditableTextField_42' AND Value LIKE '%hello%')))
		//
		if ($keywords)
		{
			$whereKeywords = array();
			$keywordsEscaped = Convert::raw2sql($keywords);
			foreach ($userSubmissionHolder->Fields() as $editableFormField)
			{
				if ($editableFormField->UseInKeywordSearch)
				{
					$nameEscaped = Convert::raw2sql($editableFormField->Name);
					$whereKeywords[$editableFormField->Name.'_keywords'] = array(
						"Name = '{$nameEscaped}'",
						"Value LIKE '%{$keywordsEscaped}%'",
					);
				}
			}
			if ($whereKeywords)
			{
				$whereKeywordsSQL = '';
				foreach ($whereKeywords as $whereGroup)
				{
					$whereKeywordsSQL .= ($whereKeywordsSQL) ? ' OR ' : '';
					$whereKeywordsSQL .= '('.implode(' AND ', $whereGroup).')';
				}
				$wheres['_keywords'] = array($whereKeywordsSQL);
			}
		}

		// Only search form field values that belong to a SubmittedForm object that belongs to 
		// a UserSubmissionPage (or page extended with UserSubmissionExtended)
		$list = SubmittedFormField::get()->filter(array(
			'ParentID' => $submissionIDs,
		));

		// For explicit searches on fields, ie selecting a dropdown value or typing on a text field
		// that searches on a specific field.
		//
		// eg. (Name = 'EditableTextField_34' AND Value = 'VIC') AND (Name = 'EditableTextField_34' AND Value LIKE '%school%')
		//
		if ($wheres)
		{
			$whereSQL = '';
			foreach ($wheres as $whereGroup)
			{
				$whereSQL .= ($whereSQL) ? ' AND ' : '';
				$whereSQL .= '('.implode(' AND ', $whereGroup).')';
			}
			$list = $list->where($whereSQL);
		}

		$resultSubmittedFormIDs = $list->column('ParentID');
		if (!$resultSubmittedFormIDs)
		{
			// Empty result
			$userSubmissionHolder->AllListing = array();
			return array();
		}

		$resultRecords = array();
		foreach (SubmittedForm::get()->filter('ID', $resultSubmittedFormIDs) as $submission)
		{
			if (($page = $submission->UserSubmissionPage()))
			{
				$resultRecords[$page->ClassName.'_'.$page->ID] = $page;
			}
		}

		$userSubmissionHolder->AllListing = new ArrayList($resultRecords);
		return array();
	}
}