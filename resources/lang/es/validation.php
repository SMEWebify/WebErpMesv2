<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Líneas de idioma para la Validación
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma contienen los mensajes de error predeterminados utilizados por
    | la clase de validación. Algunas de estas reglas tienen varias versiones, como
    | las reglas de tamaño. No dude en ajustar cada uno de estos mensajes aquí.
    |
    */

    'accepted' => 'El campo :attribute debe ser aceptado.',
    'accepted_if' => 'El campo :attribute debe ser aceptado cuando :other es :value.',
    'active_url' => 'El campo :attribute no es una URL válida.',
    'after' => 'El campo :attribute debe ser una fecha posterior a :date.',
    'after_or_equal' => 'El campo :attribute debe ser una fecha posterior o igual a :date.',
    'alpha' => 'El campo :attribute solo puede contener letras.',
    'alpha_dash' => 'El campo :attribute solo puede contener letras, números, guiones y guiones bajos.',
    'alpha_num' => 'El campo :attribute solo puede contener letras y números.',
    'array' => 'El campo :attribute debe ser un arreglo.',
    'before' => 'El campo :attribute debe ser una fecha anterior a :date.',
    'before_or_equal' => 'El campo :attribute debe ser una fecha anterior o igual a :date.',
    'between' => [
        'array' => 'El campo :attribute debe tener entre :min y :max elementos.',
        'file' => 'El campo :attribute debe tener entre :min y :max kilobytes.',
        'numeric' => 'El campo :attribute debe estar entre :min y :max.',
        'string' => 'El campo :attribute debe tener entre :min y :max caracteres.',
    ],
    'boolean' => 'El campo :attribute debe ser verdadero o falso.',
    'confirmed' => 'La confirmación del campo :attribute no coincide.',
    'current_password' => 'La contraseña actual es incorrecta.',
    'date' => 'El campo :attribute no es una fecha válida.',
    'date_equals' => 'El campo :attribute debe ser una fecha igual a :date.',
    'date_format' => 'El campo :attribute no coincide con el formato :format.',
    'declined' => 'El campo :attribute debe ser declinado.',
    'declined_if' => 'El campo :attribute debe ser declinado cuando :other es :value.',
    'different' => 'El campo :attribute y :other deben ser diferentes.',
    'digits' => 'El campo :attribute debe tener :digits dígitos.',
    'digits_between' => 'El campo :attribute debe tener entre :min y :max dígitos.',
    'dimensions' => 'El campo :attribute tiene dimensiones de imagen no válidas.',
    'distinct' => 'El campo :attribute tiene un valor duplicado.',
    'doesnt_end_with' => 'El campo :attribute no debe terminar con ninguno de los siguientes valores: :values.',
    'doesnt_start_with' => 'El campo :attribute no debe comenzar con ninguno de los siguientes valores: :values.',
    'email' => 'El campo :attribute debe ser una dirección de correo electrónico válida.',
    'ends_with' => 'El campo :attribute debe terminar con uno de los siguientes valores: :values.',
    'enum' => 'El :attribute seleccionado es inválido.',
    'exists' => 'El :attribute seleccionado es inválido.',
    'file' => 'El campo :attribute debe ser un archivo.',
    'filled' => 'El campo :attribute debe tener un valor.',
    'gt' => [
        'array' => 'El campo :attribute debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe ser mayor que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor que :value.',
        'string' => 'El campo :attribute debe ser mayor que :value caracteres.',
    ],
    'gte' => [
        'array' => 'El campo :attribute debe tener :value elementos o más.',
        'file' => 'El campo :attribute debe ser mayor o igual que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser mayor o igual que :value.',
        'string' => 'El campo :attribute debe ser mayor o igual que :value caracteres.',
    ],
    'image' => 'El campo :attribute debe ser una imagen.',
    'in' => 'El :attribute seleccionado es inválido.',
    'in_array' => 'El campo :attribute no existe en :other.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'ip' => 'El campo :attribute debe ser una dirección IP válida.',
    'ipv4' => 'El campo :attribute debe ser una dirección IPv4 válida.',
    'ipv6' => 'El campo :attribute debe ser una dirección IPv6 válida.',
    'json' => 'El campo :attribute debe ser una cadena JSON válida.',
    'lowercase' => 'El campo :attribute debe estar en minúsculas.',
    'lt' => [
        'array' => 'El campo :attribute debe tener menos de :value elementos.',
        'file' => 'El campo :attribute debe ser menor que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor que :value.',
        'string' => 'El campo :attribute debe ser menor que :value caracteres.',
    ],
    'lte' => [
        'array' => 'El campo :attribute no debe tener más de :value elementos.',
        'file' => 'El campo :attribute debe ser menor o igual que :value kilobytes.',
        'numeric' => 'El campo :attribute debe ser menor o igual que :value.',
        'string' => 'El campo :attribute debe ser menor o igual que :value caracteres.',
    ],
    'mac_address' => 'El campo :attribute debe ser una dirección MAC válida.',
    'max' => [
        'array' => 'El campo :attribute no debe tener más de :max elementos.',
        'file' => 'El campo :attribute no debe ser mayor que :max kilobytes.',
        'numeric' => 'El campo :attribute no debe ser mayor que :max.',
        'string' => 'El campo :attribute no debe ser mayor que :max caracteres.',
    ],
    'max_digits' => 'El campo :attribute no debe tener más de :max dígitos.',
    'mimes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'mimetypes' => 'El campo :attribute debe ser un archivo de tipo: :values.',
    'min' => [
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'file' => 'El campo :attribute debe ser al menos de :min kilobytes.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'string' => 'El campo :attribute debe ser al menos de :min caracteres.',
    ],
    'min_digits' => 'El campo :attribute debe tener al menos :min dígitos.',
    'multiple_of' => 'El campo :attribute debe ser un múltiplo de :value.',
    'not_in' => 'El :attribute seleccionado es inválido.',
    'not_regex' => 'El formato del campo :attribute es inválido.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'password' => [
        'letters' => 'El campo :attribute debe contener al menos una letra.',
        'mixed' => 'El campo :attribute debe contener al menos una letra mayúscula y una letra minúscula.',
        'numbers' => 'El campo :attribute debe contener al menos un número.',
        'symbols' => 'El campo :attribute debe contener al menos un símbolo.',
        'uncompromised' => 'El :attribute proporcionado ha aparecido en una violación de datos. Elija un :attribute diferente.',
    ],
    'present' => 'El campo :attribute debe estar presente.',
    'prohibited' => 'El campo :attribute está prohibido.',
    'prohibited_if' => 'El campo :attribute está prohibido cuando :other es :value.',
    'prohibited_unless' => 'El campo :attribute está prohibido a menos que :other esté en :values.',
    'prohibits' => 'El campo :attribute prohíbe que :other esté presente.',
    'regex' => 'El formato del campo :attribute es inválido.',
    'required' => 'El campo :attribute es obligatorio.',
    'required_array_keys' => 'El campo :attribute debe contener entradas para :values.',
    'required_if' => 'El campo :attribute es obligatorio cuando :other es :value.',
    'required_if_accepted' => 'El campo :attribute es obligatorio cuando :other es aceptado.',
    'required_unless' => 'El campo :attribute es obligatorio a menos que :other esté en :values.',
    'required_with' => 'El campo :attribute es obligatorio cuando :values está presente.',
    'required_with_all' => 'El campo :attribute es obligatorio cuando :values están presentes.',
    'required_without' => 'El campo :attribute es obligatorio cuando :values no está presente.',
    'required_without_all' => 'El campo :attribute es obligatorio cuando ninguno de :values está presente.',
    'same' => 'El campo :attribute y :other deben coincidir.',
    'size' => [
        'array' => 'El campo :attribute debe contener :size elementos.',
        'file' => 'El campo :attribute debe ser de :size kilobytes.',
        'numeric' => 'El campo :attribute debe ser :size.',
        'string' => 'El campo :attribute debe tener :size caracteres.',
    ],
    'starts_with' => 'El campo :attribute debe comenzar con uno de los siguientes valores: :values.',
    'string' => 'El campo :attribute debe ser una cadena de caracteres.',
    'timezone' => 'El campo :attribute debe ser una zona horaria válida.',
    'unique' => 'El campo :attribute ya ha sido tomado.',
    'uploaded' => 'El campo :attribute no pudo ser cargado.',
    'uppercase' => 'El campo :attribute debe estar en mayúsculas.',
    'url' => 'El campo :attribute debe ser una URL válida.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Aquí puedes especificar mensajes de validación personalizados para los atributos usando
    | la convención "attribute.rule" para nombrar las líneas. Esto permite
    | especificar rápidamente un mensaje personalizado específico para una regla de atributo dada.
    |
    */

    'custom' => [
        'nombre-del-atributo' => [
            'nombre-de-la-regla' => 'mensaje-personalizado',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | Las siguientes líneas de idioma se utilizan para reemplazar nuestros marcadores de posición de atributo
    | por algo más amigable para el lector, como "Correo electrónico" en lugar de "email".
    | Esto simplemente ayuda a hacer nuestro mensaje más expresivo.
    |
    */

    'attributes' => [],

];