<?php
/**
 * Request Handler Middleware
 * 
 * Processes HTTP requests through a chain of middleware
 */

class RequestHandler {
    private array $middleware = [];
    private $handler = null;
    
    /**
     * Add middleware to the chain
     */
    public function addMiddleware(callable $middleware): self {
        $this->middleware[] = $middleware;
        return $this;
    }
    
    /**
     * Set the final handler
     */
    public function setHandler(callable $handler): self {
        $this->handler = $handler;
        return $this;
    }
    
    /**
     * Process request through middleware chain
     */
    public function handle(array $request) {
        if (!$this->handler) {
            throw new Exception('No handler set');
        }
        
        $next = $this->handler;
        
        // Build middleware chain (reverse order for proper flow)
        foreach (array_reverse($this->middleware) as $middleware) {
            $currentNext = $next;
            $next = function ($req) use ($middleware, $currentNext) {
                try {
                    $result = $middleware($req, $currentNext);
                    return $result ?? $currentNext($req);
                } catch (Exception $e) {
                    error_log("Middleware error: " . $e->getMessage());
                    Response::serverError('Internal server error');
                }
            };
        }
        
        return $next($request);
    }
}
