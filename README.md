# StackTimer

A simple [StackPHP][stackphp] middleware that reports on the time it takes the
inner middlewares/app to run.

## Usage

Simply wrap your application in the timer middleware and the load time will be
injected into the response body.

    $app = new App(); // implements HttpKernelInterface
    $timer = new StackTimer($app);

    $request = Request::createFromGlobals();
    $timer->handle($request)->send();

## Options

Pass an array with any of the following keys to
`StackTimer::__construct(HttpKernelInterface, array)`:

-   **inject** (boolean, default: false) - whether to inject into the response
    body,
-   **callbacks** (callable|callable[]) - an array of closures/callables that
    are passed the following arguments:

    function(HttpFoundation\Request $request, float $microseconds) {
    }

-   **format** (string, default: `{ms}ms`) - the text to be injected, see list of
    replaced strings below.
-   **wrapper** (string, default: `<div style="...">%s</div>`) - HTML the text
    is contained in, must contain a single `%s` where the text will be added.
-   **injection** (string, default: `</body>`) - where to inject the snippet,
    this is passed through `preg_quote` and cannot contain regular expressions.
-   **inject_before** (boolean, default: true) - whether to inject the snippet
    before the injection point (i.e. before `</body>`).
