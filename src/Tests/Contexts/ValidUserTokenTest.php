<?php

namespace Pantono\Authentication\Tests\Contexts;

use PHPUnit\Framework\TestCase;
use Pantono\Authentication\Gates\ValidUserToken;
use Pantono\Authentication\UserAuthentication;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Contracts\Security\SecurityContextInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Model\User;
use Pantono\Authentication\Model\UserToken;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;

class ValidUserTokenTest extends TestCase
{
    private MockClass|UserAuthentication $userAuthentication;
    private MockObject|ParameterBag $securityContext;
    private MockObject|EventDispatcher $eventDispatcher;

    public function setUp(): void
    {
        $this->userAuthentication = $this->getMockBuilder(UserAuthentication::class)->disableOriginalConstructor()->getMock();
        $this->securityContext = $this->createMock(SecurityContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
    }

    public function testNoCodeExists()
    {
        $validator = new ValidUserToken($this->userAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], []);
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('User authentication token is required');
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    public function testCodeInHeader()
    {
        $validator = new ValidUserToken($this->userAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], ['HTTP_UserToken' => 'test']);
        $token = new UserToken();
        $user = new User();
        $user->setId(1);
        $token->setUser($user);
        $token->setId(1);
        $token->setDateExpires(new \DateTime('+1 hour'));
        $token->setApiTokenId(1);
        $this->securityContext->expects($this->exactly(2))
            ->method('set');
        $token->setDateCreated(new \DateTime("-1 hour"));
        $this->userAuthentication->expects($this->once())
            ->method('getUserTokenByToken')
            ->willReturn($token);
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    public function testCodeInSession()
    {
        $validator = new ValidUserToken($this->userAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], []);
        $session = $this->getSessionMock();
        $session->expects($this->once())
            ->method('get')
            ->with('api_token')
            ->willReturn('TEST TOKEN STRING');
        $token = new UserToken();
        $user = new User();
        $user->setId(1);
        $token->setUser($user);
        $token->setId(1);
        $token->setDateExpires(new \DateTime('+1 hour'));
        $token->setApiTokenId(1);
        $this->securityContext->expects($this->exactly(2))
            ->method('set');
        $token->setDateCreated(new \DateTime("-1 hour"));
        $this->userAuthentication->expects($this->once())
            ->method('getUserTokenByToken')
            ->with('TEST TOKEN STRING')
            ->willReturn($token);
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $session);
    }

    public function testExpiredUser()
    {
        $validator = new ValidUserToken($this->userAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], ['HTTP_UserToken' => 'test']);
        $token = new UserToken();
        $user = new User();
        $user->setId(1);
        $token->setUser($user);
        $token->setId(1);
        $token->setDateExpires(new \DateTime('-1 hour'));
        $token->setApiTokenId(1);
        $token->setDateCreated(new \DateTime("-1 hour"));

        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('You have been logged out');
        $this->userAuthentication->expects($this->once())
            ->method('getUserTokenByToken')
            ->willReturn($token);
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    private function getEndpointMock(): EndpointDefinitionInterface
    {
        return $this->createMock(EndpointDefinitionInterface::class);
    }

    private function getSessionMock(): Session|MockClass
    {
        return $this->getMockBuilder(Session::class)->disableOriginalConstructor()->getMock();
    }
}
