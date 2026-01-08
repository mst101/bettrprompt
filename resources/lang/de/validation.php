<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Sprachzeilen für Validierung
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen enthalten die Standard-Fehlermeldungen, die von
    | der Validatorklasse verwendet werden. Einige dieser Regeln haben mehrere
    | Versionen, wie z. B. die Grössenregeln. Ändern Sie gerne jede dieser
    | Meldungen hier ab.
    |
    */

    'accepted' => 'Das Feld {attribute} muss akzeptiert werden.',
    'accepted_if' => 'Das Feld {attribute} muss akzeptiert werden, wenn {other} ist {value}.',
    'active_url' => 'Das Feld {attribute} ist keine gültige URL.',
    'after' => 'Das Feld {attribute} muss ein Datum nach {date} sein.',
    'after_or_equal' => 'Das Feld {attribute} muss ein Datum nach oder gleich {date} sein.',
    'alpha' => 'Das Feld {attribute} darf nur Buchstaben enthalten.',
    'alpha_dash' => 'Das Feld {attribute} darf nur Buchstaben, Ziffern, Bindestriche und Unterstriche enthalten.',
    'alpha_num' => 'Das Feld {attribute} darf nur Buchstaben und Ziffern enthalten.',
    'array' => 'Das Feld {attribute} muss ein Array sein.',
    'ascii' => 'Das Feld {attribute} darf nur einfache alphanumerische Zeichen und Symbole enthalten.',
    'before' => 'Das Feld {attribute} muss ein Datum vor {date} sein.',
    'before_or_equal' => 'Das Feld {attribute} muss ein Datum vor oder gleich {date} sein.',
    'between' => [
        'array' => 'Das Feld {attribute} muss zwischen {min} und {max} Elementen enthalten.',
        'file' => 'Das Feld {attribute} muss zwischen {min} und {max} Kilobyte liegen.',
        'numeric' => 'Das Feld {attribute} muss zwischen {min} und {max} liegen.',
        'string' => 'Das Feld {attribute} muss zwischen {min} und {max} Zeichen lang sein.',
    ],
    'boolean' => 'Das Feld {attribute} muss wahr oder falsch sein.',
    'can' => 'Das Feld {attribute} enthält einen nicht autorisierten Wert.',
    'confirmed' => 'Die Bestätigung des Feldes {attribute} stimmt nicht überein.',
    'current_password' => 'Das Passwort ist nicht korrekt.',
    'date' => 'Das Feld {attribute} ist kein gültiges Datum.',
    'date_equals' => 'Das Feld {attribute} muss ein Datum gleich {date} sein.',
    'date_format' => 'Das Feld {attribute} stimmt nicht mit dem Format {format} überein.',
    'decimal' => 'Das Feld {attribute} muss {decimal} Dezimalstellen haben.',
    'declined' => 'Das Feld {attribute} muss abgelehnt werden.',
    'declined_if' => 'Das Feld {attribute} muss abgelehnt werden, wenn {other} ist {value}.',
    'different' => 'Das Feld {attribute} und {other} müssen unterschiedlich sein.',
    'digits' => 'Das Feld {attribute} muss {digits} Ziffern sein.',
    'digits_between' => 'Das Feld {attribute} muss zwischen {min} und {max} Ziffern liegen.',
    'dimensions' => 'Das Feld {attribute} hat ungültige Bildabmessungen.',
    'distinct' => 'Das Feld {attribute} hat einen doppelten Wert.',
    'email' => 'Das Feld {attribute} muss eine gültige E-Mail-Adresse sein.',
    'ends_with' => 'Das Feld {attribute} muss mit einem der folgenden Werte enden: {values}.',
    'exists' => 'Das ausgewählte Feld {attribute} ist ungültig.',
    'file' => 'Das Feld {attribute} muss eine Datei sein.',
    'filled' => 'Das Feld {attribute} muss einen Wert haben.',
    'gt' => [
        'array' => 'Das Feld {attribute} muss mehr als {value} Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss grösser als {value} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss grösser als {value} sein.',
        'string' => 'Das Feld {attribute} muss grösser als {value} Zeichen sein.',
    ],
    'gte' => [
        'array' => 'Das Feld {attribute} muss {value} oder mehr Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss grösser oder gleich {value} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss grösser oder gleich {value} sein.',
        'string' => 'Das Feld {attribute} muss grösser oder gleich {value} Zeichen sein.',
    ],
    'image' => 'Das Feld {attribute} muss ein Bild sein.',
    'in' => 'Das ausgewählte Feld {attribute} ist ungültig.',
    'in_array' => 'Das Feld {attribute} existiert nicht in {other}.',
    'integer' => 'Das Feld {attribute} muss eine ganze Zahl sein.',
    'ip' => 'Das Feld {attribute} muss eine gültige IP-Adresse sein.',
    'ipv4' => 'Das Feld {attribute} muss eine gültige IPv4-Adresse sein.',
    'ipv6' => 'Das Feld {attribute} muss eine gültige IPv6-Adresse sein.',
    'json' => 'Das Feld {attribute} muss eine gültige JSON-Zeichenkette sein.',
    'lowercase' => 'Das Feld {attribute} muss in Kleinbuchstaben sein.',
    'lt' => [
        'array' => 'Das Feld {attribute} muss weniger als {value} Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss kleiner als {value} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss kleiner als {value} sein.',
        'string' => 'Das Feld {attribute} muss kleiner als {value} Zeichen sein.',
    ],
    'lte' => [
        'array' => 'Das Feld {attribute} darf nicht mehr als {value} Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss kleiner oder gleich {value} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss kleiner oder gleich {value} sein.',
        'string' => 'Das Feld {attribute} muss kleiner oder gleich {value} Zeichen sein.',
    ],
    'mac_address' => 'Das Feld {attribute} muss eine gültige MAC-Adresse sein.',
    'max' => [
        'array' => 'Das Feld {attribute} darf nicht mehr als {max} Elemente enthalten.',
        'file' => 'Das Feld {attribute} darf nicht grösser als {max} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} darf nicht grösser als {max} sein.',
        'string' => 'Das Feld {attribute} darf nicht grösser als {max} Zeichen sein.',
    ],
    'mimes' => 'Das Feld {attribute} muss eine Datei des Typs sein: {values}.',
    'mimetypes' => 'Das Feld {attribute} muss eine Datei des Typs sein: {values}.',
    'min' => [
        'array' => 'Das Feld {attribute} muss mindestens {min} Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss mindestens {min} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss mindestens {min} sein.',
        'string' => 'Das Feld {attribute} muss mindestens {min} Zeichen sein.',
    ],
    'multiple_of' => 'Das Feld {attribute} muss ein Vielfaches von {value} sein.',
    'not_in' => 'Das ausgewählte Feld {attribute} ist ungültig.',
    'not_regex' => 'Das Format des Feldes {attribute} ist ungültig.',
    'numeric' => 'Das Feld {attribute} muss eine Zahl sein.',
    'password' => 'Das Passwort ist nicht korrekt.',
    'present' => 'Das Feld {attribute} muss vorhanden sein.',
    'regex' => 'Das Format des Feldes {attribute} ist ungültig.',
    'required' => 'Das Feld {attribute} ist erforderlich.',
    'required_array_keys' => 'Das Feld {attribute} muss Einträge für folgende Werte enthalten: {values}.',
    'required_if' => 'Das Feld {attribute} ist erforderlich, wenn {other} ist {value}.',
    'required_if_accepted' => 'Das Feld {attribute} ist erforderlich, wenn {other} akzeptiert wird.',
    'required_unless' => 'Das Feld {attribute} ist erforderlich, es sei denn, {other} ist in {values}.',
    'required_with' => 'Das Feld {attribute} ist erforderlich, wenn {values} vorhanden ist.',
    'required_with_all' => 'Das Feld {attribute} ist erforderlich, wenn {values} vorhanden sind.',
    'required_without' => 'Das Feld {attribute} ist erforderlich, wenn {values} nicht vorhanden ist.',
    'required_without_all' => 'Das Feld {attribute} ist erforderlich, wenn keines der {values} vorhanden sind.',
    'same' => 'Das Feld {attribute} und {other} müssen übereinstimmen.',
    'size' => [
        'array' => 'Das Feld {attribute} muss {size} Elemente enthalten.',
        'file' => 'Das Feld {attribute} muss {size} Kilobyte sein.',
        'numeric' => 'Das Feld {attribute} muss {size} sein.',
        'string' => 'Das Feld {attribute} muss {size} Zeichen sein.',
    ],
    'starts_with' => 'Das Feld {attribute} muss mit einem der folgenden Werte beginnen: {values}.',
    'string' => 'Das Feld {attribute} muss eine Zeichenkette sein.',
    'timezone' => 'Das Feld {attribute} muss eine gültige Zone sein.',
    'unique' => 'Das Feld {attribute} wird bereits verwendet.',
    'uploaded' => 'Das Feld {attribute} konnte nicht hochgeladen werden.',
    'uppercase' => 'Das Feld {attribute} muss in Grossbuchstaben sein.',
    'url' => 'Das Format des Feldes {attribute} ist ungültig.',
    'ulid' => 'Das Feld {attribute} muss eine gültige ULID sein.',
    'uuid' => 'Das Feld {attribute} muss eine gültige UUID sein.',

    /*
    |--------------------------------------------------------------------------
    | Sprachzeilen für benutzerdefinierte Validierung
    |--------------------------------------------------------------------------
    |
    | Hier können Sie benutzerdefinierte Validierungsmeldungen für Attribute
    | mit der Konvention „attribute.rule" angeben, um die Zeile zu benennen.
    | Dies macht es schnell möglich, eine bestimmte benutzerdefinierte
    | Sprachzeile für eine bestimmte Attributregel anzugeben.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Attribute für benutzerdefinierte Validierung
    |--------------------------------------------------------------------------
    |
    | Die folgenden Sprachzeilen werden verwendet, um unseren
    | Attribut-Platzhalter mit etwas Besserleserlichem zu ersetzen,
    | wie z. B. „E-Mail-Adresse" statt „E-Mail". Dies hilft uns einfach,
    | die Meldungen ein wenig sauberer zu gestalten.
    |
    */

    'attributes' => [],
];
