<?php

declare(strict_types=1);

namespace Twint\Magento\Test\Unit\Block;

use Mockery;
use PHPUnit\Framework\TestCase;

class UploadCertificateTest extends TestCase
{
    function tearDown(): void
    {
        Mockery::close();
    }

    public function testGetElementHtml()
    {
        $htmlId = 'test_id';
        $name = 'test_name';
        $value = 'test_value';
        $uiId = 'ui-id-1';

        $mock = Mockery::mock('Twint\Magento\Block\Adminhtml\Form\CertificateUpload')->makePartial();
        $mock->shouldAllowMockingProtectedMethods();
        $mock->shouldReceive('getEscapedValue')->andReturn('value');
        $mock->shouldReceive('getHtmlId')->andReturn($htmlId);
        $mock->shouldReceive('getName')->andReturn($name);
        $mock->shouldReceive('_getUiId')->andReturn($uiId);
        $mock->shouldReceive('getHtmlAttributes')->andReturn(['checked']);
        $mock->shouldReceive('serialize')->andReturn('serialized');


        $result = $mock->getElementHtml();

        $this->assertStringContainsString('<div id="twint-certificate-container">', $result);
        $this->assertStringContainsString('<input type="hidden" id="' . $htmlId . '" name="' . $name . '"', $result);
        $this->assertStringContainsString($uiId, $result);
        $this->assertStringContainsString('requirejs(["twintCertificateUpload"]', $result);
        $this->assertStringContainsString('Only .p12 files are allowed', $result);
        $this->assertStringContainsString('Certificate password is required', $result);
        $this->assertStringContainsString('Certificate encrypted and stored,', $result);
        $this->assertStringContainsString('Upload new certificate', $result);
    }
}
