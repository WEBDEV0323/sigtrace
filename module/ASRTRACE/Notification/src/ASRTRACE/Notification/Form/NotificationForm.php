<?php

namespace Notification\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class NotificationForm extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            array(
            'name' => 'n_name',
            'options' => array(
                'label' => 'Notification Name',
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
                'label' => 'Form Name',
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
                'label' => 'Notify When',
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
