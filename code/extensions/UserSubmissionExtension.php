<?php

class UserSubmissionExtension extends DataExtension {
	private static $has_one = array(
		'Submission' => 'SubmittedForm',
		'OwnedSubmission' => 'SubmittedForm', // NOTE: Only set if page created from nothing and it needed a submission.
	);

	private static $can_be_root = false;

	private static $allowed_children = 'none';

	private static $default_parent = 'UserSubmissionHolder';

	/**
	 * Update DB fields defined here based on UserSubmissionHolder::$SubmissionPageTitleField
	 *
	 * @config
	 * @var array
	 */
	private static $title_fields = array(
		'Title',
		'MenuTitle',
	);

	/**
	 * Update DB field defined here with $TemplateHolderMarkup when this page or the holder is updated.
	 *
	 * @config
	 * @var string 
	 */
	private static $content_field = 'Content';

	public function updateCMSFields(FieldList $fields) {
		/**
		 * @var SubmittedForm
		 */
		$submission = $this->owner->Submission();
		if (!$submission || !$submission->exists())
		{
			// If no submission and not underneath a 'UserSubmissionHolder' then show an error
			$fields->insertBefore(LiteralField::create('InvalidSetup_Text', '<p><span style="color: #C00;">Warning: </span>This page is missing SubmittedForm data.</p>'), 'Title');
			$fields->removeByName(array('Title', 'MenuTitle', 'Content'));
			return;
		}

		// Detect if submission parent matches this page parent. If it doesn't tell the user there is an issue.
		$userSubmissionHolder = $submission->UserSubmissionHolder();
		$parent = $this->owner->Parent();
		if ($userSubmissionHolder && $parent && $userSubmissionHolder->ID != $parent->ID) {
			$fields->insertBefore(LiteralField::create('InvalidSetup_Text', '<p><span style="color: #C00;">Warning: </span>This page must be moved underneath a "'.$userSubmissionHolder->Title.'" (ID #'.$userSubmissionHolder->ID.') page to work properly as the submission is bound to that page.</p>'), 'Title');
			$fields->removeByName(array('Title', 'MenuTitle', 'Content'));
			return;
		}

		Requirements::javascript(USERSUBMISSIONPAGES_DIR.'/javascript/userformpages.js');
		Requirements::css(USERSUBMISSIONPAGES_DIR.'/css/userformpages.css');

		// Get the submission form field to use for populating $Title/$MenuTitle
		$title_fields = $this->owner->stat('title_fields');
		if ($title_fields)
		{
			$holderPage = $submission->UserSubmissionHolder();
			$submissionFieldTitle = '<INVALID FIELD>';
			if ($holderPage && $holderPage->exists()) {
				$record = $holderPage->Fields()->find('Name', $holderPage->SubmissionPageTitleField);
				if ($record) {
					$submissionFieldTitle = $record->Title;
				}
			}

			// Inform the CMS user where these values are being pulled from.
			foreach ($title_fields as $fieldName) {
	 			$field = $fields->dataFieldByName($fieldName);
	 			if ($field) {
	 				$readonlyField = $field->performReadonlyTransformation();
	 				$readonlyField->setName($fieldName.'_Readonly');
	 				$readonlyField->setValue($this->owner->$fieldName);
	 				$readonlyField->setRightTitle('Uses the "'.$submissionFieldTitle.'" field as defined on the parent page.');
	 				$fields->insertAfter($readonlyField, $fieldName);
	 				$fields->removeByName($fieldName);
	 			}
			}
		}

		// Allow direct editing of submission field data
		$fields->insertAfter($gridField = GridField::create('SubmissionFields', 'Fields', $submission->Values()), 'Content');
		$config = $gridField->getConfig();
		$config->addComponent(new GridFieldEditButton);
		$config->addComponent(new GridFieldDetailForm);
		$config->addComponent(new GridFieldUserSubmissionAddMissingFields);

		$fields->removeByName(array('Abstract', 'Content'));

		// If content_field is set and not on the form, show underneath Metadata
		$content_field = $this->owner->stat('content_field');
		if ($content_field)
		{
			// NOTE: Using getField() because this is for inspecting the DB data.
			$value = $this->owner->getField($content_field);
			$fields->addFieldToTab('Root.Main', ToggleCompositeField::create(__CLASS__.'_Internal', 'Internal',
				array(
					$metaFieldDesc = ReadonlyField::create($content_field.'_Readonly_'.__CLASS__, $content_field, $value),
				)
			));
		}
	}

	public function onBeforeWrite() {
		if (!$this->owner->SubmissionID) {
			$parent = $this->owner->Parent();
			if ($parent && $parent instanceof UserSubmissionHolder) {
				$submission = SubmittedForm::create();
				$submission->SubmittedBy = Member::currentUserID();
				$submission->ParentID = $parent->ID;
				$submission->write();
				// Attach newly created SubmittedForm to this page
				$this->owner->OwnedSubmissionID = $this->owner->SubmissionID = $submission->ID;
				// Add missing values
				$submission->addMissingValues();
			}
		}

		if (!$this->owner->ID) {
			// Setup fields based on SubmittedForm data only if new page instance
			$this->owner->updateDBFromSubmission();

			// If $Title isn't set and the URLSegment is blank, set it to a decent default to avoid bugs with
			// having a blank URLSegment. 
			if ($this->owner->hasDatabaseField('URLSegment') && !$this->owner->Title && !$this->owner->URLSegment) {
				if ($this->owner->SubmissionID) {
					$this->owner->URLSegment = 'page-submission-'.$this->owner->SubmissionID;
				} else {
					$this->owner->URLSegment = 'new-page-submission';
				}
			}
		}
		parent::onBeforeWrite();
	}

