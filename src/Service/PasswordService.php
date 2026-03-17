<?php

namespace App\Service;


class PasswordService
{
    public function __construct()
    {
    }

    public function passwordHasRequirements(string $password):bool
    {
        return preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[\.\\\@$!£^%_#+()*?&\/\-\[\]\{\}])[A-Za-z\d\.\\\@$!£%^_#+()*?&\/\-\[\]\{\}]{8,}$/", $password);

    }
}
