<?php

namespace Tests\Feature;

use Dotenv\Repository\Adapter\ArrayAdapter;
use Dotenv\Repository\RepositoryBuilder;
use Illuminate\Support\Env;
use ReflectionProperty;
use Symfony\Component\Mailer\Transport\Smtp\EsmtpTransport;
use Tests\TestCase;

class MailTransportCompatibilityTest extends TestCase
{
    public function test_default_and_legacy_tls_require_starttls(): void
    {
        foreach ([null, 'tls'] as $encryption) {
            $transport = $this->transport(['MAIL_ENCRYPTION' => $encryption]);

            $this->assertFalse($transport->getStream()->isTLS());
            $this->assertTrue($transport->isAutoTls());
            $this->assertTrue($transport->isTlsRequired());
        }
    }

    public function test_legacy_ssl_uses_implicit_tls_even_on_a_nonstandard_port(): void
    {
        $transport = $this->transport([
            'MAIL_ENCRYPTION' => 'ssl',
            'MAIL_PORT' => '8465',
        ]);

        $this->assertTrue($transport->getStream()->isTLS());
        $this->assertSame(8465, $transport->getStream()->getPort());
        $this->assertTrue($transport->isTlsRequired());
    }

    public function test_explicit_null_retains_plain_smtp_including_port_465(): void
    {
        foreach (['25', '465'] as $port) {
            $transport = $this->transport([
                'MAIL_ENCRYPTION' => 'null',
                'MAIL_PORT' => $port,
            ]);

            // Symfony otherwise infers implicit TLS solely from port 465.
            $this->assertFalse($transport->getStream()->isTLS());
            $this->assertFalse($transport->isAutoTls());
            $this->assertFalse($transport->isTlsRequired());
            $this->assertSame((int) $port, $transport->getStream()->getPort());
        }
    }

    public function test_explicit_scheme_can_select_implicit_tls(): void
    {
        $transport = $this->transport([
            'MAIL_ENCRYPTION' => 'null',
            'MAIL_SCHEME' => 'smtps',
        ]);

        $this->assertTrue($transport->getStream()->isTLS());
        $this->assertTrue($transport->isTlsRequired());
    }

    public function test_explicit_tls_requirement_enables_and_requires_starttls(): void
    {
        $transport = $this->transport([
            'MAIL_ENCRYPTION' => 'null',
            'MAIL_REQUIRE_TLS' => 'true',
        ]);

        $this->assertFalse($transport->getStream()->isTLS());
        $this->assertTrue($transport->isAutoTls());
        $this->assertTrue($transport->isTlsRequired());
    }

    public function test_explicit_false_can_make_starttls_optional(): void
    {
        $transport = $this->transport([
            'MAIL_ENCRYPTION' => 'tls',
            'MAIL_REQUIRE_TLS' => 'false',
        ]);

        $this->assertFalse($transport->getStream()->isTLS());
        $this->assertTrue($transport->isAutoTls());
        $this->assertFalse($transport->isTlsRequired());
    }

    private function transport(array $overrides): EsmtpTransport
    {
        $previousRepository = Env::getRepository();
        $repository = RepositoryBuilder::createWithNoAdapters()
            ->addAdapter(ArrayAdapter::class)
            ->make();
        $repositoryProperty = new ReflectionProperty(Env::class, 'repository');
        $values = array_merge([
            'MAIL_HOST' => 'smtp.example.invalid',
            'MAIL_PORT' => '587',
            'MAIL_ENCRYPTION' => null,
            'MAIL_SCHEME' => null,
            'MAIL_REQUIRE_TLS' => null,
            'MAIL_USERNAME' => null,
            'MAIL_PASSWORD' => null,
        ], $overrides);
        foreach ($values as $key => $value) {
            if ($value !== null) {
                $repository->set($key, $value);
            }
        }

        // Keep installed MAIL_* variables isolated from these configuration cases.
        $repositoryProperty->setValue(null, $repository);
        try {
            $config = require base_path('config/mail.php');

            // Construct the real transport without connecting or sending a message.
            return $this->app['mail.manager']->createSymfonyTransport($config['mailers']['smtp']);
        } finally {
            $repositoryProperty->setValue(null, $previousRepository);
        }
    }
}
