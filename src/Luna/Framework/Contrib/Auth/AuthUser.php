<?php

namespace Luna\Framework\Contrib\Auth;

use Luna\Framework\Contrib\Auth\Exception\UserNotFoundException;
use Luna\Framework\Database\Exception\RecordNotFoundException;
use Luna\Framework\Database\ORM\Model;

/**
 * An Eloquent Model: 'Luna\Framework\Contrib\Auth\AuthUser'
 *
 * @property    string  $username
 */
class AuthUser extends Model
{
    protected $usernameField = 'username';
    protected $passwordField = 'password';

    public function auth(string $username, string $password)
    {
        try {
            $user = $this->alive()->whereAnd(
                "{$this->usernameField} = :username",
                [
                    'username' => $username,
                ]
            )->getRecordSet()->getFirst();
            if ($user->verifyPassword($password)) {
                return $user;
            } else {
                throw new UserNotFoundException(); 
            }
        } catch (RecordNotFoundException $ex) {
            throw new UserNotFoundException(); 
        }
    }

    public function verifyPassword(string $password)
    {
        $hash = $this->get($this->passwordField);
        return password_verify($password, $hash);
    }

    public function passwordHash(string $password)
    {
        return password_hash($password, PASSWORD_ARGON2I);
    }

    public function insert(array $columns = [])
    {
        foreach ($columns as $key => $val)
        {
            if ($key == $this->passwordField)
            {
                $columns[$key] = $this->passwordHash($val);
            }
        }
        return parent::insert($columns);
    }

    public function update(array $columns = [])
    {
        foreach ($columns as $key => $val)
        {
            if ($key == $this->passwordField)
            {
                $columns[$key] = $this->passwordHash($val);
            }
        }
        return parent::update($columns);
    }
}
