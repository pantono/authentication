<?php

namespace Pantono\Authentication\Tests\Contexts;

use PHPUnit\Framework\TestCase;
use Pantono\Contracts\Security\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use PHPUnit\Framework\MockObject\MockObject;
use Pantono\Authentication\Gates\IsTwoFactorAuthed;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Exception\TwoFactorAuthRequired;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Authentication\TwoFactorAuth;
use Pantono\Authentication\Model\UserTfaAttempt;

class IsTwoFactorAuthedTest extends TestCase
{
    private MockClass|TwoFactorAuth $twoFactorAuth;

    public function setUp(): void
    {
        $this->twoFactorAuth = $this->getMockBuilder(TwoFactorAuth::class)->disableOriginalConstructor()->getMock();
    }

    public function testValidTwoFactorAuth()
    {
        $validator = new IsTwoFactorAuthed($this->twoFactorAuth);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = new Session();
        $session->set('tfa_attempt_id', '1');
        $attempt = new UserTfaAttempt();
        $attempt->setDateExpires(new \DateTime('+1 hour'));
        $attempt->setVerified(true);
        $this->twoFactorAuth->expects($this->once())
            ->method('getAttemptById')
            ->willReturn($attempt);
        $validator->isValid($request, $endpoint, new ParameterBag(), $session);
    }

    public function testInvalidAuthNoSession()
    {
        $validator = new IsTwoFactorAuthed($this->twoFactorAuth);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = $this->getSessionMock();
        $session->expects($this->once())
            ->method('has')
            ->with('tfa_attempt_id')
            ->willReturn(false);
        $this->expectException(TwoFactorAuthRequired::class);
        $validator->isValid($request, $endpoint, new ParameterBag(), $session);
    }

    public function testInvalidAuthCodeNotExists()
    {
        $validator = new IsTwoFactorAuthed($this->twoFactorAuth);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = $this->getSessionMock();
        $session->expects($this->once())
            ->method('has')
            ->with('tfa_attempt_id')
            ->willReturn(true);

        $session->expects($this->once())
            ->method('get')
            ->with('tfa_attempt_id')
            ->willReturn('1');
        $this->twoFactorAuth->expects($this->once())
            ->method('getAttemptById')
            ->with('1')
            ->willReturn(null);
        $this->expectException(TwoFactorAuthRequired::class);
        $validator->isValid($request, $endpoint, new ParameterBag(), $session);
    }

    private function getEndpointMock(): EndpointDefinitionInterface
    {
        return $this->createMock(EndpointDefinitionInterface::class);
    }

    private function getSessionMock(): Session|MockObject
    {
        return $this->getMockBuilder(Session::class)->getMock();
    }
}
