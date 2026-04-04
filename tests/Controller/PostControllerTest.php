<?php

namespace App\Tests\Controller;

use App\Entity\Post;
use App\Entity\User;
use App\Message\PostCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class PostControllerTest extends WebTestCase {
    use InteractsWithMessenger;

    private EntityManagerInterface $emi;
    private $client;
    private User $testUser;

    protected function setUp(): void {
        parent::setUp();
        $this->client = static::createClient();
        $this->emi = self::getContainer()->get('doctrine.orm.entity_manager');

        $this->testUser = new User();
        $this->testUser->setEmail('post_user_' . uniqid() . '@gmail.com');
        $this->testUser->setPassword('password123');
        $this->testUser->setRoles(['ROLE_USER']);
        
        $this->emi->persist($this->testUser);
        $this->emi->flush();

        $this->client->loginUser($this->testUser);
    }

    private function assertJsonContains(array $expected, string $jsonResponse): void {
        $actual = json_decode($jsonResponse, true);
        foreach ($expected as $key => $value) {
            $this->assertArrayHasKey($key, $actual);
            $this->assertEquals($value, $actual[$key]);
        }
    }

    public function testCreatePostSuccess(): void {
        $this->client->request(
            'POST',
            '/api/posts',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'title' => 'Test Post Title',
                'content' => 'Test Post Content',
                'status' => 'draft'
            ])
        );

        $this->assertResponseStatusCodeSame(201);
        
        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);
        
        $this->assertArrayHasKey('id', $responseData);

        $post = $this->emi->getRepository(Post::class)->find($responseData['id']);
        $this->assertNotNull($post);
        $this->assertEquals('Test Post Title', $post->getTitle());
        $this->assertEquals($this->testUser->getId(), $post->getAuthor()->getId());

        $this->transport()->queue()->assertCount(1);
        $this->transport()->queue()->assertContains(PostCreatedMessage::class);
    }

    public function testIndexPosts(): void {
        $post = new Post();
        $post->setTitle('Index Title');
        $post->setContent('Index Content');
        $post->setAuthor($this->testUser);
        
        $this->emi->persist($post);
        $this->emi->flush();

        $this->client->request('GET', '/api/posts');

        $this->assertResponseStatusCodeSame(200);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertNotEmpty($content);
    }

    public function testShowPost(): void {
        $post = new Post();
        $post->setTitle('Show Title');
        $post->setContent('Show Content');
        $post->setAuthor($this->testUser);
        
        $this->emi->persist($post);
        $this->emi->flush();

        $this->client->request('GET', '/api/posts/' . $post->getId());

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['title' => 'Show Title'], $this->client->getResponse()->getContent());
    }

    public function testUpdatePost(): void {
        $post = new Post();
        $post->setTitle('Old Title');
        $post->setContent('Old Content');
        $post->setAuthor($this->testUser);
        
        $this->emi->persist($post);
        $this->emi->flush();

        $this->client->request(
            'PATCH',
            '/api/posts/' . $post->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['title' => 'Updated Title'])
        );

        $this->assertResponseStatusCodeSame(200);
        $this->assertJsonContains(['title' => 'Updated Title'], $this->client->getResponse()->getContent());
        
        $updatedPost = $this->emi->getRepository(Post::class)->find($post->getId());
        $this->assertEquals('Updated Title', $updatedPost->getTitle());
    }

    public function testDeletePost(): void {
        $post = new Post();
        $post->setTitle('To Delete');
        $post->setContent('Content');
        $post->setAuthor($this->testUser);
        
        $this->emi->persist($post);
        $this->emi->flush();

        $postId = $post->getId();

        $this->client->request('DELETE', '/api/posts/' . $postId);

        $this->assertResponseStatusCodeSame(204);
        
        $deletedPost = $this->emi->getRepository(Post::class)->find($postId);
        $this->assertNull($deletedPost);
    }
}
