import './stimulus_bootstrap.js';
/*
 * Welcome to your app's main JavaScript file!
 *
 * This file will be included onto the page via the importmap() Twig function,
 * which should already be in your base.html.twig.
 */
import './styles/app.css';
import './styles/taskflow-ui.css';
import './styles/tailone-1.0.0/tailone-1.0.0/dist/css/style.css';
import './styles/tailone-1.0.0/tailone-1.0.0/dist/js/scripts.js';

// Import SweetAlert2 (binding explicite — requis avec les ES modules AssetMapper)
import Swal from 'sweetalert2';

// SweetAlert2 delete confirmation
window.confirmDelete = function(message = 'Êtes-vous sûr de vouloir supprimer cet élément ?') {
    return Swal.fire({
        title: 'Confirmation',
        text: message,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler'
    }).then((result) => {
        return result.isConfirmed;
    });
};

// Attach to delete forms
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('form[data-confirm]').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const message = form.dataset.confirmMessage || 'Êtes-vous sûr ?';
            const confirmed = await window.confirmDelete(message);
            if (confirmed) {
                form.submit();
            }
        });
    });
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
