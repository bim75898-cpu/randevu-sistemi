<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/ApiAuth.php';

ApiAuth::cors();
ApiAuth::init($pdo);

$requestUri = $_SERVER['REQUEST_URI'];
$basePath = dirname($_SERVER['SCRIPT_NAME']);
$route = str_replace($basePath, '', parse_url($requestUri, PHP_URL_PATH));
$route = '/' . trim($route, '/');

$parts = explode('/', trim($route, '/'));
$resource = $parts[0] ?? '';
$id = $parts[1] ?? null;
$sub = $parts[2] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

function validateRequired($data, $fields) {
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($data[$f]) || (is_string($data[$f]) && trim($data[$f]) === '')) {
            $missing[] = $f;
        }
    }
    if ($missing) ApiAuth::respond(400, ['error' => 'Missing required fields', 'fields' => $missing]);
}

function success($data = [], $code = 200, $pagination = null) {
    if (!headers_sent()) {
        http_response_code($code);
        header('Content-Type: application/json');
    }
    $resp = ['success' => true, 'data' => $data];
    if ($pagination) $resp['pagination'] = $pagination;
    echo json_encode($resp, JSON_UNESCAPED_UNICODE);
    exit;
}

function error($msg, $code = 400) {
    ApiAuth::respond($code, ['error' => $msg]);
}

function paginate($pdo, $query, $countQuery, $params = []) {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min(100, max(1, (int)($_GET['per_page'] ?? 50)));

    $total = $pdo->prepare($countQuery);
    $total->execute($params);
    $totalCount = (int)$total->fetchColumn();

    $offset = ($page - 1) * $perPage;
    $query .= " LIMIT $perPage OFFSET $offset";
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);

    return [
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'pagination' => [
            'page' => $page,
            'per_page' => $perPage,
            'total' => $totalCount,
            'total_pages' => max(1, (int)ceil($totalCount / $perPage)),
        ]
    ];
}

