<?php

class UserSubmissionHolder extends UserDefinedForm {
	private static $db = array(
		'ItemsPerPage' => 'Int',
		'ContentAdd' => 'HTMLText',
		'TemplateHolderMarkup' => 'Text',
		'TemplatePageMarkup' => 'Text',
		'SubmissionPageClassName' => 'Varchar',
		'SubmissionPageTitleField' => 'Varchar',
	);

	private static $defaults = array(
		'Content' => '$Listing',
		'ContentAdd' => '$UserDefinedForm',
		'ItemsPerPage' => 5,
	);

	private static $allowed_children = array(
		'UserSubmissionExtension',
	);

	/**
	 * Enables search form functionality. 
	 *
	 * @var boolean
	 */
	private static $enable_search_form = true;

	/**
	 * @var string
	 */
	private static $description = 'Adds a customizable form where users can submit information to be approved and added to a listing.';

	/**
	 * @var string
	 */
	private static $icon = 'usersubmissionpages/images/holder_sitetree_icon.png';

	public function getCMSFields() {
		Requirements::javascript(USERSUBMISSIONPAGES_DIR.'/javascript/userformpages.js');
		Requirements::css(USERSUBMISSIONPAGES_DIR.'/css/userformpages.css');
		$self = $this;

 		$this->beforeUpdateCMSFields(function($fields) use ($self) {
 			//
 			// WARNING: Some of the fields here are moved with 'insertAfter' after
 			//			$this->extend('updateCMSFields') is called, so their location is immutable.
 			//			However, any other data to do with the field can be modified.
 			//

 			// Add dropdown that defines what page type gets created.
 			$pageTypes = array();
 			foreach (UserSubmissionExtension::get_classes_extending() as $class) {
 				$pageTypes[$class] = singleton($class)->singular_name();
 			}

 			$field = null;
 			if ($pageTypes && count($pageTypes) > 1) {
 				$fields->push($field = DropdownField::create('SubmissionPageClassName', null, $pageTypes));
 			} else if ($pageTypes) {
 				// Seperated into *_Readonly so the value can look nice and use the 'singular_name'.
 				$fields->push($field = ReadonlyField::create('SubmissionPageClassName_Readonly', null, reset($pageTypes)));
 			}
 			if ($field) {
 				$field->setTitle('Submission Page Type');
 				$field->setRightTitle('The page type to create underneath this page when a submission is approved.');
 			}

 			// Add dropdown that defines what field is used for the Page title of the published
 			// submission listing items
 			$userFields = $self->InputFields()->map('Name', 'Title');
 			if (!$userFields instanceof ArrayList) {
 				$userFields = $userFields->toArray();
 			}
			if ($self->SubmissionPageTitleField && !isset($userFields[$self->SubmissionPageTitleField])) {
				// If set value isn't in dropdown, make it appear in there.
				$userFields[$self->SubmissionPageTitleField] = '<Field Deleted>';
			} else if (!$self->SubmissionPageTitleField) {
				$userFields = array_merge(array('' => '(Select Field)'), $userFields);
			}
			$fields->push($field = DropdownField::create('SubmissionPageTitleField', 'Submission Page Title Field', $userFields));
			$field->setRightTitle('Be careful when modifying this value as it will change the "Title" and "Menu Title" of all your child pages.');

			// Update 'Submissions' gridfield to show the current state of a submission
			$submissionsGridField = $fields->dataFieldByName('Submissions');
			if ($submissionsGridField) {
				$class = $submissionsGridField->getList()->dataClass();
				$displayFields = ArrayLib::valuekey($class::config()->summary_fields);
				$displayFields['CMSState'] = 'State';
				$submissionsGridField->getConfig()->getComponentByType('GridFieldDataColumns')->setDisplayFields($displayFields);
			}

			// Add content
			$instructions = '';
			foreach ($self->ContentVariables() as $varname => $data) {
				$value = isset($data['Help']) ? $data['Help'] : $data['Value'];
				$instructions .= $varname .' = ' . Convert::raw2xml($value) . '<br/>';
			}
			$field = $fields->dataFieldByName('Content');
			if ($field) {
				$field->setTitle($field->Title() . ' (Listing)');
				$field->setRightTitle($instructions);
			}
			$fields->addFieldToTab('Root.Submissions', NumericField::create('ItemsPerPage')->setRightTitle('If set to 0, then show all items.'));
			$fields->addFieldToTab('Root.Submissions', $field = HtmlEditorField::create('ContentAdd', 'Content (Form)'));
			$field->setRightTitle($instructions);

			// Add templating
			$fields->addFieldToTab('Root.Main', $field = TextareaField::create('TemplateHolderMarkup', 'Template Holder')
				->setRows(10)
			);
			$fields->addFieldToTab('Root.Main', $field = TextareaField::create('TemplatePageMarkup', 'Template Page')
				->setRows(10)
			);

			// Add search info
			if ($self->config()->enable_search_form)
			{
				$fields->addFieldToTab('Root.Main', $field = ReadonlyField::create('Search_EnableOnSearchForm_Readonly', 'Search Form Fields'));
				$field->setValue(implode(', ', $self->Fields()->filter('EnableOnSearchForm', true)->map('ID', 'Title')->toArray()));
				$field->setRightTitle('Fields that currently have "Show field on search form?" checked in their options.');

				$fields->addFieldToTab('Root.Main', $field = ReadonlyField::create('Search_UseInKeywordSearch_Readonly', 'Search Form Fields in Keyword Search'));
				$field->setValue(implode(', ', $self->Fields()->filter('UseInKeywordSearch', true)->map('ID', 'Title')->toArray()));
				$field->setRightTitle('Fields that currently have "Use for "Keywords" field search?" checked in their options.');
			}

			// Update Email Recipients gridfield to use custom email recipient class
			$gridField = $fields->dataFieldByName('EmailRecipients');
			if ($gridField) {
				$gridField->setModelClass('UserSubmissionHolder_EmailRecipient');
			}
		});

		$fields = parent::getCMSFields();
		if ($field = $fields->dataFieldByName('Search_UseInKeywordSearch_Readonly')) { $fields->insertAfter($field, 'Fields'); }
		if ($field = $fields->dataFieldByName('Search_EnableOnSearchForm_Readonly')) { $fields->insertAfter($field, 'Fields'); }
		if ($field = $fields->dataFieldByName('TemplatePageMarkup')) {
			$fields->insertAfter($field, 'Fields');
			$this->insertCMSTemplateAddFieldButtons($fields, 'TemplatePageMarkup');
		}
		if ($field = $fields->dataFieldByName('TemplateHolderMarkup')) {
			$fields->insertAfter($field, 'Fields');
			$this->insertCMSTemplateAddFieldButtons($fields, 'TemplateHolderMarkup');
		}
		if ($field = $fields->dataFieldByName('SubmissionPageTitleField')) { $fields->insertAfter($field, 'Fields'); }
		if ($field = $fields->dataFieldByName('SubmissionPageClassName')) { $fields->insertAfter($field, 'Fields'); }
		if ($field = $fields->dataFieldByName('SubmissionPageClassName_Readonly')) { $fields->insertAfter($field, 'Fields'); }
		return $fields;
	}

