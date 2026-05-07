<?php

declare(strict_types=1);

define('REPOS_PATH', getenv('REPOS_PATH') ?: __DIR__ . '/../repos');

function e(string $string): string
{
    return htmlentities($string, ENT_QUOTES, 'UTF-8');
}

$repoPaths = glob(REPOS_PATH . '/*', GLOB_ONLYDIR);
$requestUri = urldecode($_SERVER['REQUEST_URI']);

$repoName = array_find(
    array_map(fn (string $repoPath): string => substr(basename($repoPath), 0, -4), $repoPaths),
    fn (string $name): bool => preg_match('#^/' . preg_quote($name) . '(/|\.git|$)#', $requestUri) === 1,
);

if ($repoName === null) {
    header('HTTP/1.0 404 Not Found');
    echo '404';
    die();
}

$repoPath = REPOS_PATH . '/' . $repoName . '.git';

if (str_starts_with($requestUri, '/' . $repoName . '.git')) {
    $filePath = str_replace('/' . $repoName . '.git/', '', parse_url($requestUri, PHP_URL_PATH));

    if (file_exists($repoPath . '/' . $filePath) === false) {
        header('HTTP/1.0 404 Not Found');
        echo '404';
        exit;
    }

    if (str_starts_with(realpath($repoPath . '/' . $filePath), realpath($repoPath)) === false) {
        header('HTTP/1.0 400 Bad Request');
        exit;
    }

    readfile($repoPath . '/' . $filePath);
    die();
}

$filePaths = shell_exec('git --git-dir=' . escapeshellarg($repoPath) . ' -c core.quotePath=false ls-tree --full-tree --name-only -r HEAD');
$filePaths = is_string($filePaths) === false ? [] : explode("\n", trim($filePaths));
usort(
        $filePaths,
        function(string $filePathA, string $filePathB): int {
            return str_contains($filePathB, '/') <=> str_contains($filePathA, '/') ?: strcmp($filePathA, $filePathB);
        },
);

if (str_starts_with($requestUri, '/' . $repoName . '/file/')) {
    $filePath = array_find(
        $filePaths,
        fn (string $path): bool => $requestUri === '/' . $repoName . '/file/' . $path
    );

    if ($filePath === null) {
        var_dump(1);
        header('HTTP/1.0 404 Not Found');
        echo '404';
        die();
    }

    match(pathinfo($filePath, PATHINFO_EXTENSION)) {
        'jpg', 'jpeg', => header('Content-Type: image/jpeg'),
        'png', => header('Content-Type: image/png'),
        'gif' => header('Content-Type: image/gif'),
        'svg' => header('Content-Type: image/svg+xml'),
        'json' => header('Content-Type: application/json'),
        default => header('Content-Type: text/plain'),
    };

    echo shell_exec('git --git-dir=' . escapeshellarg($repoPath) . ' show ' . escapeshellarg('HEAD:' . $filePath));
    die();
}

if ($requestUri !== '/' . $repoName) {
    header('HTTP/1.0 404 Not Found');
    echo '404';
    die();
}

$branches = shell_exec('git --git-dir=' . escapeshellarg($repoPath) . ' for-each-ref --format="%(refname:short) (%(committerdate:iso))" refs/heads 2>&1');
$branches = $branches === null ? [] : explode("\n", trim($branches));

$readme = shell_exec('git --git-dir=' . escapeshellarg($repoPath) . ' show HEAD:README.md', );
if ($readme !== null) {
    $readme = preg_replace('/<(https?:\/\/[^>]+)>/', '[$1]($1)', $readme);
    $readme = e($readme);
    $readme = preg_replace('/!\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', '<img src="$2" alt="$1">', $readme);
    $readme = preg_replace('/!\[([^\]]*)\]\(([^\)]+)\)/', '<img src="' . $requestUri . '/file/$2" alt="$1">', $readme);
    $readme = preg_replace('/\[([^\]]+)\]\((https?:\/\/[^\)]+)\)/', '<a href="$2">$1</a>', $readme);
    $readme = preg_replace('/\[([^\]]+)\]\(([^\)]+)\)/', '<a href="' . $requestUri . '/file/$2">$1</a>', $readme);
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($repoName) ?></title>
    <link href="/styles.css" rel="stylesheet">
</head>
<body>

<h1><?= e($repoName) ?></h1>

<div style="display: grid; grid-template-columns: 450px 8fr; grid-gap: 10px;">
    <div style="overflow-y: scroll; text-wrap: nowrap;">
        <h2>Branches</h2>
        <table style="width: 100%;">
            <?php foreach ($branches as $branch) : ?>
                <tr>
                    <td><?= $branch ?></td>
                </tr>
            <?php endforeach; ?>
        </table>


        <h2>Files</h2>
        <table class="files" style="width: 100%;">
            <thead>
            <tr>
                <th>Name</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($filePaths as $filePath) : ?>
                <tr>
                    <td>
                        <?php
                        $filePathDirectory = dirname($filePath);

                        if ($filePathDirectory !== '.') {
                            $label = '<span class="dir">' . e($filePathDirectory) . '</span>/' . e(basename($filePath));
                        } else {
                            $label = e($filePath);
                        }
                        ?>
                        <a href="/<?= e($repoName) ?>/file/<?= e($filePath) ?>"><?= $label ?></a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div>
        <?php if ($readme !== null) : ?>
            <pre id="readme" style="text-wrap: auto"><?= $readme ?></pre>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
