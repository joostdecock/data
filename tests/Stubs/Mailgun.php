<?php

namespace Freesewing\Data\Tests\Stubs;

class Mailgun
{
    public function messages()
    {
        return new MailgunMessage();
    }
}