try {
    ApiAuth::authenticate();

    // ─── API Info ───
    if ($resource === '' || $resource === 'api') {
        success([
            'name' => 'Randevu Sistemi API',
            'version' => '1.0.0',
            'documentation' => 'http://localhost/randv/api/test.php',
            'endpoints' => [
                'GET    /',
                'GET|POST|PUT|DELETE    /services',
                'GET|POST|PUT|DELETE    /appointments',
                'GET|POST|PUT|DELETE    /employees',
                'GET|POST|PUT|DELETE    /customers',
                'GET    /available-times',
                'GET|PUT    /working-hours',
                'GET|POST|DELETE    /breaks',
                'GET|POST|DELETE    /series',
                'POST   /bulk-message',
                'GET    /dashboard/stats',
                'GET    /dashboard/charts',
            ]
        ]);
    }

    // ─── Services ───
    elseif ($resource === 'services') {
        if ($method === 'GET') {
            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'service ID');
                $stmt = $pdo->prepare("SELECT * FROM services WHERE id = ?");
                $stmt->execute([$id]);
                $svc = $stmt->fetch();
                $svc ? success($svc) : error('Service not found', 404);
            } else {
                $result = paginate($pdo,
                    "SELECT * FROM services ORDER BY name",
                    "SELECT COUNT(*) FROM services"
                );
                success($result['data'], 200, $result['pagination']);
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['name', 'duration', 'price']);
            $duration = (int)$data['duration'];
            if ($duration < 5 || $duration > 480) error('Duration must be between 5 and 480 minutes');
            $price = (float)$data['price'];
            if ($price < 0) error('Price cannot be negative');
            $stmt = $pdo->prepare("INSERT INTO services (name, duration, price, description, requires_payment, is_active) VALUES (?,?,?,?,?,?)");
            $stmt->execute([
                substr($data['name'], 0, 100),
                $duration,
                $price,
                substr($data['description'] ?? '', 0, 1000),
                isset($data['requires_payment']) ? (int)(bool)$data['requires_payment'] : 0,
                isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1,
            ]);
            success(['id' => (int)$pdo->lastInsertId()], 201);
        } elseif ($method === 'PUT' && $id) {
            $id = ApiAuth::validateNumericId($id, 'service ID');
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            $allowed = ['name', 'duration', 'price', 'description', 'requires_payment', 'is_active'];
            $fields = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    if ($f === 'duration') { $v = (int)$data[$f]; if ($v < 5 || $v > 480) error('Duration must be 5-480'); }
                    elseif ($f === 'price') { $v = (float)$data[$f]; if ($v < 0) error('Price cannot be negative'); }
                    elseif ($f === 'requires_payment' || $f === 'is_active') { $v = (int)(bool)$data[$f]; }
                    elseif ($f === 'name') { $v = substr($data[$f], 0, 100); }
                    elseif ($f === 'description') { $v = substr($data[$f], 0, 1000); }
                    else { $v = $data[$f]; }
                    $fields[] = "$f = ?";
                    $params[] = $v;
                }
            }
            if (!$fields) error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE services SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($params);
            success(['updated' => $stmt->rowCount()]);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'service ID');
            $stmt = $pdo->prepare("DELETE FROM services WHERE id = ?");
            $stmt->execute([$id]);
            success(['deleted' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405, ['error' => 'Method not allowed']);
        }
    }

    // ─── Appointments ───
    elseif ($resource === 'appointments') {
        if ($method === 'GET') {
            $query = "SELECT a.*, s.name as service_name, s.price as service_price,
                      c.name as customer_name, c.email as customer_email, c.phone as customer_phone,
                      e.name as employee_name
                      FROM appointments a
                      LEFT JOIN services s ON a.service_id = s.id
                      LEFT JOIN customers c ON a.customer_id = c.id
                      LEFT JOIN employees e ON a.employee_id = e.id
                      WHERE 1=1";
            $countQuery = "SELECT COUNT(*) FROM appointments a WHERE 1=1";
            $params = [];

            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'appointment ID');
                $query .= " AND a.id = ?";
                $params[] = $id;
            }
            if (isset($_GET['date'])) {
                ApiAuth::validateDate($_GET['date'], 'date');
                $query .= " AND a.appointment_date = ?";
                $countQuery .= " AND a.appointment_date = ?";
                $params[] = $_GET['date'];
            }
            if (isset($_GET['from'])) {
                ApiAuth::validateDate($_GET['from'], 'from');
                $query .= " AND a.appointment_date >= ?";
                $countQuery .= " AND a.appointment_date >= ?";
                $params[] = $_GET['from'];
            }
            if (isset($_GET['to'])) {
                ApiAuth::validateDate($_GET['to'], 'to');
                $query .= " AND a.appointment_date <= ?";
                $countQuery .= " AND a.appointment_date <= ?";
                $params[] = $_GET['to'];
            }
            if (isset($_GET['status'])) {
                ApiAuth::validateStatus($_GET['status']);
                $query .= " AND a.status = ?";
                $countQuery .= " AND a.status = ?";
                $params[] = $_GET['status'];
            }
            if (isset($_GET['customer_id'])) {
                $query .= " AND a.customer_id = ?";
                $countQuery .= " AND a.customer_id = ?";
                $params[] = ApiAuth::validateNumericId($_GET['customer_id'], 'customer_id');
            }
            if (isset($_GET['employee_id'])) {
                $query .= " AND a.employee_id = ?";
                $countQuery .= " AND a.employee_id = ?";
                $params[] = ApiAuth::validateNumericId($_GET['employee_id'], 'employee_id');
            }
            $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            if ($id) {
                $stmt = $pdo->prepare($query);
                $stmt->execute($params);
                $row = $stmt->fetch();
                $row ? success($row) : error('Appointment not found', 404);
            } else {
                $result = paginate($pdo, $query, $countQuery, $params);
                success($result['data'], 200, $result['pagination']);
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['service_id', 'customer_name', 'customer_phone', 'appointment_date', 'appointment_time']);
            $serviceId = ApiAuth::validateNumericId($data['service_id'], 'service_id');
            ApiAuth::validateDate($data['appointment_date'], 'appointment_date');
            ApiAuth::validateTime($data['appointment_time'], 'appointment_time');
            $phone = ApiAuth::validatePhone($data['customer_phone']);

            $stmt = $pdo->prepare("SELECT id FROM customers WHERE phone = ?");
            $stmt->execute([$phone]);
            $customer = $stmt->fetch();
            if ($customer) {
                $customerId = $customer['id'];
                $email = !empty($data['customer_email']) ? ApiAuth::validateEmail($data['customer_email']) : null;
                $stmt = $pdo->prepare("UPDATE customers SET name=?, email=? WHERE id=?");
                $stmt->execute([substr($data['customer_name'], 0, 100), $email, $customerId]);
            } else {
                $email = !empty($data['customer_email']) ? ApiAuth::validateEmail($data['customer_email']) : null;
                $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, created_at) VALUES (?,?,?,NOW())");
                $stmt->execute([substr($data['customer_name'], 0, 100), $email, $phone]);
                $customerId = (int)$pdo->lastInsertId();
            }

            $stmt = $pdo->prepare("SELECT duration, price FROM services WHERE id = ? AND is_active = 1");
            $stmt->execute([$serviceId]);
            $svc = $stmt->fetch();
            if (!$svc) error('Service not found or inactive', 404);

            $employeeId = isset($data['employee_id']) ? ApiAuth::validateNumericId($data['employee_id'], 'employee_id') : null;
            $token = bin2hex(random_bytes(16));
            $stmt = $pdo->prepare("INSERT INTO appointments (customer_id, service_id, employee_id, appointment_date, appointment_time, duration, price, notes, status, token, created_at) VALUES (?,?,?,?,?,?,?,?,'pending',?,NOW())");
            $stmt->execute([
                $customerId, $serviceId, $employeeId,
                $data['appointment_date'], $data['appointment_time'],
                (int)$svc['duration'], (float)$svc['price'],
                substr($data['notes'] ?? '', 0, 1000),
                $token,
            ]);
            success(['id' => (int)$pdo->lastInsertId(), 'token' => $token], 201);
        } elseif ($method === 'PUT' && $id) {
            $id = ApiAuth::validateNumericId($id, 'appointment ID');
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            $allowed = ['status', 'notes', 'appointment_date', 'appointment_time', 'employee_id'];
            $fields = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    if ($f === 'status') { $v = ApiAuth::validateStatus($data[$f]); }
                    elseif ($f === 'appointment_date') { $v = ApiAuth::validateDate($data[$f], 'appointment_date'); }
                    elseif ($f === 'appointment_time') { $v = ApiAuth::validateTime($data[$f], 'appointment_time'); }
                    elseif ($f === 'employee_id') { $v = ApiAuth::validateNumericId($data[$f], 'employee_id'); }
                    elseif ($f === 'notes') { $v = substr($data[$f], 0, 1000); }
                    else { $v = $data[$f]; }
                    $fields[] = "$f = ?"; $params[] = $v;
                }
            }
            if (!$fields) error('No valid fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE appointments SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($params);
            success(['updated' => $stmt->rowCount()]);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'appointment ID');
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ? AND status != 'cancelled'");
            $stmt->execute([$id]);
            success(['cancelled' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405, ['error' => 'Method not allowed']);
        }
    }

    // ─── Employees ───
    elseif ($resource === 'employees') {
        if ($method === 'GET') {
            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'employee ID');
                $stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
                $stmt->execute([$id]);
                $emp = $stmt->fetch();
                if (!$emp) error('Employee not found', 404);
                $stmt = $pdo->prepare("SELECT * FROM employee_hours WHERE employee_id = ? ORDER BY day_of_week");
                $stmt->execute([$id]);
                $emp['hours'] = $stmt->fetchAll();
                success($emp);
            } else {
                $includeHours = isset($_GET['include_hours']);
                $result = paginate($pdo,
                    "SELECT * FROM employees ORDER BY name",
                    "SELECT COUNT(*) FROM employees"
                );
                $employees = $result['data'];
                if ($includeHours && $employees) {
                    $ids = array_column($employees, 'id');
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $stmt = $pdo->prepare("SELECT * FROM employee_hours WHERE employee_id IN ($placeholders) ORDER BY employee_id, day_of_week");
                    $stmt->execute($ids);
                    $allHours = $stmt->fetchAll();
                    $hoursByEmp = [];
                    foreach ($allHours as $h) $hoursByEmp[$h['employee_id']][] = $h;
                    foreach ($employees as &$e) $e['hours'] = $hoursByEmp[$e['id']] ?? [];
                }
                success($employees, 200, $result['pagination']);
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['name']);
            $email = !empty($data['email']) ? ApiAuth::validateEmail($data['email']) : null;
            $phone = !empty($data['phone']) ? ApiAuth::validatePhone($data['phone']) : '';
            $stmt = $pdo->prepare("INSERT INTO employees (name, email, phone, is_active) VALUES (?,?,?,?)");
            $stmt->execute([substr($data['name'], 0, 100), $email, $phone,
                isset($data['is_active']) ? (int)(bool)$data['is_active'] : 1]);
            $empId = (int)$pdo->lastInsertId();
            if (isset($data['hours']) && is_array($data['hours'])) {
                $hStmt = $pdo->prepare("INSERT INTO employee_hours (employee_id, day_of_week, is_open, open_time, close_time) VALUES (?,?,?,?,?)");
                foreach ($data['hours'] as $h) {
                    $dow = min(6, max(0, (int)($h['day_of_week'] ?? 0)));
                    $ot = ApiAuth::validateTime($h['open_time'] ?? '09:00', 'open_time');
                    $ct = ApiAuth::validateTime($h['close_time'] ?? '18:00', 'close_time');
                    $hStmt->execute([$empId, $dow, $h['is_open'] ?? 1, $ot, $ct]);
                }
            }
            success(['id' => $empId], 201);
        } elseif ($method === 'PUT' && $id) {
            $id = ApiAuth::validateNumericId($id, 'employee ID');
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            $allowed = ['name', 'email', 'phone', 'is_active'];
            $fields = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    if ($f === 'email') $v = !empty($data[$f]) ? ApiAuth::validateEmail($data[$f]) : null;
                    elseif ($f === 'phone') $v = !empty($data[$f]) ? ApiAuth::validatePhone($data[$f]) : '';
                    elseif ($f === 'is_active') $v = (int)(bool)$data[$f];
                    elseif ($f === 'name') $v = substr($data[$f], 0, 100);
                    else $v = $data[$f];
                    $fields[] = "$f = ?"; $params[] = $v;
                }
            }
            if ($fields) {
                $params[] = $id;
                $stmt = $pdo->prepare("UPDATE employees SET " . implode(', ', $fields) . " WHERE id = ?");
                $stmt->execute($params);
            }
            if (isset($data['hours']) && is_array($data['hours'])) {
                $pdo->prepare("DELETE FROM employee_hours WHERE employee_id = ?")->execute([$id]);
                $hStmt = $pdo->prepare("INSERT INTO employee_hours (employee_id, day_of_week, is_open, open_time, close_time) VALUES (?,?,?,?,?)");
                foreach ($data['hours'] as $h) {
                    $dow = min(6, max(0, (int)($h['day_of_week'] ?? 0)));
                    $ot = ApiAuth::validateTime($h['open_time'] ?? '09:00', 'open_time');
                    $ct = ApiAuth::validateTime($h['close_time'] ?? '18:00', 'close_time');
                    $hStmt->execute([$id, $dow, $h['is_open'] ?? 1, $ot, $ct]);
                }
            }
            success(['updated' => 1]);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'employee ID');
            $pdo->prepare("DELETE FROM employee_hours WHERE employee_id = ?")->execute([$id]);
            $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
            $stmt->execute([$id]);
            success(['deleted' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405, ['error' => 'Method not allowed']);
        }
    }

    // ─── Customers ───
    elseif ($resource === 'customers') {
        if ($method === 'GET') {
            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'customer ID');
                $stmt = $pdo->prepare("SELECT * FROM customers WHERE id = ?");
                $stmt->execute([$id]);
                $c = $stmt->fetch();
                if (!$c) error('Customer not found', 404);
                $stmt = $pdo->prepare("SELECT a.*, s.name as service_name FROM appointments a LEFT JOIN services s ON a.service_id = s.id WHERE a.customer_id = ? ORDER BY a.appointment_date DESC LIMIT 20");
                $stmt->execute([$id]);
                $c['recent_appointments'] = $stmt->fetchAll();
                success($c);
            } else {
                $search = trim($_GET['search'] ?? '');
                $where = ''; $params = [];
                if ($search !== '') {
                    $search = substr(ApiAuth::sanitize($search), 0, 100);
                    $where = " WHERE name LIKE ? OR email LIKE ? OR phone LIKE ?";
                    $s = "%$search%";
                    $params = [$s, $s, $s];
                }
                $result = paginate($pdo,
                    "SELECT * FROM customers$where ORDER BY name",
                    "SELECT COUNT(*) FROM customers$where",
                    $params
                );
                success($result['data'], 200, $result['pagination']);
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['name', 'phone']);
            $phone = ApiAuth::validatePhone($data['phone']);
            $email = !empty($data['email']) ? ApiAuth::validateEmail($data['email']) : null;
            $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone, notes, created_at) VALUES (?,?,?,?,NOW())");
            $stmt->execute([substr($data['name'], 0, 100), $email, $phone, substr($data['notes'] ?? '', 0, 1000)]);
            success(['id' => (int)$pdo->lastInsertId()], 201);
        } elseif ($method === 'PUT' && $id) {
            $id = ApiAuth::validateNumericId($id, 'customer ID');
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            $allowed = ['name', 'email', 'phone', 'notes'];
            $fields = []; $params = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $data)) {
                    if ($f === 'email') $v = !empty($data[$f]) ? ApiAuth::validateEmail($data[$f]) : null;
                    elseif ($f === 'phone') $v = ApiAuth::validatePhone($data[$f]);
                    elseif ($f === 'name') $v = substr($data[$f], 0, 100);
                    elseif ($f === 'notes') $v = substr($data[$f], 0, 1000);
                    else $v = $data[$f];
                    $fields[] = "$f = ?"; $params[] = $v;
                }
            }
            if (!$fields) error('No fields to update');
            $params[] = $id;
            $stmt = $pdo->prepare("UPDATE customers SET " . implode(', ', $fields) . " WHERE id = ?");
            $stmt->execute($params);
            success(['updated' => $stmt->rowCount()]);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'customer ID');
            $stmt = $pdo->prepare("DELETE FROM customers WHERE id = ?");
            $stmt->execute([$id]);
            success(['deleted' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405, ['error' => 'Method not allowed']);
        }
    }

    // ─── Available Times ───
    elseif ($resource === 'available-times') {
        ApiAuth::requireMethod('GET');
        $serviceId = isset($_GET['service_id']) ? ApiAuth::validateNumericId($_GET['service_id'], 'service_id') : null;
        $date = isset($_GET['date']) ? ApiAuth::validateDate($_GET['date'], 'date') : null;
        $employeeId = isset($_GET['employee_id']) && $_GET['employee_id'] !== '' ? ApiAuth::validateNumericId($_GET['employee_id'], 'employee_id') : null;

        if (!$serviceId || !$date) error('service_id and date are required');

        $stmt = $pdo->prepare("SELECT duration, requires_payment FROM services WHERE id = ? AND is_active = 1");
        $stmt->execute([$serviceId]);
        $service = $stmt->fetch();
        if (!$service) error('Service not found or inactive', 404);

        $duration = (int)$service['duration'];
        $dayOfWeek = (int)date('w', strtotime($date));

        if ($employeeId) {
            $stmt = $pdo->prepare("SELECT * FROM employee_hours WHERE employee_id = ? AND day_of_week = ?");
            $stmt->execute([$employeeId, $dayOfWeek]);
        } else {
            $stmt = $pdo->prepare("SELECT * FROM working_hours WHERE day_of_week = ?");
            $stmt->execute([$dayOfWeek]);
        }
        $hours = $stmt->fetch();

        if (!$hours || !$hours['is_open']) {
            success(['slots' => [], 'date' => $date, 'message' => 'Closed']);
        }

        $openTime = strtotime($hours['open_time']);
        $closeTime = strtotime($hours['close_time']);

        $breakSql = "SELECT start_time, end_time FROM break_times WHERE day_of_week = ? AND (employee_id IS NULL";
        $breakParams = [$dayOfWeek];
        if ($employeeId) { $breakSql .= " OR employee_id = ?"; $breakParams[] = $employeeId; }
        $breakSql .= ") ORDER BY start_time";
        $stmt = $pdo->prepare($breakSql);
        $stmt->execute($breakParams);
        $breaks = $stmt->fetchAll();

        if ($employeeId) {
            $stmt = $pdo->prepare("SELECT appointment_time, duration FROM appointments WHERE appointment_date = ? AND status != 'cancelled' AND employee_id = ?");
            $stmt->execute([$date, $employeeId]);
        } else {
            $stmt = $pdo->prepare("SELECT appointment_time, duration FROM appointments WHERE appointment_date = ? AND status != 'cancelled'");
            $stmt->execute([$date]);
        }
        $bookings = $stmt->fetchAll();

        $slots = [];
        $current = $openTime;
        while ($current + $duration * 60 <= $closeTime) {
            $timeStr = date('H:i', $current);
            $slotEnd = $current + $duration * 60;
            $available = true;

            foreach ($breaks as $b) {
                $bStart = strtotime($b['start_time']);
                $bEnd = strtotime($b['end_time']);
                if (max($current, $bStart) < min($slotEnd, $bEnd)) { $available = false; break; }
            }

            if ($available) {
                foreach ($bookings as $b) {
                    $btSec = strtotime($b['appointment_time']);
                    $btEnd = $btSec + (int)$b['duration'] * 60;
                    if (max($current, $btSec) < min($slotEnd, $btEnd)) { $available = false; break; }
                }
            }

            $isPast = ($date === date('Y-m-d') && $current <= time());
            $slots[] = ['time' => $timeStr, 'available' => $available && !$isPast];
            $current += 30 * 60;
        }

        success(['slots' => $slots, 'date' => $date, 'service_id' => $serviceId, 'employee_id' => $employeeId]);
    }

    // ─── Working Hours ───
    elseif ($resource === 'working-hours') {
        if ($method === 'GET') {
            if ($id) {
                $dow = ApiAuth::validateNumericId($id, 'day_of_week');
                if ($dow < 0 || $dow > 6) error('day_of_week must be 0-6');
                $stmt = $pdo->prepare("SELECT * FROM working_hours WHERE day_of_week = ?");
                $stmt->execute([$dow]);
                $wh = $stmt->fetch();
                $wh ? success($wh) : error('Not found', 404);
            } else {
                $stmt = $pdo->query("SELECT * FROM working_hours ORDER BY day_of_week");
                success($stmt->fetchAll());
            }
        } elseif ($method === 'PUT') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            if ($id === null) error('Day of week required');
            $dow = ApiAuth::validateNumericId($id, 'day_of_week');
            if ($dow < 0 || $dow > 6) error('day_of_week must be 0-6');
            $ot = ApiAuth::validateTime($data['open_time'] ?? '09:00', 'open_time');
            $ct = ApiAuth::validateTime($data['close_time'] ?? '18:00', 'close_time');
            $stmt = $pdo->prepare("UPDATE working_hours SET is_open=?, open_time=?, close_time=? WHERE day_of_week=?");
            $stmt->execute([isset($data['is_open']) ? (int)(bool)$data['is_open'] : 1, $ot, $ct, $dow]);
            success(['updated' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405);
        }
    }

    // ─── Break Times ───
    elseif ($resource === 'breaks') {
        if ($method === 'GET') {
            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'break ID');
                $stmt = $pdo->prepare("SELECT * FROM break_times WHERE id = ?");
                $stmt->execute([$id]);
                $b = $stmt->fetch();
                $b ? success($b) : error('Not found', 404);
            } else {
                $employeeId = isset($_GET['employee_id']) ? ApiAuth::validateNumericId($_GET['employee_id'], 'employee_id') : null;
                $day = isset($_GET['day_of_week']) ? min(6, max(0, (int)$_GET['day_of_week'])) : null;
                $q = "SELECT * FROM break_times WHERE 1=1";
                $p = [];
                if ($employeeId !== null) { $q .= " AND (employee_id = ? OR employee_id IS NULL)"; $p[] = $employeeId; }
                if ($day !== null) { $q .= " AND day_of_week = ?"; $p[] = $day; }
                $q .= " ORDER BY day_of_week, start_time";
                $stmt = $pdo->prepare($q);
                $stmt->execute($p);
                success($stmt->fetchAll());
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['day_of_week', 'start_time', 'end_time']);
            $dow = min(6, max(0, (int)$data['day_of_week']));
            if ($dow < 0 || $dow > 6) error('day_of_week must be 0-6');
            $st = ApiAuth::validateTime($data['start_time'], 'start_time');
            $et = ApiAuth::validateTime($data['end_time'], 'end_time');
            if ($st >= $et) error('start_time must be before end_time');
            $empId = isset($data['employee_id']) ? ApiAuth::validateNumericId($data['employee_id'], 'employee_id') : null;
            $stmt = $pdo->prepare("INSERT INTO break_times (employee_id, day_of_week, start_time, end_time, label) VALUES (?,?,?,?,?)");
            $stmt->execute([$empId, $dow, $st, $et, substr($data['label'] ?? '', 0, 100)]);
            success(['id' => (int)$pdo->lastInsertId()], 201);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'break ID');
            $stmt = $pdo->prepare("DELETE FROM break_times WHERE id = ?");
            $stmt->execute([$id]);
            success(['deleted' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405);
        }
    }

    // ─── Appointment Series ───
    elseif ($resource === 'series') {
        if ($method === 'GET') {
            if ($id) {
                $id = ApiAuth::validateNumericId($id, 'series ID');
                $stmt = $pdo->prepare("SELECT s.*, sv.name as service_name FROM appointment_series s LEFT JOIN services sv ON s.service_id = sv.id WHERE s.id = ?");
                $stmt->execute([$id]);
                $ser = $stmt->fetch();
                $ser ? success($ser) : error('Not found', 404);
            } else {
                $stmt = $pdo->query("SELECT s.*, sv.name as service_name FROM appointment_series s LEFT JOIN services sv ON s.service_id = sv.id ORDER BY s.start_date DESC");
                success($stmt->fetchAll());
            }
        } elseif ($method === 'POST') {
            $data = ApiAuth::sanitize(ApiAuth::getJson());
            validateRequired($data, ['service_id', 'customer_name', 'customer_phone', 'appointment_time', 'frequency', 'start_date']);
            $serviceId = ApiAuth::validateNumericId($data['service_id'], 'service_id');
            $phone = ApiAuth::validatePhone($data['customer_phone']);
            $time = ApiAuth::validateTime($data['appointment_time'], 'appointment_time');
            $freq = ApiAuth::validateFrequency($data['frequency']);
            $start = ApiAuth::validateDate($data['start_date'], 'start_date');
            $end = !empty($data['end_date']) ? ApiAuth::validateDate($data['end_date'], 'end_date') : null;
            $empId = isset($data['employee_id']) ? ApiAuth::validateNumericId($data['employee_id'], 'employee_id') : null;
            $email = !empty($data['customer_email']) ? ApiAuth::validateEmail($data['customer_email']) : null;
            $stmt = $pdo->prepare("INSERT INTO appointment_series (service_id, employee_id, customer_name, customer_email, customer_phone, appointment_time, frequency, day_of_week, day_of_month, start_date, end_date, notes) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([
                $serviceId, $empId, substr($data['customer_name'], 0, 100),
                $email, $phone, $time, $freq,
                isset($data['day_of_week']) ? min(6, max(0, (int)$data['day_of_week'])) : null,
                isset($data['day_of_month']) ? min(31, max(1, (int)$data['day_of_month'])) : null,
                $start, $end, substr($data['notes'] ?? '', 0, 1000),
            ]);
            success(['id' => (int)$pdo->lastInsertId()], 201);
        } elseif ($method === 'DELETE' && $id) {
            $id = ApiAuth::validateNumericId($id, 'series ID');
            $stmt = $pdo->prepare("DELETE FROM appointment_series WHERE id = ?");
            $stmt->execute([$id]);
            success(['deleted' => $stmt->rowCount()]);
        } else {
            ApiAuth::respond(405);
        }
    }

    // ─── Bulk Message ───
    elseif ($resource === 'bulk-message') {
        ApiAuth::requireMethod('POST');
        $data = ApiAuth::sanitize(ApiAuth::getJson());
        validateRequired($data, ['type', 'message']);
        if (!in_array($data['type'], ['email', 'sms'])) error('type must be email or sms');
        require_once __DIR__ . '/../lib/MailService.php';
        require_once __DIR__ . '/../lib/SmsService.php';
        $type = $data['type'];
        $subject = substr($data['subject'] ?? '', 0, 255);
        $message = substr($data['message'], 0, 10000);
        $audience = $data['audience'] ?? 'all';

        $query = "SELECT * FROM customers WHERE 1=1";
        $params = [];
        if ($audience === 'active') {
            $query .= " AND (SELECT COUNT(*) FROM appointments WHERE customer_id = customers.id AND appointment_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)) > 0";
        }
        $stmt = $pdo->prepare($query);
        $stmt->execute($params);
        $customers = $stmt->fetchAll();
        $count = 0;
        foreach ($customers as $c) {
            if ($type === 'email' && !empty($c['email'])) {
                @MailService::send($c['email'], $subject ?: 'Bilgilendirme', $message);
                $count++;
            } elseif ($type === 'sms' && !empty($c['phone'])) {
                @SmsService::send($c['phone'], $message);
                $count++;
            }
        }
        success(['sent' => $count, 'total_customers' => count($customers)]);
    }

    // ─── Dashboard ───
    elseif ($resource === 'dashboard') {
        $dashboardSub = $id;
        if ($dashboardSub === 'stats') {
            $today = date('Y-m-d');
            $monthStart = date('Y-m-01');
            $totalAppts = $pdo->query("SELECT COUNT(*) FROM appointments")->fetchColumn();
            $todayAppts = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
            $todayAppts->execute([$today]);
            $todayAppts = (int)$todayAppts->fetchColumn();
            $pending = $pdo->query("SELECT COUNT(*) FROM appointments WHERE status = 'pending'")->fetchColumn();
            $totalRevenue = $pdo->query("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.status != 'cancelled'")->fetchColumn();
            $monthRevenue = $pdo->prepare("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.appointment_date >= ? AND a.status != 'cancelled'");
            $monthRevenue->execute([$monthStart]);
            $monthRevenue = (float)$monthRevenue->fetchColumn();
            $customers = $pdo->query("SELECT COUNT(*) FROM customers")->fetchColumn();
            $employees = $pdo->query("SELECT COUNT(*) FROM employees WHERE is_active = 1")->fetchColumn();
            $todayCompleted = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ? AND status = 'completed'");
            $todayCompleted->execute([$today]);
            success([
                'total_appointments' => (int)$totalAppts,
                'today_appointments' => $todayAppts,
                'today_completed' => (int)$todayCompleted->fetchColumn(),
                'pending_appointments' => (int)$pending,
                'total_revenue' => (float)$totalRevenue,
                'month_revenue' => $monthRevenue,
                'total_customers' => (int)$customers,
                'active_employees' => (int)$employees,
            ]);
        } elseif ($dashboardSub === 'charts') {
            $chartLabels = []; $chartAppts = []; $chartRevenue = [];
            for ($i = 6; $i >= 0; $i--) {
                $d = date('Y-m-d', strtotime("-$i days"));
                $chartLabels[] = date('d.m', strtotime($d));
                $c = $pdo->prepare("SELECT COUNT(*) FROM appointments WHERE appointment_date = ?");
                $c->execute([$d]); $chartAppts[] = (int)$c->fetchColumn();
                $r = $pdo->prepare("SELECT COALESCE(SUM(s.price),0) FROM appointments a JOIN services s ON a.service_id = s.id WHERE a.appointment_date = ? AND a.status != 'cancelled'");
                $r->execute([$d]); $chartRevenue[] = (float)$r->fetchColumn();
            }
            $popular = $pdo->query("SELECT s.name, COUNT(*) as cnt FROM appointments a JOIN services s ON a.service_id = s.id GROUP BY a.service_id ORDER BY cnt DESC LIMIT 5")->fetchAll();
            $employeePerf = $pdo->query("SELECT e.name, COUNT(*) as total, COALESCE(SUM(s.price),0) as revenue FROM appointments a JOIN services s ON a.service_id = s.id RIGHT JOIN employees e ON a.employee_id = e.id WHERE a.status != 'cancelled' OR a.id IS NULL GROUP BY e.id, e.name ORDER BY total DESC LIMIT 5")->fetchAll();
            success([
                'weekly' => ['labels' => $chartLabels, 'appointments' => $chartAppts, 'revenue' => $chartRevenue],
                'popular_services' => $popular,
                'employee_performance' => $employeePerf,
            ]);
        } else {
            error('Dashboard sub-resource not found', 404);
        }
    }

    else {
        ApiAuth::respond(404, ['error' => 'Endpoint not found']);
    }
} catch (PDOException $e) {
    ApiAuth::logSecurity('api_db_error', substr($e->getMessage(), 0, 200));
    error('A database error occurred', 500);
} catch (Exception $e) {
    ApiAuth::logSecurity('api_error', substr($e->getMessage(), 0, 200));
    error('An internal error occurred', 500);
}
