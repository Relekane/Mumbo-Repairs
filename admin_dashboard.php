<?php
session_start();
require_once __DIR__ . '/db.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: admin_login.php');
    exit;
}

$pdo = getPDO();
$statusFilter = $_GET['status'] ?? '';
$params = [];
$sql = "SELECT * FROM bookings";
if (in_array($statusFilter, ['pending','approved','rejected'])) {
    $sql .= " WHERE status = :s";
    $params[':s'] = $statusFilter;
}
$sql .= " ORDER BY created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Admin dashboard - Bookings</title>
  <meta content="width=device-width, initial-scale=1.0" name="viewport">
  <meta content="" name="keywords">
  <meta content="" name="description">

  <!-- Favicon -->
  <link href="img/favicon.ico" rel="icon">

  <!-- Google Web Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Barlow:wght@600;700&family=Ubuntu:wght@400;500&display=swap" rel="stylesheet">

  <!-- Icon Font Stylesheet -->
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.4.1/font/bootstrap-icons.css" rel="stylesheet">

  <!-- Libraries Stylesheet (if used by template) -->
  <link href="lib/animate/animate.min.css" rel="stylesheet">
  <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">
  <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

  <!-- Customized Bootstrap Stylesheet -->
  <link href="css/bootstrap.min.css" rel="stylesheet">

  <!-- Template Stylesheet -->
  <link href="css/style.css" rel="stylesheet">

  <style>
    /* Minor page-specific tweaks so admin table looks good with the template */
    .admin-actions form { display:inline-block; margin:0 4px 4px 0; }
    .admin-details summary { cursor: pointer; }
    .required-star { color: #ff4d4f; }

    /* ensure file links don't overflow */
    .img-link { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; max-width: 12rem; vertical-align: middle; }

    /* small spacing for the approval details form */
    details .manage-form { margin-top: .75rem; }
    textarea.form-control { min-height: 90px; }
  </style>
</head>
<body>
  <!-- Topbar (optional, same visual system as site) -->
  <div class="container-fluid bg-light p-0">
    <div class="row gx-0 d-none d-lg-flex">
      <div class="col-lg-7 px-5 text-start">
        <div class="h-100 d-inline-flex align-items-center py-3 me-4">
          <small class="fa fa-map-marker-alt text-primary me-2"></small>
          <small>Admin Panel</small>
        </div>
      </div>
      <div class="col-lg-5 px-5 text-end">
        <div class="h-100 d-inline-flex align-items-center py-3">
          <a class="btn btn-sm-square bg-white text-primary me-1" href="../index.html"><i class="fa fa-home"></i></a>
          <a class="btn btn-sm-square bg-white text-primary me-1" href="logout.php"><i class="fa fa-sign-out-alt"></i></a>
        </div>
      </div>
    </div>
  </div>

  <!-- Main container -->
  <div class="container-xxl py-5">
    <div class="container">
      <div class="row mb-4">
        <div class="col-12 d-flex justify-content-between align-items-center">
          <div>
            <h1 class="mb-0">Bookings</h1>
            <p class="text-muted mb-0">Manage customer booking requests</p>
          </div>
          <div>
            <a href="admin_dashboard.php?status=pending" class="btn btn-outline-primary <?php echo $statusFilter==='pending' ? 'active' : ''; ?>">Pending</a>
            <a href="admin_dashboard.php?status=approved" class="btn btn-outline-success <?php echo $statusFilter==='approved' ? 'active' : ''; ?>">Approved</a>
            <a href="admin_dashboard.php?status=rejected" class="btn btn-outline-danger <?php echo $statusFilter==='rejected' ? 'active' : ''; ?>">Rejected</a>
            <a href="admin_dashboard.php" class="btn btn-outline-secondary <?php echo $statusFilter==='' ? 'active' : ''; ?>">All</a>
            <a href="logout.php" class="btn btn-outline-dark">Logout</a>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                  <thead class="table-dark">
                    <tr>
                      <th style="width:5%;">ID</th>
                      <th style="width:18%;">Name</th>
                      <th style="width:15%;">Email</th>
                      <th style="width:10%;">Phone</th>
                      <th style="width:12%;">Service</th>
                      <th style="width:8%;">Image</th>
                      <th style="width:8%;">Status</th>
                      <th style="width:12%;">Created</th>
                      <th style="width:12%;">Actions</th>
                    </tr>
                  </thead>
                  <tbody>
<?php if (count($bookings) === 0): ?>
                    <tr>
                      <td colspan="9" class="text-center text-muted py-4">No bookings found.</td>
                    </tr>
<?php else: ?>
<?php foreach ($bookings as $b): ?>
                    <tr>
                      <td><?php echo $b['id']; ?></td>
                      <td><?php echo htmlspecialchars($b['customer_name']); ?></td>
                      <td><a href="mailto:<?php echo htmlspecialchars($b['customer_email']); ?>"><?php echo htmlspecialchars($b['customer_email']); ?></a></td>
                      <td><?php echo htmlspecialchars($b['customer_phone']); ?></td>
                      <td><?php echo htmlspecialchars($b['service_type']); ?></td>
                      <td>
                        <?php if (!empty($b['image_path'])): ?>
                          <a class="img-link" href="<?php echo htmlspecialchars($b['image_path']); ?>" target="_blank" rel="noopener">View</a>
                        <?php else: ?>
                          <span class="text-muted">—</span>
                        <?php endif; ?>
                      </td>
                      <td>
                        <?php
                          $status = htmlspecialchars($b['status']);
                          $badgeClass = 'secondary';
                          if ($status === 'pending') $badgeClass = 'warning';
                          if ($status === 'approved') $badgeClass = 'success';
                          if ($status === 'rejected') $badgeClass = 'danger';
                        ?>
                        <span class="badge bg-<?php echo $badgeClass; ?> text-capitalize"><?php echo $status; ?></span>
                      </td>
                      <td><?php echo htmlspecialchars($b['created_at']); ?></td>
                      <td>
                        <div class="admin-actions mb-2">
                          <?php if ($b['status'] === 'pending'): ?>
                            <!-- Replace the simple approve/reject buttons with an inline form so admin can add a note -->
                            <details>
                              <summary class="small text-primary">Approve / Reject with note</summary>
                              <form action="approve_booking.php" method="post" class="manage-form">
                                <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                <div class="mb-2">
                                  <textarea name="admin_note" class="form-control" placeholder="Optional admin note (this will be emailed to the customer)"></textarea>
                                </div>
                                <div>
                                  <button type="submit" name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
                                  <button type="submit" name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
                                </div>
                              </form>
                            </details>
                          <?php else: ?>
                            <em class="text-muted">—</em>
                          <?php endif; ?>
                        </div>

                        <div class="admin-details">
                          <details>
                            <summary class="small text-primary">Details / Admin note</summary>
                            <div class="mt-2">
                              <p class="mb-2"><strong>Customer notes:</strong><br><?php echo nl2br(htmlspecialchars($b['details'])); ?></p>
                              <p class="mb-0"><strong>Admin note:</strong><br><?php echo nl2br(htmlspecialchars($b['admin_note'])); ?></p>
                            </div>
                          </details>
                        </div>
                      </td>
                    </tr>
<?php endforeach; ?>
<?php endif; ?>
                  </tbody>
                </table>
              </div> <!-- /.table-responsive -->
            </div> <!-- /.card-body -->
          </div> <!-- /.card -->
        </div>
      </div>
    </div>
  </div>

  <!-- Footer (keeps style consistent with site) -->
  <div class="container-fluid bg-dark text-light footer pt-4 mt-4">
    <div class="container py-3">
      <div class="row">
        <div class="col-md-6 text-start">
          &copy; <?php echo date('Y'); ?> <a class="border-bottom text-light" href="#">Mumbo Repairs</a>, All Right Reserved.
        </div>
        <div class="col-md-6 text-end">
          <a class="text-light me-3" href="../index.html">Home</a>
          <a class="text-light" href="#">Help</a>
        </div>
      </div>
    </div>
  </div>

  <!-- JavaScript Libraries (match template) -->
  <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="lib/wow/wow.min.js"></script>
  <script src="lib/easing/easing.min.js"></script>
  <script src="lib/waypoints/waypoints.min.js"></script>
  <script src="lib/counterup/counterup.min.js"></script>
  <script src="lib/owlcarousel/owl.carousel.min.js"></script>
  <script src="lib/tempusdominus/js/moment.min.js"></script>
  <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
  <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>

  <!-- Template Javascript -->
  <script src="js/main.js"></script>
</body>
</html>