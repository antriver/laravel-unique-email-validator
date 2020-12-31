<?php

namespace Antriver\LaravelUniqueEmailValidator;

use Illuminate\Database\Connection;

/**
 * Some email providers (like Gmail) allow you to add a + in the front part of the email address and it still arrives.
 * e.g. anthonykuske@gmail.com can also be anthonykuske+a@gmail.com or anthonykuske+bcdefg@gmail.com
 *
 * Gmail also lets you add dots in the email. e.g. anthony.kuske@gmail.com or a.n.t.h.onyk.u.ske@gmail.com
 *
 * We want to detect uses of this and ensure only a unique user@domain email is used, ignoring the + and .s
 */
class EmailValidator
{
    /**
     * @var Connection
     */
    private $dbConnection;

    public function __construct(Connection $dbConnection)
    {
        $this->dbConnection = $dbConnection;
    }

    public function selectMatchingEmails(string $email, string $table, string $column): array
    {
        $regex = $this->createRegexForEmail($email);

        return $this->dbConnection->select(
            "SELECT `{$column}` FROM `{$table}` WHERE LOWER(`{$column}`) REGEXP ?",
            [
                $regex
            ]
        );
    }

    public function createRegexForEmail(string $email): string
    {
        $email = strtolower($email);

        // Split user@domain.com into "user" and "domain.com"
        [$user, $domain] = explode('@', $email);

        // Split user+123 into user and 123.
        [$realUser] = explode('+', $user);

        $dottedRealUser = $this->createRegexForEmailUserDots($realUser);

        // Match the original and with any plus.
        return "{$dottedRealUser}[+a-z0-9._-]*?@{$domain}";
    }

    public function createRegexForEmailUserDots(string $user): string
    {
        $user = str_replace('.', '', $user);

        return implode(
            '',
            array_map(
                function (string $letter) {
                    return "{$letter}.*?";
                },
                str_split($user)
            )
        );
    }
}
