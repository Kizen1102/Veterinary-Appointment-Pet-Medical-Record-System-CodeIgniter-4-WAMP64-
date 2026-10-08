<?php

use App\Models\UserModel;
use CodeIgniter\Config\Services;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use CodeIgniter\Test\Mock\MockEmail;
use Config\Email;
use Config\Filters;

/**
 * "Forgot password": the reset link is e-mailed when SMTP is set up,
 * and shown on the screen (development only) when it is not.
 *
 * @internal
 */
final class PasswordResetEmailTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate     = true;
    protected $migrateOnce = false;
    protected $refresh     = true;
    protected $namespace   = null;

    private MockEmail $mailbox;

    protected function setUp(): void
    {
        parent::setUp();

        $filters                    = config(Filters::class);
        $filters->globals['before'] = array_values(array_diff($filters->globals['before'], ['csrf']));

        (new UserModel())->insert(['role' => 'owner', 'full_name' => 'Maria Santos', 'email' => 'maria@example.com', 'password_hash' => 'x']);

        // A fake mail server: nothing leaves the computer, the sent e-mails are kept in ->archive
        $this->mailbox = new MockEmail(config(Email::class));
        Services::injectMock('email', $this->mailbox);
    }

    private function useSmtp(): void
    {
        $config            = config(Email::class);
        $config->protocol  = 'smtp';
        $config->SMTPHost  = 'smtp.example.com';
        $config->fromEmail = 'clinic@example.com';
    }

    public function testResetLinkIsEmailed(): void
    {
        $this->useSmtp();

        $response = $this->post('forgot-password', ['email' => 'maria@example.com']);
        $response->assertRedirectTo('/forgot-password');
        $response->assertSessionHas('success');
        $this->assertNull(session('devLink')); // not shown on the screen when it was e-mailed

        $this->assertSame(['maria@example.com'], $this->mailbox->archive['recipients']);
        $this->assertStringContainsString('reset-password/', $this->mailbox->archive['body']);
        $this->assertStringContainsString('Maria Santos', $this->mailbox->archive['body']);
    }

    public function testUnknownEmailGetsTheSameMessageAndNoEmail(): void
    {
        $this->useSmtp();

        $response = $this->post('forgot-password', ['email' => 'nobody@example.com']);
        $response->assertSessionHas('success');
        $this->assertEmpty($this->mailbox->archive);
    }

    public function testLinkIsShownOnScreenWhenEmailIsNotSetUp(): void
    {
        // Default config: protocol "mail" and no SMTP host, so nothing is sent
        $this->post('forgot-password', ['email' => 'maria@example.com'])->assertSessionHas('success');

        $this->assertEmpty($this->mailbox->archive);
        $this->assertStringContainsString('reset-password/', (string) session('devLink'));
    }

    public function testFailedSendingFallsBackToTheScreenLink(): void
    {
        $this->useSmtp();
        $this->mailbox->returnValue = false; // the SMTP server refused the e-mail

        $this->post('forgot-password', ['email' => 'maria@example.com']);

        $this->assertStringContainsString('reset-password/', (string) session('devLink'));
    }
}
