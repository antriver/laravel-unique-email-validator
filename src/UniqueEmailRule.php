<?php

namespace Antriver\LaravelUniqueEmailValidator;

use Illuminate\Contracts\Validation\Rule;

class UniqueEmailRule implements Rule
{
    /**
     * @var string
     */
    private $column;

    /**
     * @var string
     */
    private $table;

    public function __construct(string $table, string $column)
    {
        $this->table = $table;
        $this->column = $column;
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param string $attribute
     * @param mixed $value
     *
     * @return bool
     */
    public function passes($attribute, $value)
    {
        /** @var EmailValidator $validator */
        $validator = app(EmailValidator::class);

        return empty($validator->selectMatchingEmails($value, $this->table, $this->column));
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return 'That email address is already in use.';
    }
}
