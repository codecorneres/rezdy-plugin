<?php
if (! defined('ABSPATH')) exit;

$currency_icon = PLUGIN_URL . 'assets/images/currency-arrow.svg';
$currency_icon_alt = '';
$rezdy_booking_type = get_option('cc_picked_color');
$currencies = [
    'EUR' => '€ EU',
    'USD' => '$ US',
    'GBP' => '£ GB'
];

$selected_currency = isset($_COOKIE['rezdy_selected_currency'])
    ? esc_attr($_COOKIE['rezdy_selected_currency'])
    : (get_option('cc_default_currency') ?: 'EUR');
$active_currency = explode(' ', $currencies[$selected_currency]);
unset($currencies[$selected_currency]);
?>

<div class="rezdy-currency-dropdown rezdy-currency-dropdown--<?php echo $rezdy_booking_type; ?>">
    <div class="rezdy-dropdown-toggle" onclick="rezdyToggleDropdown(this)">
        <span class="symbol"><?php echo $active_currency[0]; ?></span>
        <span class="separator">|</span>
        <span class="country"><?php echo $active_currency[1]; ?></span>
        <span class="arrow">
            <?php if ($rezdy_booking_type == 'theme-tipsy') : ?>
                <svg width="13" height="6" viewBox="0 0 13 6" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M0.812384 0.000604153L12.1261 0.000604153L6.46924 5.65746L0.812384 0.000604153Z" fill="#FF1590"/>
                </svg>
            <?php else : ?>
                <img src="<?php echo $currency_icon; ?>"/>
            <?php endif; ?>
        </span>
    </div>
    <div class="rezdy-dropdown-menu" id="dropdownMenu">
        <?php foreach ($currencies as $currencyKey => $currency) : ?>
            <?php $currency_split = explode(' ', $currency); ?>
            <div class="rezdy-dropdown-item" onclick="rezdySetCurrency('<?php echo $currencyKey; ?>')">
                <span class="symbol"><?php echo $currency_split[0]; ?></span><span class="separator">|</span><span class="country"><?php echo $currency_split[1]; ?></span><span class="arrow"><img src="<?php echo $currency_icon; ?>"/></span>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
    function rezdyToggleDropdown(toggleElement) {
        const parentDropdown = toggleElement.closest('.rezdy-currency-dropdown')
        if (! parentDropdown) return

        const toggleMenu = parentDropdown.querySelector('.rezdy-dropdown-menu')

        toggleElement.classList.toggle('active')
        toggleMenu.classList.toggle('active')
    }

    function rezdySetCurrency(value) {
        document.cookie = 'rezdy_selected_currency=' + value + ';path=/;max-age=31536000'
        location.reload()
    }

    document.addEventListener('click', function(event) {
        if (! event.target.closest('.rezdy-currency-dropdown')) {
            const dropdownMenu = document.querySelector('.rezdy-currency-dropdown .rezdy-dropdown-menu')
            const dropdownToggle = document.querySelector('.rezdy-currency-dropdown .rezdy-dropdown-toggle')
            if (dropdownMenu.classList.contains('active')) {
                dropdownMenu.classList.remove('active')
                dropdownToggle.classList.remove('active')
            }
        }
    })

    function moveCurrencySelectorOnMobile() {
        const toMove = document.querySelector('header nav .navbar-collapse .rezdy-currency-dropdown')
        if (! toMove) return

        if (window.innerWidth <= 1009) {
            toMove.classList.add('rezdy-currency-dropdown--moved')
            document.querySelector('header nav .navbar-collapse > ul').appendChild(toMove)
        } else {
            document.querySelector('header nav .navbar-collapse').appendChild(toMove)
            toMove.classList.remove('rezdy-currency-dropdown--moved')
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Hide the mobile floating book section
        const footer = document.querySelector('footer.footer')
        const targetElement = document.querySelector('.product .form-right_tour')

        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => {
                const targetElement = document.querySelector('.product .form-right_tour')
                if (targetElement) {
                    if (entry.isIntersecting) {
                        targetElement.setAttribute('style', 'display: none !important;')
                    } else {
                        targetElement.removeAttribute('style')
                    }
                }
            })
        }, { threshold: 0.1 })

        if (footer) {
            observer.observe(footer)
            if (footer.getBoundingClientRect().top < window.innerHeight) {
                if (targetElement) {
                    targetElement.setAttribute('style', 'display: none !important')
                }
            }
        }

        // Trigger event for moving currency selector on mobile
        moveCurrencySelectorOnMobile()
    })

    window.addEventListener('resize', function() {
        moveCurrencySelectorOnMobile()
    })
</script>
