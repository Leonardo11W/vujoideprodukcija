// Stripe payment JS helper (Razorpay-style)
// Usage: call startStripePayment(backendUrl, payload, csrfToken)
function startStripePayment(backendUrl, payload, csrfToken) {
    fetch(backendUrl, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(res => res.json())
    .then(response => {
        if (response.success && (response.redirect || response.session_url)) {
            window.location.href = response.redirect || response.session_url; // Redirect to Stripe Checkout
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Stripe Payment Failed',
                text: response.message || 'Stripe payment initialization failed.'
            });
        }
    })
    .catch(err => {
        Swal.fire({
            icon: 'error',
            title: 'Stripe Error',
            text: 'Stripe Error: ' + err
        });
    });
}

// Example integration for a button with id 'stripePayButton'
document.addEventListener('DOMContentLoaded', function() {
    var btn = document.getElementById('stripePayButton');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            // Gather your booking data here
            const payload = {
                price: document.getElementById('booking_price')?.value,
                employee_id: document.getElementById('employee_id')?.value,
                branch_id: document.getElementById('branch_id')?.value,
                date: document.getElementById('booking_date')?.value,
                time: document.getElementById('booking_time')?.value,
                services: JSON.parse(document.getElementById('services_json')?.value || '[]'),
                payment_method: 'stripe'
            };
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            startStripePayment('/payment/process', payload, csrfToken);
        });
    }
}); 