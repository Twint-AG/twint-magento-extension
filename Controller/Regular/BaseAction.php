<?php

namespace Twint\Magento\Controller\Regular;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ActionInterface;

class BaseAction extends Action implements ActionInterface, HttpPostActionInterface
{

    protected function getPostData(): array
    {
        try {
            $data = json_decode($this->getRequest()->getContent(), true);
        }
        catch (\Throwable $e){
            $data = [];
        }

        return $data;
    }

    public function execute(){}
}
