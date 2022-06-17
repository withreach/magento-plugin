<?php

namespace Reach\Payment\Webapi\Rest\Request\Deserializer;


use Magento\Framework\App\State;
use Magento\Framework\Phrase;
use Reach\Payment\Model\PostbackNotification\Decoder;

class XWwwFormUrlencoded implements \Magento\Framework\Webapi\Rest\Request\DeserializerInterface
{

    /** @var Decoder */
    protected $decoder;

    /**
     * @var State
     */
    protected $_appState;

    /**
     * @param Decoder $decoder
     * @param \Magento\Framework\App\State $appState
     */
    public function __construct(Decoder $decoder, State $appState)
    {
        $this->decoder = $decoder;
        $this->_appState = $appState;
    }

    /**
     * Parse Request body into array of params.
     *
     * @param string $encodedBody Posted content from request.
     * @return array|null Return NULL if content is invalid.
     * @throws \Exception
     * @throws \Magento\Framework\Webapi\Exception If decoding error was encountered.
     */
    public function deserialize($encodedBody)
    {
        if (!is_string($encodedBody)) {
            throw new \InvalidArgumentException(
                sprintf('"%s" data type is invalid. String is expected.', gettype($encodedBody))
            );
        }
        try {
            $decodedBody = $this->decoder->decode($encodedBody);
        } catch (\Exception $e) {
            if ($this->_appState->getMode() !== State::MODE_DEVELOPER) {
                throw new \Magento\Framework\Webapi\Exception(new Phrase('Decoding error.'));
            } else {
                throw new \Magento\Framework\Webapi\Exception(
                    new Phrase(
                        'Decoding error: %1%2%3%4',
                        [PHP_EOL, $e->getMessage(), PHP_EOL, $e->getTraceAsString()]
                    )
                );
            }
        }
        return $decodedBody;
    }
}