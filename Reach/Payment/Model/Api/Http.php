<?php

namespace Reach\Payment\Model\Api;

abstract class Http
{
    /** @var string */
    private $basicAuth;

    /** @var string */
    private $bearerAuth;

    /** @var string */
    private $contentType;

    /** @var string */
    private $responseData;

    /** @var string */
    private $destinationUrl;

    /** @var  \Reach\Payment\Api\Data\HttpResponseInterface */
    private $returnData;

    /** @var integer */
    private $responseCode;

    /** @var \Magento\Framework\HTTP\Adapter\Curl */
    private $curl;


    public function __construct(
        \Magento\Framework\HTTP\Adapter\Curl $curl,
        \Reach\Payment\Api\Data\HttpResponseInterface $returnData
    ) {
        $this->curl        = $curl;
        $this->returnData  = $returnData;
    }


    /**
     * @return integer
     */
    public function getResponseCode()
    {
        return $this->responseCode;
    }

    /**
     * @return string
     */
    public function getResponseData()
    {
        return $this->responseData;
    }

    /**
     * @return \Reach\Payment\Api\Data\HttpResponseInterface
     */
    public function getReturnData()
    {
        return $this->returnData;
    }

    public function setBasicAuth($auth)
    {
        $this->basicAuth = $auth;
    }

    public function setBearerAuth($auth)
    {
        $this->bearerAuth = $auth;
    }

    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    public function setUrl($url)
    {
        $this->destinationUrl = $url;
    }

    public function initialize()
    {
        $config = [
            'timeout'    => 120,
            'verifyhost' => 2,
        ];
        $this->curl->setConfig($config);
    }

    /**
     * @param $body
     * @return \Reach\Payment\Api\Data\HttpResponseInterface
     */
    public function executePost($body)
    {
        $this->initialize();

        $headers=[];
        $headers[]='Content-type: ' . $this->contentType;
        if ($this->basicAuth !== null) {
            $headers[] = 'Authorization: Basic '.$this->basicAuth;
        }
        if ($this->bearerAuth !== null) {
            $headers[] = 'Authorization: Bearer '.$this->bearerAuth;
        }

        $this->curl->write(
            \Zend_Http_Client::POST,
            $this->destinationUrl,
            '1.0',
            $headers,
            $body
        );
        $this->responseData = $this->curl->read();

        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();

        return $this->processResponse();
    }

    /**
     * @return \Reach\Payment\Api\Data\HttpResponseInterface
     */
    public function executeGet()
    {
        $this->initialize();
        $headers=[];
        $headers[]='Content-type: ' . $this->contentType;
        if ($this->basicAuth !== null) {
            $headers[] = 'Authorization: Basic '.$this->basicAuth;
        }
        if ($this->bearerAuth !== null) {
            $headers[] = 'Authorization: Bearer '.$this->bearerAuth;
        }
        $this->curl->write(
            \Zend_Http_Client::GET,
            $this->destinationUrl,
            '1.0',
            $headers
        );
        $this->responseData = $this->curl->read();
        $this->responseCode = $this->curl->getInfo(CURLINFO_HTTP_CODE);
        $this->curl->close();
        return $this->processResponse();
    }

    /**
     * @return \Reach\Payment\Api\Data\HttpResponseInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    abstract public function processResponse();
}
