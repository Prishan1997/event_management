// event_management.js

document.addEventListener('DOMContentLoaded', () => {
    // --- EVENT MANAGEMENT CONSTANTS ---
    const eventModal = document.getElementById('eventModal');
    const modalTitle = document.getElementById('modalTitle');
    const modalSubmitBtn = document.getElementById('modalSubmitBtn');
    const eventAction = document.getElementById('eventAction');
    const eventID = document.getElementById('eventID');
    
    // --- UTILITY FUNCTION ---
    function resetEventModalForm() {
        // Reset form fields for 'create' action
        document.getElementById('event_name').value = '';
        document.getElementById('event_date').value = '';
        document.getElementById('num_tickets').value = 100;
    }

    // --- 1. Create Event Button Handler ---
    const openCreateEventModal = document.getElementById('openCreateEventModal');
    if (openCreateEventModal) {
        openCreateEventModal.addEventListener('click', function() {
            resetEventModalForm();
            modalTitle.textContent = 'Create New Event';
            modalSubmitBtn.textContent = 'Create Event';
            modalSubmitBtn.style.backgroundColor = '#4CAF50'; // Green for create
            eventAction.value = 'create';
            eventID.value = '';
            eventModal.style.display = 'flex';
        });
    }

    // --- 2. Edit Event Buttons Handler ---
    document.querySelectorAll('.edit-event-btn').forEach(button => {
        button.addEventListener('click', function() {
            const eventId = this.getAttribute('data-event-id');
            
            // Populate fields from data attributes
            document.getElementById('event_name').value = this.getAttribute('data-name');
            document.getElementById('event_date').value = this.getAttribute('data-date');
            document.getElementById('num_tickets').value = this.getAttribute('data-tickets');
            
            // Update modal context
            modalTitle.textContent = 'Edit Event (ID: ' + eventId + ')';
            modalSubmitBtn.textContent = 'Save Changes';
            modalSubmitBtn.style.backgroundColor = '#007bff'; // Blue for edit/save
            eventAction.value = 'edit';
            eventID.value = eventId;

            eventModal.style.display = 'flex';
        });
    });
});