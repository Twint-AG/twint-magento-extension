<?php
namespace Twint\Core\Validator\Certificate;

class CertificateFileValidator{
    const ALLOWED_EXT = 'application/x-pkcs12';
    const ALLOWED_SIZE = 5000; //Kb

    const ERR_BLANK = 'Certificate file is required';
    const ERR_INVALID_EXT = 'Only allow file in .p12 format';
    const ERR_INVALID_SIZE = 'Maximum file size is 5Mb';

    public function validate(array $file){
        if(empty($file)){
            return self::ERR_BLANK;
        }

        if($file['type'] != self::ALLOWED_EXT){
            return self::ERR_INVALID_EXT;
        }

        if($file['size'] > self::ALLOWED_SIZE){
            return self::ERR_INVALID_SIZE;
        }

        return true;
    }
}
