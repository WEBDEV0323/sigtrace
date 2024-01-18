<?php

namespace Product\Form;
use Zend\Form\Form;

class ActiveSubstanceForm extends Form
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
    }
}
