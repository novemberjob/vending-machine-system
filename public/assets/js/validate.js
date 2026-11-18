// Client-side validation mirrors the server rules; the server remains authoritative.
(function () {
    'use strict';

    document.querySelectorAll('form[data-confirm]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            var message = form.getAttribute('data-confirm');
            if (message && !window.confirm(message)) {
                event.preventDefault();
            }
        });
    });

    document.querySelectorAll('form[data-validate]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                if (typeof form.reportValidity === 'function') {
                    form.reportValidity();
                }
                return;
            }

            var password = form.querySelector('input[name="password"]');
            var confirmation = form.querySelector('input[name="password_confirmation"]');
            if (password && confirmation && password.value !== confirmation.value) {
                event.preventDefault();
                confirmation.setCustomValidity('Passwords do not match.');
                confirmation.reportValidity();
                confirmation.addEventListener('input', function () {
                    confirmation.setCustomValidity('');
                }, { once: true });
            }
        });

        form.querySelectorAll('input').forEach(function (input) {
            input.addEventListener('input', function () {
                if (input.validity.customError) {
                    input.setCustomValidity('');
                }
            });
        });
    });

    var quantity = document.getElementById('quantity');
    var priceNode = document.getElementById('unit-price');
    var totalNode = document.getElementById('total');

    if (quantity && priceNode && totalNode) {
        var updateTotal = function () {
            var price = parseFloat(priceNode.getAttribute('data-price')) || 0;
            var count = parseInt(quantity.value, 10);
            var total = isNaN(count) || count < 0 ? 0 : price * count;
            totalNode.textContent = total.toFixed(3) + ' USD';
        };
        quantity.addEventListener('input', updateTotal);
        updateTotal();
    }
})();
