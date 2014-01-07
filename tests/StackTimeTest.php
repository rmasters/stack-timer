<?php

use Rmasters\StackTime\StackTime;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Mockery as m;

class StackTimeTest extends PHPUnit_Framework_TestCase
{
    public function testInitialOptions()
    {
        $timer = new StackTime($this->getApp(), []);

        $options = $timer->options();

        $this->assertArrayHasKey('inject', $options);
        $this->assertFalse($options['inject']);

        $this->assertArrayHasKey('callbacks', $options);
        $this->assertEquals([], $options['callbacks']);

        $this->assertArrayHasKey('format', $options);
        $this->assertEquals(StackTime::DEFAULT_FORMAT, $options['format']);

        $this->assertArrayHasKey('wrapper', $options);
        $this->assertEquals(StackTime::DEFAULT_WRAPPER, $options['wrapper']);

        $this->assertArrayHasKey('injection', $options);
        $this->assertEquals(StackTime::INJECT_END_BODY, $options['injection']);

        $this->assertArrayHasKey('inject_before', $options);
        $this->assertTrue($options['inject_before']);
    }

    public function testInitial()
    {
        $request = $this->getRequest();
        $response = $this->getInjectingResponse();
        $app = $this->getHandledApp($request, $response);

        $timer = new StackTime($app, []);

        $pattern = '<html><body>Hello world</body></html>';

        $response = $timer->handle($request);
        $this->assertEquals($pattern, $response->getContent());
    }

    public function testInjected()
    {
        $request = $this->getRequest();
        
        $response = $this->getInjectingResponse();
        $pattern = '/^' . preg_quote('<html><body>Hello world<div>', '/');
        $pattern .= '([0-9\.]+)ms' . preg_quote('</div></body></html>', '/'). '$/';
        $response->shouldReceive('setContent')->with($pattern)->once();

        $app = $this->getHandledApp($request, $response);

        $timer = new StackTime($app, [
            'inject' => true,
            'wrapper' => '<div>%s</div>',
            'format' => '{ms}ms',
            'injection' => '</body>',
            'inject_before' => true,
        ]);

        $response = $timer->handle($request);
    }

    public function testAlternateInjected()
    {
        $request = $this->getRequest();
        
        $response = $this->getInjectingResponse();
        $pattern = '/^' . preg_quote('<html><header>', '/');
        $pattern .= '([0-9\.]+)s' . preg_quote('</header><body>Hello world</body></html>', '/'). '$/';
        $response->shouldReceive('setContent')->with($pattern)->once();

        $app = $this->getHandledApp($request, $response);

        $timer = new StackTime($app, [
            'inject' => true,
            'wrapper' => '<header>%s</header>',
            'format' => '{s}s',
            'injection' => '<html>',
            'inject_before' => false,
        ]);

        $response = $timer->handle($request);
    }

    public function testCallbacks()
    {
        $request = $this->getRequest();

        $callback = m::mock()
            ->shouldReceive('callback')
            ->with($request, m::type('float'))
            ->once();

        $app = $this->getApp();
        $app->shouldReceive('handle')->once();

        $timer = new StackTime($app, [
            'callbacks' => array($callback, 'callback')
        ]);

        $timer->handle($request);
    }

    private function getApp()
    {
        return m::mock('Symfony\Component\HttpKernel\HttpKernelInterface');
    }

    private function getHandledApp($request, $response)
    {
        $app = $this->getApp();
        $app->shouldReceive('handle')
            ->with($request, HttpKernelInterface::MASTER_REQUEST, true)
            ->once()
            ->andReturn($response);

        return $app;
    }

    private function getRequest()
    {
        return new Symfony\Component\HttpFoundation\Request;
    }

    private function getInjectingResponse()
    {
        $response = m::mock('Symfony\Component\HttpFoundation\Response');

        $response->shouldReceive('getContent')
            ->once()
            ->andReturn('<html><body>Hello world</body></html>');

        return $response;
    }
}
