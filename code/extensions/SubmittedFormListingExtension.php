<?php

class SubmittedFormListingExtension extends DataExtension {
	private static $belongs_to = array(
		//'UserSubmissionPage' => 'UserSubmissionPage', // NOTE: Handled automatically in 'UserSubmissionPage' function.
	);

	private static $better_buttons_actions = array(
        'approve',
    );

	/**
	 * The permission code required to approve and publish a page. 
	 *
	 * @config
	 * @var string
	 */
    private static $can_approve_permission_code = 'CMS_ACCESS_LeftAndMain';

    /**
     * Whether or not the page is immediately published or not on approval.
     *
     * @var boolean
     */
    private static $make_approve_action_publish = true;

    /**
     * @param BetterButtonCustomAction $action
     * @param GridFieldDetailForm_ItemRequest $itemRequest
     * @param SS_HTTPRequest $request
     */
    public function approve($action, $itemRequest, $request) {
    	if (!class_exists('BetterButtonCustomAction')) {
    		$this->httpError(400);
    	}
		if (!$this->owner->ID) {
			$this->httpError(400);
		}
		$page = $this->owner->UserSubmissionPage();
		if ($page && $page->exists()) {
			$this->httpError(400);
		}
		if (!$this->owner->canApprove()) {
			$this->httpError(400);
		}
        $newPage = $this->createInstanceFromThis();
        $action->setRedirectURL(Controller::join_links(singleton('CMSPageEditController')->Link('show'), $newPage->ID));
    }

	public function updateCMSFields(FieldList $fields) {
		if ($this->owner->UserSubmissionHolder()) {
			$gridField = $fields->dataFieldByName('Values');
			if ($gridField) {
				$config = $gridField->getConfig();
				$config->addComponent(new GridFieldEditButton());
				$config->addComponent(new GridFieldToolbarHeader());
				$config->addComponent($sort = new GridFieldSortableHeader());
				$config->addComponent(new GridFieldDetailForm());
			}
			$page = $this->owner->UserSubmissionPage();
			if ($page && $page->exists()) {
				$fields->insertBefore(LiteralField::create('Values_UserSubmissionPage_Message', '<p><span style="color: #C00;">Warning:</span> Editing the submission at this level will affect the page that this is tied to.</p>'), 'Values');
			}
		}
	}

	public function updateBetterButtonsActions($fields) {
		if ($this->owner->UserSubmissionHolder() && $this->owner->ID) {
			$page = $this->owner->UserSubmissionPage();
			if ((!$page || !$page->exists())
				&& 
				$this->owner->canApprove()) 
			{
				$approveText = ($this->owner->config()->make_approve_action_publish) ? 'Approve and Publish Page' : 'Approve and Create Draft Page';
				$fields->push(BetterButtonCustomAction::create('approve', $approveText));
			}
		}
	}

	public function getCMSState() {
		if ($this->owner->UserSubmissionHolder()) {
			$page = $this->owner->UserSubmissionPage();
			if ($page && $page->exists()) {
				$colour = '#18BA18';
				$text = 'Page Created';
			} else {
				$colour = '#1391DF';
				$text = 'Pending';
			}
			$html = HTMLText::create('CMSState');
			$html->setValue(sprintf(
				'<span style="color: %s;">%s</span>',
				$colour,
				htmlentities($text)
			));
			return $html;
		}
	}

	/**
	 * Returns array of fields that aren't on this submission but
	 * are on UserSubmissionHolder
	 *
	 * @return array
	 */
	public function MissingValues() {
		$holderPage = $this->owner->UserSubmissionHolder();
		if (!$holderPage || !$holderPage->exists()) {
			return array();
		}
    	$availableValues = $this->owner->Values()->map('Name', 'ClassName')->toArray();
        $currentFields = $holderPage->InputFields()->toArray();
        $missingFields = array();
        foreach ($currentFields as $record)
        {
        	if (!isset($availableValues[$record->Name]))
        	{
        		$missingFields[$record->Name] = $record;
        	}
        }
        return $missingFields;
    }

    /**
	 * Adds missing values
	 *
	 * @return array
	 */
	public function addMissingValues() {
		if (!$this->owner->ID) {
			throw new Exception('Cannot add missing values for an unsaved SubmittedForm');
		}
		$missingFields = $this->MissingValues();
		foreach ($missingFields as $fieldRecord) {
        	$record = SubmittedFormField::create();
        	$record->Name = $fieldRecord->Name;
        	$record->Title = $fieldRecord->Title;
        	$record->ParentID = $this->owner->ID;
        	$record->write();
        }
	}

	/**
	 * Creates a record from this SubmittedForm data object.
	 *
	 * @return UserSubmissionPage
	 */
	public function createInstanceFromThis($doPublish = null) {
		if ($doPublish === null) {
			$doPublish = $this->owner->config()->make_approve_action_publish;
		}

		/**
		 * @var UserSubmissionHolder
		 */
		$parent = $this->owner->Parent();
		if (!$parent || !$parent->exists()) {
			throw new Exception($this->owner->ClassName.' requires has_one Parent is set.');
		}
		$class = $parent->SubmissionPageClassName;
		if (!$class) {
			throw new Exception($parent->ClassName.' field "SubmissionPageClassName" is blank, which is not allowed.');
		}
		$newPage = $class::create();
        $newPage->SubmissionID = $this->owner->ID;
        if ($newPage instanceof SiteTree) {
        	$newPage->ParentID = $this->owner->ParentID;
    	}
        $newPage->write();
        if ($doPublish) {
        	$newPage->publish('Stage', 'Live');
        }
        return $newPage;
	}

	/**
	 * @return UserSubmissionHolder
	 */
	public function UserSubmissionHolder() {
		$parent = $this->owner->Parent();
		if ($parent && $parent instanceof UserSubmissionHolder)
		{
			return $parent;
		}
		return null;
	}

	/**
	 * This was implemented as an alternative to making 'SubmittedForm' have a 'has_one' 
	 * relationship with 'Page'. This is essentially a magic 'belongs_to'.
	 * 
	 * @return Page
	 */
	public function UserSubmissionPage() {
		if (!$this->owner->ID) {
			return null;
		}
		$results = array();
		foreach (UserSubmissionExtension::get_classes_extending() as $class => $title)
		{
			$result = $class::get()->filter(array('SubmissionID' => $this->owner->ID))->first();
			if ($result && $result->exists()) {
				return $result;
			}
		}
		return null;
	}

	/**
	 * Check if user has permission to approve turning this into a page
	 *
	 * @return boolean
	 */
	protected $inCanApproveCall = false;
	public function canApprove($member = null) {
		if ($this->inCanApproveCall) {
			return null;
		}
		if (!$member) {
			$member = Member::currentUser();
		}

		// Prevent recursion with extend functions.
		$this->inCanApproveCall = true;
		$extended = $this->owner->extendedCan(__FUNCTION__, $member);
		$this->inCanApproveCall = false;

		if($extended !== null) {
			return $extended;
		}
		$permissionCode = $this->owner->config()->can_approve_permission_code;
		if (!$permissionCode) {
			// If permission code not set properly, don't allow anyone to approve.
			return false;
		}
		return Permission::check($permissionCode, 'any', $member);
	}

	public function canDelete($member = null) {
		$page = $this->owner->UserSubmissionPage();
		if ($page && $page->exists()) {
			return false;
		}
	}
}
