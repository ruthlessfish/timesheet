<?php

namespace App\Console;

use Illuminate\Console\Command as BaseCommand;

abstract class Command extends BaseCommand
{
    protected function getUserOption()
    {
        $id = $this->option('user');

        if (! $id) {
            error('Please provide --user (id or email).');

            return null;
        }

        $user = User::where('id', $id)->orWhere('email', $id)->first();

        if (! $user) {
            error('User not found.');

            return null;
        }

        return $user;
    }
}
