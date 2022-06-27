<?php

require_once __DIR__ . '/../vendor/autoload.php';

use CheckCommonModules\Environment;
use Github\Api\Repo;
use Github\AuthMethod;
use Github\Client;
use Github\Exception\RuntimeException;
use GuzzleHttp\Client as GuzzleClient;
use Http\Client\Common\HttpMethodsClientInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Path;

$githubToken = Environment::get('GITHUB_TOKEN');
if (!$githubToken) {
    throw new RuntimeException('Must set the github token first.');
}

function useRepos(int $pageNum, Client $client)
{
    $mode = Environment::get('COMPOSER_MODE') ?? 'json';
    $org = Environment::get('GITHUB_ORG');
    $reposApi = $client->api('repo');
    /** @var Repo $reposApi */
    $repos = $reposApi->org($org, ['type' => 'all', 'per_page' => 100, 'page' => $pageNum]);
    $outputDir = Path::makeAbsolute(Path::canonicalize('output'), getcwd());
    $fileSystem = new Filesystem();
    /** @var HttpMethodsClientInterface $httpClient */
    $httpClient = $client->getHttpClient();
    foreach ($repos as $repo) {
        // Skip forked, archived, and empty repos
        if ($repo['fork'] === 'true' || $repo['fork'] === true) {
            continue;
        }
        if ($repo['archived'] === 'true' || $repo['archived'] === true) {
            continue;
        }
        if ($repo['size'] === '0' || $repo['size'] === 0) {
            continue;
        }
        // Try to get composer.json or composer.lock from the repo
        try {
            var_dump('trying ' . $repo['name']);
            $composer = $reposApi->contents()->download($org, $repo['name'], 'composer.' . $mode);
        } catch (RuntimeException $e) {
            // If the relevant composer file is missing, just skip this repo
            if ($e->getMessage() !== 'Not Found') {
                throw $e;
            }
            var_dump("composer.$mode doesn't exist in " . $repo['name']);
            continue;
        }
        // Save the composer file in the output dir
        $fileName = Path::join($outputDir, $repo['name'] . '_composer.' . $mode);
        $fileSystem->dumpFile($fileName, $composer);
    }
    if (count($repos) === 100) {
        useRepos($pageNum + 1, $client);
    }
}

$client = Client::createWithHttpClient(new GuzzleClient());
$client->authenticate($githubToken, null, AuthMethod::ACCESS_TOKEN);

useRepos(1, $client);
