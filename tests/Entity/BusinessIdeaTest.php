<?php

namespace App\Tests\Entity;

use App\Entity\BusinessIdea;
use App\Entity\User;
use PHPUnit\Framework\TestCase;

class BusinessIdeaTest extends TestCase
{
    public function testBusinessIdeaInitialization(): void
    {
        $idea = new BusinessIdea();
        
        $this->assertNull($idea->getId());
        $this->assertNull($idea->getTitle());
        $this->assertEquals('draft', $idea->getStatus());
        $this->assertInstanceOf(\DateTimeImmutable::class, $idea->getCreatedAt());
        $this->assertNull($idea->getCreator());
        $this->assertNull($idea->getRevenueModel());
        $this->assertNull($idea->getTargetAudience());
    }

    public function testBusinessIdeaProperties(): void
    {
        $idea = new BusinessIdea();
        
        $idea->setTitle('My Super Business');
        $this->assertEquals('My Super Business', $idea->getTitle());
        
        $idea->setDescription('This is a great idea.');
        $this->assertEquals('This is a great idea.', $idea->getDescription());
        
        $idea->setStatus('adopted');
        $this->assertEquals('adopted', $idea->getStatus());
        
        $user = new User();
        $user->setEmail('creator@example.com');
        $idea->setCreator($user);
        $this->assertSame($user, $idea->getCreator());
        
        $idea->setRevenueModel('recurring');
        $this->assertEquals('recurring', $idea->getRevenueModel());
        
        $idea->setTargetAudience('b2b');
        $this->assertEquals('b2b', $idea->getTargetAudience());
    }
}
