<?php

namespace App\Test\Fixture;

/**
 * User values that can be inserted into the database
 */
class UserFixture
{
    // Table name
    public string $table = 'user';

    // Database records in 2d array
    public array $records = [
        [
            'id' => 1,
            'name' => 'Admin Example',
            'email' => 'admin@example.com',
            // Cleartext password is 12345678 and is used in
            'password_hash' => '$2y$10$r8t5LRX7Hq1.22/h6dwe1uLrrhZnGTOnsue5p/rUgeD8BAhDwFhk2',
            'role' => 'admin',
            'updated_at' => null,
            'created_at' => '2021-01-01 00:00:01',
            'deleted_at' => null,
        ],
        [
            'id' => 2,
            'name' => 'User Example',
            'email' => 'user@example.com',
            'password_hash' => '$2y$10$G42IQACXblpLSoVez77qjeRBS./junh4X3.zdZeuAxJbKZGhfvymC',
            'role' => 'user',
            'updated_at' => null,
            'created_at' => '2021-02-01 00:00:01',
            'deleted_at' => null,
        ],
    ];
}
