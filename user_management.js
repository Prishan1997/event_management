// user_management.js - Handles all client-side logic for User CRUD operations

document.addEventListener('DOMContentLoaded', function() {

    // --- USER MODAL JS VARIABLES ---
    const userModal = document.getElementById('userModal');
    const userModalTitle = document.getElementById('userModalTitle');
    const userModalSubmitBtn = document.getElementById('userModalSubmitBtn');
    const userAction = document.getElementById('userAction');
    const userIdField = document.getElementById('userIdField');
    const userNameField = document.getElementById('user_name');
    const userEmailField = document.getElementById('user_email');
    const userPasswordField = document.getElementById('user_password');
    const userIsAdminField = document.getElementById('user_is_admin');
    const openCreateUserModal = document.getElementById('openCreateUserModal'); // Assuming you add this ID to the button

    // Function to clear and reset the user modal form fields
    function resetUserModalForm() {
        userNameField.value = '';
        userEmailField.value = '';
        userPasswordField.value = '';
        // Remove 'required' for password by default (only needed for CREATE)
        userPasswordField.removeAttribute('required'); 
        userIsAdminField.checked = false;
    }
    
    // --- EVENT LISTENERS ---

    // 1. Logic for CREATING a new user (Button handler)
    if (openCreateUserModal) {
        openCreateUserModal.addEventListener('click', function() {
            resetUserModalForm();
            userModalTitle.textContent = 'Add New User';
            userModalSubmitBtn.textContent = 'Add User';
            userModalSubmitBtn.style.backgroundColor = '#4CAF50';
            userAction.value = 'create';
            userIdField.value = '';
            // Password IS required for a new user
            userPasswordField.setAttribute('required', 'required'); 
            userModal.style.display = 'flex';
        });
    }

    // 2. Logic for EDITING an existing user (Table button handler)
    document.querySelectorAll('.edit-user-btn').forEach(button => {
        button.addEventListener('click', function() {
            resetUserModalForm();
            
            // Get data directly from the button's data attributes
            const userId = this.getAttribute('data-user-id');
            const name = this.getAttribute('data-name');
            const email = this.getAttribute('data-email');
            const isAdmin = this.getAttribute('data-is-admin'); // 1 or 0
            
            // Populate fields
            userNameField.value = name;
            userEmailField.value = email;
            // Set checkbox based on database value (1=checked, 0=unchecked)
            userIsAdminField.checked = (isAdmin === '1'); 
            
            // Update modal context
            userModalTitle.textContent = 'Edit User (ID: ' + userId + ')';
            userModalSubmitBtn.textContent = 'Save Changes';
            userModalSubmitBtn.style.backgroundColor = '#007bff';
            userAction.value = 'edit';
            userIdField.value = userId;

            userModal.style.display = 'flex';
        });
    });

    // 3. Close modal when clicking the overlay 
    if (userModal) {
        userModal.addEventListener('click', function(e) {
            if (e.target === userModal) {
                userModal.style.display = 'none';
            }
        });
    }

});