<!-- views/staff/manage_staff.php -->
<?php 
$current_page = 'staff';
require_once __DIR__ . '/../includes/header.php'; 
?>

<div class="container-fluid">
    <div class="row">
        <?php require_once __DIR__ . '/sidebar.php'; ?>
        
        <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Manage Staff</h1>
            </div>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                    <?= $_SESSION['success']; ?>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['errors']['general'])): ?>
                <div class="alert alert-danger">
                    <?php foreach ($_SESSION['errors']['general'] as $error): ?>
                        <p><?= $error ?></p>
                    <?php endforeach; ?>
                </div>
                <?php unset($_SESSION['errors']['general']); ?>
            <?php endif; ?>
            
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-table mr-1"></i>
                    Staff Members
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="staffTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($staff as $member): ?>
                                <tr>
                                    <td><?= htmlspecialchars($member->name) ?></td>
                                    <td><?= htmlspecialchars($member->username) ?></td>
                                    <td><?= htmlspecialchars($member->email) ?></td>
                                    <td><?= htmlspecialchars($member->role) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $member->status === 'approved' ? 'success' : ($member->status === 'pending' ? 'warning' : 'danger') ?>">
                                            <?= ucfirst(htmlspecialchars($member->status)) ?>
                                        </span>
                                    </td>
                                    <td><?= date('Y-m-d', strtotime($member->created_at)) ?></td>
                                    <td>
                                        <?php if ($member->status === 'pending'): ?>
                                        <form action="staff-approve" method="POST" class="d-inline">
                                            <input type="hidden" name="staff_id" value="<?= $member->id ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-sm btn-success">Approve</button>
                                        </form>
                                        <form action="staff-approve" method="POST" class="d-inline">
                                            <input type="hidden" name="staff_id" value="<?= $member->id ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-sm btn-danger">Reject</button>
                                        </form>
                                        <?php else: ?>
                                        <a href="staff-edit?id=<?= $member->id ?>" class="btn btn-sm btn-primary">Edit</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>

<script>
$(document).ready(function() {
    $('#staffTable').DataTable();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>