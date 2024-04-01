<?php

namespace Pantono\Authentication\Tests\Contexts;

use PHPUnit\Framework\TestCase;
use Pantono\Contracts\Security\SecurityContextInterface;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\MockObject\Generator\MockClass;
use PHPUnit\Framework\MockObject\MockObject;
use Pantono\Authentication\Gates\MaybeTwoFactorAuthed;
use Pantono\Authentication\UserAuthentication;
use Pantono\Contracts\Endpoint\EndpointDefinitionInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\ParameterBag;

class MaybeTwoFactorAuthedTest extends TestCase
{
    private MockClass|UserAuthentication $userAuthentication;
    private MockObject $securityContext;

    public function setUp(): void
    {
        $this->userAuthentication = $this->getMockBuilder(UserAuthentication::class)->disableOriginalConstructor()->getMock();
        $this->securityContext = $this->createMock(SecurityContextInterface::class);
    }

    public function testValidTwoFactorAuth()
    {
        $validator = new MaybeTwoFactorAuthed($this->userAuthentication, $this->securityContext);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = new Session();
        $session->set('tfa_attempt_code', 'test');
        $this->userAuthentication->expects($this->once())
            ->method('isTwoFactorCodeValid')
            ->willReturn(true);
        $matcher = $this->exactly(2);
        $this->securityContext->expects($matcher)
            ->method('set')
            ->willReturnCallback(function (mixed $key, mixed $value) use ($matcher) {
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('tfa_attempt_code', $key),
                    2 => $this->assertEquals('is_tfa', $key)
                };
                match ($matcher->numberOfInvocations()) {
                    1 => $this->assertEquals('test', $value),
                    2 => $this->assertEquals(true, $value)
                };
            });
        $validator->isValid($request, $endpoint, new ParameterBag(), $session);
    }

    public function testInvalidAuthNoSession()
    {
        $validator = new MaybeTwoFactorAuthed($this->userAuthentication, $this->securityContext);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = $this->getSessionMock();
        $session->expects($this->once())
            ->method('has')
            ->with('tfa_attempt_code')
            ->willReturn(false);
        $this->securityContext->expects($this->never())
            ->method('set');
        $this->userAuthentication->expects($this->never())
            ->method('isTwoFactorCodeValid');
        $validator->isValid($request, $endpoint, new ParameterBag(), $session);
    }

    public function testInvalidAuthCodeNotExists()
    {
        $validator = new MaybeTwoFactorAuthed($this->userAuthentication, $this->securityContext);
        $request = new Request([], [], [], [], [], ['HTTP_ApiKey' => 'test']);
        $endpoint = $this->getEndpointMock();
        $session = $this->getSessionMock();
        $session->expects($this->once())
            ->method('has')
            ->with('tfa_attempt_code')
            ->willReturn(true);

        $session->expects($this->once())
            ->method('get')
            ->with('tfa_attempt_code')
            ->willReturn('test');
        $this->userAuthentication->expects($this->once())
            ->method('isTwoFactorCodeValid')
            ->willReturn(false);

        $this->securityContext->expects($this->never())
            ->method('set');
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
