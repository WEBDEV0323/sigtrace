<?php

namespace Common\Tracker\Form;

use Zend\Form\Form;

class RulesForm extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            array(
            'name' => 'c_name',
            'options' => array(
                'label' => 'Group Name',
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
            'name' => 'c_real_name',
            'options' => array(
                'label' => 'User Real Name',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_real_name',
                'name' => 'c_real_name',
                'required' => 'true'
            ),
            'type' => 'Text',
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
            'name' => 'rules_form',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Form',
                'empty_option' => 'Select Form',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'rules_form',
                'name' => 'rules_form',
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
            'name' => 'c_role',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Role',
                'empty_option' => '--- Select Role ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_role',
                'name' => 'c_role',
                'required' => 'true'
            ),
            )
        );










        // We could also define the input filter here, or
        // lazy-create it in the getInputFilter() method.
    }
}
