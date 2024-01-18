<?php

namespace Common\Triggerform\Form;
use Zend\Form\Form;

class TriggerformForm extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            array(
            'name' => 'c_name',
            'options' => array(
                'label' => 'Trigger Name',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_name',
                'name' => 'c_name',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );

        $this->add(
            array(
            'name' => 'trigger_when',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Trigger When',
                'empty_option' => '-- Select Trigger When Condition --',
                'options' => array(
                    'modified' => 'modified',
                    'added' => 'added',
                    'deleted' => 'deleted',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'trigger_when',
                'name' => 'trigger_when',                
            ),
            )
        );

        $this->add(
            array(
            'name' => 'trigger_then',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Trigger Then',
                'empty_option' => '-- Select Trigger Then Condition --',
                'options' => array(
                    'modified record' => 'modified record',
                    'add record' => 'add record',
                    'delete record' => 'delete record',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'trigger_then',
                'name' => 'trigger_then',                
            ),
            )
        );

        $this->add(
            array(
            'name' => 'destination',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Destination',
                'empty_option' => '-- Select Destination Table--',
                'options' => array(
                    '199' => 'Signal Detection',
                    '200' => 'Validation And Assessment',
                    '201' => 'siganl123',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'destination',
                'name' => 'destination',                
            ),
            )
        );

        $this->add(
            array(
            'name' => 'source',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Source',
                'empty_option' => '-- Select Source Table--',
                'options' => array(
                    '199' => 'Signal Detection',
                    '200' => 'Validation And Assessment',
                    '201' => 'siganl123',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'source',
                'name' => 'source',                
            ),
            )
        );

        $this->add(
            array(
            'name' => 'when_conditions',
            'options' => array(
                'label' => 'When Conditions',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'when_conditions',
                'name' => 'when_conditions',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );

        $this->add(
            array(
            'name' => 'fields_to_copy',
            'options' => array(
                'label' => 'Fields to copy',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'fields_to_copy',
                'name' => 'fields_to_copy',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );


        $this->add(
            array(
            'name' => 'c_hidden',
            'type' => 'hidden',
            )
        );
        $this->add(
            array(
            'name' => 't_hidden',
            'type' => 'hidden',
             'attributes' => array(
                'id' => 't_hidden',
            ),
            )
        );

        $this->add(
            array(
            'name' => 'c_flag',
            'type' => 'hidden',
            'attributes' => array(
                'id' => 'c_flag',
            ),
            )
        );

        $this->add(
            array(
            'name' => 'c_status',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Status',
                'empty_option' => 'Select Status',
                'options' => array(
                    'Active' => 'Active',
                    'Inactive' => 'Inactive',
                ),
            ),
            'attributes' => array(
                'class' => 'selectpicker form-control',
                'id' => 'c_status',
                'name' => 'c_status',
                'required' => 'true',
                'data-container'=>"body"
            ),
            )
        );

        $this->add(
            array(
            'name' => 'c_archive',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Archived Status',
                'empty_option' => 'Select Archived Status',
                'options' => array(
                    '0' => 'Unarchived',
                    '1' => 'Archived',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_archive',
                'name' => 'c_archive',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'c_role',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Trigger',
                'empty_option' => '--- Select Trigger ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_role',
                'name' => 'c_role',
                'required' => 'true'
            ),
            )
        );


        $this->add(
            array(
            'name' => 'n_name',
            'options' => array(
                'label' => 'Trigger Name',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_name',
                'name' => 'n_name',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );
        $this->add(
            array(
            'type' => 'Zend\Form\Element\Email',
            'name' => 'c_email',
            'options' => array(
                'label' => 'Client email address',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_email',
                'name' => 'c_email',
                'required' => 'true'
            ),
            )
        );
        $this->add(
            array(
            'name' => 'n_subject',
            'options' => array(
                'label' => 'Subject',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_subject',
                'name' => 'n_subject',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );
        $this->add(
            array(
            'name' => 'n_msg',
            'options' => array(
                'label' => 'Email Body',
            ),
            'attributes' => array(
                'class' => 'controls col-md-8',
                'id' => 'summernote',
                'name' => 'n_msg',
                'required' => 'true'
            ),
            'type' => 'TextArea',
            )
        );

        $this->add(
            array(
            'name' => 'n_cond',
            'options' => array(
                'label' => 'Condition',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_cond',
                'name' => 'n_cond',
                'required' => 'true'
            ),
            'type' => 'TextArea',
            )
        );
        $this->add(
            array(
            'name' => 'n_form',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Source',
                'empty_option' => '--- Select Form ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_form',
                'name' => 'n_form',
                // 'required' => 'true',
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_form2',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Destination',
                'empty_option' => '--- Select Form ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_form2',
                'name' => 'n_form2',
                // 'required' => 'true',
            ),
            )
        );
        
        $this->add(
            array(
            'name' => 'submit',
            'type' => 'button',
            'attributes' => array(
                'value' => 'Save',
                'class' => "btn btn-primary",
                'onclick' => 'addNewClient()'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'submit_tracker',
            'type' => 'button',
            'attributes' => array(
                'value' => 'Save',
                'class' => "btn btn-primary",
                'onclick' => 'addTracker()'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'cancel',
            'type' => 'button',
            'attributes' => array(
                'value' => 'Cancel',
                'onclick' => 'window.location.href="/client"',
                'class' => "btn btn-primary",
            ),
            )
        );

        $this->add(
            array(
            'name' => 'next',
            'type' => 'button',
            'attributes' => array(
                'value' => 'Next',
                'class' => "btn btn-primary",
            ),
            )
        );


        $this->add(
            array(
            'name' => 'c_hidden',
            'type' => 'hidden',
            )
        );

        $this->add(
            array(
            'name' => 'c_hidden_fields',

            'attributes' => array(
                'id'=>'c_hidden_fields',
            ),
            'type' => 'hidden',
            )
        );


        $this->add(
            array(
            'name' => 'c_flag',
            'type' => 'hidden',
            'attributes' => array(
                'id' => 'c_flag',
            ),
            )
        );

        $this->add(
            array(
            'name' => 'reason_for_change',
            'options' => array(
                'label' => 'Reason For Change',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'reason_for_change',
                'name' => 'reason_for_change',
                'required' => 'true'
            ),
            'type' => 'TextArea',
            )
        );

        $this->add(
            array(
            'name' => 'n_condition',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'When Conditions',
                'empty_option' => '-- Select Condition --',
                'options' => array(
                    'AND' => 'And',
                    'OR' => 'OR',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_condition',
                'name' => 'n_condition',                
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_workflowname',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Select Field',
                'empty_option' => '--- Select Workflow ---',
            ),
            'attributes' => array(
                'class' => 'form-control n_workflowname',
                'id' => 'n_workflowname',
                'name' => 'n_workflowname',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_condtionfield',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Select Field',
                 'empty_option' => '-- Select Fields --'
            ),
            'attributes' => array(
                'class' => 'form-control n_condtionfield',
                'id' => 'n_condtionfield',
                'name' => 'n_condtionfield',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_condition_operand',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Select Condition Operand',
                'empty_option' => '--- Select Condition Operand ---',
                'options' => array(
                    '==' => '=',
                    '!=' => '!=',
                    '>' => '>',
                    '<' => '<',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control n_condition_operand',
                'id' => 'n_condition_operand',
                'name' => 'n_condition_operand',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_value',
            'options' => array(
                'label' => 'Value',
            ),
            'attributes' => array(
                'class' => 'form-control n_value',
                'id' => 'n_value',
                'name' => 'n_value',
                // 'required' => 'true'
            ),
            'type' => 'Text',
            )
        );


        $this->add(
            array(
            'name' => 'c_select_client',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Status',
                'empty_option' => '--- Select Client ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_select_client',
                'name' => 'c_select_client',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'n_cc',
            'options' => array(
                'label' => 'CC To Whom',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'n_cc',
                'name' => 'n_cc',
                'placeholder' => 'Multiple email ids should be separeted by comma(,).',
            ),
            'type' => 'TextArea',
            )
        );
    }
}
