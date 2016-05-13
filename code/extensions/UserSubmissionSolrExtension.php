<?php

class UserSubmissionSolrExtension extends DataExtension {
	public function updateSolrSearchableFields(&$fields)
    {
        if (!$this->owner->hasExtension('UserSubmissionExtension')) 
        {
        	throw new Exception(__CLASS__.' should not be applied to "'.$this->owner->class.'" as it does not have the "UserSubmissionExtension".');
        }

        /*$all = $this->owner->getAllMetadata();
        foreach ($all as $schema => $schemaFields) {
            foreach ($schemaFields as $key => $val) {
                if (strlen($val)) {
                    $fields[$key] = true;
                }
            }
        }*/
    }

    public function additionalSolrValues()
    {
        if (!$this->owner->hasExtension('UserSubmissionExtension')) 
        {
        	throw new Exception(__CLASS__.' should not be applied to "'.$this->owner->class.'" as it does not have the "UserSubmissionExtension".');
        }

        $result = array();
        /*foreach ($this->owner->getSchemas() as $schema) {
            foreach ($schema->Fields() as $field) {
                $value = $this->owner->Metadata($schema, $field);
                if (!$value || ($value instanceof DBField && !$value->hasValue())) {
                    continue;
                }
                if (is_object($value)) {
                    $value = $value instanceof DBField ? $value->Nice() : $value->getTitle();
                }
                
                if ($field instanceof MetadataSelectField) {
                    $value = explode(',', $value);
                }
                if (is_array($value) || strlen($value)) {
                    $result[$field->Name] = $value;
                }
            }
        }*/
        return $result;
    }
}