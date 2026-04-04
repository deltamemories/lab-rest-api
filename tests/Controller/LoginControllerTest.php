<?php

namespace App\Tests\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginControllerTest extends WebTestCase {
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

    private function createTestUser(string $email, string $password): User {
        $user = new User();
        $user->setEmail($email);
        
        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        $user->setPassword($hasher->hashPassword($user, $password));
        $user->setRoles(['ROLE_USER']);

        $this->emi->persist($user);
        $this->emi->flush();

        return $user;
    }

    public function testLoginSuccess(): void {
        $email = 'login_test_' . uniqid() . '@gmail.com';
        $password = 'correct_password';
        $this->createTestUser($email, $password);

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $email,
                'password' => $password
            ])
        );

        $this->assertResponseStatusCodeSame(200);
        
        $content = $this->client->getResponse()->getContent();
        $this->assertJson($content);
        $this->assertArrayHasKey("token", json_decode($content, true));
    }

    public function testLoginInvalidPassword(): void {
        $email = 'login_fail_' . uniqid() . '@gmail.com';
        $this->createTestUser($email, 'correct_password');

        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => $email,
                'password' => 'wrong_password'
            ])
        );

        $this->assertResponseStatusCodeSame(401);
        $this->assertJsonContains(['message' => 'Invalid credentials.'], $this->client->getResponse()->getContent());
    }

    public function testLoginUserNotFound(): void {
        $this->client->request(
            'POST',
            '/api/login',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'username' => 'non_existent@gmail.com',
                'password' => 'some_password'
            ])
        );

        $this->assertResponseStatusCodeSame(401);
    }
}
