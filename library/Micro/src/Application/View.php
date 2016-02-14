<?php

namespace Micro\Application;

use Exception as CoreException;

class View
{
    protected $template;

    protected $parent;

    protected $__currentSection;

    protected $__data = [];

    protected $paths = [];

    protected $sections = [];

    protected $renderParent = \true;

    protected static $helpers = [];

    protected $package;

    protected $resolvedPaths = [];

    /**
     * @param string $template
     * @param array $data
     */
    public function __construct($template = \null, array $data = \null)
    {
        $this->template = $template;
        $this->__data = $data ?: [];
    }

    /**
     * @param string $path
     * @return View
     */
    public function addPath($path)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                $this->addPath($p);
            }
            return $this;
        }

        $path = rtrim($path, '/\\');

        $this->paths[$path] = $path;

        return $this;
    }

    /**
     * @param string $template
     * @throws CoreException
     * @return string
     */
    public function render($template = \null)
    {
        $renderParent = $this->renderParent;

        if ($template !== \null) {
            $renderParent = \false;
        } else {
            $template = $this->template;
        }

        if (empty($template)) {
            throw new CoreException('Template is empty', 500);
        }

        $file = ltrim($template . '.phtml', '/\\');

        foreach ($this->paths as $path) {
            $filePath = $path . '/' . $file;
            if ((isset($this->resolvedPaths[$filePath]) || \is_file($filePath))) {
                $content = $this->evalFile($this->resolvedPaths[$filePath] = $filePath);
                if ($renderParent === \true && $this->parent !== \null) {
                    $this->parent->setSections(array_merge(
                        $this->getSections(),
                        ['content' => $content]
                    ));
                    $content = $this->parent->render();
                }
                return $content;
            }
        }

        throw new CoreException('Template "' . $file . '" not found in ' . implode(', ', $this->paths), 500);
    }

    /**
     * @param string $__path
     * @throws Exception
     * @return string
     */
    public function evalFile($__path)
    {
        $__obLevel = ob_get_level();

        ob_start();

        extract($this->__data);

        try {
            include $__path;
        } catch (\Exception $e) {
            while (ob_get_level() > $__obLevel) {
                ob_end_clean();
            }
            throw $e;
        }

        return ob_get_clean();
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return get_class($this);
    }

    /**
     * @param string $data
     * @return View
     */
    public function setData(array $data)
    {
        $this->__data = $data;

        return $this;
    }

    /**
     * @param string|array $key
     * @param mixed $value
     * @return View
     */
    public function assign($key, $value = \null)
    {
        if (is_array($key)) {
            foreach ($key as $k => $v) {
                $this->assign($k, $v);
            }
            return $this;
        }

        if ($value === null) {
            return $this;
        }

        $this->__data[$key] = $value;

        return $this;
    }

    /**
     * @param string $template
     * @return View
     */
    public function setTemplate($template)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return isset($this->__data[$key]) ? $this->__data[$key] : \null;
    }

    /**
     * @param string $key
     * @param mixed $value
     */
    public function __set($key, $value)
    {
        $this->__data[$key] = $value;
    }

    /**
     * @param string $key
     * @return boolean
     */
    public function __isset($key)
    {
        return isset($this->__data[$key]);
    }

    /**
     * @param string $key
     */
    public function __unset($key)
    {
        if (isset($this->__data[$key])) {
            unset($this->__data[$key]);
        }
    }

    /**
     * @param string $template
     * @param array $data
     * @return string
     */
    public function partial($template, array $data = [])
    {
        $view = clone $this;

        $view->setSections([]);

        $view->setData($data);

        $view->setParent(\null);

        $view->setRenderParent(\false);

        $view->setTemplate($template);

        return $view->render();
    }

    /**
     * @param string $template
     * @param array $data
     */
    public function extend($template, array $data = [])
    {
        $view = clone $this;

        $view->setRenderParent(\true);

        $view->setParent(\null);

        $view->assign($data);

        $view->setTemplate($template);

        $this->setParent($view);
    }

    /**
     * @param View $parent
     * @return View
     */
    public function setParent(View $parent = \null)
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @param string $section
     * @throws CoreException
     */
    public function start($section)
    {
        if ($this->__currentSection !== \null) {
            throw new CoreException('There is current started section', 500);
        }

        $this->__currentSection = $section;

        ob_start();
    }

    /**
     * @throws CoreException
     * @return View
     */
    public function stop()
    {
        if ($this->__currentSection === \null) {
            throw new CoreException('There is not current started section', 500);
        }

        $section = $this->__currentSection;

        $this->__currentSection = \null;

        return $this->section($section, ob_get_clean());
    }

    /**
     * @param string $section
     * @param string $content
     * @return View
     */
    public function section($section, $content)
    {
        if (!isset($this->sections[$section])) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $content;

        return $this;
    }

    /**
     * @param string $section
     * @param string $default
     * @return string
     */
    public function renderSection($section, $default = \null)
    {
        if (!isset($this->sections[$section])) {
            return $default;
        }

        return implode("\n", (array) $this->sections[$section]);
    }

    /**
     * @param array $sections
     * @return View
     */
    public function setSections(array $sections)
    {
        $this->sections = $sections;

        return $this;
    }

    /**
     * @return array
     */
    public function getSections()
    {
        return $this->sections;
    }

    /**
     * @param string $package
     * @return View
     */
    public function widget($package)
    {
        $this->package = $package;

        return $this;
    }

    /**
     * @param string $method
     * @param array $params
     * @throws CoreException
     * @return mixed
     */
    public function __call($method, $params)
    {
        $method = \ucfirst($method);

        if (!isset(static::$helpers[$method])) {

            $search = [];
            $packages = [];

            if (\null !== $this->package) {
                $packages = [$this->package];
            } else {
                $packages = array_keys(\config('packages', []));
            }

            foreach ($packages as $package) {
                $search[] = $helper = $package . '\\View\\' . \ucfirst($method);
                if (\class_exists($helper, \true)) {
                    static::$helpers[$method] = new $helper($this);
                    break;
                }
            }

            if (!isset(static::$helpers[$method])) {
                throw new CoreException('Invalid view helper: [' . \implode('], [', $search) . ']', 500);
            }
        }

        $this->package = \null;

        return \call_user_func_array(static::$helpers[$method], $params);
    }

    /**
     * @param boolean $flag
     * @return View
     */
    public function setRenderParent($flag)
    {
        $this->renderParent = (bool) $flag;

        return $this;
    }
}