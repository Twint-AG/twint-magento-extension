<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Form;

use Magento\Backend\Model\UrlInterface;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Data\Form\Element\CollectionFactory;
use Magento\Framework\Data\Form\Element\Factory;
use Magento\Framework\Escaper;
use Magento\Framework\Math\Random;
use Magento\Framework\View\Helper\SecureHtmlRenderer;

class CertificateUpload extends AbstractElement
{
    public function __construct(
        private readonly UrlInterface $backendUrl,
        Factory $factoryElement,
        CollectionFactory $factoryCollection,
        Escaper $escaper,
        $data = [],
        ?SecureHtmlRenderer $secureRenderer = null,
        ?Random $random = null
    ) {
        parent::__construct($factoryElement, $factoryCollection, $escaper, $data, $secureRenderer, $random);
    }

    public function getElementHtml(): string
    {
        $value = $this->getEscapedValue();
        $hiddenInput = '<input type="hidden" id="' . $this->getHtmlId() . '" name="' . $this->getName() . '" ' . $this->_getUiId()
            . ' value="' . $value . '" ' . $this->serialize($this->getHtmlAttributes()) . '/>';

        return '<div id="twint-certificate-container" 
                data-upload-url="' . $this->backendUrl->getRouteUrl('twint/certificate/upload') . '"
                data-validation-url="' . $this->backendUrl->getRouteUrl('twint/credentials/validation') . '"
                data-value-url="' . $this->backendUrl->getRouteUrl('twint/credentials/values') . '"
            > 
            <script>
                requirejs(["twintCertificateUpload"], function($){});
            </script>
            ' . $hiddenInput . '
            
            <div id="twint-certificate-input-container" class="d-none">
                <div class="field certificate-upload">
                    <div class="control">
                        <div class="file-upload-container">
                            <label class="file-upload">
                                <input type="file" id="certificate-file" accept=".p12"/>
                                <span class="file-upload-btn">' . __('Choose File') . '</span>
                                <span class="file-upload-label" 
                                    id="certificate-file-name"
                                    data-empty-title="' . __('Only .p12 files are allowed') . '"
                                >' . __('Only .p12 files are allowed') . '</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="field certificate-password">
                    <div class="control">
                        <input type="password" id="certificate-password" name="certificate_password">
                    </div>
                    <div class="note">
                        ' . __('Certificate password is required') . '                    
                    </div>
                </div>
                
                <div id="certificate-error-container" class="d-none message mage-error message-warning">
                </div>
            </div>
            <div id="twint-certificate-loaded-container" class="d-none">
                ' . __('Certificate encrypted and stored,') . '
                <span id="twint-upload-new">' . __('Upload new certificate') . '</span>
            </div>
        </div>';
    }
}
