<?php
namespace MillenniumFalcon\FormDescriptor;

use Doctrine\DBAL\Connection;
use MillenniumFalcon\Core\ORM\Asset;
use MillenniumFalcon\Core\Service\AssetService;
use MillenniumFalcon\Core\Service\ModelService;
use MillenniumFalcon\Core\Service\UtilsService;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\Form\FormBuilder;
use Symfony\Component\Form\FormFactory;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Environment;

class FormDescriptorService
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @var KernelInterface
     */
    protected $kernel;

    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var Environment
     */
    protected $environment;

    /**
     * @var \Swift_Mailer
     */
    protected $mailer;

    /**
     * @var string
     */
    protected $formBuilderClass;

    const FORM_DESCRIPTOR_BUILDER = FormDescriptorBuilder::class;

    /**
     * Shop constructor.
     * @param Container $container
     */
    public function __construct(
        Connection $connection,
        KernelInterface $kernel,
        FormFactoryInterface $formFactory,
        SessionInterface $session,
        Environment $environment,
        \Swift_Mailer $mailer
    ) {
        $this->connection = $connection;
        $this->kernel = $kernel;
        $this->formFactory = $formFactory;
        $this->session = $session;
        $this->environment = $environment;
        $this->mailer = $mailer;
        $this->formBuilderClass = static::FORM_DESCRIPTOR_BUILDER;
    }

    /**
     * @param $code
     * @param array $options
     * @return FormDescriptor
     * @throws \Exception
     */
    public function getForm($code, $options = array())
    {
        $pdo = $this->connection;
        $request = Request::createFromGlobals();
        $baseUrl = $request->getSchemeAndHttpHost();
        $fullUri = $request->getUri();
        $ip = $request->getClientIp();

        $fullClass = ModelService::fullClass($pdo, 'FormDescriptor');
        $formDescriptor = $fullClass::getByField($pdo, 'code', $code);
        if (is_null($formDescriptor)) {
            throw new NotFoundHttpException();
        }
        $formDescriptor->sent = false;

       /** @var FormFactory $formFactory */
        $formFactory = $this->formFactory;

        /** @var \Symfony\Component\Form\Form $form */
        $form = $formFactory->createNamedBuilder(
            'form_' . $formDescriptor->getCode(),
            $this->formBuilderClass,
            null,
            array(
                'formDescriptor' => $formDescriptor,
            )
        )->getForm();

        $form->handleRequest($request);
        $formDescriptor->form = $form->createView();

        if ($form->isSubmitted()) {
            if ($form->isValid()) {
                $data = (array)$form->getData();

                $assetFullClass = ModelService::fullClass($this->connection, 'Asset');
                $result = array();
                foreach (json_decode($formDescriptor->getFormFields()) as $field) {
                    if ($field->widget == 'submit') {
                        continue;
                    }
                    $value = $data[$field->id];
                    if (gettype($value) == 'array') {
                        $value = implode(', ', $value);
                    } elseif (gettype($value) == 'object' && get_class($value) == 'Symfony\Component\HttpFoundation\File\UploadedFile') {
                        $parentName = 'Submitted form uploads';
                        $parent = $assetFullClass::data($this->connection, [
                            'whereSql' => 'm.title = ? AND (m.parentId IS NULL OR m.parentId = 0)',
                            'params' => [$parentName],
                            'limit' => 1,
                            'oneOrNull' => 1,
                        ]);
                        if (!$parent) {
                            $parent = new $assetFullClass($this->connection);
                            $parent->setTitle($parentName);
                            $parent->setIsFolder(1);
                            $parent->setParentId(0);
                            $parent->save();
                        }
                        $folder = $assetFullClass::data($this->connection, [
                            'whereSql' => 'm.title = ? AND m.parentId = ?',
                            'params' => [$formDescriptor->getTitle(), $parent->getId()],
                            'limit' => 1,
                            'oneOrNull' => 1,
                        ]);
                        if (!$folder) {
                            $folder = new $assetFullClass($this->connection);
                            $folder->setTitle($formDescriptor->getTitle());
                            $folder->setIsFolder(1);
                            $folder->setParentId($parent->getId());
                            $folder->setRank(time());
                            $folder->save();
                        }

                        $originalName = $value->getClientOriginalName();
                        $asset = new $assetFullClass($this->connection);
                        $asset->setTitle($originalName);
                        $asset->setIsFolder(0);
                        $asset->setParentId($folder->getId());
                        $asset->setRank(time());
                        $asset->save();

                        AssetService::processUploadedFileWithAsset($this->connection, $value, $asset);

                        $value = $baseUrl . "/downloads/assets/{$asset->getId()}";
                    }

                    $result[] = [
                        $field->label, $value, $field->widget
                    ];
                }

                $this->beforeSend($formDescriptor, $result, $data);

                if ($formDescriptor->getRecipients()) {
                    $countryInfo = $this->session->get(UtilsService::COUNTRY_SESSION_KEY);
                    /**
                    if (!$countryInfo) {
                        $request = Request::createFromGlobals();
                        $countryInfo = UtilsService::ip_info(getenv('TEST_CLIENT_IP') ?: $request->getClientIp());
                        $countryInfo = $countryInfo ?: [];
                        $this->session->set(static::COUNTRY_SESSION_KEY, $countryInfo);
                    }
                     */

                    $fullClass = ModelService::fullClass($pdo, 'FormSubmission');
                    $submission = new $fullClass($pdo);
                    $submission->setDate(date('Y-m-d H:i:s'));
                    $submission->setFromAddress($formDescriptor->getFromAddress());
                    $submission->setRecipients($formDescriptor->getRecipients());
                    $submission->setContent(json_encode($result));
                    $submission->setEmailStatus(0);
                    $submission->setFormDescriptorId($formDescriptor->getId());
                    $submission->setUrl($fullUri);
                    $submission->setIP($ip);
                    $submission->setCountry(json_encode($countryInfo));
                    $submission->save();

                    $code = UtilsService::generateHex(4) . '-' . $submission->getId();
                    $submission->setTitle("{$formDescriptor->getFormName()} #{$code}");
                    $submission->setUniqueId($code);
                    $submission->save();

                    $formDescriptor->setFormSubmission($submission);

                    $messageBody = $this->environment->render('cms/emails/form_submission.twig', array(
                        'formDescriptor' => $formDescriptor,
                        'submission' => $submission,
                    ));

                    $message = (new \Swift_Message())
                        ->setSubject("{$formDescriptor->getTitle()} {$submission->getTitle()}")
                        ->setFrom(array($formDescriptor->getFromAddress()))
                        ->setTo(array_filter(array_map('trim', explode(',', $formDescriptor->getRecipients()))))
                        ->setBcc(array(getenv('EMAIL_BCC')))
                        ->setBody(
                            $messageBody, 'text/html'
                        );
                    if (isset($data['email']) && $data['email'] && filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                        $message->setReplyTo(array($data['email']));
                    }
                    $formDescriptor->sent = $this->mailer->send($message);

                    $submission->setEmailStatus($formDescriptor->sent ? 1 : 2);
                    $submission->setEmailRequest($messageBody);
                    $submission->setEmailResponse($formDescriptor->sent);
                    $submission->save();

                    $formDescriptor->sent = 1;
                } else {
                    $formDescriptor->sent = 1;
                }

                $this->afterSend($formDescriptor, $result, $data);
            }
        }

        return $formDescriptor;
    }

    /**
     * @param $formDescriptor
     * @param $result
     * @param $data
     * @param $orm
     */
    public function beforeSend($formDescriptor, &$result, $data) {}

    /**
     * @param $formDescriptor
     * @param $result
     * @param $data
     * @param $orm
     */
    public function afterSend($formDescriptor, &$result, $data) {}
}
