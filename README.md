# Check common modules

## Setup

At a minimum, you must add a github token as `GITHUB_TOKEN` in the .env file before running `src/getRepos.php`.
The token must have necessary permissions to get private repo data, unless you change `GITHUB_ORG` to a public
organisation or are otherwise only interested in public data. In that case you will need to change the logic a
bit, because `getRepos` is written in a way that expects a token.

## Get all of the composer.json files from repositories in the silverstripeltd organisation

Skips empty, archived, and forked repositories, and any repositories that don't contain a composer.json file.
Outputs the composer.json into the output dir.

```bash
php src/getRepos.php
```

## Parse the composer.json files and make a list of the most commonly used dependencies

Outputs a file to output/dependencies.csv with a list of dependencies, their count, and what level of support
it currently has (if known).

```bash
php src/collateDependencies.php
```

### Levels of support

- Core
  - A core module according to [silverstripe/supported-modules](https://github.com/silverstripe/supported-modules)
- Supported
  - Commercially supported according to [silverstripe/supported-modules](https://github.com/silverstripe/supported-modules)
- Satellite
  - Sits in the silverstripe organisation, but not commercially supported
- Unknown
  - Probably a community module, but might also be owned by bespoke, for example
