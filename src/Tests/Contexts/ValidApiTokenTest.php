<?php

namespace Pantono\Authentication\Tests\Contexts;

use PHPUnit\Framework\TestCase;
use Pantono\Contracts\Security\SecurityContextInterface;
use Pantono\Authentication\ApiAuthentication;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use PHPUnit\Framework\MockObject\MockObject;
use Pantono\Authentication\Gates\ValidApiToken;
use Symfony\Component\HttpFoundation\Session\Session;
use Pantono\Authentication\Model\ApiToken;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class ValidApiTokenTest extends TestCase
{
    private MockClass|ApiAuthentication $apiAuthentication;
    private MockObject $securityContext;
    private EventDispatcher|MockObject $eventDispatcher;

    public function setUp(): void
    {
        $this->apiAuthentication = $this->getMockBuilder(ApiAuthentication::class)->disableOriginalConstructor()->getMock();
        $this->securityContext = $this->createMock(SecurityContextInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcher::class);
    }

    public function testValidToken()
    {
        $validator = new ValidApiToken($this->apiAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $expected = new ApiToken();
        $expected->setId(1);
        $expected->setDateExpires((new \DateTime('+2 year')));
        $this->apiAuthentication->expects($this->once())
            ->method('getApiTokenByToken')
            ->with('test')
            ->willReturn($expected);
        $this->apiAuthentication->expects($this->once())
            ->method('updateApiTokenLastSeen');
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    public function testExpiredToken()
    {
        $validator = new ValidApiToken($this->apiAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $expected = new ApiToken();
        $expected->setId(1);
        $expected->setDateExpires(new \DateTime('-2 year'));
        $this->apiAuthentication->expects($this->once())
            ->method('getApiTokenByToken')
            ->with('test')
            ->willReturn($expected);
        $this->apiAuthentication->expects($this->once())
            ->method('updateApiTokenLastSeen');
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('ApiKey is expired');
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    public function testInvalidToken()
    {
        $validator = new ValidApiToken($this->apiAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $this->apiAuthentication->expects($this->once())
            ->method('getApiTokenByToken')
            ->with('test')
            ->willReturn(null);
        $this->apiAuthentication->expects($this->never())
            ->method('updateApiTokenLastSeen');
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('ApiKey is invalid');
        $validator->isValid($request, $this->getEndpointMock(), new ParameterBag(), $this->getSessionMock());
    }

    public function testTokenNotExists()
    {
        $validator = new ValidApiToken($this->apiAuthentication, $this->securityContext, $this->eventDispatcher);
        $request = new Request([], [], [], [], [], []);
        $this->apiAuthentication->expects($this->never())
            ->method('getApiTokenByToken');
        $this->apiAuthentication->expects($this->never())
            ->method('updateApiTokenLastSeen');
        $this->expectExceptionCode(401);
        $this->expectExceptionMessage('ApiKey is required');
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
