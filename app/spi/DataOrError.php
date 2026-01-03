<?php

namespace App\spi;

class DataOrError
{
    public $data;
    public $errors;
    public bool $isError;

    public function __construct($data, $errors, $isError)
    {
        $this->data = $data;
        $this->errors = $errors;
        $this->isError = $isError;
    }

    public function isValid()
    {
        return !$this->isError && $this->data !== null && $this->errors === null;
    }

}
