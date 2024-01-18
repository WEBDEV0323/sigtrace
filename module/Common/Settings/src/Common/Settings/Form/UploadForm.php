<?php
// File: UploadForm.php
namespace Common\Settings\Form;

use Zend\Form\Element;
use Zend\Form\Form;

class UploadForm extends Form
{
    public function __construct($name = null, $options = array())
    {
        parent::__construct($name, $options);
        $this->addElements();
        $this->add(
            array(
            'name' => 't_hidden',
            'type' => 'hidden',
            )
        );
        $this->add(
            array(
            'name' => 'image-file',
            'type' => 'file',
            'options' => array(
                'label' => 'User Manual',
            ),
            'attributes' => array(
                'class' => 'custom-file-input',
                'id' => 'image-file',
            ),
            )
        );

    }

    public function addElements()
    {
        // File Input
        $file = new Element\File('image-file');
        $file->setLabel('Upload Attachment')->setAttributes(array('id'=> 'image-file' ,'class'=>'sdgs'));
        $this->add($file);
    }
}
