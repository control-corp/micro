<?php

namespace Micro\Helper;

use Micro\Application\Package;

class Files
{
    public static function fetchControllers($dirs = \null)
    {
        if ($dirs === \null) {
            $dirs = config('packages', []);
        }

        $resources = [];

        foreach ($dirs as $dir) {

            if ($dir instanceof Package) {
                $dir = $dir->getDir();
            }

            $it = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

            foreach ($it as $f) {

                if ($f instanceof \SplFileInfo) {

                    if ($f->isFile() && $f->getExtension() === 'php') {

                        $class = static::getClassFromFile($f->getPathname());

                        if ($class) {

                            $reflection = \null;

                            try {
                                $reflection = new \ReflectionClass($class);
                            } catch (\Exception $e) {

                            }

                            if ($reflection && $reflection->isSubclassOf(\Micro\Application\Controller::class)) {

                                foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {

                                    $name = $method->name;

                                    if ('Action' !== substr($name, -6)) {
										continue;
                                    }

                                    $resources[$class . '@' . substr($name, 0, -6)] = static::parseDocBlock($method->getDocComment());
                                }
                            }
                        }
                    }
                }
            }
        }

        $tree = [];

        foreach ($resources as $resource => $doc) {
            $resource = str_replace(array("\\", "@"), "_", $resource);
            static::buildResourcesTree(explode('_', $resource), $tree, \null, $resource, $doc);
        }

        return $tree;
    }

    public static function buildResourcesTree(array $parts, &$tree, $parent = \null, $resource = \null, array $info = \null)
    {
        $part = array_shift($parts);

        if ($part === \null) {
            return;
        }

        if ($parent === \null) {
            $parent = $part;
        } else {
            $parent = $parent . '_' . $part;
        }

        if (!isset($tree[$part])) {
            $name = $part;
            if ($info !== \null && $resource === $parent && isset($info['translate'])) {
                $name = $info['translate'];
            }
            $tree[$part] = [
                'id' => $parent,
                'name' => $name,
                'resources' => []
            ];
        }

        static::buildResourcesTree($parts, $tree[$part]['resources'], $parent, $resource, $info);
    }

    public static function parseDocBlock($docComment)
    {
        $lines = [];

        // First remove doc block line starters
        $docComment = preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ ]{0,1}(.*)?#', '$1', $docComment);
        $docComment = ltrim($docComment, "\r\n");

        // Next parse out the tags and descriptions
        $parsedDocComment = $docComment;
        $lineNumber = $firstBlandLineEncountered = 0;

        while (($newlinePos = strpos($parsedDocComment, "\n")) !== \false) {
            $lineNumber++;
            $line = substr($parsedDocComment, 0, $newlinePos);

            if ((strpos($line, '@') === 0) && (preg_match('#^(@\w+.*?)(\n)(?:@|\r?\n|$)#s', $parsedDocComment, $matches))) {
                if (preg_match('#^@(\w+)(?:\s+([^\s].*)|$)?#', $matches[1], $submatches)) {
                    $lines[$submatches[1]] = $submatches[2];
                }
                $parsedDocComment = str_replace($matches[1] . $matches[2], '', $parsedDocComment);
            } else {
                if ($line == '') {
                    $firstBlandLineEncountered = \true;
                }
                $parsedDocComment = substr($parsedDocComment, $newlinePos + 1);
            }
        }

        return $lines;
    }

    public static function getClassFromFile($path_to_file)
    {
        //Grab the contents of the file
        $contents = file_get_contents($path_to_file);

        //Start with a blank namespace and class
        $namespace = $class = "";

        //Set helper values to know that we have found the namespace/class token and need to collect the string values after them
        $getting_namespace = $getting_class = \false;

        //Go through each token and evaluate it as necessary
        foreach (token_get_all($contents) as $token) {

            //If this token is the namespace declaring, then flag that the next tokens will be the namespace name
            if (is_array($token) && $token[0] == T_NAMESPACE) {
                $getting_namespace = \true;
            }

            //If this token is the class declaring, then flag that the next tokens will be the class name
            if (is_array($token) && $token[0] == T_CLASS) {
                $getting_class = \true;
            }

            //While we're grabbing the namespace name...
            if ($getting_namespace === \true) {

                //If the token is a string or the namespace separator...
                if(is_array($token) && in_array($token[0], [T_STRING, T_NS_SEPARATOR])) {

                    //Append the token's value to the name of the namespace
                    $namespace .= $token[1];

                }
                else if ($token === ';') {

                    //If the token is the semicolon, then we're done with the namespace declaration
                    $getting_namespace = \false;

                }
            }

            //While we're grabbing the class name...
            if ($getting_class === \true) {

                //If the token is a string, it's the name of the class
                if (is_array($token) && $token[0] == T_STRING) {

                    //Store the token's value as the class name
                    $class = $token[1];

                    //Got what we need, stope here
                    break;
                }
            }
        }

        //Build the fully-qualified class name and return it
        return $namespace ? $namespace . '\\' . $class : $class;
    }
}