<?php
// scripts/seed.php
// Run from CLI: php scripts/seed.php
// Or via browser (once) after uploading to your host. Remove after use.
require_once __DIR__ . '/../web/config.php';

try {
    // Try to connect to MySQL. If driver is missing or connection fails, fall back to generating
    // the final SQL dump without attempting to execute statements against a DB.
    $dsn = 'mysql:host=' . DB_HOST . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $canUsePdo = true;
    } catch (PDOException $pdoEx) {
        echo "Notice: DB connection failed ({$pdoEx->getMessage()}); falling back to offline SQL dump generation.\n";
        $canUsePdo = false;
    }

    $masterPass = 'password';
    $masterHash = password_hash($masterPass, PASSWORD_DEFAULT);

    // Data we want in the dump
    $followers = [
        ['username' => 'john', 'account_name' => "John's Growth", 'account_id' => 'FG-456', 'balance' => 25000.00, 'status' => 'Connected'],
        ['username' => 'sarah', 'account_name' => "Sarah's Portfolio", 'account_id' => 'HJ-789', 'balance' => 50000.00, 'status' => 'Disconnected'],
        ['username' => 'retire', 'account_name' => 'Retirement Fund', 'account_id' => 'KL-101', 'balance' => 150000.00, 'status' => 'Disconnected'],
        ['username' => 'aggressive', 'account_name' => 'Aggressive Bets', 'account_id' => 'MN-212', 'balance' => 10000.00, 'status' => 'Connected'],
        ['username' => 'test', 'account_name' => 'Test Account', 'account_id' => 'OP-313', 'balance' => 5000.00, 'status' => 'Error'],
    ];

    $created = [];

    if ($canUsePdo) {
        echo "Executing schema...\n";
        $sql = file_get_contents(__DIR__ . '/../sql/schema.sql');
        $parts = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));
        foreach ($parts as $p) {
            if ($p) { $pdo->exec($p); }
        }

        // Insert master
        $stmt = $pdo->prepare('INSERT INTO users (username, password_hash, role, account_id, account_name, balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute(['master', $masterHash, 'master', 'ALICEBLUE-1', 'Master Account', 125430.50, 'Connected']);

        // Followers
        $ins = $pdo->prepare('INSERT INTO users (username, password_hash, role, account_id, account_name, telegram_id, balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        foreach ($followers as $f) {
            $pw = substr(bin2hex(random_bytes(4)), 0, 8);
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $ins->execute([$f['username'], $hash, 'follower', $f['account_id'], $f['account_name'], '@' . $f['username'], $f['balance'], $f['status']]);
            $created[] = ['username' => $f['username'], 'password' => $pw, 'account_id' => $f['account_id']];
        }

        // Risk settings and trades, logs
        $pdo->exec("INSERT INTO risk_settings (is_global, lot_multiplier, daily_loss_limit, max_exposure_per_symbol, created_at) VALUES (1, 1.000, 5000.00, 10000.00, NOW())");
        $pdo->exec("INSERT INTO risk_settings (account_id, lot_multiplier, daily_loss_limit, max_exposure_per_symbol, created_at) VALUES (2, 1.000, 2000.00, 5000.00, NOW())");

        $tins = $pdo->prepare('INSERT INTO trades (account_id, symbol, type, order_type, quantity, price, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
        $tins->execute([2, 'AAPL', 'Buy', 'Limit', 100, 172.25]);
        $tins->execute([3, 'GOOGL', 'Sell', 'Market', 50, 135.50]);
        $tins->execute([4, 'TSLA', 'Buy', 'Stop', 200, 245.00]);

        $lin = $pdo->prepare('INSERT INTO logs (type, message, meta, created_at) VALUES (?, ?, ?, NOW())');
        $lin->execute(['system', 'Database seeded', json_encode(['by' => php_uname('n')])]);
        $lin->execute(['trade', 'Master executed BUY AAPL', json_encode(['symbol'=>'AAPL','qty'=>100])]);

        // Build dump from the DB
        $tables = ['users','risk_settings','trades','logs'];
        $dump = "-- EquityMirror final dump\nSET FOREIGN_KEY_CHECKS=0;\n\n";
        foreach ($tables as $table) {
            $rows = $pdo->query("SELECT * FROM {$table}")->fetchAll();
            if (!$rows) continue;
            $cols = array_keys($rows[0]);
            $dump .= "TRUNCATE TABLE `{$table}`;\n";
            foreach ($rows as $r) {
                $vals = [];
                foreach ($cols as $c) {
                    if ($r[$c] === null) { $vals[] = 'NULL'; continue; }
                    $vals[] = "'" . addslashes($r[$c]) . "'";
                }
                $dump .= "INSERT INTO `{$table}` (`" . implode('`,`', $cols) . "`) VALUES (" . implode(',', $vals) . ");\n";
            }
            $dump .= "\n";
        }
        $dump .= "SET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents(__DIR__ . '/../sql/final_dump.sql', $dump);

        echo "Seeding complete (DB). SQL dump written to sql/final_dump.sql\n";
    } else {
        // Build dump offline without DB
        echo "Generating offline SQL dump...\n";
        $rows_users = [];
        $now = date('Y-m-d H:i:s');
        // Master row
        $rows_users[] = [
            'id' => 1,
            'username' => 'master',
            'password_hash' => $masterHash,
            'role' => 'master',
            'account_id' => 'ALICEBLUE-1',
            'account_name' => 'Master Account',
            'telegram_id' => null,
            'balance' => '125430.50',
            'lots' => 'NULL',
            'status' => 'Connected',
            'created_at' => $now
        ];
        $nextId = 2;
        $rows_risk = [];
        foreach ($followers as $f) {
            $pw = substr(bin2hex(random_bytes(4)), 0, 8);
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $rows_users[] = [
                'id' => $nextId,
                'username' => $f['username'],
                'password_hash' => $hash,
                'role' => 'follower',
                'account_id' => $f['account_id'],
                'account_name' => $f['account_name'],
                'telegram_id' => '@' . $f['username'],
                'balance' => number_format($f['balance'],2,'.',''),
                'lots' => 'NULL',
                'status' => $f['status'],
                'created_at' => $now
            ];
            $created[] = ['username' => $f['username'], 'password' => $pw, 'account_id' => $f['account_id']];
            $nextId++;
        }

        // simple risk rows
        $rows_risk[] = [ 'id'=>1, 'account_id'=>NULL, 'is_global'=>1, 'lot_multiplier'=>'1.000', 'daily_loss_limit'=>'5000.00', 'max_exposure_per_symbol'=>'10000.00', 'created_at'=>$now ];
        $rows_risk[] = [ 'id'=>2, 'account_id'=>2, 'is_global'=>0, 'lot_multiplier'=>'1.000', 'daily_loss_limit'=>'2000.00', 'max_exposure_per_symbol'=>'5000.00', 'created_at'=>$now ];

        // sample trades
        $rows_trades = [
            [ 'id'=>1, 'account_id'=>2, 'symbol'=>'AAPL', 'type'=>'Buy', 'order_type'=>'Limit', 'quantity'=>100, 'price'=>'172.2500', 'created_at'=>$now ],
            [ 'id'=>2, 'account_id'=>3, 'symbol'=>'GOOGL', 'type'=>'Sell', 'order_type'=>'Market', 'quantity'=>50, 'price'=>'135.5000', 'created_at'=>$now ],
            [ 'id'=>3, 'account_id'=>4, 'symbol'=>'TSLA', 'type'=>'Buy', 'order_type'=>'Stop', 'quantity'=>200, 'price'=>'245.0000', 'created_at'=>$now ],
        ];

        $rows_logs = [
            [ 'id'=>1, 'type'=>'system', 'message'=>'Database seeded', 'meta'=>json_encode(['by'=>php_uname('n')]), 'created_at'=>$now ],
            [ 'id'=>2, 'type'=>'trade', 'message'=>'Master executed BUY AAPL', 'meta'=>json_encode(['symbol'=>'AAPL','qty'=>100]), 'created_at'=>$now ]
        ];

        // Build SQL
        $dump = "-- EquityMirror final dump (offline generation)\nSET FOREIGN_KEY_CHECKS=0;\n\n";

        // users
        $dump .= "TRUNCATE TABLE `users`;\n";
        foreach ($rows_users as $r) {
            $vals = [];
            foreach (['id','username','password_hash','role','account_id','account_name','telegram_id','balance','lots','status','created_at'] as $c) {
                $v = $r[$c];
                if ($v === null || $v === 'NULL') { $vals[] = 'NULL'; } else { $vals[] = "'" . addslashes($v) . "'"; }
            }
            $dump .= "INSERT INTO `users` (`id`,`username`,`password_hash`,`role`,`account_id`,`account_name`,`telegram_id`,`balance`,`lots`,`status`,`created_at`) VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\n";

        // risk_settings
        $dump .= "TRUNCATE TABLE `risk_settings`;\n";
        foreach ($rows_risk as $r) {
            $vals = [];
            foreach (['id','account_id','is_global','lot_multiplier','daily_loss_limit','max_exposure_per_symbol','created_at'] as $c) {
                $v = $r[$c];
                if ($v === null || $v === 'NULL') { $vals[] = 'NULL'; } else { $vals[] = "'" . addslashes($v) . "'"; }
            }
            $dump .= "INSERT INTO `risk_settings` (`id`,`account_id`,`is_global`,`lot_multiplier`,`daily_loss_limit`,`max_exposure_per_symbol`,`created_at`) VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\n";

        // trades
        $dump .= "TRUNCATE TABLE `trades`;\n";
        foreach ($rows_trades as $r) {
            $vals = [];
            foreach (['id','account_id','symbol','type','order_type','quantity','price','created_at'] as $c) {
                $v = $r[$c];
                if ($v === null || $v === 'NULL') { $vals[] = 'NULL'; } else { $vals[] = "'" . addslashes($v) . "'"; }
            }
            $dump .= "INSERT INTO `trades` (`id`,`account_id`,`symbol`,`type`,`order_type`,`quantity`,`price`,`created_at`) VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\n";

        // logs
        $dump .= "TRUNCATE TABLE `logs`;\n";
        foreach ($rows_logs as $r) {
            $vals = [];
            foreach (['id','type','message','meta','created_at'] as $c) {
                $v = $r[$c];
                if ($v === null || $v === 'NULL') { $vals[] = 'NULL'; } else { $vals[] = "'" . addslashes($v) . "'"; }
            }
            $dump .= "INSERT INTO `logs` (`id`,`type`,`message`,`meta`,`created_at`) VALUES (" . implode(',', $vals) . ");\n";
        }
        $dump .= "\nSET FOREIGN_KEY_CHECKS=1;\n";
        file_put_contents(__DIR__ . '/../sql/final_dump.sql', $dump);

        echo "Offline SQL dump written to sql/final_dump.sql\n";
    }

    echo "Created follower credentials:\n";
    foreach ($created as $c) {
        echo " - {$c['username']} / {$c['password']} (Account: {$c['account_id']})\n";
    }

    echo "\nIMPORTANT: Remove or secure scripts/seed.php after running it on your host.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>