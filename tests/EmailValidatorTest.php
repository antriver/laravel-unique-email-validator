<?php

namespace Antriver\LaravelUniqueEmailValidatorTests;

use Antriver\LaravelUniqueEmailValidator\EmailValidator;
use Illuminate\Database\Connection;
use PHPUnit\Framework\TestCase;

class EmailValidatorTest extends TestCase
{
    /**
     * These are hard coded sorry :(
     * No time to to it better
     *
     * @var string[]
     */
    private $testDbDetails = [
        'host' => '127.0.0.1',
        'username' => 'root',
        'password' => 'password',
        'database' => 'unique-email-rule-test',
    ];

    /**
     * @var EmailValidator
     */
    private $emailValidator;

    protected $userAEmails = [
        1 => 'anthonykuske@gmail.com',
        2 => 'anthony.kuske@gmail.com',
        3 => 'anthonykuske+@gmail.com',
        4 => 'anthonykuske+1@gmail.com',
        5 => 'anthonykuske+a@gmail.com',
        6 => 'anthonykuske+hello@gmail.com',
        7 => 'anthony.kuske@gmail.com',
        8 => 'anthony.kuske+hello@gmail.com',
        9 => 'anthony.kuske+h+ello@gmail.com',
        10 => 'anthony.ku.ske+h.ello@gmail.com',
        11 => 'anthony.kuske+h...ello@gmail.com',
    ];

    protected $otherRegisteredEmails = [
        91 => 'anthonykuske@icloud.com',
        92 => 'anthonykuske@gmail.co.uk',
        93 => 'antkuske@gmail.com',
        94 => 'ant.kuske@gmail.com',
        95 => 'antkuske+1@gmail.com',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        // Connect to a test DB.
        $pdo = new \PDO(
            "mysql:host={$this->testDbDetails['host']};dbname={$this->testDbDetails['database']};charset=utf8mb4;",
            $this->testDbDetails['username'],
            $this->testDbDetails['password']
        );
        $dbConnection = new Connection($pdo, $this->testDbDetails['database']);

        // Create users table.
        $dbConnection->affectingStatement("DROP TABLE IF EXISTS `users`");
        $dbConnection->affectingStatement(
            "CREATE TABLE `users` (
                `id` INT UNSIGNED NOT NULL,
                `email` VARCHAR(200) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`id`)
            )"
        );

        foreach ($this->userAEmails as $id => $email) {
            $dbConnection->insert(
                "INSERT INTO users (id, email) VALUES (?, ?)",
                [
                    $id,
                    $email,
                ]
            );
        }

        foreach ($this->otherRegisteredEmails as $id => $email) {
            $dbConnection->insert(
                "INSERT INTO users (id, email) VALUES (?, ?)",
                [
                    $id,
                    $email,
                ]
            );
        }

        $this->emailValidator = new EmailValidator($dbConnection);
    }

    public function dataForTestSelectRowsWithAliasedEmails()
    {
        return array_map(
            function (string $email) {
                return [$email];
            },
            $this->userAEmails
        );
    }

    /**
     * @dataProvider dataForTestSelectRowsWithAliasedEmails
     */
    public function testSelectRowsWithAliasedEmails(string $inputEmail)
    {
        $results = $this->emailValidator->selectMatchingEmails(
            $inputEmail,
            'users',
            'email'
        );

        $this->assertCount(count($this->userAEmails), $results);
        $resultEmails = array_map(
            function ($row) {
                return $row->email;
            },
            $results
        );
        $this->assertSame(array_values($this->userAEmails), $resultEmails);
        $this->assertNotContains($this->otherRegisteredEmails, $resultEmails);
    }

    public function dataForTestCreateRegexForEmail()
    {
        return [
            'anthonykuske@gmail.com' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?[+a-z0-9._-]*?@gmail.com'],
            'anthonykuske+@gmail.com' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?[+a-z0-9._-]*?@gmail.com'],
            'anthonykuske+123@gmail.com' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?[+a-z0-9._-]*?@gmail.com'],
            'a.n.t.h.o.n.ykuske+123@gmail.com' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?[+a-z0-9._-]*?@gmail.com'],
        ];
    }

    /**
     * @dataProvider dataForTestCreateRegexForEmail
     *
     * @param string $expectedRegex
     */
    public function testCreateRegexForEmail(string $expectedRegex)
    {
        $input = $this->dataName();

        $result = $this->emailValidator->createRegexForEmail($input);
        $this->assertSame($expectedRegex, $result);
    }

    public function dataForCreateRegexForEmailUserDots()
    {
        return [
            'anthonykuske' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?'],
            'a.n.t.h.o.n.ykuske' => ['a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?'],
        ];
    }

    /**
     * @dataProvider dataForCreateRegexForEmailUserDots
     *
     * @param string $expectedRegex
     */
    public function testCreateRegexForEmailUserDots(string $expectedRegex)
    {
        $input = $this->dataName();

        $result = $this->emailValidator->createRegexForEmailUserDots($input);
        $this->assertSame($expectedRegex, $result);
    }

    public function dataForTestRegexGeneratedByCreateRegexForEmail()
    {
        return [
            'anthonykusk@gmail.com' => [false],
            'anthonykusk+@gmail.com' => [false],
            'anthonykusk+123@gmail.com' => [false],
            'anthonykusk+a@gmail.com' => [false],
            'anthonykuske@gmail.com' => [true],
            'anthonykuske+@gmail.com' => [true],
            'anthonykuske+123@gmail.com' => [true],
            'anthonykuske+1@gmail.com' => [true],
            'a.n.t.h.o.n.y.k.u.s.k.e@gmail.com' => [true],
            'anthon....ykuske@gmail.com' => [true],
            'anthon....ykuske+a.b.c@gmail.com' => [true],
            'anthon....ykuske+abc@gmail.com' => [true],
        ];
    }

    /**
     * @dataProvider dataForTestRegexGeneratedByCreateRegexForEmail
     *
     * @param bool $expectMatch
     */
    public function testRegexGeneratedByCreateRegexForEmail(bool $expectMatch)
    {
        $regex = '/'.'a.*?n.*?t.*?h.*?o.*?n.*?y.*?k.*?u.*?s.*?k.*?e.*?[+a-z0-9._-]*?@gmail.com'.'/';

        $testEmail = $this->dataName();
        $this->assertSame($expectMatch, (bool) preg_match($regex, $testEmail));
    }
}
