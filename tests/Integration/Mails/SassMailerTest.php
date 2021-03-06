<?php

namespace Tests\Integration\Mails;

use App\mail\SassMailer;
use PHPMailer;

/**
 * @author  Rizart Dokollari <r.dokollari@gmail.com>
 * @since   2/14/18
 */
class SassMailerTest extends \Tests\TestCase
{
    /** @test */
    public function the_sass_mailer_can_send_an_email()
    {
        $sassMailer = new SassMailer();

        $requestSucceeded = $sassMailer->send([
            'to'      => 'r.dokollari@gmail.com',
            'subject' => 'SASS Account Recovery',
            'html'    => 'some-html',
        ]);

        $this->assertInstanceOf(PHPMailer::class, $requestSucceeded);
    }
}