	/**
	 * @return array
	 */
	public function allowedChildren() {
		$result = parent::allowedChildren();
		// Replace 'UserSubmissionExtension' with all SiteTree classes that are using that
		// extension.
		foreach ($result as $i => $allowedClass)
		{
			if ($allowedClass === 'UserSubmissionExtension')
			{
				unset($result[$i]);
				foreach (ClassInfo::subclassesFor('SiteTree') as $class)
				{
					if ($class::has_extension('UserSubmissionExtension') && !in_array($class, $result))
					{
						$result[] = $class;
					}
				}
				break;
			}
		}
		return $result;
	}

	/**
	 * @return array
	 */
	public function getCMSAddFieldItems() {
		$source = array();
		foreach ($this->InputFields()->toArray() as $i => $field) {
			$source[$field->Name] = array(
				'Title' => $field->Title,
				'Name' => $field->Name,
				// Todo(Jake): Move this out to *.ss template that isn't ->process()'d but just gets the template string so that
				//			   the markup provided can be modified easily.
				'Markup' => "<% if \${$field->Name} %>\n\t<p><strong>{$field->Title}: </strong>{\${$field->Name}}</p>\n<% end_if %>",
			);
		}
		$source['Extra_Readmore'] = array(
			'Title' => 'Read More Link',
			'Name' => 'Extra_Readmore',
			'Markup' => '<a href="$Link">Read more</a>',
		);
		return $source;
	}

