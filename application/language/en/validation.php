<?php

defined('DS') or exit('No direct access.');

return [
    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | This file contains the language strings used by the Validation library.
    | Feel free to change them to anything you want to customize your views to
    | better match your application. You can also change the default error
    | messages used by the library.
    |
    */

    'accepted' => 'The :attribute must be accepted.',
    'active_url' => 'The :attribute is not a valid URL.',
    'after' => 'The :attribute must be a date after :date.',
    'alpha' => 'The :attribute may only contain letters.',
    'alpha_dash' => 'The :attribute may only contain letters, numbers, and dashes.',
    'alpha_num' => 'The :attribute may only contain letters and numbers.',
    'array' => 'The :attribute must have selected elements.',
    'before' => 'The :attribute must be a date before :date.',
    'before_or_equals' => 'The :attribute must be a date before or equals to :date.',
    'between' => [
        'numeric' => 'The :attribute must be between :min - :max.',
        'file' => 'The :attribute must be between :min - :max kilobytes.',
        'string' => 'The :attribute must be between :min - :max characters.',
    ],
    'boolean' => 'The :attribute is not a valid boolean value.',
    'confirmed' => 'The :attribute confirmation does not match.',
    'count' => 'The :attribute must have exactly :count selected elements.',
    'countbetween' => 'The :attribute must have between :min and :max selected elements.',
    'countmax' => 'The :attribute must have less than :max selected elements.',
    'countmin' => 'The :attribute must have at least :min selected elements.',
    'date' => 'The :attribute is not a valid date.',
    'date_format' => 'The :attribute must have a valid date format :format.',
    'different' => 'The :attribute and :other must be different.',
    'email' => 'The :attribute format is invalid.',
    'exists' => 'The selected :attribute is invalid.',
    'image' => 'The :attribute must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'integer' => 'The :attribute must be an integer.',
    'ip' => 'The :attribute must be a valid IP address.',
    'match' => 'The :attribute format is invalid.',
    'regex' => 'The :attribute format is invalid.',
    'max' => [
        'numeric' => 'The :attribute must be less than :max.',
        'file' => 'The :attribute must be less than :max kilobytes.',
        'string' => 'The :attribute must be less than :max characters.',
    ],
    'mimes' => 'The :attribute must be a file of type: :values.',
    'min' => [
        'numeric' => 'The :attribute must be at least :min.',
        'file' => 'The :attribute must be at least :min kilobytes.',
        'string' => 'The :attribute must be at least :min characters.',
    ],
    'not_in' => 'The selected :attribute is invalid.',
    'numeric' => 'The :attribute must be a number.',
    'required' => 'The :attribute field is required.',
    'required_with' => 'The :attribute field is required with :field',
    'same' => 'The :attribute and :other must match.',
    'size' => [
        'numeric' => 'The :attribute must be :size.',
        'file' => 'The :attribute must be :size kilobyte.',
        'string' => 'The :attribute must be :size characters.',
    ],
    'unique' => 'The :attribute has already been taken.',
    'url' => 'The :attribute format is invalid.',
    'gt' => 'The :attribute must be greater than :value.',
    'gte' => 'The :attribute must be greater than or equal to :value.',
    'lt' => 'The :attribute must be less than :value.',
    'lte' => 'The :attribute must be less than or equal to :value.',
    'digits' => 'The :attribute must be :digits digits.',
    'digits_between' => 'The :attribute must be between :min and :max digits.',
    'string' => 'The :attribute must be a string.',
    'json' => 'The :attribute must be a valid JSON string.',
    'timezone' => 'The :attribute must be a valid timezone.',
    'ipv4' => 'The :attribute must be a valid IPv4 address.',
    'ipv6' => 'The :attribute must be a valid IPv6 address.',
    'not_regex' => 'The :attribute format is invalid.',
    'present' => 'The :attribute field must be present.',
    'filled' => 'The :attribute field must have a value.',
    'file' => 'The :attribute must be a file.',
    'mimetypes' => 'The :attribute must be a file of type: :values.',
    'dimensions' => 'The :attribute has invalid image dimensions.',
    'distinct' => 'The :attribute field has duplicate values.',
    'ends_with' => 'The :attribute must end with one of the following: :values.',
    'starts_with' => 'The :attribute must start with one of the following: :values.',
    'in_array' => 'The :attribute field must exist in :other.',
    'date_equals' => 'The :attribute must be equal to :date.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_unless' => 'The :attribute field is required unless :other is :value.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values are not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    | For example, to specify a custom message for the 'email' attribute
    | simple add 'email_required' to this array with your custom message.
    |
    */

    'custom' => [
        // ..
    ],

    /*
    |--------------------------------------------------------------------------
    | Validation Attribute
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap attribute place-holders
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make messages a little cleaner.
    |
    | The class validator will automatically search this array, and replace
    | the :attribute placeholder with the value you specify here.
    |
    */

    'attributes' => [
        // ..
    ],
];
