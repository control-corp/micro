<?php

use Micro\Container\Container;
use Micro\Application\View;
use Micro\Exception\Exception as CoreException;
use Micro\Http\Response\JsonResponse;
use Micro\Http\Response\RedirectResponse;
use Micro\Paginator\Paginator;
use Micro\Acl\RoleInterface;
use Micro\Auth\Auth;
use Micro\Helper\Flash;

if (!function_exists('app')) {
    function app($service = \null, $app = 'app')
    {
        $container = Container::getInstance($app);

        if ($service !== \null) {
            return $container->get($service);
        }

        return $container;
    }
}

if (!function_exists('app_path')) {
    function app_path($path = \null)
    {
        $appPath = ltrim(config('application.path', 'application'), '/\\');

        if ($path !== \null) {
            $appPath .= DIRECTORY_SEPARATOR . trim($path, '/\\');
        }

        return $appPath;
    }
}

if (!function_exists('public_path')) {
    function public_path($path = \null)
    {
        $publicPath = ltrim(config('application.public_path', 'public'), '/\\');

        if ($path !== \null) {
            $publicPath .= DIRECTORY_SEPARATOR . trim($path, '/\\');
        }

        return $publicPath;
    }
}

if (!function_exists('package_path')) {
    function package_path($package, $path = \null)
    {
        $packages = app('config')->get('packages', []);

        if (!isset($packages[$package])) {
            throw new CoreException(sprintf('[' . __FUNCTION__ . '] Invalid package "%s"', $package));
        }

        $packagePath = $packages[$package];

        if ($path !== \null) {
            $packagePath .= DIRECTORY_SEPARATOR . trim($path, '/\\');
        }

        return $packagePath;
    }
}

if (!function_exists('config')) {
    function config($key = \null, $value = \null)
    {
        static $cache = [];

        if (array_key_exists($key, $cache)) {
            return $cache[$key];
        }

        return $cache[$key] = app('config')->get($key, $value);
    }
}

if (!function_exists('env')) {
    function env($env = \null)
    {
        if (defined('APP_ENV')) {
            $_env = APP_ENV;
        } else {
            $_env = getenv('APP_ENV');
        }

        if ($_env === \false) {
            $_env = 'production';
        }

        if ($env === \null) {
            return $_env;
        }

        if ($_env === $env) {
            return \true;
        }

        return \false;
    }
}

if (!function_exists('route')) {
    function route($name = \null, array $data = [], $reset = \false, $qsa = \true)
    {
        return app('router')->assemble($name, $data, $reset, $qsa);
    }
}

if (!function_exists('base_url')) {
    function base_url($path = \null)
    {
        $baseUrl = app('request')->getBaseUrl();

        if ($path !== \null) {
            $baseUrl .= '/' . trim($path, '/\\');
        }

        return $baseUrl;
    }
}

if (!function_exists('server_url')) {
    function server_url($path = \null)
    {
        $request   = app('request');
        $serverUrl = $request->getScheme() . '://' . $request->getHttpHost();

        if ($path !== \null) {
            $serverUrl .= '/' . trim($path, '/\\');
        }

        return $serverUrl;
    }
}

if (!function_exists('json')) {
    /**
     * @param string $body
     * @param int $code
     * @return \Micro\Http\Response\JsonResponse
     */
    function json($body = '', $code = 200)
    {
        return new JsonResponse($body, $code);
    }
}

if (!function_exists('redirect')) {
    /**
     * @param string $url
     * @param int $code
     * @return \Micro\Http\Response\RedirectResponse
     */
    function redirect($url, $code = 302)
    {
        return new RedirectResponse($url, $code);
    }
}

if (!function_exists('view')) {
    /**
     * @param string $template
     * @param array $data
     * @param boolean $injectPaths
     * @return \Micro\Application\View
     */
    function view($template, array $data = \null, $injectPaths = \false)
    {
        return new View($template, $data, $injectPaths);
    }
}

if (!function_exists('identity')) {
    /**
     * @param unknown $force
     * @return \Micro\Auth\Identity
     */
    function identity($force = \false)
    {
        return Auth::identity($force);
    }
}

if (!function_exists('flash')) {
    function flash()
    {
        $flash = new Flash();

        return $flash;
    }
}

