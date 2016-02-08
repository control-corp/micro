# micro

## Container default services (Interop container) :

######'app' = Micro\Application\Application

######'request' = Micro\Http\Request
    -> Psr7
    
######'response' = Micro\Http\Response
    -> Psr7
    
######'router' = Micro\Router\Router
    -> match($uri)
    -> map($pattern, $handler, $name = null)
    -> assemble($name = null, array $data = [], $reset = false, $qsa = true)
    
######'logger' = Micro\Log\Filelog
    -> Psr3

######'event' = Micro\Event\EventManager 
    -> attach($event, $callable, $priority = 10) 
    -> trigger($event, array $params = [])
    
######'resolver' = Micro\Application\Application 
    -> resolve($package, ServerRequestInterface $request, ResponseInterface $response, $subRequest = false)
    
######'exception.handler' = Micro\Application\Application 
    -> handleException(\Exception $e, ServerRequestInterface $request, ResponseInterface $response)
    
######'translator' = Micro\Translator\Translator

######'caches' = array of cache factories 
    -> config key array "cache.adapters"

######'cache' = default cache factory 
    -> config key string "cache.default"

######'acl = Micro\Acl\Acl
    -> isAllowed($role = null, $resource = null, $privilege = null)

######'db' = factory Micro\Database\Adapter\AdapterAbstract
    -> config key string "db.default" - default adapter
    -> config key array "db.adapters" - array of adapters
    -> config key bool "db.set_default_adapter" -> set default adapter in table instances
    -> config key bool "db.set_default_cache" -> set default cache for table metadata
