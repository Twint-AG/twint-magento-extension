<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Twint\Magento\Service\AppsService;

class ScanQrModal extends Template
{
    private array $links = [];

    public function __construct(
        private readonly AppsService $appService,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function getContent(): string
    {
        return '<div id="modal">Demo</div>';
    }

    public function getStoreName(): string
    {
        return $this->_storeManager->getStore()
            ->getName();
    }

    public function getMobileClass(): string
    {
        return $this->getIsMobile() ? 'tw-mobile' : '';
    }

    public function getDisplayClass(): string
    {
        return $this->getIsMobile() ? 'hidden' : '';
    }

    public function getIsMobile(): bool
    {
        if ($this->links === []) {
            $this->links = $this->appService->getLinks((string) $this->_storeManager->getStore()->getId());
        }

        return isset($this->links['android']) || isset($this->links['ios']);
    }

    public function getLinks(): string
    {
        if ($this->links === []) {
            $this->links = $this->appService->getLinks((string) $this->_storeManager->getStore()->getId());
        }

        $links = $this->links;

        $isAndroid = isset($links['android']);
        $isIos = isset($links['ios']);

        $mobile = $isAndroid || $isIos;

        $html = '';

        if ($isAndroid) {
            $html .= '<div class="text-center mt-4 px-4">
                        <a id="twint-addroid-button" class="block mb-1 bg-black text-white font-bold p-4 rounded-lg text-center hover:bg-gray-800 focus:outline-none focus:ring-gray-600 focus:ring-opacity-75
                            hover:text-white hover:no-underline
                        "
                           data-href="javascript:window.location = \'' . htmlentities($links['android']) . '\'"
                           href="#"
                           >
                            ' . __('Switch to TWINT app now') . '
                        </a>
                    </div>';
        }

        if ($isIos) {
            $refinedApps = [
                'UBS TWINT' => 'bank-ubs',
                'Raiffeisen TWINT' => 'bank-raiffeisen',
                'PostFinance TWINT' => 'bank-pf',
                'ZKB TWINT' => 'bank-zkb',
                'Credit Suisse TWINT' => 'bank-cs',
                'BCV TWINT' => 'bank-bcv',
            ];

            $app = '';
            $else = '';

            foreach ($links['ios'] as $link) {
                $icon = $refinedApps[$link['name']] ?? null;
                if ($icon) {
                    $app .= '<img src="' . $this->getViewFileUrl("Twint_Magento/images/apps/{$icon}.png") . '" 
                    class="shadow-2xl w-64 h-64 rounded-3xl mx-auto"
                    data-link="' . htmlentities($link['link']) . '"
                    alt="' . htmlentities($link['name']) . '">';
                } else {
                    $else .= '<option value="' . htmlentities($link['link']) . '">' . htmlentities(
                        $link['name']
                    ) . '</option>';
                }
            }

            $html .= '
                <div id="twint-ios-container">
                    <div class="my-6 text-center">
                        ' . __('Choose your TWINT app:') . '
                    </div>
        
                    <div class="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                        ' . $app . '
                    </div>
                    
                    <select class="twint-select h-55 block my-4 w-full p-4 bg-white text-center appearance-none border-none focus:outline-none focus:ring-0">
                        <option>' . __('Other banks') . '</option>
                        ' . $else . '
                    </select>    
                </div>        
            ';
        }

        if ($mobile) {
            $html .= '
                <div class="default-hidden text-center">
                    <div class="flex items-center justify-center mx-4">
                        <div class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                        <span class="mx-4 text-black">' . __('or') . '</span>
                        <div class="flex-grow border-b-0 border-t border-solid border-gray-300"></div>
                    </div>
    
                    <div class="row qr-code my-3">
                        <div class="col-9 text-center">' . __('Enter this code in your TWINT app:') . '</div>
                    </div>
                </div>
            ';
        }

        return $html;
    }
}
