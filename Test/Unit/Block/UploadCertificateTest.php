<?php

declare(strict_types=1);

namespace Twint\Magento\Test\Unit\Block;

use Mockery;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
class UploadCertificateTest extends TestCase
{
    protected function tearDown(): void
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
        $mock->shouldReceive('getEscapedValue')
            ->andReturn('value');
        $mock->shouldReceive('getHtmlId')
            ->andReturn($htmlId);
        $mock->shouldReceive('getName')
            ->andReturn($name);
        $mock->shouldReceive('_getUiId')
            ->andReturn($uiId);
        $mock->shouldReceive('getHtmlAttributes')
            ->andReturn(['checked']);
        $mock->shouldReceive('serialize')
            ->andReturn('serialized');


        $result = $mock->getElementHtml();

        self::assertStringContainsString('<div id="twint-certificate-container">', $result);
        self::assertStringContainsString('<input type="hidden" id="' . $htmlId . '" name="' . $name . '"', $result);
        self::assertStringContainsString($uiId, $result);
        self::assertStringContainsString('requirejs(["twintCertificateUpload"]', $result);
        self::assertStringContainsString('Only .p12 files are allowed', $result);
        self::assertStringContainsString('Certificate password is required', $result);
        self::assertStringContainsString('Certificate encrypted and stored,', $result);
        self::assertStringContainsString('Upload new certificate', $result);
    }
}