	/**
	 * @return FieldGroup
	 */
	public function insertCMSTemplateAddFieldButtons(FieldList $fields, $fieldName) {
		$fieldItems = $this->getCMSAddFieldItems();
		$source = array();
		foreach ($this->getCMSAddFieldItems() as $data)
		{
			$source[$data['Name']] = $data['Title'];
		}
		if (!$source) {
			return null;
		}

		// Get field that the buttons are being attached to and added appropriate data
		$markupField = $fields->dataFieldByName($fieldName);
		if (!$markupField) {
			throw new Exception('Missing '.$fieldName.'. Cannot call "insertCMSTemplateAddFieldButtons" if the field isn\'t present.');
		}
		$markupField->addExtraClass('js-field-template')->setAttribute('data-field-items', json_encode($fieldItems));

		// Add dropdown + "add field" button
		$fields->insertAfter(
			$fieldGroup = FieldGroup::create(
				DropdownField::create($fieldName.'_Field_Select')
					->addExtraClass('js-field-template-select')
					->setSource($source)
					->setAttribute('data-field-name', $fieldName)
				,
				FormAction::create($fieldName.'_Field_Add', 'Add Field')
					->addExtraClass('js-field-template-add ss-ui-action-constructive ')
					->setAttribute('data-field-name', $fieldName)
					->setUseButtonTag(true)
				,
				LiteralField::create(
					$fieldName.'_Field_Hint', 
					$this->customise(array('FieldName' => $fieldName))->renderWith('UserSubmission_FieldHint')->RAW()
				)
		)->addExtraClass('usersubmissionholder-add-field-buttons'), $fieldName);
		return $fieldGroup;
	}

	public function onAfterWrite() {
		parent::onAfterWrite();
		$changedFields = $this->getChangedFields();

		if (isset($changedFields['SubmissionPageTitleField']) && $changedFields['SubmissionPageTitleField']) {
			$fieldState = $changedFields['SubmissionPageTitleField'];
			if ($fieldState['before'] !== $fieldState['after'] && $fieldState['after'] != '') {
				$subPagesModified = false;
				$classes = UserSubmissionExtension::get_classes_extending();
				foreach (SiteTree::get()->filter(array('ClassName' => $classes)) as $record) {
					$subPagesModified = $subPagesModified || $record->writeAndUpdateDBFromSubmission();
				}
				// note(Jake): explored updating the pages in the sitetree, but it isn't worth it.
			}
		}
	}

	public function getSubmissionPageClassName() {
		$value = $this->getField('SubmissionPageClassName');
		if ($value) {
			return $value;
		}
		$classesExtending = UserSubmissionExtension::get_classes_extending();
		if ($classesExtending) {
			return reset($classesExtending);
		}
		return '';
	}

	/**
	 * Get SubmittedForm IDs for items that are attached to pages. ie. approved.
	 *
	 * NOTE: Cached as its called ~3 times for the UserSubmissionSearchForm per request.
	 *
	 * @return array
	 */
	protected $_cache_submitted_form_ids = null;
	public function PublishedSubmittedFormIDs() {
		if ($this->_cache_submitted_form_ids != null) {
			return $this->_cache_submitted_form_ids;
		}
		// Get list of page IDs of approved submissions
		$submissionIDs = array();
		foreach ($this->AllListing_DataLists() as $class => $dataList)
		{
			// Multiple data lists are to support pages using UserSubmissionExtension
			foreach ($dataList->column('SubmissionID') as $id)
			{
				$submissionIDs[$id] = $id;
			}
		}
		return $this->_cache_submitted_form_ids = $submissionIDs;
	}

	/**
	 * @return string
	 */
	public function Content() {
		return $this->applyContentVariables($this->getField(__FUNCTION__));
	}

	/**
	 * @return string
	 */
	public function ContentAdd() {
		return $this->applyContentVariables($this->getField(__FUNCTION__));
	}