	/**
	 * The markup to show on the holder page / $Listing
	 *
	 * @return string
	 */
	public function TemplateHolderMarkup() {
		$holderPage = $this->owner->UserSubmissionHolder();
		if ($holderPage && $holderPage->TemplateHolderMarkup) {
			return $this->owner->processTemplateMarkup($holderPage->TemplateHolderMarkup);
		}
		return '';
	}

	/**
	 * The markup to show on the page.
	 *
	 * @return string
	 */
	public function TemplatePageMarkup() {
		$holderPage = $this->owner->UserSubmissionHolder();
		if ($holderPage && $holderPage->TemplatePageMarkup) {
			return $this->owner->processTemplateMarkup($holderPage->TemplatePageMarkup);
		}
		return '';
	}

	/**
	 * @return UserSubmissionHolder
	 */
	public function UserSubmissionHolder() {
		$submission = $this->owner->Submission();
		if (!$submission || !$submission->exists()) {
			return null;
		}
		$holderPage = $submission->UserSubmissionHolder();
		if (!$holderPage || !$holderPage->exists()) {
			return null;
		}
		return $holderPage;
	}

	/**
	 * Process an SS template as string so that it can access $EditableTextField_6746f values
	 */
	public function processTemplateMarkup($ssMarkup, $data = array()) {
		$submission = $this->owner->Submission();
		if (!$submission || !$submission->exists())
		{
			return null;
		}
		$data = array(
			'Values' => $submission->Values()
		);
		foreach ($submission->Values() as $record) {
			$data[$record->Name] = $record->getFormattedValue();
		}
		return DBField::create_field('HTMLText', SSViewer::fromString($ssMarkup)->process($this->owner->customise($data)));
	}

	/**
	 * Updates $Title and $MenuTitle fields based on SubmittedForm
	 * data object.
	 */
	protected $__HasUpdatedDBFromSubmission = false;
	public function updateDBFromSubmission() {
		if ($this->__HasUpdatedDBFromSubmission) {
			return;
		}
		$this->__HasUpdatedDBFromSubmission = true;
		$submission = $this->owner->Submission();
		if (!$submission || !$submission->exists()) {
			return;
		}
		$holderPage = $submission->Parent();
		if (!$holderPage || !$holderPage->exists())
		{
			return;
		}

		// Update content field
		$content_field = $this->owner->stat('content_field');
		if ($content_field && $this->owner->hasDatabaseField($content_field))
		{
			$contentValue = $this->owner->{$content_field};
			$templatePageMarkup = $this->owner->TemplatePageMarkup();
			if ($templatePageMarkup instanceof HTMLText) {
				$templatePageMarkup = $templatePageMarkup->getValue();
			}
			if ($contentValue != $templatePageMarkup)
			{
				$this->owner->{$content_field} = $templatePageMarkup;
			}
		}

		// Update title fields (defaults to $Title/$MenuTitle)
		$title_fields = $this->owner->stat('title_fields');
		if ($title_fields)
		{
			// Get form field name to use. ie. $EditableTextField_6746f
			$fieldName = $holderPage->SubmissionPageTitleField;
			if ($fieldName)
			{
				// Only set the value if they aren't equal. This ensures
				// that changed fields isn't updated with an identical value.
				$formFieldValue = SubmittedFormField::get()->filter(array(
					'ParentID' => $submission->ID,
					'Name' => $fieldName
				))->first();
				if ($formFieldValue) 
				{
					$title = $formFieldValue->Value;
					if ($title !== null)
					{
						$title = (string)$title;
						
						foreach ($title_fields as $fieldName)
						{
							if ($this->owner->hasDatabaseField($fieldName) && $this->owner->{$fieldName} !== $title) {
								$this->owner->{$fieldName} = $title;
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Updates $Title and $MenuTitle fields based on SubmittedForm
	 * data object and writes the changes into the database.
	 *
	 */
	public function writeAndUpdateDBFromSubmission() {
		$this->owner->updateDBFromSubmission();
		if ($this->owner->getChangedFields(true)) {
			$this->owner->write();
   			return true;
		}
		return false;
	}

	protected static $_classes_extending_cache = null;
	public static function get_classes_extending() {
		if (static::$_classes_extending_cache) {
			return static::$_classes_extending_cache;
		}

		$result = array();
		foreach (ClassInfo::subclassesFor('SiteTree') as $class)
		{
			if ($class::has_extension(__CLASS__))
			{
				$result[$class] = $class;
			}
		}
		return static::$_classes_extending_cache = $result;
	}
}