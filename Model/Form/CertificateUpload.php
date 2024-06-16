<?php
namespace Twint\Core\Model\Form;

use Magento\Framework\Data\Form\Element\AbstractElement;

class CertificateUpload extends AbstractElement
{
    public function getElementHtml()
    {
        $value = $this->getEscapedValue();
        $hiddenInput = '<input type="hidden" id="' . $this->getHtmlId() . '" name="' . $this->getName() . '" ' . $this->_getUiId()
            . ' value="' . $value . '" ' . $this->serialize($this->getHtmlAttributes()) . '/>';

        return '<div id="twint-certificate-container"> 
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
                                <span class="file-upload-btn">'.__("Chose File") .'</span>
                                <span class="file-upload-label" 
                                    id="certificate-file-name"
                                    data-empty-title="'.__("Only .p12 files are allowed.") .'"
                                >'.__("Only .p12 files are allowed.") .'</span>
                            </label>
                        </div>
                    </div>
                </div>
                
                <div class="field certificate-password">
                    <div class="control">
                        <input type="password" id="certificate-password" name="certificate_password">
                    </div>
                    <div class="note">
                        '.__("Password is required for all TWINT certificate.") .'                    
                    </div>
                </div>
                
                <div id="certificate-error-container" class="d-none message mage-error message-warning">
                </div>
            </div>
            <div id="twint-certificate-loaded-container" class="d-none">
                ' . __("Certificate are loaded and encrypted successfully,") .'
                <span id="twint-upload-new">' . __("upload a new") .'</span>
            </div>
        </div>';
    }
}
