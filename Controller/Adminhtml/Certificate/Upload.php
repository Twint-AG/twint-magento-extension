<?php

declare(strict_types=1);

namespace Twint\Magento\Controller\Adminhtml\Certificate;

use Exception;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Twint\Magento\Util\CertificateReader;
use Twint\Magento\Util\CryptoHandler;
use Twint\Magento\Validator\Input\CertificateFileValidator;
use Twint\Sdk\Certificate\Pkcs12Certificate;

class Upload extends Action implements ActionInterface, HttpPostActionInterface
{
    protected JsonFactory $jsonFactory;

    protected Http $request;

    protected CertificateFileValidator $inputValidator;

    protected CertificateReader $certificateReader;

    protected CryptoHandler $crypto;

    public function __construct(
        Action\Context $context,
        JsonFactory $jsonFactory,
        Http $request,
        CertificateFileValidator $fileValidator,
        CertificateReader $certificateReader,
        CryptoHandler $crypto
    ) {
        parent::__construct($context);
        $this->jsonFactory = $jsonFactory;
        $this->request = $request;
        $this->inputValidator = $fileValidator;
        $this->certificateReader = $certificateReader;
        $this->crypto = $crypto;
    }

    public function _isAllowed(): bool
    {
        return $this->_authorization->isAllowed('Twint_Magento::payment');
    }

    public function dispatch(RequestInterface $request)
    {
        return $this->execute();
    }

    public function execute()
    {
        $resultJson = $this->jsonFactory->create();
        try {
            $file = $this->request->getFiles()['file'] ?? [];
            $password = $this->request->get('password') ?? '';

            $errors = $this->inputValidator->validate($file, $password);

            if ($errors !== []) {
                return $resultJson->setData([
                    'success' => false,
                    'message' => __('Invalid input'),
                    'errors' => $errors,
                ]);
            }

            $fileContent = file_get_contents($file['tmp_name']);
            $certificate = $this->certificateReader->read($fileContent, $password);

            if ($certificate instanceof Pkcs12Certificate) {
                return $resultJson->setData([
                    'success' => true,
                    'message' => __('Certificate validation successful'),
                    'data' => [
                        'certificate' => $this->crypto->encrypt($certificate->content()),
                        'passphrase' => $this->crypto->encrypt($certificate->passphrase()),
                    ],
                ]);
            }

            return $resultJson->setData([
                'success' => false,
                'message' => __($certificate),
            ]);
        } catch (LocalizedException $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'message' => __('Something went wrong while processing the data.'),
            ]);
        }
    }
}
