<?php


namespace App\Service;


class ValidatorOutputFormatter
{
    public function formatOutput($errors): array
    {
        $messages = [];
        $i = 0;
        foreach ($errors as $violation) {

            $messages[$i]['field'] = $violation->getPropertyPath();
            $messages[$i]['message']= $violation->getMessage();

            $i++;
        }

        return $messages;
    }
}