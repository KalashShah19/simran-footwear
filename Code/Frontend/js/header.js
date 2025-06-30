$(document).ready(function () {
    // Load Header
    $('#header').load('header.html', function () {
        // Mobile navigation toggle
        const navOpen = document.querySelector('.mobile-open-btn');
        const navClose = document.querySelector('.mobile-close-btn');
        const primaryNavigation = document.getElementById('primary-navigation');

        navOpen.addEventListener('click', () => {
            const visibility = primaryNavigation.getAttribute('data-visible');
            const isVisible = visibility === 'true';
            primaryNavigation.setAttribute('data-visible', !isVisible);
            navClose.setAttribute('data-visible', !isVisible);
        });

        navClose.addEventListener('click', () => {
            primaryNavigation.setAttribute('data-visible', false);
            navClose.setAttribute('data-visible', false);
        });

        // Cart menu toggle
        const shoppingBag = document.getElementById('cart-box');
        const cartItem = document.getElementById('cart-icon');
        const crossBtn = document.getElementById('cross-btn');

        shoppingBag.addEventListener('click', () => {
            const showCart = cartItem.getAttribute('data-visible');
            cartItem.setAttribute('data-visible', showCart === 'false');
        });

        crossBtn.addEventListener('click', () => {
            cartItem.setAttribute('data-visible', false);
        });
    });

    // Open Auth Popup
    $(document).on('click', '.header-login p', function () {
        $('#auth-popup').fadeIn();
    });

    // Close Auth Popup - by clicking cross icon
    $(document).on('click', '.close-popup', function () {
        $('#auth-popup').fadeOut();
        $('.popup-card').removeClass('show-signup'); // reset to login view
        $('.toggle-btns button').removeClass('active');
        $('#login-tab, #login-tab-2').addClass('active');
    });

    // Close Auth Popup - by clicking outside the popup
    $(document).on('click', '#auth-popup', function (e) {
        if ($(e.target).is('#auth-popup')) {
            $('#auth-popup').fadeOut();
            $('.popup-card').removeClass('show-signup');
            $('.toggle-btns button').removeClass('active');
            $('#login-tab, #login-tab-2').addClass('active');
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
