<?php
require_once '../config/config.php';
require_once '../libs/Db.php';
require_once '../libs/Session.php';
require_once '../libs/Logger.php';
require_once '../libs/ReservationManager.php';

// Initialize session
Session::init();

// Check if user is admin
if (!Session::isAdmin()) {
    header('Location: login.php');
    exit();
}

// Initialize database and logger
$db = new Db();
$logger = new Logger();
$reservationManager = new ReservationManager($db, $logger);

// Get dashboard statistics
$today = date('Y-m-d');
$monthStart = date('Y-m-01');
$monthEnd = date('Y-m-t');

$todayReservations = $reservationManager->getReservationsByDate($today);
$monthlyStats = $reservationManager->getStatistics($monthStart, $monthEnd);
$upcomingReservations = $reservationManager->getUpcomingReservations(5);

// Log admin dashboard access
$logger->logActivity('admin_dashboard_accessed', [
    'user_id' => Session::getUserId(),
    'username' => Session::getUsername()
]);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Food Chef Cafe</title>
    <link rel="stylesheet" href="../public/css/bootstrap.css">
    <link rel="stylesheet" href="../public/css/font-awesome.css">
    <link rel="stylesheet" href="../public/css/style.css">
    <link rel="stylesheet" href="../public/css/custom.css">
    <style>
        .dashboard-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 10px;
        }
        .quick-action {
            background: white;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: transform 0.3s ease;
            cursor: pointer;
        }
        .quick-action:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .reservation-item {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid #28a745;
        }
        .status-pending { border-left-color: #ffc107; }
        .status-confirmed { border-left-color: #28a745; }
        .status-cancelled { border-left-color: #dc3545; }
    </style>
</head>
<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Food Chef Admin</a>
            <div class="navbar-nav ml-auto">
                <span class="navbar-text mr-3">
                    Welcome, <?php echo htmlspecialchars(Session::getUsername()); ?>
                </span>
                <a class="nav-link" href="logout.php">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action active">
                        <i class="fa fa-dashboard"></i> Dashboard
                    </a>
                    <a href="reservations.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-calendar"></i> Reservations
                    </a>
                    <a href="food.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-cutlery"></i> Food Management
                    </a>
                    <a href="users.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-users"></i> Users
                    </a>
                    <a href="settings.php" class="list-group-item list-group-item-action">
                        <i class="fa fa-cog"></i> Settings
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <h2 class="mb-4">Dashboard Overview</h2>

                <!-- Statistics Cards -->
                <div class="row">
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="stats-number"><?php echo count($todayReservations); ?></div>
                            <div>Today's Reservations</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="stats-number"><?php echo $monthlyStats['total_reservations'] ?? 0; ?></div>
                            <div>Monthly Total</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="stats-number"><?php echo $monthlyStats['confirmed'] ?? 0; ?></div>
                            <div>Confirmed</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="dashboard-card">
                            <div class="stats-number"><?php echo $monthlyStats['avg_guests'] ?? 0; ?></div>
                            <div>Avg Guests</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="row mb-4">
                    <div class="col-12">
                        <h4>Quick Actions</h4>
                        <div class="row">
                            <div class="col-md-3">
                                <div class="quick-action" onclick="location.href='reservations.php?action=new'">
                                    <i class="fa fa-plus fa-2x text-primary mb-2"></i>
                                    <div>New Reservation</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-action" onclick="location.href='food.php?action=add'">
                                    <i class="fa fa-cutlery fa-2x text-success mb-2"></i>
                                    <div>Add Food Item</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-action" onclick="location.href='reports.php'">
                                    <i class="fa fa-chart-bar fa-2x text-info mb-2"></i>
                                    <div>View Reports</div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="quick-action" onclick="location.href='settings.php'">
                                    <i class="fa fa-cog fa-2x text-warning mb-2"></i>
                                    <div>System Settings</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Reservations -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Today's Reservations</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($todayReservations)): ?>
                                    <p class="text-muted">No reservations for today</p>
                                <?php else: ?>
                                    <?php foreach ($todayReservations as $reservation): ?>
                                        <div class="reservation-item status-<?php echo $reservation['status']; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($reservation['name']); ?></strong>
                                                    <br>
                                                    <small><?php echo $reservation['reservation_time']; ?> - <?php echo $reservation['guests']; ?> guests</small>
                                                </div>
                                                <span class="badge badge-<?php echo $reservation['status'] === 'confirmed' ? 'success' : ($reservation['status'] === 'pending' ? 'warning' : 'secondary'); ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h5>Upcoming Reservations</h5>
                            </div>
                            <div class="card-body">
                                <?php if (empty($upcomingReservations)): ?>
                                    <p class="text-muted">No upcoming reservations</p>
                                <?php else: ?>
                                    <?php foreach ($upcomingReservations as $reservation): ?>
                                        <div class="reservation-item status-<?php echo $reservation['status']; ?>">
                                            <div class="d-flex justify-content-between">
                                                <div>
                                                    <strong><?php echo htmlspecialchars($reservation['name']); ?></strong>
                                                    <br>
                                                    <small><?php echo $reservation['reservation_date']; ?> at <?php echo $reservation['reservation_time']; ?></small>
                                                </div>
                                                <span class="badge badge-<?php echo $reservation['status'] === 'confirmed' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($reservation['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Popular Time Slots -->
                <?php if (!empty($monthlyStats['popular_times'])): ?>
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h5>Popular Time Slots (This Month)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($monthlyStats['popular_times'] as $timeSlot): ?>
                                        <div class="col-md-2 text-center">
                                            <div class="p-3 bg-light rounded">
                                                <div class="h4 text-primary"><?php echo $timeSlot['count']; ?></div>
                                                <div class="text-muted"><?php echo $timeSlot['reservation_time']; ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="../public/js/jquery-2.1.4.min.js"></script>
    <script src="../public/js/bootstrap.js"></script>
    <script>
        // Auto-refresh dashboard every 5 minutes
        setInterval(function() {
            location.reload();
        }, 300000);

        // Add some interactivity
        $('.quick-action').click(function() {
            $(this).addClass('bg-light');
            setTimeout(() => {
                $(this).removeClass('bg-light');
            }, 200);
        });
    </script>
</body>
</html>
