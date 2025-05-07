document.addEventListener('DOMContentLoaded', function() {
    // View Staff Modal
    const viewStaffModal = document.getElementById('viewStaffModal');
    if (viewStaffModal) {
        viewStaffModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const username = button.getAttribute('data-username');
            const role = button.getAttribute('data-role');
            const status = button.getAttribute('data-status');
            const created = button.getAttribute('data-created');
            
            document.getElementById('viewStaffName').textContent = name;
            document.getElementById('viewStaffEmail').textContent = email;
            document.getElementById('viewStaffUsername').textContent = username;
            document.getElementById('viewStaffRole').textContent = role.charAt(0).toUpperCase() + role.slice(1);
            document.getElementById('viewStaffCreated').textContent = created;
            
            const statusBadge = document.getElementById('viewStaffStatus');
            statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1);
            statusBadge.className = 'badge ms-2';
            
            if (status === 'approved') {
                statusBadge.classList.add('bg-success');
            } else if (status === 'pending') {
                statusBadge.classList.add('bg-warning');
            } else if (status === 'rejected') {
                statusBadge.classList.add('bg-danger');
            } else {
                statusBadge.classList.add('bg-secondary');
            }
            
            // Populate permissions based on role
            const permissionsList = document.getElementById('viewStaffPermissions');
            permissionsList.innerHTML = '';
            
            const rolePermissions = {
                'admin': [
                    'View Dashboard', 'Manage Users', 'Manage Staff', 
                    'Manage Content', 'Manage Settings', 'Manage Appointments',
                    'Manage Services', 'View Reports'
                ],
                'staff': [
                    'View Dashboard', 'Manage Appointments'
                ],
                'service_provider': [
                    'View Dashboard', 'Manage Appointments'
                ]
            };
            
            const permissions = rolePermissions[role] || [];
            
            permissions.forEach(permission => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `<i class="bi bi-check-circle-fill text-success me-2"></i> ${permission}`;
                permissionsList.appendChild(li);
            });
        });
    }
    
    // Edit Staff Modal
    const editStaffModal = document.getElementById('editStaffModal');
    if (editStaffModal) {
        editStaffModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            const email = button.getAttribute('data-email');
            const username = button.getAttribute('data-username');
            const role = button.getAttribute('data-role');
            const status = button.getAttribute('data-status');
            
            document.getElementById('editStaffId').value = id;
            document.getElementById('editStaffName').value = name;
            document.getElementById('editStaffEmail').value = email;
            document.getElementById('editStaffUsername').value = username;
            document.getElementById('editStaffRole').value = role;
            document.getElementById('editStaffStatus').value = status;
            document.getElementById('editStaffPassword').value = '';
        });
    }
    
    // Delete Staff Modal
    const deleteStaffModal = document.getElementById('deleteStaffModal');
    if (deleteStaffModal) {
        deleteStaffModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const name = button.getAttribute('data-name');
            
            document.getElementById('deleteStaffId').value = id;
            document.getElementById('deleteStaffName').textContent = name;
        });
    }
    
    // Toggle Password Visibility
    const togglePassword = document.getElementById('togglePassword');
    if (togglePassword) {
        togglePassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('editStaffPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    const toggleAddPassword = document.getElementById('toggleAddPassword');
    if (toggleAddPassword) {
        toggleAddPassword.addEventListener('click', function() {
            const passwordInput = document.getElementById('addStaffPassword');
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            
            // Toggle icon
            const icon = this.querySelector('i');
            icon.classList.toggle('bi-eye');
            icon.classList.toggle('bi-eye-slash');
        });
    }
    
    // Password confirmation validation
    const addStaffForm = document.getElementById('addStaffForm');
    if (addStaffForm) {
        addStaffForm.addEventListener('submit', function(event) {
            const password = document.getElementById('addStaffPassword').value;
            const confirmPassword = document.getElementById('addStaffConfirmPassword').value;
            
            if (password !== confirmPassword) {
                event.preventDefault();
                document.getElementById('addStaffConfirmPassword').classList.add('is-invalid');
            } else {
                document.getElementById('addStaffConfirmPassword').classList.remove('is-invalid');
            }
        });
        
        document.getElementById('addStaffConfirmPassword').addEventListener('input', function() {
            const password = document.getElementById('addStaffPassword').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.classList.add('is-invalid');
            } else {
                this.classList.remove('is-invalid');
            }
        });
    }
    
    // Filtering functionality
    const applyFiltersBtn = document.getElementById('applyFilters');
    if (applyFiltersBtn) {
        applyFiltersBtn.addEventListener('click', filterStaff);
    }
    
    const searchInput = document.getElementById('searchStaff');
    if (searchInput) {
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterStaff();
            }
        });
    }
    
    function filterStaff() {
        const statusFilter = document.getElementById('statusFilter').value;
        const roleFilter = document.getElementById('roleFilter').value;
        const searchTerm = document.getElementById('searchStaff').value.toLowerCase();
        
        const staffItems = document.querySelectorAll('.staff-item');
        let visibleCount = 0;
        
        staffItems.forEach(item => {
            const status = item.getAttribute('data-status');
            const role = item.getAttribute('data-role');
            const name = item.getAttribute('data-name').toLowerCase();
            const email = item.getAttribute('data-email').toLowerCase();
            
            const statusMatch = statusFilter === 'all' || status === statusFilter;
            const roleMatch = roleFilter === 'all' || role === roleFilter;
            const searchMatch = name.includes(searchTerm) || email.includes(searchTerm);
            
            if (statusMatch && roleMatch && searchMatch) {
                item.style.display = '';
                visibleCount++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Show/hide no results message
        const noStaffMessage = document.getElementById('noStaffMessage');
        if (noStaffMessage) {
            noStaffMessage.style.display = visibleCount === 0 ? 'block' : 'none';
        }
    }
});
