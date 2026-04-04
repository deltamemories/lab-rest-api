<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Entity\Post;
use App\Entity\User;
use App\Message\CommentCreatedMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Messenger\Test\InteractsWithMessenger;

class CommentControllerTest extends WebTestCase {
    use InteractsWithMessenger;

    private EntityManagerInterface $emi;
    private $client;
    private User $testUser;
    private Post $testPost;

    protected function setUp(): void {
        parent::setUp();
        $this->client = static::createClient();
        $this->emi = self::getContainer()->get('doctrine.orm.entity_manager');

        $this->testUser = new User();
        $this->testUser->setEmail('comment_user_' . uniqid() . '@gmail.com');
        $this->testUser->setPassword('password123');
        $this->testUser->setRoles(['ROLE_USER']);
        
        $this->emi->persist($this->testUser);

        $this->testPost = new Post();
        $this->testPost->setTitle('Post for comments');
        $this->testPost->setContent('Content');
        $this->testPost->setAuthor($this->testUser);

        $this->emi->persist($this->testPost);
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

    public function testCreateCommentSuccess(): void {
        $this->client->request(
            'POST',
            '/api/comments',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'post' => $this->testPost->getId(),
                'content' => 'This is a test comment'
            ])
        );

        $this->assertResponseStatusCodeSame(201);

        $responseContent = $this->client->getResponse()->getContent();
        $responseData = json_decode($responseContent, true);

        $this->assertArrayHasKey('id', $responseData);

        $comment = $this->emi->getRepository(Comment::class)->find($responseData['id']);
        $this->assertNotNull($comment);
        $this->assertEquals('This is a test comment', $comment->getContent());
        $this->assertEquals($this->testUser->getId(), $comment->getAuthor()->getId());
        $this->assertEquals($this->testPost->getId(), $comment->getPost()->getId());

        $this->transport()->queue()->assertCount(1);
        $this->transport()->queue()->assertContains(CommentCreatedMessage::class);
    }

    public function testCreateCommentPostNotFound(): void {
        $this->client->request(
            'POST',
            '/api/comments',
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode([
                'post' => 999999,
                'content' => 'Comment for non-existent post'
            ])
        );

        $this->assertResponseStatusCodeSame(404);
        $this->assertJsonContains(['error' => "Can't find post with this id"], $this->client->getResponse()->getContent());
    }

    public function testIndexComments(): void {
        $comment = new Comment();
        $comment->setContent('My comment');
        $comment->setAuthor($this->testUser);
        $comment->setPost($this->testPost);

        $this->emi->persist($comment);
        $this->emi->flush();

        $this->client->request('GET', '/api/comments');

        $this->assertResponseStatusCodeSame(200);
        $content = json_decode($this->client->getResponse()->getContent(), true);
        $this->assertIsArray($content);
        $this->assertNotEmpty($content);
    }

    public function testShowComment(): void {
        $comment = new Comment();
        $comment->setContent('Show this comment');
        $comment->setAuthor($this->testUser);
        $comment->setPost($this->testPost);

        $this->emi->persist($comment);
        $this->emi->flush();

        $this->client->request('GET', '/api/comments/' . $comment->getId());

        $this->assertResponseStatusCodeSame(200);
    }

    public function testUpdateComment(): void {
        $comment = new Comment();
        $comment->setContent('Old comment text');
        $comment->setAuthor($this->testUser);
        $comment->setPost($this->testPost);

        $this->emi->persist($comment);
        $this->emi->flush();

        $this->client->request(
            'PATCH',
            '/api/comments/' . $comment->getId(),
            [],
            [],
            ['CONTENT_TYPE' => 'application/json'],
            json_encode(['content' => 'Updated comment text'])
        );

        $this->assertResponseStatusCodeSame(200);

        $updatedComment = $this->emi->getRepository(Comment::class)->find($comment->getId());
        $this->assertEquals('Updated comment text', $updatedComment->getContent());
    }

    public function testDeleteComment(): void {
        $comment = new Comment();
        $comment->setContent('To Delete');
        $comment->setAuthor($this->testUser);
        $comment->setPost($this->testPost);

        $this->emi->persist($comment);
        $this->emi->flush();

        $commentId = $comment->getId();

        $this->client->request('DELETE', '/api/comments/' . $commentId);

        $this->assertResponseStatusCodeSame(204);

        $deletedComment = $this->emi->getRepository(Comment::class)->find($commentId);
        $this->assertNull($deletedComment);
    }
}
