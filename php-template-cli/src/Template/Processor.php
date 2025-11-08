<?php

namespace Template;

use Utils\VariablesResolver;

class Processor
{
    private $variablesResolver;

    public function __construct(VariablesResolver $variablesResolver)
    {
        $this->variablesResolver = $variablesResolver;
    }

    public function processTemplates(string $directory): void
    {
        $files = glob($directory . '/*.tpl');

        foreach ($files as $file) {
            $this->processTemplateFile($file);
        }
    }

    private function processTemplateFile(string $file): void
    {
        $content = file_get_contents($file);
        $processedContent = $this->variablesResolver->resolveVariables($content);
        $outputFile = str_replace('.tpl', '.processed', $file);
        file_put_contents($outputFile, $processedContent);
    }
}