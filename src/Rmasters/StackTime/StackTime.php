<?php

namespace Rmasters\StackTime;

use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Timing middleware
 * @todo Is it possible to time each middleware individually?
 */
class StackTime implements HttpKernelInterface
{
    /** @var Symfony\Component\HttpKernel\HttpKernelInterface */
    protected $app;

    /** @var array */
    protected $options;

    const DEFAULT_FORMAT = '{ms}ms';
    const DEFAULT_WRAPPER = '<div style="position: absolute; bottom: 0; right: 0; background: #fff; color: #000; z-index 9999;">%s</div>';
    const INJECT_END_BODY = '</body>';
    const INJECT_START_BODY = '<body>';

    /**
     * @param Symfony\Component\HttpKernel\HttpKernelInterface
     * @param array
     */
    public function __construct(HttpKernelInterface $app, array $options = array())
    {
        $this->app = $app;

        $defaults = array(
            'inject' => false,
            'callbacks' => array(),
            'format' => self::DEFAULT_FORMAT,
            'wrapper' => self::DEFAULT_WRAPPER,
            'injection' => self::INJECT_END_BODY,
            'inject_before' => true
        );
        $this->options = array_merge($defaults, $options);
    }

    /**
     * Get the options
     * @return array Options
     */
    public function options()
    {
        return $this->options;
    }

    /**
     * Time a response
     * @param Symfony\Component\HttpFoundation\Request $request
     * @param int $type Request type
     * @param boolean $catch Whether to catch exceptions
     * @return Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, $type = HttpKernelInterface::MASTER_REQUEST, $catch = true) {
        // Time the sub-handlers
        $start = microtime(true);
        $response = $this->app->handle($request, $type, $catch);
        $end = microtime(true);

        $delta = $end - $start;

        // If injections are enabled, inject into the response content
        if ($this->options['inject']) {
            $this->inject($response, $delta);
        }

        // Make sure callbacks is an array
        if (!is_array($this->options['callbacks'])) {
            $this->options['callbacks'] = array($this->options['callbacks']);
        }
        // Call each callable callback with the Request and delta
        foreach ($this->options['callbacks'] as $callback) {
            if (is_callable($callback)) {
                call_user_func($callback, $request, $delta);
            }
        }

        return $response;
    }

    /**
     * Inject the response time into the body
     * @param Symfony\Component\HttpFoundation\Response $response
     * @param float $delta Microseconds
     */
    protected function inject(Response $response, $delta)
    {
        $text = $this->buildText($delta, $this->options['format']);
        $snippet = sprintf($this->options['wrapper'], $text);

        $search = '/(' . preg_quote($this->options['injection'], '/') . ')/';
        $replace = $this->options['inject_before'] ? $snippet.'${1}' : '${1}'.$snippet;

        $response->setContent(preg_replace($search, $replace, $response->getContent(), 1));
    }

    /**
     * Build the injected text
     * @param float $delta Microseconds
     * @param string $format Format string
     * @return string
     */
    protected function buildText($delta, $format)
    {
        $replacements = array(
            '{us}' => $delta,
            '{ms}' => $delta * 1000,
            '{s}' => $delta * 1000000
        );
        return str_replace(array_keys($replacements), array_values($replacements), $format);
    }
}
