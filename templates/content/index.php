<?php
function is_hledger_account_name($s)
{
    $top_level_accounts = [
        'assets',
        'liabilities',
        'equity',
        'income',
        'expenses'
    ];
    foreach ($top_level_accounts as $account) {
        if (str_starts_with($s, $account . ':')) {
            return true;
        }
    }
    return false;
}
?>

<table class="hledger-data">

<?php
foreach ($_['report'] as $row) {
    $outline = in_array(trim($row[0]), ['Account', 'Total:']) ? 'outline' : '';

    if ($row[0] == 'Account' || is_hledger_account_name($row[0])) {
        $row[0] = "&nbsp;&nbsp;&nbsp;&nbsp;" . $row[0];
    }

    ?><tr><td class="<?= $outline ?>"><?= $row[0] ?></td><?php

    for ($i = 1; $i < count($row); $i++) {
        ?><td class="<?= $outline ?>"><?= $row[$i] ?></td><?php
    }

    ?></tr><?php
}
?>
</table>
