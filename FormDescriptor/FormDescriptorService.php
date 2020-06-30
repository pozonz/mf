<?php
namespace MillenniumFalcon\FormDescriptor;

use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class FormDescriptorService
{
    /**
     * Shop constructor.
     * @param Container $container
     */
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $code
     * @param array $options
     * @return FormDescriptor
     * @throws \Exception
     */
    public function getForm($code, $options = array())
    {
        $orm = isset($options['orm']) && $options['orm'] ? $options['orm'] : null;

        $pdo = $this->container->get('doctrine.dbal.default_connection');

        $fullClass = ModelService::fullClass($pdo, 'FormDescriptor');
        $formDescriptor = $fullClass::getByField($pdo, 'code', $code);
        if (is_null($formDescriptor)) {
            throw new NotFoundHttpException();
        }
        $formDescriptor->sent = false;

       /** @var FormFactory $formFactory */
        $formFactory = $this->container->get('form.factory');

        /** @var \Symfony\Component\Form\Form $form */
        $form = $formFactory->createNamedBuilder(
            'form_' . $formDescriptor->getCode(),
            FormDescriptorBuilder::class,
            null,
            array(
                'formDescriptor' => $formDescriptor,
            )
        )->getForm();

        $request = Request::createFromGlobals();
        if ('POST' == $request->getMethod() && isset($_POST['form_' . $formDescriptor->getCode()])) {
            $form->handleRequest($request);

            if ($form->isValid()) {
                $data = (array)$form->getData();
                $result = array();
                foreach (json_decode($formDescriptor->getFormFields()) as $field) {
                    if ($field->widget == 'submit') {
                        continue;
                    }
                    $result[] = array($field->label, $data[$field->id], $field->widget);
                    $formDescriptor->setThankYouMessage(str_replace("{{$field->id}}", $data[$field->id], $formDescriptor->getThankYouMessage()));
                }
                $this->beforeSend($formDescriptor, $result, $data, $orm);

                if ($orm) {
                    $orm->save();
                }

                if ($formDescriptor->getRecipients()) {
                    $code = uniqid();
                    $fullClass = ModelService::fullClass($pdo, 'FormSubmission');
                    $submission = new $fullClass($pdo);
                    $submission->setTitle("{$formDescriptor->getTitle()} #{$code} " .  (isset($data['email']) ? $data['email'] : ''));
                    $submission->setUniqueId($code);
                    $submission->setDate(date('Y-m-d H:i:s'));
                    $submission->setFromAddress($formDescriptor->getFromAddress());
                    $submission->setRecipients($formDescriptor->getRecipients());
                    $submission->setContent(json_encode($result));
                    $submission->setEmailStatus(0);
                    $submission->setFormDescriptorId($formDescriptor->getId());
                    $submission->save();

                    $dir = $this->container->getParameter('kernel.project_dir') . '/vendor/pozoltd/mf/Resources/views';
                    $loader = $this->container->get('twig')->getLoader();
                    $loader->addPath($dir);
                    $messageBody = $this->container->get('twig')->render('cms/emails/form_submission.twig', array(
                        'submission' => $submission,
                    ));

                    $message = (new \Swift_Message())
                        ->setSubject("{$formDescriptor->getTitle()} #" . $submission->getUniqueId())
                        ->setFrom(array($formDescriptor->getFromAddress()))
                        ->setTo(array_filter(array_map('trim', explode(',', $formDescriptor->getRecipients()))))
                        ->setBcc(array(getenv('EMAIL_BCC')))
                        ->setBody(
                            $messageBody, 'text/html'
                        );
                    if (isset($data['email']) && $data['email'] && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $message->setReplyTo(array($data['email']));
                    }
                    $formDescriptor->sent = $this->container->get('mailer')->send($message);

                    $submission->setEmailStatus($formDescriptor->sent ? 1 : 2);
                    $submission->setEmailRequest($messageBody);
                    $submission->setEmailResponse($formDescriptor->sent);
                    $submission->save();
                } else {
                    $formDescriptor->sent = 1;
                }

                /** @var \Symfony\Component\Form\Form $form */
                $form = $formFactory->createNamedBuilder(
                    'form_' . $formDescriptor->getCode(),
                    FormDescriptorBuilder::class,
                    null,
                    array(
                        'formDescriptor' => $formDescriptor,
                    )
                )->getForm();
                $this->afterSend($formDescriptor, $result, $data, $orm);
            }
        }

        $formDescriptor->form = $form->createView();
        return $formDescriptor;

    }

    /**
     * @param $formDescriptor
     * @param $result
     * @param $data
     * @param $orm
     */
    public function beforeSend($formDescriptor, &$result, $data, $orm) {}

    /**
     * @param $formDescriptor
     * @param $result
     * @param $data
     * @param $orm
     */
    public function afterSend($formDescriptor, &$result, $data, $orm) {}
}
