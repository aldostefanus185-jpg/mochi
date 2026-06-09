<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deteksi apakah sedang berjalan di local development (localhost atau CLI)
$is_local = (php_sapi_name() === 'cli') || !isset($_SERVER['HTTP_HOST']) || in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', '[::1]']);

if ($is_local) {
    // ==========================================
    // KONFIGURASI LOCAL (XAMPP/MAMP/LARAGON)
    // ==========================================
    $host = 'localhost';
    $port = '3307';            // Port default MySQL XAMPP local
    $dbname = 'daifuku_mochi';
    $username = 'root';
    $password = '';
} else {
    // ==========================================
    // KONFIGURASI HOSTING (SESUAIKAN DI SINI)
    // ==========================================
    $host = 'localhost';       // Biasanya tetap localhost di cPanel/hosting
    $port = '3306';            // Port default MySQL hosting
    $dbname = 'daifuku_mochi'; // GANTI dengan nama database hosting Anda
    $username = 'root';        // GANTI dengan username database hosting Anda
    $password = '';            // GANTI dengan password database hosting Anda
}

try {
    // Coba koneksi langsung ke database MySQL asli
    $conn = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $_SESSION['demo_mode'] = false;
} catch (PDOException $e) {
    // Jika koneksi MySQL gagal (misal di hosting tanpa DB atau di local tanpa menyalakan XAMPP),
    // sistem otomatis masuk ke MOCK DATABASE MODE (Session-based) sehingga web tetap bisa di-hosting di mana saja
    // dan seluruh tampilan sistem/UI tetap berfungsi penuh secara interaktif.
    $_SESSION['demo_mode'] = true;
    $conn = new MockPDO();
}

// =========================================================================
// MOCK DATABASE DRIVER (PHP SESSION)
// Digunakan agar aplikasi dapat berjalan tanpa MySQL Database (Hostable & Portable)
// =========================================================================
class MockPDO {
    public function __construct() {
        if (!isset($_SESSION['mock_db'])) {
            $_SESSION['mock_db'] = [
                'users' => [
                    1 => ['ID_USER' => 1, 'USERNAME' => 'admin', 'PASSWORD' => 'admin123', 'ROLE' => 'admin'],
                    2 => ['ID_USER' => 2, 'USERNAME' => 'customer', 'PASSWORD' => 'customer123', 'ROLE' => 'pelanggan']
                ],
                'produk' => [
                    1 => ['ID_PRODUK' => 1, 'NAMA_PRODUK' => 'Chocolate', 'HARGA' => 10000, 'STOK' => 50, 'FOTO' => 'image/coklat.jpg', 'DESKRIPSI' => 'Mochi lembut isian coklat premium'],
                    2 => ['ID_PRODUK' => 2, 'NAMA_PRODUK' => 'Strawberry', 'HARGA' => 10000, 'STOK' => 50, 'FOTO' => 'image/strowberry.jpg', 'DESKRIPSI' => 'Mochi segar rasa strawberry'],
                    3 => ['ID_PRODUK' => 3, 'NAMA_PRODUK' => 'Matcha', 'HARGA' => 10000, 'STOK' => 50, 'FOTO' => 'image/matcha.jpg', 'DESKRIPSI' => 'Mochi matcha Jepang asli'],
                    4 => ['ID_PRODUK' => 4, 'NAMA_PRODUK' => 'Blueberry', 'HARGA' => 10000, 'STOK' => 50, 'FOTO' => 'image/anggur.jpg', 'DESKRIPSI' => 'Mochi rasa blueberry segar'],
                    5 => ['ID_PRODUK' => 5, 'NAMA_PRODUK' => 'Manggo Creamy', 'HARGA' => 10000, 'STOK' => 50, 'FOTO' => 'image/manggo.jpg', 'DESKRIPSI' => 'Mochi mangga creamy']
                ],
                'keranjang' => [],
                'pesanan' => [],
                'detail_pesanan' => []
            ];
            $_SESSION['mock_db_increments'] = [
                'users' => 2,
                'produk' => 5,
                'keranjang' => 0,
                'pesanan' => 0,
                'detail_pesanan' => 0
            ];
        }
    }

