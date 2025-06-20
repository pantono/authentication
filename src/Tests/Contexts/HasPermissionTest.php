<?php

namespace Pantono\Authentication\Tests\Contexts;

use PHPUnit\Framework\TestCase;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use Pantono\Authentication\Gates\HasPermission;
use Pantono\Authentication\Model\User;
use Pantono\Contracts\Security\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Pantono\Authentication\Exception\AccessDeniedException;
use RuntimeException;
use Pantono\Authentication\Model\Permission;

class HasPermissionTest extends TestCase
{
    private SecurityContextInterface|MockClass $securityContext;

    public function setUp(): void
    {
        $this->securityContext = $this->getMockBuilder(SecurityContextInterface::class)->disableOriginalConstructor()->getMock();
    }

    public function testValidPermission()
    {
        $user = new User();
        $user->setPermissions(['test' => true]);
        $this->securityContext->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($user);
        $validator = new HasPermission($this->securityContext);
        $request = new Request([], [], [], [], [], []);
        $parameters = new ParameterBag(['permission' => 'test']);
        $validator->isValid($request, $this->getEndpointMock(), $parameters, $this->getSessionMock());
    }

    public function testInValidPermission()
    {
        $user = new User();
        $user->setPermissions([]);
        $this->securityContext->expects($this->once())
            ->method('get')
            ->with('user')
            ->willReturn($user);
        $validator = new HasPermission($this->securityContext);
        $request = new Request([], [], [], [], [], []);
        $parameters = new ParameterBag(['permission' => 'test']);
        $this->expectExceptionMessage('You are not authorised to perform this action');
        $this->expectException(AccessDeniedException::class);
        $validator->isValid($request, $this->getEndpointMock(), $parameters, $this->getSessionMock());
    }

    public function testInvalidOptions()
    {
        $user = new User();
        $user->setPermissions(['test' => false]);
        $this->securityContext->expects($this->never())
            ->method('get')
            ->with('user')
            ->willReturn($user);
        $validator = new HasPermission($this->securityContext);
        $request = new Request([], [], [], [], [], []);
        $parameters = new ParameterBag();
        $this->expectExceptionMessage('Permission is required');
        $this->expectException(RuntimeException::class);
        $validator->isValid($request, $this->getEndpointMock(), $parameters, $this->getSessionMock());
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
