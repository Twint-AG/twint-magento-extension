<?php

// Include the Magento autoloader
require_once realpath(__DIR__ . '/../../app/Mage.php');
//
//// Initialize Magento
Mage::app('default');

// Set up PHPUnit autoloader if needed
if (!class_exists('PHPUnit\Framework\TestCase', true)) {
    require_once 'PHPUnit/Autoload.php';
}

// Add your custom autoloader for test classes if needed
spl_autoload_register(function ($class) {
    $file = str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require $file;
        return true;
    }
    return false;
});

// Set up any additional configuration or mocks needed for your tests

// Clean up after tests if necessary
register_shutdown_function(function() {
    // Perform any cleanup tasks
});
