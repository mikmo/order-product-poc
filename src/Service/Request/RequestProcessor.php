<?php

namespace App\Service\Request;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RequestProcessor
{
    private SerializerInterface $serializer;
    private ValidatorInterface $validator;

    public function __construct(SerializerInterface $serializer, ValidatorInterface $validator)
    {
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    /**
     * Deserializza e valida la richiesta in un oggetto DTO
     *
     * @param Request $request La richiesta HTTP
     * @param string $dtoClass Il nome della classe DTO da utilizzare
     * @param string $format Il formato di deserializzazione (default: json)
     * @return array ['data' => object, 'errors' => array|null]
     */
    public function processRequest(Request $request, string $dtoClass, string $format = 'json'): array
    {
        $content = $request->getContent();

        try {
            // Deserializza il contenuto della richiesta nella classe DTO specificata
            $dto = $this->serializer->deserialize($content, $dtoClass, $format);

            // Valida l'oggetto DTO
            $violations = $this->validator->validate($dto);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $propertyPath = $violation->getPropertyPath();
                    $errors[$propertyPath] = $violation->getMessage();
                }

                return ['data' => null, 'errors' => $errors];
            }

            return ['data' => $dto, 'errors' => null];
        } catch (\Exception $e) {
            return [
                'data' => null,
                'errors' => ['_format' => 'Formato dei dati non valido: ' . $e->getMessage()]
            ];
        }
    }
}
