<?php

namespace App\Tests\Entity;

use App\Entity\User;
use App\Service\CriteriaManager;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testUserInitialization(): void
    {
        $user = new User();
        $this->assertNull($user->getId());
        $this->assertEmpty($user->getPreferences());
        $this->assertEquals(['ROLE_USER'], $user->getRoles());
    }

    public function testUserProperties(): void
    {
        $user = new User();
        
        $user->setEmail('test@example.com');
        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('test@example.com', $user->getUserIdentifier());
        
        $user->setRoles(['ROLE_ADMIN']);
        $this->assertContains('ROLE_ADMIN', $user->getRoles());
        $this->assertContains('ROLE_USER', $user->getRoles());

        $user->setPassword('hashed_password');
        $this->assertEquals('hashed_password', $user->getPassword());
        
        $user->setPseudo('JohnDoe');
        $this->assertEquals('JohnDoe', $user->getPseudo());
        $this->assertEquals('JohnDoe', $user->getDisplayName());
    }

    public function testPreferences(): void
    {
        $user = new User();
        
        $this->assertEquals(CriteriaManager::WEIGHT_MEDIUM, $user->getPreferenceWeight('profitability'));
        
        $user->setPreferenceWeight('profitability', CriteriaManager::WEIGHT_HIGH);
        $this->assertEquals(CriteriaManager::WEIGHT_HIGH, $user->getPreferenceWeight('profitability'));
        
        // Test invalid weight
        $user->setPreferenceWeight('profitability', 'invalid_weight');
        $this->assertEquals(CriteriaManager::WEIGHT_HIGH, $user->getPreferenceWeight('profitability'));
    }
}
