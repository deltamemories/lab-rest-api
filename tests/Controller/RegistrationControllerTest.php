<?php

namespace App\Tests\Controller;

use App\Entity\User;
use App\Message\UserCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;


class RegistrationControllerTest extends WebTestCase {
    use InteractsWithMessenger;

    private EntityManagerInterface $emi;
    private $client;

    protected function setUp(): void {
        parent::setUp();
        $this->client = static::createClient();
        $this->emi = self::getContainer()->get('doctrine.orm.entity_manager');
    }

    private function assertJsonContains(array $expected, string $jsonResponse): void {
        $actual = json_decode($jsonResponse, true);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }

    public function testRegistrationSuccess(): void {
        $email = 'new_test_user_' . uniqid() . '@gmail.com';

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'email' => $email,
                'password' => 'password123'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        $this->assertJsonContains(['status' => 'User created'], $this->client->getResponse()->getContent());

        $user = $this->emi->getRepository(User::class)->findOneBy(['email' => $email]);
        $this->assertNotNull($user, 'User was not saved to DB');
        $this->assertEquals($email, $user->getEmail());

        $this->transport()->queue()->assertCount(1);
        $this->transport()->queue()->assertContains(UserCreatedMessage::class);
    }

    public function testRegistrationDuplicateEmail(): void {
        $email = 'existing@gmail.com';

        $user = new User();
        $user->setEmail($email);
        $user->setPassword('password111');
        $this->emi->persist($user);
        $this->emi->flush();

        $this->client->request(
            'POST',
            '/api/register',
            [],
            [],
            ['CONTENT_TYPE', 'application/json'],
            json_encode(['email' => $email, 'password' => 'password222'])
        );

        $this->assertResponseStatusCodeSame(409);
        $this->assertJsonContains(['error' => "User with this email already exists"], $this->client->getResponse()->getContent());
    }
}
