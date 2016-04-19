<?php

class GridFieldUserSubmissionAddMissingFields implements GridField_HTMLProvider, GridField_ActionProvider
{
    protected $targetFragment;

    protected $actionName = 'us_addmissingfields';

    public $completeMessage;

    public function __construct($targetFragment = 'after', $action = null)
    {
        $this->targetFragment = $targetFragment;
        if ($action !== null) {
        	$this->actionName = $action;
    	}
    }

    public function getHTMLFragments($gridField)
    {
    	$submission = $this->getSubmission($gridField);
    	if (!$submission) {
    		throw new Exception('Unable to find "SubmittedForm".');
    	}
       	$missingFields = $submission->MissingValues();
        if (!$missingFields) {
        	return;
        }

        $button = GridField_FormAction::create(
            $gridField,
            $this->actionName,
            '('.count($missingFields).') Add Missing Fields',
            $this->actionName,
            null
        );
        $button->setAttribute('data-icon', 'add')->addExtraClass('new new-link ui-button-text-icon-primary ss-ui-action-constructive usersubmissionholder-add-missing-fields');

        return array(
            $this->targetFragment => $button->Field(),
        );
    }

    public function getSubmission($gridField) {
    	return $gridField->getForm()->getRecord()->Submission();
    }

    public function getActions($gridField)
    {
        return array($this->actionName);
    }

    public function handleAction(GridField $gridField, $actionName, $arguments, $data)
    {
        if ($actionName !== $this->actionName) {
        	return;
        }

        $submission = $this->getSubmission($gridField);
        if (!$submission) {
        	return;
        }
        $missingFields = $submission->MissingValues();
        if (!$missingFields) {
        	$response->addHeader('X-Status', rawurlencode(_t('GridField.NOMISSINGFIELDS', 'No missing fields to add.')));
        	return;
        }

        // Add missing values
        $submission->addMissingValues();

        $controller = Controller::curr();
        if ($controller && $response = $controller->Response) {
            $response->addHeader('X-Status', rawurlencode(_t('GridField.DONE', 'Done.')));
        }
    }

    /*protected function saveAllRecords(\GridField $grid, $arguments, $data)
    {
        if (isset($data[$grid->Name])) {
            $currValue = $grid->Value();
            $grid->setValue($data[$grid->Name]);
            $model = singleton($grid->List->dataClass());

            foreach ($grid->getConfig()->getComponents() as $component) {
                if ($component instanceof \GridField_SaveHandler) {
                    $component->handleSave($grid, $model);
                }
            }

            if ($this->publish) {
                // Only use the viewable list items, since bulk publishing can take a toll on the system
                $list = ($paginator = $grid->getConfig()->getComponentByType('GridFieldPaginator')) ? $paginator->getManipulatedData($grid, $grid->List) : $grid->List;

                $list->each(
                    function ($item) {
                        if ($item->hasExtension('Versioned')) {
                            $item->writeToStage('Stage');
                            $item->publish('Stage', 'Live');
                        }
                    }
                );
            }

            if ($model->exists()) {
                $model->delete();
                $model->destroy();
            }

            $grid->setValue($currValue);

            if (\Controller::curr() && $response = \Controller::curr()->Response) {
                if (!$this->completeMessage) {
                    $this->completeMessage = _t('GridField.DONE', 'Done.');
                }

                $response->addHeader('X-Status', rawurlencode($this->completeMessage));
            }
        }
    }*/
}