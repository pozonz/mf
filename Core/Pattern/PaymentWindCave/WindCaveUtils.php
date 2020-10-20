<?php

namespace MillenniumFalcon\Core\Pattern\PaymentWindCave;

use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use MillenniumFalcon\Core\Service\ModelService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class WindCaveUtils
{
    protected $_windCaveInterface;

    protected $_request;

    protected $_connection;

    public $PxPayUserId;

    public $PxPayKey;

    public $UrlFail;

    public $UrlSuccess;

    public $AmountInput;

    public $EnableAddBillCard = 1;

    public $Opt;

    public $TxnType = 'Purchase';

    public $CurrencyInput = 'NZD';

    public $TxnData1;

    public $TxnData2;

    public $TxnData3;

    public $MerchantReference;

    public $EmailAddress;

    public $BillingId;

    public $TxnId;

    public $DpsTxnRef;

    public $DpsBillingId;

    /**
     * WindCaveParam constructor.
     */
    public function __construct(WindCaveInterface $windCaveInterface = null, Request $request, Connection $connection = null)
    {
        $this->_windCaveInterface = $windCaveInterface;
        $this->_request = $request;
        $this->_connection = $connection;

        $this->PxPayUserId = getenv('PXPAYUSERID');
        $this->PxPayKey = getenv('PXPAYKEY');
    }

    /**
     * @param $UrlFail
     * @param $UrlSuccess
     * @param $AmountInput
     * @param $MerchantReference
     * @param $EmailAddress
     * @param $BillingId
     * @param $TxnId
     */
    public function setExpress($UrlFail, $UrlSuccess, $AmountInput, $MerchantReference, $EmailAddress, $BillingId, $TxnId)
    {
        $this->UrlFail = $UrlFail;
        $this->UrlSuccess = $UrlSuccess;
        $this->AmountInput = $AmountInput;
        $this->MerchantReference = $MerchantReference;
        $this->EmailAddress = $EmailAddress;
        $this->BillingId = $BillingId;
        $this->TxnId = $TxnId;
    }

    /**
     * @return \SimpleXMLElement
     */
    public function toGatewayXmlRequest()
    {
        $xml = new \SimpleXMLElement('<GenerateRequest/>');

        $vars = get_class_vars(__CLASS__);
        $params = [];
        foreach ($vars as $idx => $itm) {
            if (substr($idx, 0, 1) == '_') {
                continue;
            }
            $xml->addChild($idx, $this->{$idx});
        }
        return $xml->asXML();
    }

    /**
     * @return \SimpleXMLElement
     */
    public function toPaymentStatusXmlRequest(Request $request)
    {
        $xml = new \SimpleXMLElement('<ProcessResponse/>');
        $xml->addChild('PxPayUserId', $this->PxPayUserId);
        $xml->addChild('PxPayKey', $this->PxPayKey);
        $xml->addChild('Response', $request->get('result'));
        return $xml->asXML();
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        return new Client([
            'base_uri' => 'https://sec.windcave.com'
        ]);
    }

    /**
     * @return WindCaveInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestGatewayUrl()
    {
        $this->setExpress(
            $this->_windCaveInterface->getFinaliseUrl($this->_request),
            $this->_windCaveInterface->getFinaliseUrl($this->_request),
            $this->_windCaveInterface->getAmountInput(),
            "{$this->_windCaveInterface->getMerchantReference()}",
            "{$this->_windCaveInterface->getEmail()}",
            "{$this->_windCaveInterface->getBillingId()}",
            "{$this->_windCaveInterface->getTxnId()}",
        );

        $client = $this->getClient();

        $xmlRequest = $this->toGatewayXmlRequest();
        $requestParams = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
            'body' => $xmlRequest,
        ];

        $this->_windCaveInterface->setPaymentStatus(1);
        $this->_windCaveInterface->setPaymentGatewayRequest(json_encode($requestParams, JSON_PRETTY_PRINT));
        $this->_windCaveInterface->save();

        $response = $client->request('POST', '/pxaccess/pxpay.aspx', $requestParams);
        $response->getBody()->rewind();
        $rawResponse = $response->getBody()->getContents();

        $this->_windCaveInterface->setPaymentGatewayResponse($rawResponse);
        $this->_windCaveInterface->save();

        $xmlResponse = new \SimpleXMLElement($rawResponse);
        $url = $xmlResponse->URI->__toString();

        $this->_windCaveInterface->setPaymentToken($url);
        $this->_windCaveInterface->save();
        return $this->_windCaveInterface;
    }

    /**
     * @param $windCaveClass
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function requestPaymentStatus($windCaveClass)
    {
        $client = $this->getClient();

        $xmlRequest = $this->toPaymentStatusXmlRequest($this->_request);
        $requestParams = [
            'headers' => [
                'Content-Type' => 'text/xml; charset=utf-8',
            ],
            'body' => $xmlRequest,
        ];


        $response = $client->request('POST', '/pxaccess/pxpay.aspx', $requestParams);
        $response->getBody()->rewind();
        $rawResponse = $response->getBody()->getContents();

        $xmlResponse = new \SimpleXMLElement($rawResponse);
        $title = $xmlResponse->TxnId->__toString();

        $fullClass = ModelService::fullClass($this->_connection, $windCaveClass);
        $this->_windCaveInterface = $fullClass::getByField($this->_connection, 'title', $title);
        if (!$this->_windCaveInterface) {
            throw new NotFoundHttpException();
        }

        $this->_windCaveInterface->setPaymentStatusRequest(json_encode($requestParams, JSON_PRETTY_PRINT));
        $this->_windCaveInterface->setPaymentStatusResponse($rawResponse);
        $this->_windCaveInterface->save();

        $success = $xmlResponse->Success->__toString();
        if ($success == 1) {
            $this->_windCaveInterface->setPaymentStatus(2);
            $this->_windCaveInterface->save();
        } else {
            $this->_windCaveInterface->setPaymentStatus(3);
            $this->_windCaveInterface->save();
        }

        return $this->_windCaveInterface;
    }
}