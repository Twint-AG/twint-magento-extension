<?php

declare(strict_types=1);

namespace Twint\Magento\Block\Adminhtml\Request;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\View\Element\Template;
use Twint\Magento\Model\RequestLog;

class View extends Template
{
    protected $_template = 'Twint_Magento::request/view.phtml';

    private ?RequestLog $entity = null;

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function setEntity(RequestLog $entity)
    {
        $this->entity = $entity;
    }

    public function getEntity(): ?RequestLog
    {
        return $this->entity;
    }

    public function getInvocations(): array
    {
        if (!$this->entity instanceof RequestLog) {
            return [];
        }

        $req = json_decode($this->entity->getSoapRequest(), true);
        $res = json_decode($this->entity->getSoapResponse(), true);
        $actions = json_decode($this->entity->getActions(), true);
        $invocations = [];

        foreach ($req as $key => $value) {
            $invocations[] = [
                'action' => $actions[$key],
                'req' => $value,
                'res' => $res[$key],
            ];
        }

        return $invocations;
    }
}
