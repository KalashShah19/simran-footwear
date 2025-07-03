$(document).ready(function () {
    // Load Header
    $('#header').load('header.html', function () {
        // ======= DOM inside header is now available =======

        /* ----------  DOM references ---------- */
        const navOpen            = document.querySelector('.mobile-open-btn');
        const navClose           = document.querySelector('.mobile-close-btn');
        const primaryNavigation  = document.getElementById('primary-navigation');

        const shoppingBag = document.getElementById('cart-box');   // bag icon in header
        const cartItem    = document.getElementById('cart-icon');  // cart side‑panel
        const crossBtn    = document.getElementById('cross-btn');  // ✕ inside cart

        /* ----------  helper ---------- */
        function closeCart () {
            cartItem.setAttribute('data-visible', false);
        }

        /* ----------  Nav toggle ---------- */
        navOpen.addEventListener('click', () => {
            const isVisible = primaryNavigation.getAttribute('data-visible') === 'true';
            const nextState = !isVisible;

            primaryNavigation.setAttribute('data-visible', nextState);
            navClose.setAttribute('data-visible', nextState);

            if (nextState) {            // 🆕 nav just opened
                closeCart();            // 🆕 ensure cart panel is closed
            }
        });

        navClose.addEventListener('click', () => {
            primaryNavigation.setAttribute('data-visible', false);
            navClose.setAttribute('data-visible', false);
        });

        /* ----------  Cart toggle ---------- */
        shoppingBag.addEventListener('click', () => {
            const showCart = cartItem.getAttribute('data-visible') === 'true';
            cartItem.setAttribute('data-visible', !showCart);
        });

        crossBtn.addEventListener('click', closeCart);

        /* ----------  Auth popup ---------- */

        // Open Auth Popup
        $(document).on('click', '.header-login p', function () {
            $('#auth-popup').addClass('is-open').hide().fadeIn(200); // display:flex + fade‑in
        });

        // Close Auth Popup - by clicking cross icon
        $(document).on('click', '.close-popup', function () {
            $('#auth-popup').fadeOut(200, function () {
                $(this).removeClass('is-open');
                resetAuthPanels();
            });
        });

        // Close Auth Popup - by clicking outside the popup
        $(document).on('click', '#auth-popup', function (e) {
            if (e.target.id === 'auth-popup') {
                $('#auth-popup').fadeOut(200, function () {
                    $(this).removeClass('is-open');
                    resetAuthPanels();
                });
            }
        });

        // Show Signup Panel
        $(document).on('click', '#signup-tab, #signup-tab-2, #toggle-signup', function () {
            $('.popup-card').addClass('show-signup');
            $('.toggle-btns button').removeClass('active');
            $('#signup-tab, #signup-tab-2').addClass('active');
        });

        // Show Login Panel
        $(document).on('click', '#login-tab, #login-tab-2, #toggle-login', function () {
            $('.popup-card').removeClass('show-signup');
            $('.toggle-btns button').removeClass('active');
            $('#login-tab, #login-tab-2').addClass('active');
        });
    });
});

function resetAuthPanels() {
    $('.popup-card').removeClass('show-signup');
    $('.toggle-btns button').removeClass('active');
    $('#login-tab, #login-tab-2').addClass('active');
}
