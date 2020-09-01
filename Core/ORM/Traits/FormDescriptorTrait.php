<?php
//Last updated: 2019-07-04 20:17:29
namespace MillenniumFalcon\Core\ORM\Traits;

trait FormDescriptorTrait
{
    /**
     * @param $pdo
     */
    static public function initData($pdo)
    {
        $orm = new static($pdo);
        $orm->setTitle('Contact');
        $orm->setCode('contact');
        $orm->setFromAddress('noreply@send.final.nz');
        $orm->setRecipients('pozoltd@gmail.com');
        $orm->setCode('contact');
        $orm->setFormFields(json_encode([
            [
                "widget" => "\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType",
                "label" => "First name:",
                "id" => "firstName",
                "required" => 1,
                "sql" => ""
            ],
            [
                "widget" => "\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType",
                "label" => "Last name:",
                "id" => "lastName",
                "required" => 1,
                "sql" => ""
            ],
            [
                "widget" => "\\Symfony\\Component\\Form\\Extension\\Core\\Type\\EmailType",
                "label" => "Email:",
                "id" => "email",
                "required" => 1,
                "sql" => ""
            ],
            [
                "widget" => "\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextType",
                "label" => "Phone:",
                "id" => "phone",
                "required" => 1,
                "sql" => ""
            ],
            [
                "widget" => "\\Symfony\\Component\\Form\\Extension\\Core\\Type\\TextareaType",
                "label" => "Message:",
                "id" => "message",
                "required" => 0,
                "sql" => ""
            ]
        ]));
        $orm->setThankyouHeading('Thanks for your enquiry. We will get back to you as soon as we can.');
        $orm->save();
    }
    
    /**
     * @return string
     */
    static public function getCmsOrmTwig() {
        return 'cms/orms/orm-custom-formdescriptor.html.twig';
    }

}