<?php

namespace Application\Form;

use Zend\Form\Form;

class LoginForm extends Form
{
    public function __construct()
    {
        parent::__construct();

        $this->add(
            array(
                    'name' => 'u_name',
                    'options' => array(
                        'label' => 'Username',
                        'label_attributes' => array(
                            'class' => 'col-sm-3 col-form-label'
                        )
                    ),
                    'attributes' => array(
                        'class' => 'form-control',
                        'id' => 'u_name',
                        'name' => 'u_name',
                        'placeholder' => 'Username',
                    ),
                    'type' => 'text',
                )
        );

        $this->add(
            array(
                    'name' => 'upw',
                    'options' => array(
                        'label' => 'Password',
                        'label_attributes' => array(
                            'class' => 'col-sm-3 col-form-label'
                        )
                    ),
                    'attributes' => array(
                        'class' => 'form-control',
                        'id' => 'upw',
                        'name' => 'upw',
                        'placeholder' => 'Password',
                    ),
                    'type' => 'password',
                )
        );

        $this->add(
            array(
                    'type' => 'Button',
                    'name' => 'submit',
                    'options' => array(
                        'label' => 'Login',
                        'label_options' => array(
                            'disable_html_escape' => true,
                        )
                    ),
                    'attributes' => array(
                        'type' => 'submit',
                        'class' => 'btn btn-success'
                    )
                )
        );


        $this->add(
            array(
                    'type' => 'Button',
                    'name' => 'reset',
                    'options' => array(
                        'label' => 'Clear',
                        'label_options' => array(
                            'disable_html_escape' => true,
                        )
                    ),
                    'attributes' => array(
                        'type' => 'reset',
                        'class' => 'btn btn-default'
                    )
                )
        );
    }
}
