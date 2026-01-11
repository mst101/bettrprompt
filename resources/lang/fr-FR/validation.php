<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Lignes de langue de validation
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes contiennent les messages d'erreur par défaut
    | utilisés par la classe validateur. Certaines de ces règles ont plusieurs
    | versions telles que les règles de taille. N'hésitez pas à ajuster
    | chacun de ces messages ici.
    |
    */

    'accepted' => 'Le {attribute} doit être accepté.',
    'accepted_if' => 'Le {attribute} doit être accepté quand {other} est {value}.',
    'active_url' => 'Le {attribute} n\'est pas une URL valide.',
    'after' => 'Le {attribute} doit être une date après {date}.',
    'after_or_equal' => 'Le {attribute} doit être une date après ou égale à {date}.',
    'alpha' => 'Le {attribute} ne peut contenir que des lettres.',
    'alpha_dash' => 'Le {attribute} ne peut contenir que des lettres, des chiffres, des tirets et des traits de soulignement.',
    'alpha_num' => 'Le {attribute} ne peut contenir que des lettres et des chiffres.',
    'array' => 'Le {attribute} doit être un tableau.',
    'ascii' => 'Le {attribute} ne peut contenir que des caractères alphanumériques et des symboles à un octet.',
    'before' => 'Le {attribute} doit être une date avant {date}.',
    'before_or_equal' => 'Le {attribute} doit être une date avant ou égale à {date}.',
    'between' => [
        'array' => 'Le {attribute} doit avoir entre {min} et {max} éléments.',
        'file' => 'Le {attribute} doit être entre {min} et {max} kilooctets.',
        'numeric' => 'Le {attribute} doit être entre {min} et {max}.',
        'string' => 'Le {attribute} doit être entre {min} et {max} caractères.',
    ],
    'boolean' => 'Le champ {attribute} doit être vrai ou faux.',
    'can' => 'Le {attribute} contient une valeur non autorisée.',
    'confirmed' => 'La confirmation du {attribute} ne correspond pas.',
    'current_password' => 'Le mot de passe est incorrect.',
    'date' => 'Le {attribute} n\'est pas une date valide.',
    'date_equals' => 'Le {attribute} doit être une date égale à {date}.',
    'date_format' => 'Le {attribute} ne correspond pas au format {format}.',
    'decimal' => 'Le {attribute} doit avoir {decimal} décimales.',
    'declined' => 'Le {attribute} doit être refusé.',
    'declined_if' => 'Le {attribute} doit être refusé quand {other} est {value}.',
    'different' => 'Le {attribute} et {other} doivent être différents.',
    'digits' => 'Le {attribute} doit être {digits} chiffres.',
    'digits_between' => 'Le {attribute} doit être entre {min} et {max} chiffres.',
    'dimensions' => 'Le {attribute} a des dimensions d\'image invalides.',
    'distinct' => 'Le champ {attribute} a une valeur en double.',
    'email' => 'Le {attribute} doit être une adresse e-mail valide.',
    'ends_with' => 'Le {attribute} doit se terminer par l\'un des éléments suivants : {values}.',
    'exists' => 'Le {attribute} sélectionné est invalide.',
    'file' => 'Le {attribute} doit être un fichier.',
    'filled' => 'Le champ {attribute} doit avoir une valeur.',
    'gt' => [
        'array' => 'Le {attribute} doit avoir plus de {value} éléments.',
        'file' => 'Le {attribute} doit être supérieur à {value} kilooctets.',
        'numeric' => 'Le {attribute} doit être supérieur à {value}.',
        'string' => 'Le {attribute} doit être supérieur à {value} caractères.',
    ],
    'gte' => [
        'array' => 'Le {attribute} doit avoir {value} éléments ou plus.',
        'file' => 'Le {attribute} doit être supérieur ou égal à {value} kilooctets.',
        'numeric' => 'Le {attribute} doit être supérieur ou égal à {value}.',
        'string' => 'Le {attribute} doit être supérieur ou égal à {value} caractères.',
    ],
    'image' => 'Le {attribute} doit être une image.',
    'in' => 'Le {attribute} sélectionné est invalide.',
    'in_array' => 'Le champ {attribute} n\'existe pas dans {other}.',
    'integer' => 'Le {attribute} doit être un entier.',
    'ip' => 'Le {attribute} doit être une adresse IP valide.',
    'ipv4' => 'Le {attribute} doit être une adresse IPv4 valide.',
    'ipv6' => 'Le {attribute} doit être une adresse IPv6 valide.',
    'json' => 'Le {attribute} doit être une chaîne JSON valide.',
    'lowercase' => 'Le {attribute} doit être minuscule.',
    'lt' => [
        'array' => 'Le {attribute} doit avoir moins de {value} éléments.',
        'file' => 'Le {attribute} doit être inférieur à {value} kilooctets.',
        'numeric' => 'Le {attribute} doit être inférieur à {value}.',
        'string' => 'Le {attribute} doit être inférieur à {value} caractères.',
    ],
    'lte' => [
        'array' => 'Le {attribute} ne doit pas avoir plus de {value} éléments.',
        'file' => 'Le {attribute} doit être inférieur ou égal à {value} kilooctets.',
        'numeric' => 'Le {attribute} doit être inférieur ou égal à {value}.',
        'string' => 'Le {attribute} doit être inférieur ou égal à {value} caractères.',
    ],
    'mac_address' => 'Le {attribute} doit être une adresse MAC valide.',
    'max' => [
        'array' => 'Le {attribute} ne peut pas avoir plus de {max} éléments.',
        'file' => 'Le {attribute} ne peut pas être supérieur à {max} kilooctets.',
        'numeric' => 'Le {attribute} ne peut pas être supérieur à {max}.',
        'string' => 'Le {attribute} ne peut pas être supérieur à {max} caractères.',
    ],
    'mimes' => 'Le {attribute} doit être un fichier de type : {values}.',
    'mimetypes' => 'Le {attribute} doit être un fichier de type : {values}.',
    'min' => [
        'array' => 'Le {attribute} doit avoir au moins {min} éléments.',
        'file' => 'Le {attribute} doit être au moins {min} kilooctets.',
        'numeric' => 'Le {attribute} doit être au moins {min}.',
        'string' => 'Le {attribute} doit être au moins {min} caractères.',
    ],
    'multiple_of' => 'Le {attribute} doit être un multiple de {value}.',
    'not_in' => 'Le {attribute} sélectionné est invalide.',
    'not_regex' => 'Le format du {attribute} est invalide.',
    'numeric' => 'Le {attribute} doit être un nombre.',
    'password' => 'Le mot de passe est incorrect.',
    'present' => 'Le champ {attribute} doit être présent.',
    'regex' => 'Le format du {attribute} est invalide.',
    'required' => 'Le champ {attribute} est requis.',
    'required_array_keys' => 'Le champ {attribute} doit contenir des entrées pour : {values}.',
    'required_if' => 'Le champ {attribute} est requis quand {other} est {value}.',
    'required_if_accepted' => 'Le champ {attribute} est requis quand {other} est accepté.',
    'required_unless' => 'Le champ {attribute} est requis à moins que {other} ne soit dans {values}.',
    'required_with' => 'Le champ {attribute} est requis quand {values} est présent.',
    'required_with_all' => 'Le champ {attribute} est requis quand {values} sont présents.',
    'required_without' => 'Le champ {attribute} est requis quand {values} n\'est pas présent.',
    'required_without_all' => 'Le champ {attribute} est requis quand aucun de {values} ne sont présents.',
    'same' => 'Le {attribute} et {other} doivent correspondre.',
    'size' => [
        'array' => 'Le {attribute} doit contenir {size} éléments.',
        'file' => 'Le {attribute} doit être {size} kilooctets.',
        'numeric' => 'Le {attribute} doit être {size}.',
        'string' => 'Le {attribute} doit être {size} caractères.',
    ],
    'starts_with' => 'Le {attribute} doit commencer par l\'un des éléments suivants : {values}.',
    'string' => 'Le {attribute} doit être une chaîne.',
    'timezone' => 'Le {attribute} doit être une zone valide.',
    'unique' => 'Le {attribute} a déjà été utilisé.',
    'uploaded' => 'Le {attribute} n\'a pas pu être téléchargé.',
    'uppercase' => 'Le {attribute} doit être en majuscules.',
    'url' => 'Le format du {attribute} est invalide.',
    'ulid' => 'Le {attribute} doit être un ULID valide.',
    'uuid' => 'Le {attribute} doit être un UUID valide.',

    /*
    |--------------------------------------------------------------------------
    | Lignes de langue de validation personnalisée
    |--------------------------------------------------------------------------
    |
    | Ici, vous pouvez spécifier des messages de validation personnalisés pour
    | les attributs en utilisant la convention « attribute.rule » pour nommer
    | les lignes. Cela permet de spécifier rapidement une ligne de langue
    | personnalisée pour une règle d'attribut donnée.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attributs de validation personnalisée
    |--------------------------------------------------------------------------
    |
    | Les lignes de langue suivantes sont utilisées pour remplacer notre
    | espace réservé d'attribut par quelque chose de plus convivial
    | comme « Adresse e-mail » au lieu de « email ». Cela nous aide
    | simplement à rendre les messages un peu plus propres.
    |
    */

    'attributes' => [],
];
