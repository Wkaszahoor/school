<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use ReflectionClass;
use ReflectionMethod;

class GenerateApiMethods extends Command
{
    protected $signature = 'api:generate {controller?} {--force}';
    protected $description = 'Generate API methods for controllers based on existing web methods';

    public function handle()
    {
        $controllerName = $this->argument('controller');
        $force = $this->option('force');

        if ($controllerName) {
            $this->generateForController($controllerName, $force);
        } else {
            $this->generateForAllControllers($force);
        }
    }

    private function generateForController($name, $force = false)
    {
        $namespace = $this->detectNamespace($name);
        $className = "\\App\\Http\\Controllers\\{$namespace}\\{$name}";

        if (!class_exists($className)) {
            $this->error("Controller {$className} not found");
            return;
        }

        $this->info("Generating API methods for {$className}...");

        $reflection = new ReflectionClass($className);
        $file = $reflection->getFileName();
        $content = File::get($file);

        // Check if API methods already exist
        if (str_contains($content, 'public function apiIndex') && !$force) {
            $this->warn("API methods already exist. Use --force to overwrite.");
            return;
        }

        $methods = $this->extractPublicMethods($reflection);
        $apiMethods = $this->generateMethods($methods, $className);

        if ($apiMethods) {
            $newContent = $this->insertApiMethods($content, $apiMethods);
            File::put($file, $newContent);
            $this->info("✓ API methods added to {$name}");
        } else {
            $this->warn("No compatible methods found");
        }
    }

    private function generateForAllControllers($force = false)
    {
        $controllerPaths = [
            'Admin',
            'Principal',
            'Teacher',
            'Doctor',
            'Receptionist',
            'Helper',
            'Inventory',
        ];

        foreach ($controllerPaths as $path) {
            $dir = app_path("Http/Controllers/{$path}");
            if (!is_dir($dir)) continue;

            foreach (File::glob("{$dir}/*.php") as $file) {
                $className = basename($file, '.php');
                $this->generateForController($className, $force);
            }
        }
    }

    private function detectNamespace($controllerName)
    {
        $namespaces = ['Admin', 'Principal', 'Teacher', 'Doctor', 'Receptionist', 'Helper', 'Inventory'];

        foreach ($namespaces as $ns) {
            if (class_exists("App\\Http\\Controllers\\{$ns}\\{$controllerName}")) {
                return $ns;
            }
        }

        return '';
    }

    private function extractPublicMethods(ReflectionClass $reflection)
    {
        $methods = [];
        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->getDeclaringClass()->getName() === $reflection->getName() &&
                !str_starts_with($method->getName(), '__') &&
                !str_starts_with($method->getName(), 'api')) {
                $methods[] = $method->getName();
            }
        }
        return $methods;
    }

    private function generateMethods($methods, $className)
    {
        $commonMethods = ['index', 'show', 'create', 'store', 'edit', 'update', 'destroy'];
        $toGenerate = array_intersect($methods, $commonMethods);

        if (empty($toGenerate)) return '';

        $apiMethods = "\n\n    // API METHODS\n";

        foreach ($toGenerate as $method) {
            $apiMethods .= $this->generateMethodStub($method);
        }

        return $apiMethods;
    }

    private function generateMethodStub($method)
    {
        $stubs = [
            'index' => <<<'PHP'
    public function apiIndex(Request $request)
    {
        // TODO: Implement apiIndex based on index() method logic
        // Return: response()->json(['data' => $data, 'meta' => [...]])
    }

PHP,
            'show' => <<<'PHP'
    public function apiShow($model)
    {
        // TODO: Implement apiShow based on show() method logic
        // Return: response()->json(['data' => $model])
    }

PHP,
            'store' => <<<'PHP'
    public function apiStore(Request $request)
    {
        // TODO: Implement apiStore based on store() method logic
        // Return: response()->json(['message' => '...', 'data' => $model], 201)
    }

PHP,
            'update' => <<<'PHP'
    public function apiUpdate(Request $request, $model)
    {
        // TODO: Implement apiUpdate based on update() method logic
        // Return: response()->json(['message' => '...', 'data' => $model])
    }

PHP,
            'destroy' => <<<'PHP'
    public function apiDestroy($model)
    {
        // TODO: Implement apiDestroy based on destroy() method logic
        // Return: response()->json(['message' => '...'])
    }

PHP,
        ];

        return $stubs[$method] ?? '';
    }

    private function insertApiMethods($content, $methods)
    {
        // Find the last closing brace and insert before it
        $lastBrace = strrpos($content, '}');
        if ($lastBrace === false) return $content;

        return substr_replace($content, $methods . "\n", $lastBrace, 0);
    }
}
