<?php

declare(strict_types=1);

define('REPOS_PATH', getenv('REPOS_PATH') ?: __DIR__ . '/../repos');

function e(string $string): string
{
    return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

$repoPaths = glob(REPOS_PATH . '/*', GLOB_ONLYDIR);

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Repositories</title>
    <link href="/styles.css" rel="stylesheet">
</head>
<body>
<table>
    <thead>
    <tr>
        <th>Name</th>
        <th>Last update</th>
    </tr>
    </thead>
    <colgroup>
        <col style="width: 300px;">
        <col>
    </colgroup>
    <tbody>
    <?php foreach ($repoPaths as $repoPath) : ?>
        <tr>
            <td>
                <a href="/<?= e(basename($repoPath)); ?>">
                    <?= e(basename($repoPath)) ?>
                </a>
            </td>
            <td style="text-align: right">
                <?php $date = shell_exec('git --git-dir=' . escapeshellarg($repoPath) . ' log -1 --format=%ci'); ?>
                <?php if ($date === null) : ?>
                    <em>no commits</em>
                <?php else : ?>
                    <?= new DateTimeImmutable($date)->format('j M Y, H:i') ?>
                <?php endif; ?>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>