    public function prepare($sql) {
        return new MockPDOStatement($sql);
    }

    public function query($sql) {
        $stmt = new MockPDOStatement($sql);
        $stmt->execute();
        return $stmt;
    }

    public function beginTransaction() { return true; }
    public function commit() { return true; }
    public function rollBack() { return true; }

    public function lastInsertId() {
        return $_SESSION['last_insert_id'] ?? 0;
    }
    
    public function setAttribute($attr, $val) {}
}

class MockPDOStatement {
    private $sql;
    private $results = [];
    private $index = 0;

    public function __construct($sql) {
        $this->sql = trim(preg_replace('/\s+/', ' ', $sql));
    }

    public function execute($params = []) {
        $sql = $this->sql;
        $db = &$_SESSION['mock_db'];
        $inc = &$_SESSION['mock_db_increments'];
        $this->results = [];
        $this->index = 0;

        // 1. SELECT FROM USERS
        if (stripos($sql, 'SELECT * FROM USERS') !== false || stripos($sql, 'SELECT COUNT(*) AS JML FROM USERS') !== false) {
            if (stripos($sql, 'COUNT(*)') !== false) {
                if (stripos($sql, "ROLE='pelanggan'") !== false) {
                    $count = 0;
                    foreach ($db['users'] as $user) {
                        if ($user['ROLE'] === 'pelanggan') {
                            $count++;
                        }
                    }
                    $this->results = [['JML' => $count]];
                } else {
                    $u = $params[':u'] ?? $params[':new_username'] ?? '';
                    $u_id = $params[':u_id'] ?? null;
                    $count = 0;
                    foreach ($db['users'] as $user) {
                        if (strtolower($user['USERNAME']) === strtolower($u)) {
                            if ($u_id === null || $user['ID_USER'] != $u_id) {
                                $count++;
                            }
                        }
                    }
                    $this->results = [['JML' => $count]];
                }
            } else if (stripos($sql, 'USERNAME = :username') !== false || stripos($sql, 'USERNAME=:username') !== false) {
                $user_found = null;
                $uname = $params[':username'] ?? '';
                $pass = $params[':password'] ?? '';
                foreach ($db['users'] as $user) {
                    if (strtolower($user['USERNAME']) === strtolower($uname) && $user['PASSWORD'] === $pass) {
                        $user_found = $user;
                        break;
                    }
                }
                $this->results = $user_found ? [$user_found] : [];
            } else if (preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches)) {
                $param_key = $matches[1];
                $uid = $params[$param_key] ?? 0;
                $this->results = isset($db['users'][$uid]) ? [$db['users'][$uid]] : [];
            } else {
                $this->results = array_values($db['users']);
            }
        }
        // 2. INSERT INTO USERS
        else if (stripos($sql, 'INSERT INTO USERS') !== false) {
            $inc['users']++;
            $new_id = $inc['users'];
            $db['users'][$new_id] = [
                'ID_USER' => $new_id,
                'USERNAME' => $params[':u'] ?? $params[':username'] ?? '',
                'PASSWORD' => $params[':p'] ?? $params[':password'] ?? '',
                'ROLE' => $params[':r'] ?? $params[':role'] ?? 'pelanggan'
            ];
            $_SESSION['last_insert_id'] = $new_id;
            $this->results = [];
        }
        // 3. UPDATE USERS
        else if (stripos($sql, 'UPDATE USERS') !== false) {
            preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':u_id';
            $uid = $params[$param_key] ?? 0;
            if (isset($db['users'][$uid])) {
                if (isset($params[':u'])) {
                    $db['users'][$uid]['USERNAME'] = $params[':u'];
                }
                if (isset($params[':p'])) {
                    $db['users'][$uid]['PASSWORD'] = $params[':p'];
                }
            }
            $this->results = [];
        }
        // 4. DELETE FROM USERS
        else if (stripos($sql, 'DELETE FROM USERS') !== false) {
            preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id';
            $uid = $params[$param_key] ?? 0;
            unset($db['users'][$uid]);
            $this->results = [];
        }
        // 5. SELECT FROM PRODUK
        else if (stripos($sql, 'FROM PRODUK') !== false) {
            if (stripos($sql, 'COUNT(*)') !== false) {
                $this->results = [['JML' => count($db['produk'])]];
            } else if (preg_match('/ID_PRODUK\s*=\s*(:\w+)/i', $sql, $matches)) {
                $param_key = $matches[1];
                $pid = $params[$param_key] ?? 0;
                $this->results = isset($db['produk'][$pid]) ? [$db['produk'][$pid]] : [];
            } else {
                $this->results = array_values($db['produk']);
            }
        }
        // 6. INSERT INTO PRODUK
        else if (stripos($sql, 'INSERT INTO PRODUK') !== false) {
            $inc['produk']++;
            $new_id = $inc['produk'];
            $db['produk'][$new_id] = [
                'ID_PRODUK' => $new_id,
                'NAMA_PRODUK' => $params[':n'] ?? '',
                'HARGA' => $params[':h'] ?? 0,
                'STOK' => $params[':s'] ?? 0,
                'FOTO' => $params[':f'] ?? '',
                'DESKRIPSI' => $params[':d'] ?? ''
            ];
            $_SESSION['last_insert_id'] = $new_id;
            $this->results = [];
        }
        // 7. UPDATE PRODUK
        else if (stripos($sql, 'UPDATE PRODUK') !== false) {
            preg_match('/ID_PRODUK\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id';
            $pid = $params[$param_key] ?? 0;
            if (isset($db['produk'][$pid])) {
                $db['produk'][$pid]['NAMA_PRODUK'] = $params[':n'] ?? $db['produk'][$pid]['NAMA_PRODUK'];
                $db['produk'][$pid]['HARGA'] = $params[':h'] ?? $db['produk'][$pid]['HARGA'];
                $db['produk'][$pid]['STOK'] = $params[':s'] ?? $db['produk'][$pid]['STOK'];
                $db['produk'][$pid]['FOTO'] = $params[':f'] ?? $db['produk'][$pid]['FOTO'];
                $db['produk'][$pid]['DESKRIPSI'] = $params[':d'] ?? $db['produk'][$pid]['DESKRIPSI'];
            }
            $this->results = [];
        }
        // 8. DELETE FROM PRODUK
        else if (stripos($sql, 'DELETE FROM PRODUK') !== false) {
            preg_match('/ID_PRODUK\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id';
            $pid = $params[$param_key] ?? 0;
            unset($db['produk'][$pid]);
            $this->results = [];
        }
        // 9. SELECT FROM KERANJANG
        else if (stripos($sql, 'FROM KERANJANG') !== false) {
            if (stripos($sql, 'SUM(JUMLAH)') !== false || stripos($sql, 'COALESCE(SUM(JUMLAH),0)') !== false) {
                preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
                $param_key = $matches[1] ?? ':u_id';
                $uid = $params[$param_key] ?? 0;
                $sum = 0;
                foreach ($db['keranjang'] as $item) {
                    if ($item['ID_USER'] == $uid) {
                        $sum += $item['JUMLAH'];
                    }
                }
                $this->results = [['TOTAL' => $sum]];
            } else if (stripos($sql, 'ID_PRODUK') !== false) {
                $uid = $params[':u_id'] ?? 0;
                $pid = $params[':pid'] ?? 0;
                $found = null;
                foreach ($db['keranjang'] as $item) {
                    if ($item['ID_USER'] == $uid && $item['ID_PRODUK'] == $pid) {
                        $found = $item;
                        break;
                    }
                }
                $this->results = $found ? [$found] : [];
            } else {
                preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
                $param_key = $matches[1] ?? ':u_id';
                $uid = $params[$param_key] ?? 0;
                $res = [];
                foreach ($db['keranjang'] as $item) {
                    if ($item['ID_USER'] == $uid) {
                        $p = $db['produk'][$item['ID_PRODUK']] ?? ['NAMA_PRODUK'=>'', 'HARGA'=>0, 'FOTO'=>''];
                        $res[] = array_merge($item, [
                            'NAMA_PRODUK' => $p['NAMA_PRODUK'],
                            'HARGA' => $p['HARGA'],
                            'FOTO' => $p['FOTO']
                        ]);
                    }
                }
                $this->results = $res;
            }
        }
        // 10. INSERT INTO KERANJANG
        else if (stripos($sql, 'INSERT INTO KERANJANG') !== false) {
            $inc['keranjang']++;
            $new_id = $inc['keranjang'];
            $db['keranjang'][$new_id] = [
                'ID_KERANJANG' => $new_id,
                'ID_USER' => $params[':u_id'] ?? $params[':u'] ?? 0,
                'ID_PRODUK' => $params[':pid'] ?? 0,
                'JUMLAH' => 1
            ];
            $this->results = [];
        }
        // 11. UPDATE KERANJANG
        else if (stripos($sql, 'UPDATE KERANJANG') !== false) {
            preg_match('/ID_KERANJANG\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id';
            $kid = $params[$param_key] ?? 0;
            if (isset($db['keranjang'][$kid])) {
                $db['keranjang'][$kid]['JUMLAH'] = $params[':jml'] ?? $db['keranjang'][$kid]['JUMLAH'];
            }
            $this->results = [];
        }
        // 12. DELETE FROM KERANJANG
        else if (stripos($sql, 'DELETE FROM KERANJANG') !== false) {
            if (stripos($sql, 'ID_USER') !== false) {
                preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
                $param_key = $matches[1] ?? ':u_id';
                $uid = $params[$param_key] ?? 0;
                foreach ($db['keranjang'] as $k => $item) {
                    if ($item['ID_USER'] == $uid) {
                        unset($db['keranjang'][$k]);
                    }
                }
            } else {
                preg_match('/ID_KERANJANG\s*=\s*(:\w+)/i', $sql, $matches);
                $param_key = $matches[1] ?? ':id';
                $kid = $params[$param_key] ?? 0;
                unset($db['keranjang'][$kid]);
            }
            $this->results = [];
        }
        // 13. SELECT FROM PESANAN
        else if (stripos($sql, 'FROM PESANAN') !== false) {
            if (stripos($sql, 'COUNT(*)') !== false) {
                $this->results = [['JML' => count($db['pesanan'])]];
            } else if (stripos($sql, 'SUM(TOTAL_HARGA)') !== false || stripos($sql, 'COALESCE(SUM(TOTAL_HARGA),0)') !== false) {
                $sum = 0;
                foreach ($db['pesanan'] as $o) {
                    $sum += $o['TOTAL_HARGA'];
                }
                $this->results = [['JML' => $sum]];
            } else if (stripos($sql, 'ID_USER') !== false) {
                preg_match('/ID_USER\s*=\s*(:\w+)/i', $sql, $matches);
                $param_key = $matches[1] ?? ':u_id';
                $uid = $params[$param_key] ?? 0;
                $res = [];
                foreach ($db['pesanan'] as $o) {
                    if ($o['ID_USER'] == $uid) {
                        $res[] = $o;
                    }
                }
                usort($res, function($a, $b) { return $b['ID_PESANAN'] - $a['ID_PESANAN']; });
                $this->results = $res;
            } else {
                $res = [];
                foreach ($db['pesanan'] as $o) {
                    $u = $db['users'][$o['ID_USER']] ?? ['USERNAME' => 'Unknown'];
                    $res[] = array_merge($o, ['USERNAME' => $u['USERNAME']]);
                }
                usort($res, function($a, $b) { return $b['ID_PESANAN'] - $a['ID_PESANAN']; });
                if (stripos($sql, 'LIMIT 5') !== false) {
                    $res = array_slice($res, 0, 5);
                }
                $this->results = $res;
            }
        }
        // 14. INSERT INTO PESANAN
        else if (stripos($sql, 'INSERT INTO PESANAN') !== false) {
            $inc['pesanan']++;
            $new_id = $inc['pesanan'];
            $db['pesanan'][$new_id] = [
                'ID_PESANAN' => $new_id,
                'ID_USER' => $params[':u'] ?? $params[':u_id'] ?? 0,
                'TANGGAL_PESANAN' => date('Y-m-d'),
                'TOTAL_HARGA' => $params[':t'] ?? $params[':total'] ?? 0,
                'METODE_PEMBAYARAN' => $params[':m'] ?? $params[':metode'] ?? '',
                'STATUS_PESANAN' => $params[':s'] ?? 'Diproses',
                'ALAMAT' => $params[':a'] ?? $params[':alamat'] ?? '',
                'NO_HP' => $params[':nohp'] ?? ''
            ];
            $_SESSION['last_insert_id'] = $new_id;
            $this->results = [];
        }
        // 15. UPDATE PESANAN
        else if (stripos($sql, 'UPDATE PESANAN') !== false) {
            preg_match('/ID_PESANAN\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id';
            $pid = $params[$param_key] ?? 0;
            $status = $params[':s'] ?? $params[':status'] ?? $params[':status_pesanan'] ?? '';
            if (isset($db['pesanan'][$pid])) {
                $db['pesanan'][$pid]['STATUS_PESANAN'] = $status;
            }
            $this->results = [];
        }
        // 16. DELETE FROM PESANAN
        else if (stripos($sql, 'DELETE FROM PESANAN') !== false) {
            preg_match('/ID_PESANAN\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id_pesanan';
            $pid = $params[$param_key] ?? 0;
            unset($db['pesanan'][$pid]);
            $this->results = [];
        }
        // 17. INSERT INTO DETAIL_PESANAN
        else if (stripos($sql, 'INSERT INTO DETAIL_PESANAN') !== false) {
            $inc['detail_pesanan']++;
            $new_id = $inc['detail_pesanan'];
            $db['detail_pesanan'][$new_id] = [
                'ID_DETAIL' => $new_id,
                'ID_PESANAN' => $params[':pid'] ?? 0,
                'ID_PRODUK' => $params[':pr'] ?? $params[':prid'] ?? 0,
                'JUMLAH' => $params[':j'] ?? $params[':jml'] ?? 0,
                'HARGA_SATUAN' => $params[':h'] ?? $params[':hrg'] ?? 0
            ];
            $prid = $params[':pr'] ?? $params[':prid'] ?? 0;
            $jml = $params[':j'] ?? $params[':jml'] ?? 0;
            if (isset($db['produk'][$prid])) {
                $db['produk'][$prid]['STOK'] -= $jml;
            }
            $this->results = [];
        }
        // 18. DELETE FROM DETAIL_PESANAN
        else if (stripos($sql, 'DELETE FROM DETAIL_PESANAN') !== false) {
            preg_match('/ID_PESANAN\s*=\s*(:\w+)/i', $sql, $matches);
            $param_key = $matches[1] ?? ':id_pesanan';
            $pid = $params[$param_key] ?? 0;
            foreach ($db['detail_pesanan'] as $k => $item) {
                if ($item['ID_PESANAN'] == $pid) {
                    unset($db['detail_pesanan'][$k]);
                }
            }
            $this->results = [];
        }

        return true;
    }

    public function fetch($mode = PDO::FETCH_BOTH) {
        if ($this->index < count($this->results)) {
            $val = $this->results[$this->index];
            $this->index++;
            return $val;
        }
        return false;
    }

    public function fetchAll($mode = PDO::FETCH_BOTH) {
        return $this->results;
    }

    public function fetchColumn($column_number = 0) {
        $row = $this->fetch();
        if ($row) {
            return array_values($row)[$column_number] ?? null;
        }
        return false;
    }
}