if (!function_exists('escape')) {
    function escape($var, $encoding = 'UTF-8')
    {
        return htmlspecialchars($var, ENT_COMPAT, $encoding);
    }
}

if (!function_exists('current_package')) {
    function current_package()
    {
        $route = app('router')->getCurrentRoute();

        if ($route === \null) {
            throw new CoreException(sprintf('[' . __FUNCTION__ . '] There is no current route'));
        }

        $resource = $route->getHandler();

        if (!is_string($resource)) {
            return \null;
        }

        $parts = explode('\\', $resource);

        return $parts[0];
    }
}

if (!function_exists('is_allowed')) {
    function is_allowed($resource = \null, $role = \null, $privilege = \true)
    {
        $acl = app('acl');

        if ($acl === \null) {
            return \true;
        }

        if ($role === \null) {

            $identity = identity();

            $role = 'guest';

            if ($identity !== \null && $identity instanceof RoleInterface) {
                try {
                    $role = $identity->getRoleId();
                } catch (\Exception $e) {
                    trigger_error($e->getMessage(), E_USER_WARNING);
                }
            }
        }

        if ($resource === \null) {

            $route = app('router')->getCurrentRoute();

            if ($route === \null) {
                return \true;
            }

            $resource = $route->getHandler();

            if (!is_string($resource)) {
                return \true;
            }
        }

        return $acl->isAllowed($role, $resource, $privilege);
    }
}

if (!function_exists('forward')) {
    function forward($package, array $params = [], $subRequest = \false)
    {
        $request = clone app('request');
        $request->setParams($params);

        return app()->resolve($package, $request, clone app('response'), $subRequest);
    }
}

if (!function_exists('widget')) {
    function widget($package, array $params = [])
    {
		return forward($package, $params, \true)->getBody();
    }
}

if (!function_exists('pagination')) {
    function pagination(Paginator $paginator, $partial = 'paginator', array $params = \null, View $view = \null)
    {
        $pages = ['pages' => $paginator->getPages()];

        if ($params !== \null) {
            $pages = array_merge($pages, (array) $params);
        }

        if ($view === \null) {
            $view = new View();
        }

        return $view->partial($partial, $pages);
    }
}

if (!function_exists('translate')) {
    function translate($key, $code = \null)
    {
        static $cache;

        if (isset($cache[$key . (string) $code])) {
            return $cache[$key];
        }

        return $cache[$key . (string) $code] = app('translator')->translate($key, $code);
    }
}

if (!function_exists('str_slug')) {
    function str_slug($string)
    {
        static $cache;

        $origin = $string;

        if (isset($cache[$origin])) {
            return $cache[$origin];
        }

        if (mb_strlen($string, 'UTF-8') > 100) {
            $string = mb_substr($string, 0, 100, 'UTF-8');
        }

        $cyr = array('а','б','в','г','д','е','ж','з','и','й','к','л','м','н','о','п','р',
            'с','т','у','ф','х','ц','ч','ш','щ','ъ','ь','ю','я',
            'А','Б','В','Г','Д','Е','Ж','З','И','Й','К','Л','М','Н','О','П','Р',
            'С','Т','У','Ф','Х','Ц','Ч','Ш','Щ','Ъ','Ь', 'Ю','Я');

        $lat = array('a','b','v','g','d','e','zh','z','i','y','k','l','m','n','o','p','r',
            's','t','u','f' ,'h' ,'ts' ,'ch','sh' ,'sht' ,'a' ,'y' ,'yu','ya',
            'A','B','V','G','D','E','Zh','Z','I','Y','K','L','M','N','O','P','R',
            'S','T','U','F' ,'H' ,'Ts' ,'Ch','Sh' ,'Sht' ,'A' ,'Y' ,'Yu' ,'Ya');

        $string = str_replace($cyr, $lat, $string);

        //Unwanted:  {UPPERCASE} ; / ? : @ & = + $ , . ! ~ * ' ( )
        $string = mb_strtolower($string, 'UTF-8');

        //Strip any unwanted characters
        $string = preg_replace("/[^a-z0-9_\s-]/", "", $string);

        //Clean multiple dashes or whitespaces
        $string = preg_replace("/[\s-]+/", " ", $string);

        //Convert whitespaces and underscore to dash
        $string = preg_replace("/[\s_]/", "-", $string);

        return $cache[$origin] = $string;
    }
}