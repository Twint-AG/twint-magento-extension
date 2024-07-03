<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Frontend;

use Magento\Framework\View\Element\Template;
use Twint\Magento\Service\AppsService;

class ScanQrModal extends Template
{
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

    public function getLinks(): string
    {
        $links = $this->appService->getLinks($this->_storeManager->getStore()->getId());

        $isAndroid = isset($links['android']);
        $isIos = isset($links['ios']);

        $html = '';

        if ($isAndroid) {
            $html .= '<div class="text-center mt-4 px-4">
                        <a id="twint-addroid-button" class="block mb-1 w-full bg-black text-white font-bold p-4 rounded-lg text-center hover:bg-gray-800 focus:outline-none focus:ring-gray-600 focus:ring-opacity-75
                            hover:text-white hover:no-underline
                        "
                           href="javascript:window.location = ' . $links['android'] . '">
                            ' . __('Switch to TWINT app now') . '
                        </a>
                    </div>';
        }

        if ($isIos) {
            $prefinedApps = [
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
                $icon = $prefinedApps[$link['name']] ?? null;
                if ($icon) {
                    $app .= '<img src="' . $this->getViewFileUrl("Twint_Magento/images/apps/{$icon}.png") . '" 
                    class="shadow-2xl w-64 h-64 rounded-3xl mx-auto"
                    data-link="' . $link['link'] . '"
                    alt="' . $link['name'] . '">';
                } else {
                    $else .= '<option value="' . $link['link'] . '">' . $link['name'] . '</option>';
                }
            }

            $html .= '
                <div id="twint-ios-container">
                    <div class="my-6 text-center">
                        ' . __('Select your TWINT app:') . '
                    </div>
        
                    <div class="twint-app-container w-3/4 mx-auto justify-center max-w-screen-md mx-auto grid grid-cols-3 gap-4">
                        ' . $app . '
                    </div>
                    
                    <select class="h-55 block my-4 w-full p-4 bg-white text-center appearance-none border-none focus:outline-none focus:ring-0">
                        <option>' . __('Other banks') . '</option>
                        ' . $else . '
                    </select>    
                </div>        
            ';
        }

        if ($isIos || $isAndroid) {
            $html .= '
                <div class="text-center md:hidden">
                    <div class="flex items-center justify-center my-4">
                        <div class="flex-grow border-t border-gray-300"></div>
                        <span class="mx-4 text-black">' . __('or') . '</span>
                        <div class="flex-grow border-t border-gray-300"></div>
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
