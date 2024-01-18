<?php

namespace ASRTRACE\Tracker\Form;

use Zend\Form\Element;
use Zend\Form\Form;
use Zend\InputFilter\InputFilter;

class TrackerForm extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            array(
            'name' => 'c_name',
            'options' => array(
                'label' => 'Customer Name',
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
            'type' => 'Zend\Form\Element\Email',
            'name' => 'c_email',
            'options' => array(
                'label' => 'Customer email address',
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
            'name' => 'c_proj_manager_name',
            'options' => array(
                'label' => 'Project Manager Name',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_proj_manager_name',
                'name' => 'c_proj_manager_name',
                'required' => 'true'
            ),
            'type' => 'Text',
            )
        );
        $this->add(
            array(
            'name' => 'c_description',
            'options' => array(
                'label' => 'Description',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_description',
                'name' => 'c_description',
                'required' => 'true'
            ),
            'type' => 'TextArea',
            )
        );
        $this->add(
            array(
            'name' => 'c_country',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Country',
                'empty_option' => '--- Select Country ---',
                'options' => array(
                    'India' => 'India',
                    'US' => 'US',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_country',
                'name' => 'c_country',
                'required' => 'true'
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
            'attributes' => array(
                'id' => 'c_hidden',
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
                'empty_option' => '--- Select status ---',
                'options' => array(
                    'Active' => 'Active',
                    'Deactive' => 'Deactive',
                ),
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_status',
                'name' => 'c_status',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'c_archive',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Archived Status',
                'empty_option' => '--- Select archived status ---',
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
            'name' => 'c_tracker',
            'options' => array(
                'label' => 'Tracker Name',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_tracker',
                'name' => 'c_tracker',
                'required' => 'true'
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
                'empty_option' => 'Select Customer Name',
            ),
            'attributes' => array(
                'class' => 'selectpicker form-control',
                'id' => 'c_select_client',
                'name' => 'c_select_client',
                'required' => 'true'
            ),
            )
        );
    }
}
