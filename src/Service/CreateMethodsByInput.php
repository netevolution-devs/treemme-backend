<?php

namespace App\Service;

class CreateMethodsByInput
{
    /**
     * set attribute and value of given data to given entity
     *
     * if strict == true return error string if attribute not in entity
     * else, pass and return set entity
     *
     */
    public function createMethods( $entity, array $data): object|string
    {
        $client_data = [];
        foreach ($data as $field=>$value) {
            $client_data[lcfirst(str_replace('_', '', ucwords($field, '_')))] = $value;
        }


        $attributes = get_class_methods($entity);

        $keys = array_keys($client_data);

        foreach ($keys as $key) {
            if (!in_array('set' . ucfirst($key), $attributes)) {

                return 'Field not found: ' .$key;
            }
        }


        foreach ($client_data as $field=>$value) {
            if (\DateTime::createFromFormat('Y-m-d', substr($value,0,10))
                || \DateTime::createFromFormat('d/m/Y', substr($value,0,10))
                || \DateTime::createFromFormat('H:i', substr($value,0,10))
                || \DateTime::createFromFormat('H:i:s', substr($value,0,10))
            ) {
                $value = new \DateTime($value);
            } else {
                $value = $this->detectCommaFloat($value);
            }

            if (in_array('set' . ucfirst($field),get_class_methods($entity))) {
                $entity->{'set' . ucfirst($field)}($value);
            }
        }

        return $entity;
    }

    private function detectCommaFloat($value) {

        $value_to_number = str_replace(',', '.',$value);

        return is_numeric($value_to_number) ? (float)$value_to_number : $value;
    }
}