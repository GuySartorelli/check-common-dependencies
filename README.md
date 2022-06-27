## Check common modules

Get all of the composer.json files from repositories in the silverstripeltd organisation.
Skips empty, archived, and forked repositories, and any repositories that don't contain a composer.json file.
Outputs the composer.json into the output dir.

NOTE: You must edit the `src/getRepos.php` file to include your token before running the command. The token
must have necessary permissions to get private repo data.

```bash
php src/getRepos.php
```

Parse the composer.json files and make a list of the most commonly used dependencies

```bash
php src/collateDependencies.php
```
