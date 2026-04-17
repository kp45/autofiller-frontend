<?php

function is_password_hash(string $storedPassword): bool
{
    $info = password_get_info($storedPassword);
    return !empty($info['algo']);
}

function verify_password_compat(PDO $pdo, string $table, int $id, string $plainPassword, string $storedPassword): bool
{
    if (is_password_hash($storedPassword)) {
        return password_verify($plainPassword, $storedPassword);
    }

    if (!hash_equals($storedPassword, $plainPassword)) {
        return false;
    }

    $newHash = password_hash($plainPassword, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare(sprintf('UPDATE %s SET password = ? WHERE id = ?', $table));
    $stmt->execute([$newHash, $id]);

    return true;
}
