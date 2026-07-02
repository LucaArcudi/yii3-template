<?php

declare(strict_types=1);

/**
 * Traduzioni italiane dei messaggi di yiisoft/validator (categoria "yii-validator").
 * Il pacchetto vendor non fornisce l'italiano: gli ID sono i messaggi inglesi originali.
 */
return [
    'At least {min, number} {min, plural, one{property} other{properties}} from this list must be filled for {property}: {properties}.' =>
        'Almeno {min, number} {min, plural, one{proprietà} other{proprietà}} di questo elenco {min, plural, one{deve essere compilata} other{devono essere compilate}} per {property}: {properties}.',
    '{Property} must be either "{true}" or "{false}".' => '{Property} deve essere "{true}" oppure "{false}".',
    '{Property} must be an array or implement \Countable interface. {type} given.' =>
        '{Property} deve essere un array o implementare l\'interfaccia \Countable. Ricevuto {type}.',
    '{Property} must contain at least {min, number} {min, plural, one{item} other{items}}.' =>
        '{Property} deve contenere almeno {min, number} {min, plural, one{elemento} other{elementi}}.',
    '{Property} must contain at most {max, number} {max, plural, one{item} other{items}}.' =>
        '{Property} deve contenere al massimo {max, number} {max, plural, one{elemento} other{elementi}}.',
    '{Property} must contain exactly {exactly, number} {exactly, plural, one{item} other{items}}.' =>
        '{Property} deve contenere esattamente {exactly, number} {exactly, plural, one{elemento} other{elementi}}.',
    '{Property} must be array or iterable. {type} given.' => '{Property} deve essere un array o un iterabile. Ricevuto {type}.',
    'Every iterable key of {property} must have an integer or a string type. {type} given.' =>
        'Ogni chiave iterabile di {property} deve essere di tipo intero o stringa. Ricevuto {type}.',
    '{Property} is not a valid email address.' => '{Property} non è un indirizzo email valido.',
    '{Property} is not in the list of acceptable values.' => '{Property} non rientra tra i valori ammessi.',
    '{Property} must be a valid IP address.' => '{Property} deve essere un indirizzo IP valido.',
    '{Property} must not be an IPv4 address.' => '{Property} non deve essere un indirizzo IPv4.',
    '{Property} must not be an IPv6 address.' => '{Property} non deve essere un indirizzo IPv6.',
    '{Property} contains wrong subnet mask.' => '{Property} contiene una subnet mask errata.',
    '{Property} must be an IP address with specified subnet.' => '{Property} deve essere un indirizzo IP con subnet specificata.',
    '{Property} must not be a subnet.' => '{Property} non deve essere una subnet.',
    '{Property} is not in the allowed range.' => '{Property} non rientra nell\'intervallo consentito.',
    '{Property} must be an integer.' => '{Property} deve essere un numero intero.',
    '{Property} is not a valid JSON.' => '{Property} non è un JSON valido.',
    '{Property} must contain at least {min, number} {min, plural, one{character} other{characters}}.' =>
        '{Property} deve contenere almeno {min, number} {min, plural, one{carattere} other{caratteri}}.',
    '{Property} must contain at most {max, number} {max, plural, one{character} other{characters}}.' =>
        '{Property} deve contenere al massimo {max, number} {max, plural, one{carattere} other{caratteri}}.',
    '{Property} must contain exactly {exactly, number} {exactly, plural, one{character} other{characters}}.' =>
        '{Property} deve contenere esattamente {exactly, number} {exactly, plural, one{carattere} other{caratteri}}.',
    'Nested rule without rules requires {property} to be an object. {type} given.' =>
        'Una regola annidata senza regole richiede che {property} sia un oggetto. Ricevuto {type}.',
    'An object data set data for {property} can only have an array type. {type} given.' =>
        'I dati di un data set oggetto per {property} possono essere solo di tipo array. Ricevuto {type}.',
    'Property "{path}" is not found in {property}.' => 'La proprietà "{path}" non è stata trovata in {property}.',
    '{Property} must be a number.' => '{Property} deve essere un numero.',
    'Exactly 1 property from this list must be filled for {property}: {properties}.' =>
        'Esattamente 1 proprietà di questo elenco deve essere compilata per {property}: {properties}.',
    '{Property} is invalid.' => '{Property} non è valido.',
    '{Property} cannot be blank.' => '{Property} non può essere vuoto.',
    '{Property} not passed.' => '{Property} non è stato fornito.',
    '{Property} must be iterable. {type} given.' => '{Property} deve essere iterabile. Ricevuto {type}.',
    '{Property} is not a subset of acceptable values.' => '{Property} non è un sottoinsieme dei valori ammessi.',
    '{Property} must be "{true}".' => '{Property} deve essere "{true}".',
    '{Property} is not a valid URL.' => '{Property} non è un URL valido.',
    '{Property} must be an array or an object. {type} given.' => '{Property} deve essere un array o un oggetto. Ricevuto {type}.',
    'The allowed types for {property} are integer, float, string, boolean. {type} given.' =>
        'I tipi ammessi per {property} sono integer, float, string, boolean. Ricevuto {type}.',
    'The allowed types for {property} are integer, float, string, boolean, null and object implementing \Stringable or \DateTimeInterface. {type} given.' =>
        'I tipi ammessi per {property} sono integer, float, string, boolean, null e oggetti che implementano \Stringable o \DateTimeInterface. Ricevuto {type}.',
    '{Property} returned from a custom data set must have one of the following types: integer, float, string, boolean, null or an object implementing \Stringable interface or \DateTimeInterface. {type} given.' =>
        '{Property} restituito da un data set personalizzato deve avere uno dei seguenti tipi: integer, float, string, boolean, null oppure un oggetto che implementa \Stringable o \DateTimeInterface. Ricevuto {type}.',
    '{Property} must be equal to "{targetValueOrProperty}".' => '{Property} deve essere uguale a "{targetValueOrProperty}".',
    '{Property} must be strictly equal to "{targetValueOrProperty}".' => '{Property} deve essere strettamente uguale a "{targetValueOrProperty}".',
    '{Property} must not be equal to "{targetValueOrProperty}".' => '{Property} non deve essere uguale a "{targetValueOrProperty}".',
    '{Property} must not be strictly equal to "{targetValueOrProperty}".' => '{Property} non deve essere strettamente uguale a "{targetValueOrProperty}".',
    '{Property} must be greater than "{targetValueOrProperty}".' => '{Property} deve essere maggiore di "{targetValueOrProperty}".',
    '{Property} must be greater than or equal to "{targetValueOrProperty}".' => '{Property} deve essere maggiore o uguale a "{targetValueOrProperty}".',
    '{Property} must be less than "{targetValueOrProperty}".' => '{Property} deve essere minore di "{targetValueOrProperty}".',
    '{Property} must be less than or equal to "{targetValueOrProperty}".' => '{Property} deve essere minore o uguale a "{targetValueOrProperty}".',
    '{Property} must be a string. {type} given.' => '{Property} deve essere una stringa. Ricevuto {type}.',
    'The allowed types for {property} are integer, float and string. {type} given.' =>
        'I tipi ammessi per {property} sono integer, float e string. Ricevuto {type}.',
    '{Property} must be no less than {min}.' => '{Property} non deve essere minore di {min}.',
    '{Property} must be no greater than {max}.' => '{Property} non deve essere maggiore di {max}.',
    '{Property} must be no early than {limit}.' => '{Property} non deve essere precedente a {limit}.',
    '{Property} must be no late than {limit}.' => '{Property} non deve essere successivo a {limit}.',
    '{Property} must be a date.' => '{Property} deve essere una data.',
    '{Property} must be a time.' => '{Property} deve essere un orario.',
    'The allowed types for iterable\'s item values of {property} are integer, float, string, boolean, null and object implementing \Stringable or \DateTimeInterface. {type} given.' =>
        'I tipi ammessi per i valori degli elementi iterabili di {property} sono integer, float, string, boolean, null e oggetti che implementano \Stringable o \DateTimeInterface. Ricevuto {type}.',
    'All iterable items of {property} must have the same type.' => 'Tutti gli elementi iterabili di {property} devono avere lo stesso tipo.',
    'Every iterable\'s item must be unique.' => 'Ogni elemento dell\'iterabile deve essere univoco.',
    '{Property} must be a boolean.' => '{Property} deve essere un booleano.',
    '{Property} must be a float.' => '{Property} deve essere un numero decimale.',
    'At least one of the inner rules must pass the validation.' => 'Almeno una delle regole interne deve superare la validazione.',
    '{Property} must be an image.' => '{Property} deve essere un\'immagine.',
    'The width of {property} must be exactly {exactly, number} {exactly, plural, one{pixel} other{pixels}}.' =>
        'La larghezza di {property} deve essere esattamente di {exactly, number} pixel.',
    'The height of {property} must be exactly {exactly, number} {exactly, plural, one{pixel} other{pixels}}.' =>
        'L\'altezza di {property} deve essere esattamente di {exactly, number} pixel.',
    'The width of {property} cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.' =>
        'La larghezza di {property} non può essere inferiore a {limit, number} pixel.',
    'The height of {property} cannot be smaller than {limit, number} {limit, plural, one{pixel} other{pixels}}.' =>
        'L\'altezza di {property} non può essere inferiore a {limit, number} pixel.',
    'The width of {property} cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.' =>
        'La larghezza di {property} non può essere superiore a {limit, number} pixel.',
    'The height of {property}t cannot be larger than {limit, number} {limit, plural, one{pixel} other{pixels}}.' =>
        'L\'altezza di {property} non può essere superiore a {limit, number} pixel.',
    'The aspect ratio of {property} must be {aspectRatioWidth, number}:{aspectRatioHeight, number} with margin {aspectRatioMargin, number}%.' =>
        'Le proporzioni di {property} devono essere {aspectRatioWidth, number}:{aspectRatioHeight, number} con margine {aspectRatioMargin, number}%.',
];
