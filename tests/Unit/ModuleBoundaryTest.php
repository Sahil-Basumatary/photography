<?php

namespace Tests\Unit;

use App\Modules\Catalog;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class ModuleBoundaryTest extends TestCase
{
    public function test_modules_do_not_reference_one_another(): void
    {
        foreach (Catalog::classes() as $class) {
            $this->assertTrue(class_exists($class));

            $name = class_basename($class);
            $directory = app_path('Modules/'.$name);
            $this->assertDirectoryExists($directory);

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $source = file_get_contents($file->getPathname());
                preg_match_all('/App\\\\Modules\\\\([A-Za-z]+)\\\\/', $source, $matches);

                foreach ($matches[1] as $referenced) {
                    $this->assertSame($name, $referenced, $file->getPathname());
                }
            }
        }
    }
}
