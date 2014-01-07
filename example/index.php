<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Rmasters\StackTime\StackTime;

// The application
class App implements HttpKernelInterface
{
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true)
    {
        return new Response('<html><body>It is ' . date('H:m a') . ' on ' . date('l jS M Y') . '</body></html>');
    }
}

// Configuration for StackTime
$cfg = [
    'inject' => true,
    'callbacks' => array(function(Request $request, $delta) {
        var_dump($request->getRealMethod(), $request->getUri(), $delta);
    })
];

// Build the stack
$stack = (new Stack\Builder)->push('Rmasters\StackTime\StackTime', $cfg);
$app = $stack->resolve(new App());

// Run the app
Stack\run($app);
