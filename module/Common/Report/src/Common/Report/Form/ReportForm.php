<?php

namespace Common\Report\Form;

use Zend\Form\Form;
use Zend\Form\Element;

class ReportForm extends Form
{

    public function __construct()
    {
        parent::__construct();
        $this->setAttribute('method', 'post');
        $this->setAttribute('enctype', 'multipart/form-data');



        $this->add(
            array(
            'name' => 'c_product',
            'type' => 'Zend\Form\Element\Select',
            'options' => array(
                'label' => 'Country',
                'empty_option' => '--- Select Product ---',
            ),
            'attributes' => array(
                'class' => 'form-control',
                'id' => 'c_product',
                'name' => 'c_product',
                'required' => 'true'
            ),
            )
        );

        $this->add(
            array(
            'name' => 'submit',
            'type' => 'button',
            'attributes' => array(
                'value' => 'Generate Report',
                'class' => "btn btn-primary",
                'style'=>"margin-top: -16px;"
            ),
            )
        );

        $this->add(
            array(
            'name' => 'daterange',
            'type' => 'text',
            'attributes' => array(
                'id' => 'daterange',
                'value' => '04/18/2015 - 05/23/2015',
                'class' => "form-control",
                'required' => 'true'
            ),
            )
        );




        $this->add(
            array(
            'name' => 'file',
            'attributes' => array(
                'id' => 'file',
                'class' => 'fileupload',
                'required' => 'true'
            ),
            'options' => array(
                'label' => 'Upload CSV File',
                'label_attributes' => array('class' => 'fileupload')
            ),
            )
        );


        // $this->addElements();
    }

    public function addElements()
    {
        // File Input
        $file = new Element\File('file');
        $file->setLabel('Upload CSV File')
            ->setAttribute('id', 'file')
            ->setLabelAttributes('class', 'fileupload')
            ->setAttribute('class', 'fileupload');
        $this->add($file);
    }

}
