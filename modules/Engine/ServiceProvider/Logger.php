<?php

namespace App\Engine\ServiceProvider;


use App\Engine\Models\Order;
use App\Engine\Validator\OrderValidator;
use JetBrains\PhpStorm\Pure;
use Symfony\Component\HttpFoundation\Request;

class Logger
{

    private $errorLog;

    public function __construct($logFileName=''){

        if ($logFileName === '' || !file_exists($logFileName)){

            $this->errorLog = $_SERVER['DOCUMENT_ROOT'] . '/logs/errol.log';

        }else{

            $this->errorLog = $logFileName;

        }

    }

    public function logError($message){

        file_put_contents($this->errorLog, " $message\n\r " , FILE_APPEND);

    }

}