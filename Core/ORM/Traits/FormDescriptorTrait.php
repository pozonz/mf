<?php
//Last updated: 2019-07-04 20:17:29
namespace MillenniumFalcon\Core\ORM\Traits;

trait FormDescriptorTrait
{
    private $formSubmission;
    
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-formdescriptor.html.twig';
    }

    /**
     * @return mixed
     */
    public function getFormSubmission()
    {
        return $this->formSubmission;
    }

    /**
     * @param mixed $formSubmission
     */
    public function setFormSubmission($formSubmission)
    {
        $this->formSubmission = $formSubmission;
    }

}