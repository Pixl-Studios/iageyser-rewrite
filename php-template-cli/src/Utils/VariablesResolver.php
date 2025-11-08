<?php

namespace Utils;

class VariablesResolver
{
    private array $variables;

    public function __construct(array $variables)
    {
        $this->variables = $variables;
    }

    public function resolve(string $template): string
    {
        return preg_replace_callback('/{{\s*(\w+)\s*}}/', function ($matches) {
            return $this->variables[$matches[1]] ?? '';
        }, $template);
    }

    public function fillVariablesInFiles(string $directory): void
    {
        $files = glob($directory . '/*.txt'); // Assuming template files are .txt

        foreach ($files as $file) {
            $content = file_get_contents($file);
            $resolvedContent = $this->resolve($content);
            file_put_contents($file, $resolvedContent);
        }
    }
}