	/**
	 * @return ArrayList
	 */
	public function Listing() {
		$list = $this->AllListing();
		if (!$list) {
			return $list;
		}
		$list = PaginatedList::create($this->AllListing(), Controller::curr()->getRequest());
		if ($this->ItemsPerPage > 0) {
			$list->setPageLength((int)$this->ItemsPerPage);
		} else {
			$list->setPageLength(99999);
		}
		return $list;
	}

	/**
	 * @return array
	 */
	public function AllListing_DataLists() {
		$result = array();
		$classes = UserSubmissionExtension::get_classes_extending();
		foreach ($classes as $class)
		{
			$result[$class] = $class::get()->filter(array(
				'SubmissionID:not' => 0,
				'ParentID' => $this->ID,
			));
		}
		return $result;
	}

	/**
	 * @return ArrayList
	 */
	public function AllListing() {
		if ($this->AllListing !== null) {
			// NOTE: Allow overiding AllListing by setting property, this is used by the UserSubmissionSearchForm.
			return $this->AllListing;
		}

		$result = array();
		foreach ($this->AllListing_DataLists() as $dataList)
		{
			foreach ($dataList as $page)
			{
				$submission = $page->Submission();
				if ($submission && $submission->exists())
				{
					$result[] = $page;
				}
			}
		}
		return new ArrayList($result);
	}

	/**
	 * @return array
	 */
	public function ContentVariables() {
		$controller = Controller::curr();
		if (!$controller instanceof UserSubmissionHolder_Controller) {
			$controller = UserSubmissionHolder_Controller::create($this);
		}

		// Get listing HTML (only on frontend)
		$listingHTML = '';
		if (Config::inst()->get('SSViewer', 'theme_enabled'))
		{
			$listingHTML = $this->customise(array(
				'Listing' => $this->Listing(),
				'UserSubmissionSearchForm' => $controller->UserSubmissionSearchForm(),
			))->renderWith(array($this->ClassName.'_Listing', 'UserSubmissionHolder_Listing'));
		}

		$result = array(
			'$Listing' => array(
				'Help' => 'Displays the approved submissions.',
				'Value' => $listingHTML,
			),
			'$UserDefinedForm' => array(
				'Help' => 'Displays the form',
				'Value' => $controller->Form()->forTemplate(),
			),
		);
		if (self::config()->enable_search_form)
		{
			$result['$UserSubmissionSearchForm'] = array(
				'Help' => 'Displays the search form',
				'Value' => $controller->UserSubmissionSearchForm()->forTemplate(),
			);
		}
		return $result;
	}

	/**
	 * Replace $UserDefinedForm or $Listing with their 'Value' defined
	 * in self::ContentVariables.
	 *
	 * @return string
	 */
	public function applyContentVariables($value) {
		$controllerName = __CLASS__.'_Controller';
		$controller = singleton($controllerName);

		foreach ($this->ContentVariables() as $varname => $data) {
			if (isset($data['Value'])) {
				$value = preg_replace('/(<p[^>]*>)?\\'.$varname.'(<\\/p>)?/i', $data['Value'], $value);
			}
		}
		return $value;
	}

	/** 
	 * Get user form fields that actually hold data.
	 * ie. not titles, groups, etc. 
	 *
	 * @return HasManyList
	 */
	public function InputFields() {
		return $this->Fields()->filter(array('ClassName:not' => array(
			'EditableFormStep',
			'EditableFormHeading',
		)));
	}
}

class UserSubmissionHolder_Controller extends UserDefinedForm_Controller {
	private static $allowed_actions = array(
		'index',
		'add',
		'UserSubmissionSearchForm',
	);

	protected $templateHasForm = false;

	public function index() {
		return array(
			'Content' => $this->Content(),
			'Form' => '',
		);
	}

	public function add($request) {
		return array(
			'Content' => $this->ContentAdd(),
			'Form' => '',
		);
	}

	public function UserSubmissionSearchForm() {
		if (!UserSubmissionHolder::config()->enable_search_form) {
			if ($this->request->param('Action') == __FUNCTION__) {
				return $this->httpError(404);
			}
			return null;
		}
		return UserSubmissionSearchForm::create($this, __FUNCTION__);
	}
}