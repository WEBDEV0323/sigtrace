<?php
namespace Application\Form\Filter;

use Zend\InputFilter\InputFilter;

class LoginFilter extends InputFilter
{
    public function __construct()
    {
        $isEmpty = \Zend\Validator\NotEmpty::IS_EMPTY;
        $invalidEmail = \Zend\Validator\EmailAddress::INVALID_FORMAT;
        
        $this->add(
            array(
            'name' => 'u_name',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'StripTags'
                ),
                array(
                    'name' => 'StringTrim'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty',
                    'options' => array(
                        'messages' => array(
                            $isEmpty => 'Username can not be empty.'
                        )
                    ),
                    'break_chain_on_failure' => true
                ),
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => '/[A-Za-z0-9_~\-!@#\$%\^&\*\(\)]+$/',
                            'messages' => array(
                            \Zend\Validator\Regex::NOT_MATCH => "Invalid characters in Username"
                        )
                    ),
                )
            )
            )
        );
        
        $this->add(
            array(
            'name' => 'upw',
            'required' => true,
            'filters' => array(
                array(
                    'name' => 'StripTags'
                ),
                array(
                    'name' => 'StringTrim'
                )
            ),
            'validators' => array(
                array(
                    'name' => 'NotEmpty',
                    'options' => array(
                        'messages' => array(
                            $isEmpty => 'Password can not be empty.'
                        )
                    ),
                    'break_chain_on_failure' => true
                ),
                array(
                    'name' => 'Regex',
                    'options' => array(
                        'pattern' => '/[A-Za-z0-9_~\-!@#\$%\^&\*\(\)]+$/',
                            'messages' => array(
                            \Zend\Validator\Regex::NOT_MATCH => "Invalid characters in Password"
                        )
                    ),
                )
            )
            )
        );
    }
}
