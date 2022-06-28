<?php

use CheckCommonModules\Environment;
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

require_once __DIR__ . '/../vendor/autoload.php';

$outputDir = Path::makeAbsolute(Path::canonicalize('output'), getcwd());
$fileSystem = new Filesystem();
$dependencies = [];
$mode = Environment::get('COMPOSER_MODE') ?? 'json';

// Get supported modules
/** @var Response $response */
$response = (new Client())->request('GET', 'https://raw.githubusercontent.com/silverstripe/supported-modules/gh-pages/modules.json');
$supported = json_decode($response->getBody()->getContents(), true);

// Loop through composer.json files
foreach (new DirectoryIterator($outputDir) as $fileInfo) {
    // Skip non-json, directories, and hidden/unreadable files.
    if ($fileInfo->isDir() || $fileInfo->isDot() || !$fileInfo->isReadable() || $fileInfo->getExtension() !== $mode) {
        continue;
    }
    
    $fileName = $fileInfo->getBasename('.' . $mode);
    var_dump("Checking $fileName");

    // Parse JSON
    $json = json_decode(file_get_contents($fileInfo->getPathname()), true);
    if ($json === null && json_last_error() !== JSON_ERROR_NONE) {
        var_dump("Error while parsing $fileName: " . json_last_error_msg());
        continue;
    }

    // Skip modules, recipes, etc if possible
    if ($mode === 'json' && (isset($json['type']) && $json['type'] !== 'library' && $json['type'] !== 'project')) {
        var_dump("Skipping $fileName because it is a type " . $json['type']);
        continue;
    }

    // Get a count of dependencies
    if ($mode === 'json') {
        foreach (['require', 'require-dev'] as $key) {
            if (isset($json[$key])) {
                foreach ($json[$key] as $repo => $constraint) {
                    // Skip php and php extensions
                    if ($repo === 'php' || (str_starts_with($repo, 'ext-') && !str_contains($repo, '/'))) {
                        continue;
                    }
                    if (!array_key_exists($repo, $dependencies)) {
                        $dependencies[$repo] = 0;
                    }
                    $dependencies[$repo]++;
                }
            }
        }
    } else {
        foreach (['packages', 'packages-dev'] as $key) {
            if (isset($json[$key])) {
                foreach ($json[$key] as $package) {
                    $repo = $package['name'];
                    if (!array_key_exists($repo, $dependencies)) {
                        $dependencies[$repo] = 0;
                    }
                    $dependencies[$repo]++;
                }
            }
        }
    }
}

// Remove dependencies with <= 5 dependants
$threshold = Environment::get('COUNT_THRESHOlD') ?? 0;
foreach ($dependencies as $dep => $count) {
    if ($count <= (int)$threshold) {
        unset($dependencies[$dep]);
    }
}
// Sort from highest to lowest
arsort($dependencies);

// Prepare CSV headers
$header = [
    'repository',
    'num dependants',
    'status'
];
$records = [];

// Gets the support status of a dependency (core, supported, satellite, or unknown)
$getStatus = function(string $dependency) use ($supported): string
{
    // Check if the dependency is supported/core
    $isSupported = false;
    $isCore = false;
    foreach ($supported as $module) {
        if ($module['composer'] && $dependency === $module['composer']) {
            $isSupported = true;
            $isCore = $module['isCore'];
            continue;
        }
    }

    // Return the correct status
    if ($isCore) {
        return 'core';
    }
    if ($isSupported) {
        return 'supported';
    }
    if (str_starts_with($dependency, 'silverstripe/')) {
        return 'satellite';
    }
    return 'unknown';
};

// Prepare dependencies for CSV export
foreach ($dependencies as $dependency => $count) {
    $records[] = [
        $dependency,
        $count,
        $getStatus($dependency),
    ];
}
// Export CSV into output dir
$csv = Writer::createFromString();
$csv->insertOne($header);
$csv->insertAll($records);
var_dump('Outputting CSV');
file_put_contents(Path::join($outputDir, "$mode-dependencies.csv"), $csv->toString());
